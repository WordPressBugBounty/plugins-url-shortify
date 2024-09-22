<?php

namespace KaizenCoders\URL_Shortify\Admin\DB;

use KaizenCoders\URL_Shortify\Helper;

class Clicks extends Base_DB {
	/**
	 * Table Name
	 *
	 * @since 1.0.0
	 * @var string
	 *
	 */
	public $table_name;

	/**
	 * Table Version
	 *
	 * @since 1.0.0
	 * @var string
	 *
	 */
	public $version;

	/**
	 * Primary key
	 *
	 * @since 1.0.0
	 * @var string
	 *
	 */
	public $primary_key;

	/**
	 * Initialize
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		global $wpdb;

		parent::__construct();

		$this->table_name = $wpdb->prefix . 'kc_us_clicks';

		$this->version = '1.0';

		$this->primary_key = 'id';
	}

	/**
	 * Get columns and formats
	 *
	 * @since 1.0.0
	 */
	public function get_columns() {
		return [
			'id'              => '%d',
			'link_id'         => '%d',
			'uri'             => '%s',
			'host'            => '%s',
			'referer'         => '%s',
			'is_first_click'  => '%d',
			'is_robot'        => '%d',
			'user_agent'      => '%s',
			'os'              => '%s',
			'device'          => '%s',
			'browser_type'    => '%s',
			'browser_version' => '%s',
			'visitor_id'      => '%s',
			'country'         => '%s',
			'ip'              => '%s',
			'created_at'      => '%s',
		];
	}

	/**
	 * Get default column values
	 *
	 * @since 1.0.0
	 */
	public function get_column_defaults() {
		return [
			'link_id'         => null,
			'uri'             => null,
			'host'            => null,
			'referer'         => null,
			'is_first_click'  => 0,
			'is_robot'        => 0,
			'user_agent'      => null,
			'os'              => null,
			'device'          => null,
			'browser_type'    => null,
			'browser_version' => null,
			'visitor_id'      => null,
			'country'         => null,
			'ip'              => null,
			'created_at'      => Helper::get_current_date_time(),
		];
	}

	/**
	 * Get total by link ids
	 *
	 * @since 1.2.4
	 *
	 * @param array|null $link_ids
	 *
	 * @return int|string|null
	 *
	 */
	public function get_total_by_link_ids( $link_ids = null ) {
		if ( empty( $link_ids ) ) {
			return 0;
		}

		if ( ! is_array( $link_ids ) ) {
			$link_ids = [ $link_ids ];
		}

		$link_ids_str = $this->prepare_for_in_query( $link_ids );

		$where = "link_id IN ($link_ids_str)";

		return $this->count( $where );
	}

	/**
	 * Get total unique clicks by link ids
	 *
	 * @since 1.2.4
	 *
	 * @param array|null $link_ids
	 *
	 * @return int|string|null
	 *
	 */
	public function get_total_unique_by_link_ids( $link_ids = null ) {
		global $wpdb;

		if ( empty( $link_ids ) ) {
			return 0;
		}

		if ( ! is_array( $link_ids ) ) {
			$link_ids = [ $link_ids ];
		}

		$link_ids_str = $this->prepare_for_in_query( $link_ids );

		$where = $wpdb->prepare( "link_id IN ($link_ids_str) AND is_first_click = %d", 1 );

		return $this->count( $where );

	}

	/**
	 * Delete clicks by link id
	 *
	 * @since 1.0.2
	 *
	 * @param null $link_id
	 *
	 * @return bool
	 *
	 */
	public function delete_by_link_id( $link_id = null ) {
		if ( empty( $link_id ) ) {
			return false;
		}

		return $this->delete_by( 'link_id', $link_id );
	}

	/**
	 * Get clicks data
	 *
	 * @since 1.0.4
	 *
	 * @param int $days
	 *
	 * @param int $link_id
	 *
	 * @return array
	 *
	 */
	public function get_data_by_link_id( $link_id = 0, $days = 7 ) {
		global $wpdb;

		$where = $wpdb->prepare( 'link_id = %d AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY) ORDER BY created_at DESC', $link_id, $days );

		return $this->get_by_conditions( $where );
	}

	/**
	 * Get total unique clicks
	 *
	 * @since 1.1.5
	 * @return string|null
	 *
	 */
	public function get_total_unique_clicks() {
		global $wpdb;

		$where = $wpdb->prepare( 'is_first_click = %d', 1 );

		return $this->count( $where );
	}

