<?php

namespace KaizenCoders\URL_Shortify\Admin\DB;

use KaizenCoders\URL_Shortify\Helper;

class Favorites_Links extends Base_DB {
	public function __construct() {
		global $wpdb;
		parent::__construct();

		$this->table_name = $wpdb->prefix . 'kc_us_favorites_links';

		$this->primary_key = 'id';
	}

	/**
	 * Get columns and formats
	 *
	 * @since 1.12.2
	 */
	public function get_columns() {
		return [
			'id'         => '%d',
			'link_id'    => '%d',
			'user_id'    => '%d',
			'created_at' => '%s',
		];
	}

	/**
	 * Get default column values
	 *
	 * @since 1.12.2
	 */
	public function get_column_defaults() {
		return [
			'link_id'    => null,
			'user_id_id' => null,
			'created_at' => Helper::get_current_date_time(),
		];
	}

	/**
	 * @param $user_id
	 * @param $link_id
	 *
	 * @return bool|int|\mysqli_result|null
	 */
	public function toggle_favorite( $user_id, $link_id ) {
		global $wpdb;

		$where = $wpdb->prepare( "user_id = %d AND link_id = %d", $user_id, $link_id );

		// Check if it exists
		$is_exists = $wpdb->get_var( "SELECT id FROM {$this->table_name} WHERE $where" );

		if ( ! empty( $is_exists ) ) {
			return $this->delete_by_condition( $where );
		} else {
			return $this->insert(
				[
					'user_id' => absint( $user_id ),
					'link_id' => absint( $link_id ),
				],
			);
		}
	}

	/**
	 * Get User Favorites Links.
	 *
	 * @param $user_id
	 *
	 * @return array
	 */
	public function get_by_user_id( $user_id ) {
		global $wpdb;

		$where = $wpdb->prepare( "user_id = %d", absint( $user_id ) );

		$results = $this->get_columns_by_condition( [ 'link_id' ], $where );

		return wp_list_pluck( $results, 'link_id' );
	}

}