<?php

namespace KaizenCoders\URL_Shortify\Admin\Controllers;

use KaizenCoders\URL_Shortify\Cache;
use KaizenCoders\URL_Shortify\Common\Export;
use KaizenCoders\URL_Shortify\Common\Utils;
use KaizenCoders\URL_Shortify\Helper;

class LinkStatsController extends StatsController {
	/**
	 * Link ID
	 *
	 * @var null
	 *
	 * @since 1.0.4
	 */
	public $link_id = null;

	/**
	 * Link_Stats constructor.
	 *
	 * @param null $link_id
	 *
	 * @since 1.0.4
	 */
	public function __construct( $link_id = null ) {
		$this->link_id = $link_id;

		parent::__construct();
	}

	/**
	 * Render Link stats page
	 *
	 * @since 1.0.4
	 */
	public function render() {
		$data = $this->prepare_data( false );

		$data['icon_url'] = "https://www.google.com/s2/favicons?domain={$data['url']}";

		$data['short_url'] = Helper::get_short_link( $data['slug'], $data );

		include KC_US_ADMIN_TEMPLATES_DIR . '/link-stats.php';
	}

	/**
	 * Prepare data for report
	 *
	 * @param bool $include_click_history Keep signature compatible with parent.
	 *
	 * @return array|object|void|null
	 *
	 * @since 1.0.4
	 */
	public function prepare_data( $include_click_history = true ) {
		$refresh = (int) Helper::get_request_data( 'refresh', 0 );
		$filter_context = $this->get_clicks_filter_context();
		$time_filter    = $filter_context['time_filter'];
		$days           = $filter_context['days'];
		$start_date     = $filter_context['start_date'];
		$end_date       = $filter_context['end_date'];

		// If we have the data in cache, get it from it.
		// We store data in cache for 3 hours
		$cache_key = 'link_stats_' . $this->link_id . '_' . sanitize_key( $time_filter );
		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$cache_key .= '_' . sanitize_key( $start_date . '_' . $end_date );
		}
		$data      = Cache::get_transient( $cache_key );

		if ( ! empty( $data ) && ( 1 !== $refresh ) ) {
			return $data;
		}

		$data = US()->db->links->get_by_id( $this->link_id );

		// Click History for the selected range.
		$history_days = apply_filters( 'kc_us_clicks_info_for_days', $days );

		$clicks_data = $this->get_clicks_info( $history_days, array( $this->link_id ), $start_date, $end_date );

		$data['reports']['clicks'] = $clicks_data;

		$spline_data_filled = $this->fill_missing_dates_in_spline_data(
			US()->db->clicks->get_spline_chart_data( $days, array( $this->link_id ), $start_date, $end_date ),
			$days,
			$start_date,
			$end_date
		);

		$heatmap_data = US()->db->clicks->get_heatmap_intensity_data( 365, array( $this->link_id ), $start_date, $end_date );
		$heatmap_map   = [];
		foreach ( $heatmap_data as $row ) {
			$date = Helper::get_data( $row, 'date' );
			if ( $date ) {
				$heatmap_map[ $date ] = (int) Helper::get_data( $row, 'count' );
			}
		}

		$heatmap = $this->build_heatmap_chart_data( $heatmap_map );

		$data['chart_data'] = [
			'dates'                => array_column( $spline_data_filled, 'date' ),
			'total_series'         => array_map( 'intval', array_column( $spline_data_filled, 'total_clicks' ) ),
			'unique_series'        => array_map( 'intval', array_column( $spline_data_filled, 'unique_clicks' ) ),
			'heatmap_series'       => $heatmap['heatmap_series'],
			'has_clicks_data'      => ! empty( $heatmap_map ),
			'heatmap_week_starts'  => $heatmap['week_starts'],
			'heatmap_day_labels'   => $heatmap['day_labels'],
			'heatmap_month_labels' => $heatmap['month_labels'],
			'heatmap_color_ranges' => $heatmap['color_ranges'],
		];

		$data['click_data_for_graph'] = array_combine(
			array_column( $spline_data_filled, 'date' ),
			array_map( 'intval', array_column( $spline_data_filled, 'total_clicks' ) )
		) ?: [];

		$data['browser_info'] = $this->get_browser_info_for_graph( array( $this->link_id ) );
		$data['device_info']  = $this->get_device_info_for_graph( array( $this->link_id ) );
		$data['os_info']      = $this->get_os_info_for_graph( array( $this->link_id ) );

		$countries_data = $this->get_country_info_for_graph( array( $this->link_id ) );

		$country_info = array();