	/**
	 * Get click history
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
		global $wpdb;

		$clicks_table = "{$wpdb->prefix}kc_us_clicks";
		$links_table  = "{$wpdb->prefix}kc_us_links";

		$query = "SELECT clicks.*, links.name as name FROM {$clicks_table} as clicks, {$links_table} as links";

		$where[] = 'clicks.link_id = links.id AND clicks.created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)';

		if ( ! empty( $link_ids ) ) {
			$link_ids_str = $this->prepare_for_in_query( $link_ids );

			$where[] = "link_id IN ($link_ids_str)";
		}

		$where_str = implode( ' AND ', $where );

		$query .= " WHERE $where_str ORDER BY clicks.created_at DESC LIMIT 0, 100";

		$query = $wpdb->prepare( $query, $days );

		return $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Get all click history
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
	public function get_all_clicks_info( $days = 7, $link_ids = [] ) {
		global $wpdb;

		$clicks_table = "{$wpdb->prefix}kc_us_clicks";
		$links_table  = "{$wpdb->prefix}kc_us_links";

		$query = "SELECT clicks.*, links.name as name FROM {$clicks_table} as clicks, {$links_table} as links";

		$where[] = 'clicks.link_id = links.id AND clicks.created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)';

		if ( ! empty( $link_ids ) ) {
			$link_ids_str = $this->prepare_for_in_query( $link_ids );

			$where[] = "link_id IN ($link_ids_str)";
		}

		$where_str = implode( ' AND ', $where );

		$query .= " WHERE $where_str ORDER BY clicks.created_at DESC";

		$query = $wpdb->prepare( $query, $days );

		return $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Get clicks data
	 *
	 * @since 1.1.6
	 *
	 * @param string $end_date
	 * @param array  $link_ids
	 *
	 * @param string $start_date
	 *
	 * @return array
	 *
	 */
	public function get_clicks_count_by_days( $start_date = '', $end_date = '', $link_ids = [] ) {
		global $wpdb;

		$clicks_table = "{$wpdb->prefix}kc_us_clicks";

		$query = "SELECT DATE(created_at) as date, IF(count(*) IS NULL, 0, count(*)) as count FROM $clicks_table";

		$where = [];
		if ( ! empty( $link_ids ) ) {

			$link_ids_str = $this->prepare_for_in_query( $link_ids );

			$where[] = "link_id IN ($link_ids_str)";
		}

		$where[] = $wpdb->prepare( 'DATE(created_at) >= %s AND DATE(created_at) <= %s ', $start_date, $end_date );

		if ( ! empty( $where ) ) {
			$where = implode( ' AND ', $where );
			$query .= " WHERE $where";
		}

		$query .= 'GROUP BY DATE(created_at) ORDER BY DATE(created_at) DESC';

		$results = $wpdb->get_results( $query, ARRAY_A );

		$data = [];
		if ( Helper::is_forechable( $results ) ) {
			foreach ( $results as $result ) {
				$data[ $result['date'] ] = $result['count'];
			}

			// Move pointer to last
			end( $data );

			$last_date = key( $data );

			$stop_date = date( 'Y-m-d', strtotime( $last_date . ' -1 day' ) );

		} else {
			$stop_date = date( 'Y-m-d', strtotime( 'today -1 day' ) );
		}


		$final_data = [];
		for ( $i = 0; $stop_date <= $end_date; $i ++ ) {
			$final_data[ $stop_date ] = Helper::get_data( $data, $stop_date, 0 );

			$stop_date = date( 'Y-m-d', strtotime( $stop_date . ' +1 day' ) );
		}

		return $final_data;
	}

	/**
	 * Get browser info
	 *
	 * @since 1.2.1
	 *
	 * @param array $link_ids
	 *
	 * @return array
	 *
	 */
	public function get_browser_info( $link_ids = [] ) {

		if ( empty( $link_ids ) ) {
			return [];
		}

		$link_ids_str = $this->prepare_for_in_query( $link_ids );

		$columns = [ 'browser_type', 'count(*) as total' ];
		$where   = "link_id IN ( $link_ids_str ) GROUP BY browser_type";

		$results = $this->get_columns_by_condition( $columns, $where );

		return $this->convert_to_associative_array( $results, 'browser_type', 'total' );
	}

