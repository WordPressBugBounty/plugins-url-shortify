<?php

namespace KaizenCoders\URL_Shortify\Admin\DB;

use KaizenCoders\URL_Shortify\Helper;

class Links_Groups extends Base_DB {
	/**
	 * Table Name
	 *
	 * @since 1.1.3
	 * @var string
	 *
	 */
	public $table_name;

	/**
	 * Table Version
	 *
	 * @since 1.1.3
	 * @var string
	 *
	 */
	public $version;

	/**
	 * Primary key
	 *
	 * @since 1.1.3
	 * @var string
	 *
	 */
	public $primary_key;

	/**
	 * Initialize
	 *
	 * constructor.
	 *
	 * @since 1.1.3
	 */
	public function __construct() {
		global $wpdb;

		parent::__construct();

		$this->table_name = $wpdb->prefix . 'kc_us_links_groups';

		$this->version = '1.0';

		$this->primary_key = 'id';
	}

	/**
	 * Get columns and formats
	 *
	 * @since 1.1.3
	 */
	public function get_columns() {
		return [
			'id'            => '%d',
			'link_id'       => '%d',
			'group_id'      => '%d',
			'created_by_id' => '%d',
			'created_at'    => '%s',
		];
	}

	/**
	 * Get default column values
	 *
	 * @since 1.1.3
	 */
	public function get_column_defaults() {
		return [
			'link_id'       => null,
			'group_id'      => null,
			'created_by_id' => null,
			'created_at'    => Helper::get_current_date_time(),
		];
	}

	/**
	 * Get group ids by link ids
	 *
	 * @since 1.1.3
	 *
	 * @param array $link_ids
	 *
	 * @return array
	 *
	 */
	public function get_group_ids_by_link_ids( $link_ids = [] ) {
		$data = [];
		if ( empty( $link_ids ) ) {
			return $data;
		}

		if ( is_scalar( $link_ids ) ) {
			$link_ids = [ $link_ids ];
		}

		if ( ! Helper::is_forechable( $link_ids ) ) {
			return $data;
		}

		$link_ids_str = $this->prepare_for_in_query( $link_ids );

		$where = "link_id IN ($link_ids_str)";

		$results = $this->get_columns_by_condition( [ 'link_id', 'group_id' ], $where );

		if ( Helper::is_forechable( $results ) ) {
			foreach ( $results as $result ) {
				$data[ $result['link_id'] ][] = $result['group_id'];
			}
		}

		return $data;
	}

	/**
	 * Get group ids based on link id
	 *
	 * @since 1.3.7
	 *
	 * @param $link_id
	 *
	 * @return array|\KaizenCoders\URL_Shortify\data|string
	 *
	 */
	public function get_group_ids_by_link_id( $link_id ) {
		$group_ids = $this->get_group_ids_by_link_ids( $link_id );

		return Helper::get_data( $group_ids, $link_id, [] );
	}

	/**
	 * Get link ids by group ids
	 *
	 * @since 1.1.7
	 *
	 * @param array $group_ids
	 *
	 * @return array
	 *
	 */
	public function get_link_ids_by_group_ids( $group_ids = [] ) {
		$data = [];
		if ( empty( $group_ids ) ) {
			return $data;
		}

		if ( is_scalar( $group_ids ) ) {
			$group_ids = [ $group_ids ];
		}

		if ( ! Helper::is_forechable( $group_ids ) ) {
			return $data;
		}

		$group_ids_str = $this->prepare_for_in_query( $group_ids );

		$where = "group_id IN ($group_ids_str)";

		$results = $this->get_columns_by_condition( [ 'link_id', 'group_id' ], $where );

		if ( Helper::is_forechable( $results ) ) {
			foreach ( $results as $result ) {
				$data[ $result['group_id'] ][] = $result['link_id'];
			}
		}

		return $data;
	}

	/**
	 * Get link ids single by group id
	 *
	 * @since 1.2.4
	 *
	 * @param null $group_id
	 *
	 * @return array|string
	 *
	 */
	public function get_link_ids_by_group_id( $group_id = null ) {
		if ( empty( $group_id ) ) {
			return [];
		}

		$results = $this->get_link_ids_by_group_ids( [ $group_id ] );

		return Helper::get_data( $results, $group_id, [] );
	}

