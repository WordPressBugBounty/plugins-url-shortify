<?php

namespace KaizenCoders\URL_Shortify\Admin\DB;

use KaizenCoders\URL_Shortify\Helper;

class Clicks_Rotations extends Base_DB {
	/**
	 * Table Name
	 *
	 * @since 1.9.1
	 * @var string
	 *
	 */
	public $table_name;

	/**
	 * Table Version
	 *
	 * @since 1.9.1
	 * @var string
	 *
	 */
	public $version;

	/**
	 * Primary key
	 *
	 * @since 1.9.1
	 * @var string
	 *
	 */
	public $primary_key;

	/**
	 * Initialize
	 *
	 * constructor.
	 *
	 * @since 1.9.1
	 */
	public function __construct() {
		global $wpdb;

		parent::__construct();

		$this->table_name = $wpdb->prefix . 'kc_us_clicks_rotations';

		$this->version = '1.0';

		$this->primary_key = 'id';
	}

	/**
	 * Get columns and formats
	 *
	 * @since 1.9.1
	 */
	public function get_columns() {
		return [
			'id'       => '%d',
			'click_id' => '%d',
			'link_id'  => '%d',
			'url'      => '%s',
		];
	}

	/**
	 * Get default column values
	 *
	 * @since 1.9.1
	 */
	public function get_column_defaults() {
		return [
			'click_id' => null,
			'link_id'  => null,
			'url'      => null,
		];
	}

	/**
	 * Get group ids by link ids
	 *
	 * @since 1.9.1
	 *
	 * @param array $link_ids
	 *
	 * @return array
	 */
	public function get_click_ids_by_link_ids( $link_ids = [] ) {
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

		$results = $this->get_columns_by_condition( [ 'click_id', 'link_id' ], $where );

		if ( Helper::is_forechable( $results ) ) {
			foreach ( $results as $result ) {
				$data[ $result['link_id'] ][] = $result['click_id'];
			}
		}

		return $data;
	}

	/**
	 * Get clicks ids based on link id
	 *
	 * @since 1.3.7
	 *
	 * @param $link_id
	 *
	 * @return array|\KaizenCoders\URL_Shortify\data|string
	 *
	 */
	public function get_click_ids_by_link_id( $link_id ) {
		$click_ids = $this->get_click_ids_by_link_ids( $link_id );

		return Helper::get_data( $click_ids, $link_id, [] );
	}

	/**
	 * Delete link clicks.
	 *
	 * @since 1.9.1
	 *
	 * @param null $link_id
	 *
	 * @return bool
	 *
	 */
	public function delete_clicks_by_link_id( $link_id = null ) {
		if ( empty( $link_id ) ) {
			return false;
		}

		return $this->delete_by( 'link_id', $link_id );
	}
}