	/**
	 * Get Country info
	 *
	 * @since 1.2.1
	 *
	 * @param array $link_ids
	 *
	 * @return array
	 *
	 */
	public function get_country_info( $link_ids = [] ) {

		if ( empty( $link_ids ) ) {
			return [];
		}

		$link_ids_str = $this->prepare_for_in_query( $link_ids );

		$columns = [ 'country', 'count(*) as total' ];
		$where   = "link_id IN ( $link_ids_str ) GROUP BY country";

		$results = $this->get_columns_by_condition( $columns, $where );

		return $this->convert_to_associative_array( $results, 'country', 'total' );
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
	public function get_referrers_info( $link_ids = [] ) {

		if ( empty( $link_ids ) ) {
			return [];
		}

		$link_ids_str = $this->prepare_for_in_query( $link_ids );

		$columns = [ 'referer', 'count(*) as total' ];
		$where   = "link_id IN ( $link_ids_str ) GROUP BY referer";

		$results = $this->get_columns_by_condition( $columns, $where );

		$null_label = __( 'Direct, Email, SMS', 'url-shortify' );

		return $this->convert_to_associative_array( $results, 'referer', 'total', $null_label );
	}

	/**
	 * Get Device info
	 *
	 * @since 1.2.1
	 *
	 * @param array $link_ids
	 *
	 * @return array
	 *
	 */
	public function get_device_info( $link_ids = [] ) {

		if ( empty( $link_ids ) ) {
			return [];
		}

		$link_ids_str = $this->prepare_for_in_query( $link_ids );

		$columns = [ 'device', 'count(*) as total' ];
		$where   = "link_id IN ( $link_ids_str ) GROUP BY device";

		$results = $this->get_columns_by_condition( $columns, $where );

		return $this->convert_to_associative_array( $results, 'device', 'total' );
	}

	/**
	 * Get Device info
	 *
	 * @since 1.2.1
	 *
	 * @param array $link_ids
	 *
	 * @return array
	 *
	 */
	public function get_os_info( $link_ids = [] ) {

		if ( empty( $link_ids ) ) {
			return [];
		}

		$link_ids_str = $this->prepare_for_in_query( $link_ids );

		$columns = [ 'os', 'count(*) as total' ];
		$where   = "link_id IN ( $link_ids_str ) GROUP BY os";

		$results = $this->get_columns_by_condition( $columns, $where );

		return $this->convert_to_associative_array( $results, 'os', 'total' );
	}

	/**
	 * Get links clicks count
	 *
	 * @since 1.4.0
	 *
	 * @param int $count
	 *
	 * @return array
	 *
	 */
	public function get_links_clicks_count( $count = 5 ) {

		$query = "SELECT link_id, count(id) as total_clicks FROM {$this->table_name} GROUP BY link_id ORDER BY total_clicks DESC limit 0, $count";

		$results = $this->get_by_query( $query );

		return $this->convert_to_associative_array( $results, 'link_id', 'total_clicks' );
	}

	/**
	 * Delete clicks older than days
	 *
	 * @since 1.8.0
	 *
	 * @param int $days Default 30 days.
	 *
	 * @return bool
	 *
	 */
	public function delete_clicks_older_than_days( $days = 30 ) {
		global $wpdb;

		$where = "created_at < DATE_SUB(NOW(), INTERVAL %d DAY)";

		$where = $wpdb->prepare( $where, $days );

		return $this->delete_by_condition( $where );
	}

	/**
	 * Delete all clicks.
	 *
	 * @since 1.8.0
	 * @return bool
	 *
	 */
	public function delete_all_clicks() {
		return $this->delete_all();
	}

	/**
	 * Get total clicks count by links ids.
	 *
	 * @param $link_ids
	 *
	 * @return array|object|\stdClass[]|null
	 *
	 * Output
	 *
	 * [
	 *  1 => 10,
	 *  5 => 45
	 *]
	 *
	 * @since 1.9.0
	 */
	public function get_total_clicks_and_unique_clicks_by_link_ids( $link_ids ) {
		global $wpdb;

		if ( empty( $link_ids ) ) {
			return [];
		}

		if ( ! is_array( $link_ids ) ) {
			$link_ids = [ $link_ids ];
		}

		$link_ids_str = $this->prepare_for_in_query( $link_ids );

		$where = "link_id IN ($link_ids_str)";

		$query = "SELECT `link_id`, count(*) as total_clicks, COUNT(CASE WHEN is_first_click = 1 THEN 1 ELSE NULL END) AS unique_clicks FROM {$this->table_name} WHERE {$where} GROUP BY `link_id`";

		$results = $wpdb->get_results( $query, ARRAY_A );

		$clicks_data = [];
		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				$clicks_data[ $result['link_id'] ]['total_clicks']  = $result['total_clicks'];
				$clicks_data[ $result['link_id'] ]['unique_clicks'] = $result['unique_clicks'];
			}
		}

		return $clicks_data;
	}

	/**
	 * Get total clicks and unique clicks by group ids.
	 *
	 * @param $group_ids
	 *
	 * @return array
	 *
	 * @since 1.9.0
	 */
	public function get_total_clicks_and_unique_clicks_by_group_ids( $group_ids ) {
		global $wpdb;

		if ( empty( $group_ids ) ) {
			return [];
		}

		if ( ! is_array( $group_ids ) ) {
			$group_ids = [ $group_ids ];
		}

		$group_ids_str = $this->prepare_for_in_query( $group_ids );

		$clicks_table = $wpdb->prefix . 'kc_us_clicks';
		$link_groups_table = $wpdb->prefix . 'kc_us_links_groups';

		$query = "SELECT lg.group_id, COUNT(c.link_id) AS total_clicks, COUNT(DISTINCT CASE WHEN c.is_first_click = 1 THEN c.id ELSE NULL END) AS unique_clicks 
				FROM {$clicks_table} c
				JOIN {$link_groups_table} lg ON c.link_id = lg.link_id 
                WHERE lg.group_id IN ({$group_ids_str})
				GROUP BY  lg.group_id";

		$results = $wpdb->get_results( $query, ARRAY_A );

		$clicks_data = [];
		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				$clicks_data[ $result['group_id'] ]['total_clicks']  = $result['total_clicks'];
				$clicks_data[ $result['group_id'] ]['unique_clicks'] = $result['unique_clicks'];
			}
		}

		return $clicks_data;
	}
}
