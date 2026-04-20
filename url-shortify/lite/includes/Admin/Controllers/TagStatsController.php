<?php

namespace KaizenCoders\URL_Shortify\Admin\Controllers;

use KaizenCoders\URL_Shortify\Cache;
use KaizenCoders\URL_Shortify\Common\Export;
use KaizenCoders\URL_Shortify\Common\Utils;
use KaizenCoders\URL_Shortify\Helper;

class TagStatsController extends StatsController {
	/**
	 * Tag ID
	 *
	 * @since 1.13.1
	 * @var null
	 */
	public $tag_id = null;

	/**
	 * TagStatsController constructor.
	 *
	 * @param null $tag_id
	 */
	public function __construct( $tag_id = null ) {
		$this->tag_id = $tag_id;

		parent::__construct();
	}

	/**
	 * Render Tag stats page.
	 *
	 * @since 1.13.1
	 */
	public function render() {
		$data = $this->prepare_data( false );

		include KC_US_ADMIN_TEMPLATES_DIR . '/tag-stats.php';
	}

	/**
	 * Prepare data for report.
	 *
	 * @since 1.13.1
	 *
	 * @param bool $include_click_history
	 *
	 * @return array|object|void|null
	 */
	public function prepare_data( $include_click_history = true ) {
		$refresh = (int) Helper::get_request_data( 'refresh', 0 );

		$time_filter = sanitize_key( Helper::get_request_data( 'time_filter', '' ) );
		if ( empty( $time_filter ) ) {
			$time_filter = 'all_time';
		}

		$start_date = '';
		$end_date   = '';
		if ( 'custom' === $time_filter ) {
			$start_date = sanitize_text_field( Helper::get_request_data( 'start_date', '' ) );
			$end_date   = sanitize_text_field( Helper::get_request_data( 'end_date', '' ) );
		}

		$days = 7;
		switch ( $time_filter ) {
			case 'today':
				$days = 1;
				break;
			case 'last_7_days':
				$days = 7;
				break;
			case 'last_30_days':
				$days = 30;
				break;
			case 'last_60_days':
				$days = 60;
				break;
			case 'all_time':
			case 'custom':
				$days = 0;
				break;
		}

		$cache_suffix = $time_filter;
		if ( 'custom' === $time_filter && $start_date && $end_date ) {
			$cache_suffix .= '_' . $start_date . '_' . $end_date;
		}
		$cache_key = 'tag_stats_v1_' . $this->tag_id . '_' . $cache_suffix;

		$data = Cache::get_transient( $cache_key );
		if ( ! empty( $data ) && ( 1 !== $refresh ) ) {
			return $data;
		}

		$data = US()->db->tags->get_by( 'id', $this->tag_id );
		$link_ids_map = US()->db->links_tags->get_link_ids_by_tag_ids( [ $this->tag_id ] );
		$link_ids = Helper::get_data( $link_ids_map, $this->tag_id, [] );

		if ( empty( $link_ids ) ) {
			return $data;
		}

		$data['links'] = US()->db->links->get_by_ids( $link_ids );
		$data['reports']['clicks'] = $this->get_clicks_info( $days, $link_ids, $start_date, $end_date );

		$total_clicks_by_days = $this->get_clicks_count_by_days( $days, $link_ids, $start_date, $end_date );

		$unique_start_date = $start_date;
		$unique_end_date    = $end_date;
		if ( empty( $unique_start_date ) || empty( $unique_end_date ) ) {
			if ( 0 === (int) $days ) {
				$unique_start_date = '2000-01-01';
				$unique_end_date   = date( 'Y-m-d' );
			} else {
				$dates             = Helper::get_start_and_end_date_from_last_days( $days );
				$unique_start_date = $dates['start_date'];
				$unique_end_date   = $dates['end_date'];
			}
		}

		$unique_clicks_by_days = US()->db->clicks->get_unique_clicks_count_by_days( $unique_start_date, $unique_end_date, $link_ids );

		$spline_data = [];
		foreach ( $total_clicks_by_days as $date => $count ) {
			$spline_data[ $date ] = [
				'date'          => $date,
				'total_clicks'  => (int) $count,
				'unique_clicks' => 0,
			];
		}

		foreach ( $unique_clicks_by_days as $date => $count ) {
			if ( ! isset( $spline_data[ $date ] ) ) {
				$spline_data[ $date ] = [
					'date'          => $date,
					'total_clicks'  => 0,
					'unique_clicks' => (int) $count,
				];
				continue;
			}

			$spline_data[ $date ]['unique_clicks'] = (int) $count;
		}

		$spline_data_filled = $this->fill_missing_dates_in_spline_data(
			array_values( $spline_data ),
			$days,
			$start_date,
			$end_date
		);

		$data['chart_data'] = [
			'dates'         => array_column( $spline_data_filled, 'date' ),
			'total_series'  => array_map( 'intval', array_column( $spline_data_filled, 'total_clicks' ) ),
			'unique_series' => array_map( 'intval', array_column( $spline_data_filled, 'unique_clicks' ) ),
		];

		$data['click_data_for_graph'] = array_combine(
			array_column( $spline_data_filled, 'date' ),
			array_map( 'intval', array_column( $spline_data_filled, 'total_clicks' ) )
		) ?: [];

		$heatmap_data = US()->db->clicks->get_heatmap_intensity_data( 365, $link_ids );
		$heatmap_map   = [];
		foreach ( $heatmap_data as $row ) {
			$date = Helper::get_data( $row, 'date' );
			if ( $date ) {
				$heatmap_map[ $date ] = (int) Helper::get_data( $row, 'count' );
			}
		}

		$heatmap = $this->build_heatmap_chart_data( $heatmap_map );

		$data['chart_data']['heatmap_series']       = $heatmap['heatmap_series'];
		$data['chart_data']['has_clicks_data']      = ! empty( $heatmap_map );
		$data['chart_data']['heatmap_week_starts']  = $heatmap['week_starts'];
		$data['chart_data']['heatmap_day_labels']   = $heatmap['day_labels'];
		$data['chart_data']['heatmap_month_labels'] = $heatmap['month_labels'];
		$data['chart_data']['heatmap_color_ranges'] = $heatmap['color_ranges'];

		$data['browser_info'] = $this->get_browser_info_for_graph( $link_ids );
		$data['device_info']  = $this->get_device_info_for_graph( $link_ids );
		$data['os_info']      = $this->get_os_info_for_graph( $link_ids );

		$countries_data = $this->get_country_info_for_graph( $link_ids );
		$country_info   = [];

		if ( Helper::is_forechable( $countries_data ) ) {
			$total_count = array_sum( array_values( $countries_data ) );

			foreach ( $countries_data as $country_iso_code => $total ) {
				if ( 'Others' === $country_iso_code ) {
					$country = __( 'Others', 'url-shortify' );
				} else {
					$country = Utils::get_country_name_from_iso_code( $country_iso_code );
				}

				$country_info[ $country_iso_code ]['name']       = $country;
				$country_info[ $country_iso_code ]['total']      = $total;
				$country_info[ $country_iso_code ]['percentage'] = round( ( $total * 100 ) / $total_count, 2 );
				$country_info[ $country_iso_code ]['flag_url']   = Utils::get_country_icon_url( $country_iso_code );
			}
		}

		$data['country_info']   = $country_info;
		$data['referrers_info'] = $this->get_referrers_info_for_graph( $link_ids );
		$data['last_updated_on'] = time();

		Cache::set_transient( $cache_key, $data, HOUR_IN_SECONDS * 3 );

		return $data;
	}

	/**
	 * Export click history of a tag.
	 *
	 * @since 1.13.1
	 */
	public function export() {
		$link_ids_map = US()->db->links_tags->get_link_ids_by_tag_ids( [ $this->tag_id ] );
		$link_ids     = Helper::get_data( $link_ids_map, $this->tag_id, [] );

		$days = apply_filters( 'kc_us_clicks_info_for_days', 7 );
		$clicks_data = $this->get_all_clicks_info( $days, $link_ids );

		$export = new Export();
		$headers = $export->get_clicks_info_headers();
		$csv_data = $export->generate_csv( $headers, $clicks_data );

		$export->download_csv( $csv_data, 'click-history.csv' );
	}

	/**
	 * Export links of a tag.
	 *
	 * @since 1.13.1
	 */
	public function export_links() {
		$link_ids_map = US()->db->links_tags->get_link_ids_by_tag_ids( [ $this->tag_id ] );
		$link_ids     = Helper::get_data( $link_ids_map, $this->tag_id, [] );
		$links    = US()->db->links->get_by_ids( $link_ids );

		$export = new Export();
		$headers = $export->get_links_headers();
		$csv_data = $export->generate_csv( $headers, $links );

		$export->download_csv( $csv_data, 'links.csv' );
	}
}