	/**
	 * @since 1.1.3
	 *
	 * @param array $group_ids
	 *
	 * @param null  $link_id
	 *
	 * @return bool
	 *
	 */
	public function add_link_to_groups( $link_id = null, $group_ids = [] ) {
		if ( empty( $link_id ) ) {
			return false;
		}

		if ( ! is_array( $group_ids ) ) {
			$group_ids = [ absint( $group_ids ) ];
		}

		$link_id = esc_sql( absint( $link_id ) );

		if ( 0 != $link_id ) {

			// If we don't get any group ids, which means we have to remove link from all
			// groups and not to add in any group
			$this->delete_groups_by_link_id( $link_id );

			if ( ! empty( $group_ids ) ) {

				$link_data = $this->prepare_links_data( $link_id, $group_ids );

				return $this->bulk_insert( $link_data );
			}

		}

		return true;
	}

	/**
	 * Add links to specific group or Move links to specific group.
	 *
	 * When we add links to specific groups, we keep the current groups of links as it is.
	 *
	 * When we move links ot specific groups, we delete links' current groups and then move link to new group.
	 *
	 * @since 1.6.1
	 *
	 * @param array   $group_ids
	 * @param boolean $move Whether to move links to group or not.
	 *
	 * @param array   $link_ids
	 *
	 * @return bool
	 *
	 */
	public function map_links_and_groups( $link_ids = [], $group_ids = [], $move = false ) {
		if ( empty( $link_ids ) || empty( $group_ids ) ) {
			return false;
		}

		if ( ! is_array( $link_ids ) ) {
			$link_ids = [ absint( $link_ids ) ];
		}

		if ( ! is_array( $group_ids ) ) {
			$group_ids = [ absint( $group_ids ) ];
		}

		if ( is_array( $link_ids ) && is_array( $group_ids ) ) {

			if ( $move ) {
				$links_ids_str = $this->prepare_for_in_query( $link_ids );

				$where = "link_id IN ($links_ids_str)";

				// Delete current groups of all $link_ids.
				$this->delete_by_condition( $where );
			}

			$links_groups_data = $this->prepare_links_groups_data( $link_ids, $group_ids );

			return $this->bulk_insert( $links_groups_data );
		}

		return true;
	}

	/**
	 * Delete link from all groups
	 *
	 * @since 1.1.3
	 *
	 * @param null $link_id
	 *
	 * @return bool
	 *
	 */
	public function delete_groups_by_link_id( $link_id = null ) {
		if ( empty( $link_id ) ) {
			return false;
		}

		return $this->delete_by( 'link_id', $link_id );
	}

	/**
	 * Delete links based on group_id
	 *
	 * @since 1.1.3
	 *
	 * @param null $group_id
	 *
	 * @return bool
	 *
	 */
	public function delete_links_by_group_id( $group_id = null ) {

		if ( empty( $group_id ) ) {
			return false;
		}

		return $this->delete_by( 'group_id', $group_id );
	}

	/**
	 * Prepare link data
	 *
	 * @since 1.1.3
	 *
	 * @param array $group_ids
	 *
	 * @param array $link_id
	 *
	 * @return array
	 *
	 */
	public function prepare_links_data( $link_id = [], $group_ids = [] ) {
		if ( empty( $group_ids ) || empty( $link_id ) ) {
			return [];
		}

		$link_id = esc_sql( absint( $link_id ) );

		$link_data = [];

		if ( 0 != $link_id ) {

			$data = [
				'link_id'       => $link_id,
				'created_at'    => Helper::get_current_date_time(),
				'created_by_id' => get_current_user_id(),
			];

			foreach ( $group_ids as $group_id ) {
				$data['group_id'] = $group_id;
				$link_data[]      = $data;
			}

		}

		return $link_data;
	}

	/**
	 * Prepare links groups data for bulk insert.
	 *
	 * @since 1.6.1
	 *
	 * @param array $group_ids
	 *
	 * @param array $link_ids
	 *
	 * @return array
	 *
	 */
	public function prepare_links_groups_data( $link_ids = [], $group_ids = [] ) {
		if ( empty( $link_ids ) || empty( $group_ids ) ) {
			return [];
		}

		$link_groups_data = [];

		foreach ( $link_ids as $link_id ) {
			$data = [
				'link_id'       => $link_id,
				'created_at'    => Helper::get_current_date_time(),
				'created_by_id' => get_current_user_id(),
			];

			foreach ( $group_ids as $group_id ) {
				$data['group_id']   = $group_id;
				$link_groups_data[] = $data;
			}
		}

		return $link_groups_data;
	}

	/**
	 * Get count of links based on group id
	 *
	 * @since 1.1.3
	 *
	 * @param null $group_id
	 *
	 * @return int|string|null
	 *
	 */
	public function count_by_group_id( $group_id = null ) {
		global $wpdb;

		if ( empty( $group_id ) ) {
			return 0;
		}

		$where = $wpdb->prepare( 'group_id = %d', $group_id );


		return $this->count( $where );
	}
}
