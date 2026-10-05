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

		$time_filter = self::sanitize_time_filter( Helper::get_request_data( 'time_filter', '' ) );

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

		/*
		 * Read the comparison request before the cache key is built. The cached
		 * payload differs with compare mode, so it has to vary the key - without
		 * this, toggling compare on a page that is already cached returns the
		 * aggregate-only payload and the toggle looks broken for three hours.
		 */
		$compare = sanitize_key( Helper::get_request_data( 'compare', '' ) );
		$compare = ( ! empty( $compare ) && US()->is_pro() ) ? 'links' : '';

		$compare_metric = sanitize_key( Helper::get_request_data( 'compare_metric', 'total' ) );
		$compare_metric = in_array( $compare_metric, [ 'total', 'unique' ], true ) ? $compare_metric : 'total';

		if ( ! empty( $compare ) ) {
			$cache_suffix .= '_cmp_' . $compare . '_' . $compare_metric;
		}

		$cache_key = 'tag_stats_v5_' . $this->tag_id . '_' . $cache_suffix;

		$data = Cache::get_transient( $cache_key );
		/*
		 * A payload cached by an older version of this screen can be missing
		 * keys the template now reads, which renders as a screen with pieces
		 * missing for up to three hours. Anything not stamped with the current
		 * shape is rebuilt.
		 */
		$is_usable = ! empty( $data )
			&& (int) Helper::get_data( $data, 'payload_version', 0 ) === self::PAYLOAD_VERSION;

		if ( $is_usable && ( 1 !== $refresh ) ) {
			return $data;
		}

		$data = US()->db->tags->get_by( 'id', $this->tag_id );
		$link_ids_map = US()->db->links_tags->get_link_ids_by_tag_ids( [ $this->tag_id ] );
		$link_ids = Helper::get_data( $link_ids_map, $this->tag_id, [] );

		if ( empty( $link_ids ) ) {
			return $data;
		}

		$data['links'] = US()->db->links->get_by_ids( $link_ids );
		/*
		 * Resolve the filter to concrete dates once and hand the same window to
		 * every panel, so the headline figures, the chart and the breakdowns
		 * cannot describe different periods.
		 */
		$period = $this->resolve_stats_period(
			[
				'days'       => $days,
				'start_date' => $start_date,
				'end_date'   => $end_date,
			]
		);

		$range_start = $period['start'];
		$range_end   = $period['end'];

		$data['reports']['clicks'] = $this->get_clicks_info( $days, $link_ids, $range_start, $range_end );

		$chart = $this->build_chart_payload( $link_ids, $days, $period );

		$data['chart_data']           = $chart['chart_data'];
		$data['click_data_for_graph'] = $chart['click_data_for_graph'];


		/*
		 * Comparison mode. Off by default, so the aggregate chart above is
		 * untouched for everyone who has not asked for this. PRO only, partly
		 * because it is a PRO feature and partly because free is capped at seven
		 * days of history, which is too short a window to compare anything over.
		 */
		if ( ! empty( $compare ) ) {
			$data['chart_data']['compare'] = $this->build_compare_chart_data(
				'link',
				[],
				$data['chart_data']['dates'],
				$compare_metric,
				$link_ids
			);
		}


		$data['browser_info'] = $this->get_browser_info_for_graph( $link_ids, 0, $range_start, $range_end );
		$data['device_info']  = $this->get_device_info_for_graph( $link_ids, 0, $range_start, $range_end );
		$data['os_info']      = $this->get_os_info_for_graph( $link_ids, 0, $range_start, $range_end );

		$countries_data = $this->get_country_info_for_graph( $link_ids, 0, $range_start, $range_end );
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
		$data['referrers_info'] = $this->get_referrers_info_for_graph( $link_ids, 0, $range_start, $range_end );

		/*
		 * The standings are the one thing on this screen that is useful without
		 * a licence - which links are in here and how much each is used - so
		 * they are built for everyone. The audience columns behind them are PRO,
		 * and skipping them saves a free site three queries.
		 */
		$is_pro = US()->is_pro();

		$data['members'] = $this->build_member_breakdown( $data['links'], $period, $is_pro );

		$data['period'] = $period;

		/*
		 * Headline figures, channels and peak times are PRO, so a free site pays
		 * none of that query cost.
		 */
		if ( $is_pro ) {
			$overview = $this->build_stats_overview(
				$link_ids,
				[
					'days'       => $days,
					'start_date' => $start_date,
					'end_date'   => $end_date,
				]
			);

			$overview['members'] = $data['members'];

			$overview['insights'] = array_slice(
				array_merge(
					$this->build_member_insights( $data['members'] ),
					$overview['insights']
				),
				0,
				4
			);

			$data['overview'] = $overview;
		} else {
			$data['overview'] = [];
		}

		$data['last_updated_on'] = time();

		// Stamped so a later shape change invalidates this entry on read.
		$data['payload_version'] = self::PAYLOAD_VERSION;

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

		$export   = new Export();
		$links    = $export->decorate_links( $links );
		$headers  = $export->get_links_headers();
		$csv_data = $export->generate_csv( $headers, $links );

		$export->download_csv( $csv_data, 'links.csv' );
	}
}