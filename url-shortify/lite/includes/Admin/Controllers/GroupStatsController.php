<?php

namespace KaizenCoders\URL_Shortify\Admin\Controllers;

use KaizenCoders\URL_Shortify\Cache;
use KaizenCoders\URL_Shortify\Common\Export;
use KaizenCoders\URL_Shortify\Common\Utils;
use KaizenCoders\URL_Shortify\Helper;

class GroupStatsController extends StatsController {
	/**
	 * Group ID
	 *
	 * @since 1.1.3
	 * @var null
	 *
	 */
	public $group_id = null;

	/**
	 * Link_Stats constructor.
	 *
	 * @since 1.0.4
	 *
	 * @param null $group_id
	 *
	 */
	public function __construct( $group_id = null ) {
		$this->group_id = $group_id;

		parent::__construct();
	}

	/**
	 * Render Group stats page
	 *
	 * @since 1.1.7
	 */
	public function render() {
		$data = $this->prepare_data( false );

		include KC_US_ADMIN_TEMPLATES_DIR . '/group-stats.php';
	}

	/**
	 * Prepare data for report
	 *
	 * @since 1.1.7
	 * @return array|object|void|null
	 *
	 */
	public function prepare_data( $include_click_history = true ) {
		$refresh = (int) Helper::get_request_data( 'refresh', 0 );

		$time_filter = sanitize_key( Helper::get_request_data( 'time_filter', '' ) );
		if ( empty( $time_filter ) ) {
			$time_filter = US()->is_pro() ? 'all_time' : 'last_7_days';
		}
		if ( 'custom' === $time_filter && ! US()->is_pro() ) {
			$time_filter = 'last_7_days';
		}

		$start_date = '';
		$end_date   = '';
		if ( 'custom' === $time_filter && US()->is_pro() ) {
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

		// Build a filter-aware cache key so different time ranges are cached separately.
		$cache_suffix = $time_filter;
		if ( 'custom' === $time_filter && $start_date && $end_date ) {
			$cache_suffix .= '_' . $start_date . '_' . $end_date;
		}
		$cache_key = 'group_stats_' . $this->group_id . '_' . $cache_suffix;

		// If we have the data in cache, get it from it.
		// We store data in cache for 3 hours
		$data = Cache::get_transient( $cache_key );

		if ( ! empty( $data ) && ( 1 !== $refresh ) ) {
			return $data;
		}

		$data = US()->db->groups->get_by_id( $this->group_id );

		$link_ids = US()->db->links_groups->get_link_ids_by_group_id( $this->group_id );

		if ( empty( $link_ids ) ) {
			return $data;
		}

		$data['links'] = US()->db->links->get_by_ids( $link_ids );

		$data['reports']['clicks'] = $this->get_clicks_info( $days, $link_ids, $start_date, $end_date );

		$data['click_data_for_graph'] = $this->get_clicks_count_by_days( $days, $link_ids, $start_date, $end_date );

		$data['browser_info'] = $this->get_browser_info_for_graph( $link_ids );
		$data['device_info']  = $this->get_device_info_for_graph( $link_ids );
		$data['os_info']      = $this->get_os_info_for_graph( $link_ids );

		$countries_data = $this->get_country_info_for_graph( $link_ids );

		$country_info = [];

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

		$data['country_info'] = $country_info;

		$data['referrers_info'] = $this->get_referrers_info_for_graph( $link_ids );

		$data['last_updated_on'] = time();

		// Store data in cache for 3 hours
		Cache::set_transient( $cache_key, $data, HOUR_IN_SECONDS * 3 );

		return $data;
	}

	/**
	 * Export click history of a group.
	 *
	 * @since 1.6.5
	 * @return void
	 *
	 */
	public function export() {
		$link_ids = US()->db->links_groups->get_link_ids_by_group_id( $this->group_id );

		// Click History for last 7 days
		$days = apply_filters( 'kc_us_clicks_info_for_days', 7 );

		$clicks_data = $this->get_all_clicks_info( $days, $link_ids );

		$export = new Export();

		$headers = $export->get_clicks_info_headers();

		$csv_data = $export->generate_csv( $headers, $clicks_data );

		$file_name = 'click-history.csv';

		$export->download_csv( $csv_data, $file_name );
	}

	/**
	 * Export click history of a group.
	 *
	 * @since 1.6.5
	 * @return void
	 *
	 */
	public function export_links() {
		$link_ids = US()->db->links_groups->get_link_ids_by_group_id( $this->group_id );

		$links = US()->db->links->get_by_ids( $link_ids );

		$export = new Export();

		$headers = $export->get_links_headers();

		$csv_data = $export->generate_csv( $headers, $links );

		$file_name = 'links.csv';

		$export->download_csv( $csv_data, $file_name );
	}

}
