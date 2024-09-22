<?php


namespace KaizenCoders\URL_Shortify\Admin\Controllers;

use KaizenCoders\URL_Shortify\Helper;

class StatsController extends BaseController {

	/**
	 * StatsController constructor.
	 *
	 * @since 1.1.7
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Prepare data for report
	 *
	 * @since 1.1.7
	 * @return array|object|void|null
	 *
	 */
	public function prepare_data() {

		// Click History for last 7 days
		$days = apply_filters( 'kc_us_clicks_info_for_days', 3 );

		$clicks_data = $this->get_clicks_info( $days );

		$data['reports']['clicks'] = $clicks_data;

		$days = apply_filters( 'kc_us_clicks_count_for_days', 7 );

		$click_report = $this->get_clicks_count_by_days( $days );

		$data['click_data_for_graph'] = $click_report;

		return $data;
	}

	/**
	 * Get clicks info
	 *
	 * @since 1.1.7
	 *
	 * @param array $link_ids
	 *
	 * @param int   $days
	 *
	 * @return array
	 *
	 */
	public function get_clicks_info( $days = 7, $link_ids = [] ) {
		return US()->db->clicks->get_clicks_info( $days, $link_ids );
	}

	/**
	 * Get all clicks info
	 *
	 * @since 1.6.3
	 *
	 * @param array $link_ids
	 *
	 * @param int   $days
	 *
	 * @return array
	 *
	 */
	public function get_all_clicks_info( $days = 7, $link_ids = [] ) {
		return US()->db->clicks->get_all_clicks_info( $days, $link_ids );
	}

	/**
	 * Get clicks count by day
	 *
	 * @since 1.1.7
	 *
	 * @param array $link_ids
	 *
	 * @param int   $days
	 *
	 * @return array
	 *
	 */
	public function get_clicks_count_by_days( $days = 7, $link_ids = [] ) {
		$dates = Helper::get_start_and_end_date_from_last_days( $days );

		return US()->db->clicks->get_clicks_count_by_days( $dates['start_date'], $dates['end_date'], $link_ids );
	}

	/**
	 * Get country info
	 *
	 * @since 1.2.1
	 *
	 * @param array $link_ids
	 *
	 * @return mixed|void
	 *
	 */
	public function get_country_info( $link_ids = [] ) {
		return apply_filters( 'kc_us_link_country_info', $link_ids );
	}

	/**
	 * Get Referrers info
	 *
	 * @since 1.2.1
	 *
	 * @param array $link_ids
	 *
	 * @return mixed|void
	 *
	 */
	public function get_referrers_info( $link_ids = [] ) {
		return apply_filters( 'kc_us_link_referrers_info', $link_ids );
	}

	/**
	 * Get device info
	 *
	 * @since 1.2.1
	 *
	 * @param array $link_ids
	 *
	 * @return mixed|void
	 *
	 */
	public function get_device_info( $link_ids = [] ) {
		return apply_filters( 'kc_us_link_device_info', $link_ids );
	}

	/**
	 * Get browser info
	 *
	 * @since 1.2.1
	 *
	 * @param array $link_ids
	 *
	 * @return mixed|void
	 *
	 */
	public function get_browser_info( $link_ids = [] ) {
		return apply_filters( 'kc_us_link_browser_info', $link_ids );
	}

	/**
	 * Get OS info
	 *
	 * @since 1.2.1
	 *
	 * @param array $link_ids
	 *
	 * @return mixed|void
	 *
	 */
	public function get_os_info( $link_ids = [] ) {
		return apply_filters( 'kc_us_link_os_info', $link_ids );
	}

	/**
	 * Get Country info for graph
	 *
	 * @since 1.2.1
	 *
	 * @param array $link_ids
	 *
	 * @return array
	 *
	 */
	public function get_country_info_for_graph( $link_ids = [] ) {
		$results = $this->get_country_info( $link_ids );

		return $this->prepare_for_graph( $results, 5 );
	}

	/**
	 * Get Referrers info
	 *
	 * @since 1.2.1
	 *
	 * @param array $link_ids
	 *
	 * @return array
	 *
	 */
	public function get_referrers_info_for_graph( $link_ids = [] ) {
		$results = $this->get_referrers_info( $link_ids );

		return $this->prepare_for_graph( $results, 5 );
	}

	/**
	 * Get browser info for graph
	 *
	 * @since 1.2.1
	 *
	 * @param array $link_ids
	 *
	 * @return array
	 *
	 */
	public function get_browser_info_for_graph( $link_ids = [] ) {
		$results = $this->get_browser_info( $link_ids );

		return $this->prepare_for_graph( $results, 4 );
	}

	/**
	 * Get device info for graph
	 *
	 * @since 1.2.1
	 *
	 * @param array $link_ids
	 *
	 * @return array
	 *
	 */
	public function get_device_info_for_graph( $link_ids = [] ) {
		$results = $this->get_device_info( $link_ids );

		return $this->prepare_for_graph( $results, 4 );
	}

	/**
	 * Get OS Info for graph
	 *
	 * @since 1.2.1
	 *
	 * @param array $link_ids
	 *
	 * @return array
	 *
	 */
	public function get_os_info_for_graph( $link_ids = [] ) {
		$results = $this->get_os_info( $link_ids );

		return $this->prepare_for_graph( $results, 4 );
	}

	/**
	 * @since 1.2.1
	 *
	 * @param int $top_numbers
	 *
	 * @param     $results
	 *
	 * @return array
	 *
	 */
	public function prepare_for_graph( $results, $top_numbers = 3 ) {
		if ( empty( $results ) ) {
			return [];
		}

		$others_total = 0;
		if ( ! empty( $results['unknown'] ) ) {
			$others_total = $results['unknown'];
			unset( $results['unknown'] );
		}

		arsort( $results );

		if ( count( $results ) <= $top_numbers ) {
			if ( $others_total > 0 ) {
				$results['Others'] = $others_total;
			}

			return $results;
		} else {

			$i = 0;

			foreach ( $results as $key => $value ) {

				if ( $i >= $top_numbers ) {
					$others_total += $value;
				} else {
					$final_results[ $key ] = $value;
				}

				$i ++;
			}

			$final_results['Others'] = $others_total;
		}

		return $final_results;
	}
}
