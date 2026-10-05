<?php

namespace KaizenCoders\URL_Shortify\Admin\Controllers;

use KaizenCoders\URL_Shortify\Cache;
use KaizenCoders\URL_Shortify\Common\Export;
use KaizenCoders\URL_Shortify\Common\Utils;
use KaizenCoders\URL_Shortify\Helper;

class DashboardController extends StatsController {

	/**
	 * How many links the dashboard leaderboard shows.
	 *
	 * A summary screen wants the ones worth acting on, not the whole library;
	 * the links list is one click away for the rest.
	 *
	 * @since 2.7.0
	 */
	const DASHBOARD_TOP_LINKS = 10;
	/**
	 * DashboardController constructor.
	 *
	 * @since 1.1.5
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Render dashboard
	 *
	 * @since 1.1.5
	 */
	public function render() {
		$refresh = (int) Helper::get_request_data( 'refresh', 0 );

		$action = Helper::get_request_data( 'action' );

		if ( 'export' === $action ) {
			// In our file that handles the request, verify the nonce.
			$nonce = Helper::get_request_data( '_wpnonce' );

			if ( ! wp_verify_nonce( $nonce, 'us_action_nonce' ) ) {
				$message = __( 'You do not have permission to access this link.', 'url-shortify' );
				US()->notices->error( $message );
			} else {
				$this->export();
				exit();
			}

		} else {
			$filter = $this->get_dashboard_filter();

			$period = $this->resolve_stats_period( $filter );

			/*
			 * The cache key carries the period. It did not before, so every range
			 * shared one entry: picking a different one returned whatever had been
			 * cached first, which is part of why the filter appeared to do nothing.
			 */
			$cache_key = 'dashboard_stats_' . sanitize_key( $filter['time_filter'] );

			if ( ! empty( $filter['start_date'] ) && ! empty( $filter['end_date'] ) ) {
				$cache_key .= '_' . sanitize_key( $filter['start_date'] . '_' . $filter['end_date'] );
			}

			$data = Cache::get_transient( $cache_key );

			$is_usable = ! empty( $data )
				&& (int) Helper::get_data( $data, 'payload_version', 0 ) === self::PAYLOAD_VERSION;

			if ( ! $is_usable || ( 1 === $refresh ) ) {
				$data = $this->prepare_dashboard_data( $filter, $period );

				Cache::set_transient( $cache_key, $data, HOUR_IN_SECONDS * 3 );
			}

			include_once KC_US_ADMIN_TEMPLATES_DIR . '/dashboard.php';
		}
	}

	/**
	 * Read the period filter off the request.
	 *
	 * @since 2.7.0
	 *
	 * @return array
	 */
	private function get_dashboard_filter() {
		$time_filter = self::sanitize_time_filter( Helper::get_request_data( 'time_filter', '' ) );

		$days = [
			'today'        => 1,
			'last_7_days'  => 7,
			'last_30_days' => 30,
			'last_60_days' => 60,
			'all_time'     => 0,
			'custom'       => 0,
		];

		$filter = [
			'time_filter' => $time_filter,
			'days'        => Helper::get_data( $days, $time_filter, 7 ),
			'start_date'  => '',
			'end_date'    => '',
		];

		if ( 'custom' === $time_filter ) {
			$filter['start_date'] = sanitize_text_field( Helper::get_request_data( 'start_date', '' ) );
			$filter['end_date']   = sanitize_text_field( Helper::get_request_data( 'end_date', '' ) );
		}

		return $filter;
	}

	/**
	 * Everything the dashboard draws, for one period.
	 *
	 * @since 2.7.0
	 *
	 * @param array $filter
	 * @param array $period
	 *
	 * @return array
	 */
	private function prepare_dashboard_data( $filter, $period ) {
		$total_links = US()->db->links->count();

		$data = [
			'show_kpis'       => $total_links > 0,
			'total_links'     => $total_links,
			'total_groups'    => US()->db->groups->count(),
			'new_link_url'    => admin_url( 'admin.php?page=us_links&action=new' ),
			'new_group_url'   => admin_url( 'admin.php?page=us_groups&action=new' ),
			'links_url'       => admin_url( 'admin.php?page=us_links' ),
			'period'          => $period,
			'last_updated_on' => time(),
			'payload_version' => self::PAYLOAD_VERSION,
		];

		if ( 0 === $total_links ) {
			return $data;
		}

		$days        = (int) $filter['days'];
		$range_start = $period['start'];
		$range_end   = $period['end'];

		$link_ids = US()->db->links->get_column( 'id' );

		$data['reports']['clicks'] = $this->get_clicks_info( $days, $link_ids, $range_start, $range_end );

		$chart = $this->build_chart_payload( $link_ids, $days, $period );

		$data['chart_data']           = $chart['chart_data'];
		$data['click_data_for_graph'] = $chart['click_data_for_graph'];

		$data['browser_info'] = $this->get_browser_info_for_graph( $link_ids, 0, $range_start, $range_end );
		$data['device_info']  = $this->get_device_info_for_graph( $link_ids, 0, $range_start, $range_end );
		$data['os_info']      = $this->get_os_info_for_graph( $link_ids, 0, $range_start, $range_end );

		$countries_data = $this->get_country_info_for_graph( $link_ids, 0, $range_start, $range_end );

		$country_info = [];

		if ( Helper::is_forechable( $countries_data ) ) {
			$total_count = array_sum( array_values( $countries_data ) );

			foreach ( $countries_data as $country_iso_code => $total ) {
				$country = ( 'Others' === $country_iso_code )
					? __( 'Others', 'url-shortify' )
					: Utils::get_country_name_from_iso_code( $country_iso_code );

				$country_info[ $country_iso_code ] = [
					'name'       => $country,
					// Formatted on output, not here - the template needs the
					// number to draw a bar from.
					'total'      => (int) $total,
					'percentage' => $total_count > 0 ? round( ( $total * 100 ) / $total_count, 2 ) : 0,
					'flag_url'   => Utils::get_country_icon_url( $country_iso_code ),
				];
			}
		}

		$data['country_info']   = $country_info;
		$data['referrers_info'] = $this->get_referrers_info_for_graph( $link_ids, 0, $range_start, $range_end );

		$is_pro = US()->is_pro();

		/*
		 * Top links is PRO, so a free site builds none of it. Only the busiest
		 * handful belong on a summary screen; the rest are a click away on the
		 * links list.
		 */
		$data['members'] = [];
		$data['overview'] = [];

		if ( $is_pro ) {
			$members = $this->build_member_breakdown( US()->db->links->get_by_ids( $link_ids ), $period, true );

			$members['rows'] = array_slice( $members['rows'], 0, self::DASHBOARD_TOP_LINKS );

			$data['members']  = $members;
			$data['overview'] = $this->build_stats_overview( $link_ids, $filter );
		}

		return $data;
	}

	/**
	 * Export click history.
	 *
	 * @since 1.6.3
	 * @return void
	 *
	 */
	public function export() {
		// Click History for last 7 days
		$days = apply_filters( 'kc_us_clicks_info_for_days', 7 );

		$clicks_data = $this->get_all_clicks_info( $days );

		$export = new Export();

		$headers = $export->get_clicks_info_headers();

		$csv_data = $export->generate_csv( $headers, $clicks_data );

		$file_name = 'click-history.csv';

		$export->download_csv( $csv_data, $file_name );
	}
}