		if ( Helper::is_forechable( $countries_data ) ) {
			$tota_count = array_sum( array_values( $countries_data ) );

			foreach ( $countries_data as $country_iso_code => $total ) {

				if ( 'Others' === $country_iso_code ) {
					$country = __( 'Others', 'url-shortify' );
				} else {
					$country = Utils::get_country_name_from_iso_code( $country_iso_code );
				}

				$country_info[ $country_iso_code ]['name']       = $country;
				$country_info[ $country_iso_code ]['total']      = $total;
				$country_info[ $country_iso_code ]['percentage'] = round( ( $total * 100 ) / $tota_count, 2 );
				$country_info[ $country_iso_code ]['flag_url']   = Utils::get_country_icon_url( $country_iso_code );
			}
		}

		$data['country_info'] = $country_info;

		$data['referrers_info'] = $this->get_referrers_info_for_graph( array( $this->link_id ) );

		/**
		 * Split test results — populated by PRO via the kc_us_get_split_test_results filter.
		 * Returns an empty array for free users or links that don't use link-rotation.
		 */
		$data['split_test_results'] = apply_filters( 'kc_us_get_split_test_results', [], $this->link_id, $data );

		$data['last_updated_on'] = time();

		// Store data in cache for 3 hours
		Cache::set_transient( $cache_key, $data, HOUR_IN_SECONDS * 3 );

		return $data;
	}

	/**
	 * Prepare chart data for AJAX refreshes.
	 *
	 * @return array
	 */
	public function get_chart_data_response() {
		$data = $this->prepare_data( false );

		return [
			'chart_data'       => $data['chart_data'],
			'clicks_total'     => array_sum( array_map( 'intval', Helper::get_data( $data, 'click_data_for_graph', [] ) ) ),
			'time_filter'      => Helper::get_request_data( 'time_filter', '' ),
			'start_date'       => Helper::get_request_data( 'start_date', '' ),
			'end_date'         => Helper::get_request_data( 'end_date', '' ),
		];
	}

	/**
	 * Map the selected time filter to the number of days to query.
	 *
	 * @param string $time_filter
	 *
	 * @return int
	 */
	private function get_days_from_time_filter( $time_filter ) {
		switch ( $time_filter ) {
			case 'today':
				return 1;
			case 'last_7_days':
				return 7;
			case 'last_30_days':
				return 30;
			case 'last_60_days':
				return 60;
			case 'all_time':
				return 0;
			default:
				return US()->is_pro() ? 0 : 7;
		}
	}

	/**
	 * Resolve the filter context from the current request.
	 *
	 * @return array
	 */
	private function get_clicks_filter_context() {
		$default_time_filter = US()->is_pro() ? 'all_time' : 'last_7_days';
		$time_filter         = sanitize_key( Helper::get_request_data( 'time_filter', '' ) );

		if ( empty( $time_filter ) ) {
			$time_filter = $default_time_filter;
		}

		$context = [
			'time_filter' => $time_filter,
			'days'        => $this->get_days_from_time_filter( $time_filter ),
			'start_date'  => '',
			'end_date'    => '',
		];

		if ( 'custom' !== $time_filter ) {
			return $context;
		}

		$start_date = sanitize_text_field( Helper::get_request_data( 'start_date', '' ) );
		$end_date   = sanitize_text_field( Helper::get_request_data( 'end_date', '' ) );
		$range      = $this->normalize_custom_date_range( $start_date, $end_date );

		if ( empty( $range ) ) {
			$context['time_filter'] = $default_time_filter;
			$context['days']        = $this->get_days_from_time_filter( $default_time_filter );
			return $context;
		}

		return array_merge( $context, $range );
	}

	/**
	 * Normalize a custom range into safe dates.
	 *
	 * @param string $start_date
	 * @param string $end_date
	 *
	 * @return array
	 */
	private function normalize_custom_date_range( $start_date, $end_date ) {
		$start = \DateTimeImmutable::createFromFormat( 'Y-m-d', $start_date );
		$end   = \DateTimeImmutable::createFromFormat( 'Y-m-d', $end_date );

		if ( ! $start || ! $end ) {
			return [];
		}

		if ( $start > $end ) {
			$swap  = $start;
			$start = $end;
			$end   = $swap;
		}

		return [
			'start_date' => $start->format( 'Y-m-d' ),
			'end_date'   => $end->format( 'Y-m-d' ),
			'days'       => $start->diff( $end )->days + 1,
		];
	}

	/**
	 * Export click history.
	 *
	 * @return void
	 *
	 * @since 1.6.3
	 */
	public function export() {
		// Click History for last 7 days
		$days = apply_filters( 'kc_us_clicks_info_for_days', 7 );

		$clicks_data = $this->get_all_clicks_info( $days, array( $this->link_id ) );

		$export = new Export();

		$headers = $export->get_clicks_info_headers();

		$csv_data = $export->generate_csv( $headers, $clicks_data );

		$file_name = 'click-history.csv';

		$export->download_csv( $csv_data, $file_name );
	}

}
