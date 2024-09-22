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
		$data = $this->prepare_data();

		$data['icon_url'] = "https://www.google.com/s2/favicons?domain={$data['url']}";

		$data['short_url'] = Helper::get_short_link( $data['slug'], $data );

		include KC_US_ADMIN_TEMPLATES_DIR . '/link-stats.php';
	}

	/**
	 * Prepare data for report
	 *
	 * @return array|object|void|null
	 *
	 * @since 1.0.4
	 */
	public function prepare_data() {
		$refresh = (int) Helper::get_request_data( 'refresh', 0 );

		// If we have the data in cache, get it from it.
		// We store data in cache for 3 hours
		$data = Cache::get_transient( 'link_stats_' . $this->link_id );

		if ( ! empty( $data ) && ( 1 !== $refresh ) ) {
			return $data;
		}

		$data = US()->db->links->get_by_id( $this->link_id );

		// Click History for last 7 days
		$days = apply_filters( 'kc_us_clicks_info_for_days', 7 );

		$clicks_data = $this->get_clicks_info( $days, array( $this->link_id ) );

		$data['reports']['clicks'] = $clicks_data;

		$days = apply_filters( 'kc_us_clicks_count_for_days', 7 );

		$click_report = $this->get_clicks_count_by_days( $days, array( $this->link_id ) );

		$data['click_data_for_graph'] = $click_report;

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

		$data['last_updated_on'] = time();

		// Store data in cache for 3 hours
		Cache::set_transient( 'link_stats_' . $this->link_id, $data, HOUR_IN_SECONDS * 3 );

		return $data;
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