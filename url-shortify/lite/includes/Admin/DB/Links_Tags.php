<?php
namespace KaizenCoders\URL_Shortify\Admin\DB;

use KaizenCoders\URL_Shortify\Helper;

class Links_Tags extends Base_DB {
	public function __construct() {
		global $wpdb;
		parent::__construct();
		$this->table_name = $wpdb->prefix . 'kc_us_links_tags';
		$this->version = '1.0';
		$this->primary_key = 'id';
	}

	public function get_columns() {
		return [
			'id'            => '%d',
			'link_id'       => '%d',
			'tag_id'        => '%d',
			'created_by_id' => '%d',
			'created_at'    => '%s',
		];
	}

	public function get_column_defaults() {
		return [
			'link_id'       => null,
			'tag_id'        => null,
			'created_by_id' => null,
			'created_at'    => Helper::get_current_date_time(),
		];
	}

    // Logic to fetch all tags assigned to a specific link
	public function get_tag_ids_by_link_id( $link_id ) {
		$results = $this->get_columns_by_condition( [ 'tag_id' ], "link_id = " . absint($link_id) );
		return wp_list_pluck( $results, 'tag_id' );
	}

    // Logic to assign multiple tags to a link (used during save)
	public function add_link_to_tags( $link_id = null, $tag_ids = [] ) {
		if ( empty( $link_id ) ) return false;
		
		// 1. Remove old mapping first
		$this->delete_by( 'link_id', $link_id );

		if ( ! empty( $tag_ids ) ) {
			$data = [];
			foreach ( (array) $tag_ids as $tag_id ) {
				$data[] = [
					'link_id'       => $link_id,
					'tag_id'        => $tag_id,
					'created_at'    => Helper::get_current_date_time(),
					'created_by_id' => get_current_user_id(),
				];
			}
			return $this->bulk_insert( $data );
		}
		return true;
	}

	/**
	 * Get link ids based on tag ids
	 * @since 1.11.5
	 */
	public function get_link_ids_by_tag_ids( $tag_ids = [] ) {
		$data = [];
		if ( empty( $tag_ids ) ) {
			return $data;
		}

		$tag_ids_str = $this->prepare_for_in_query( $tag_ids );
		$where = "tag_id IN ($tag_ids_str)";

		$results = $this->get_columns_by_condition( [ 'link_id', 'tag_id' ], $where );

		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				$data[ $result['tag_id'] ][] = $result['link_id'];
			}
		}

		return $data;
	}

	/**
	 * Get tag ids based on link ids.
	 * Used for displaying tags in the main Links table.
	 * * @since 1.11.5
	 * @param array $link_ids
	 * @return array
	 */
	public function get_tag_ids_by_link_ids( $link_ids = [] ) {
		$data = [];
		if ( empty( $link_ids ) ) {
			return $data;
		}

		if ( is_scalar( $link_ids ) ) {
			$link_ids = [ $link_ids ];
		}

		$link_ids_str = $this->prepare_for_in_query( $link_ids );
		$where = "link_id IN ($link_ids_str)";

		$results = $this->get_columns_by_condition( [ 'link_id', 'tag_id' ], $where );

		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				$data[ $result['link_id'] ][] = $result['tag_id'];
			}
		}

		return $data;
	}

	/**
	 * Prepare data for bulk insertion into the links_tags table.
	 *
	 * @param array $link_ids
	 * @param array $tag_ids
	 * @return array
	 */
	public function prepare_links_tags_data( $link_ids, $tag_ids ) {
		$data = [];
		$now  = Helper::get_current_date_time();
		$user = get_current_user_id();

		foreach ( (array) $link_ids as $link_id ) {
			foreach ( (array) $tag_ids as $tag_id ) {
				$data[] = [
					'link_id'       => absint( $link_id ),
					'tag_id'        => absint( $tag_id ),
					'created_at'    => $now,
					'created_by_id' => $user,
				];
			}
		}

		return $data;
	}

	/**
	 * Map links to tags (Bulk Add or Bulk Move).
	 *
	 * @param array|int $link_ids Link IDs to be tagged.
	 * @param array|int $tag_ids  Tag IDs to assign.
	 * @param bool      $move     If true, deletes existing tag associations for these links first.
	 *
	 * @return bool|int Returns result of bulk_insert or false on failure.
	 * 
	 * @since 1.12.3
	 */
	public function map_links_and_tags( $link_ids = [], $tag_ids = [], $move = false ) {
		if ( empty( $link_ids ) || empty( $tag_ids ) ) {
			return false;
		}

		if ( ! is_array( $link_ids ) ) {
			$link_ids = [ absint( $link_ids ) ];
		}

		if ( ! is_array( $tag_ids ) ) {
			$tag_ids = [ absint( $tag_ids ) ];
		}

		if ( is_array( $link_ids ) && is_array( $tag_ids ) ) {
			$links_ids_str = $this->prepare_for_in_query( $link_ids );
			if ( $move ) {
				$where = "link_id IN ($links_ids_str)";

				$this->delete_by_condition( $where );
			} else {
				if ( count( $tag_ids ) > 0 ) {
					// If the tag is already exists? Delete it.
					foreach ( $tag_ids as $tag_id ) {
						$where = "tag_id = {$tag_id} AND link_id IN ($links_ids_str)";
						$this->delete_by_condition( $where );
					}

					$links_tags_data = $this->prepare_links_tags_data( $link_ids, $tag_ids );

					return $this->bulk_insert( $links_tags_data );
				}

			}

		}

		return true;
	}

	/**
	 * Delete link-tag associations based on link IDs.
	 *
	 * @param array|int $link_ids Link IDs for which to delete tag associations.
	 * @return bool Returns true on success, false on failure.
	 *
	 * @since 1.12.3
	 */
	public function delete_by_link_ids( $link_ids = [] ) {
		if ( empty( $link_ids ) ) {
			return false;
		}

		if ( ! is_array( $link_ids ) ) {
			$link_ids = [ absint( $link_ids ) ];
		}

		$link_ids_str = $this->prepare_for_in_query( $link_ids );
		$where        = "link_id IN ($link_ids_str)";

		return $this->delete_by_condition( $where );
	}
}