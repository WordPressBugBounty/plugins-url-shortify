<?php

namespace KaizenCoders\URL_Shortify\Admin\DB;

use KaizenCoders\URL_Shortify\Helper;

class Tracking_Pixels extends Base_DB {
	/**
	 * Table Name
	 *
	 * @since 1.8.9
	 * @var string
	 *
	 */
	public $table_name;

	/**
	 * Table Version
	 *
	 * @since 1.8.9
	 * @var string
	 *
	 */
	public $version;

	/**
	 * Primary key
	 *
	 * @since 1.8.9
	 * @var string
	 *
	 */
	public $primary_key;

	/**
	 * Initialize
	 *
	 * constructor.
	 *
	 * @since 1.8.9
	 */
	public function __construct() {
		global $wpdb;

		parent::__construct();

		$this->table_name = $wpdb->prefix . 'kc_us_tracking_pixels';

		$this->version = '1.0';

		$this->primary_key = 'id';
	}

	/**
	 * Get columns and formats
	 *
	 * @since 1.8.9
	 */
	public function get_columns() {
		return [
			'id'            => '%d',
			'name'          => '%s',
			'type'          => '%s',
			'pixel_id'      => '%s',
			'head_code'     => '%s',
			'body_code'     => '%s',
			'created_by_id' => '%d',
			'created_at'    => '%s',
			'updated_by_id' => '%d',
			'updated_at'    => '%s',
		];
	}

	/**
	 * Get default column values
	 *
	 * @since 1.8.9
	 */
	public function get_column_defaults() {
		return [
			'name'          => '',
			'type'          => '',
			'pixel_id'      => '',
			'head_code'     => '',
			'body_code'     => '',
			'created_by_id' => null,
			'created_at'    => Helper::get_current_date_time(),
			'updated_by_id' => null,
			'updated_at'    => null,
		];
	}

	/**
	 * Prepare form data
	 *
	 * @since 1.8.0
	 *
	 * @param  null  $id
	 *
	 * @param  array  $data
	 *
	 * @return array
	 *
	 */
	public function prepare_form_data( $data = [], $id = null ) {
		$form_data = [
			'name'      => Helper::get_data( $data, 'name', '', true ),
			'type'      => Helper::get_data( $data, 'type', '', true ),
			'pixel_id'  => Helper::get_data( $data, 'pixel_id', '', true ),
			'head_code' => Helper::get_data( $data, 'head_code', '', true ),
			'body_code' => Helper::get_data( $data, 'body_code', '', true ),
		];

		$current_user_id   = get_current_user_id();
		$current_date_time = Helper::get_current_date_time();

		// For Update, we want to update updated_at & updated_by_id field
		if ( ! empty( $id ) ) {
			$form_data['updated_at']    = $current_date_time;
			$form_data['updated_by_id'] = $current_user_id;
		} else {
			$form_data['created_at']    = $current_date_time;
			$form_data['created_by_id'] = $current_user_id;
		}

		return $form_data;
	}

	/**
	 * Delete Domains
	 *
	 * @since 1.3.8
	 *
	 * @param $ids array
	 *
	 */
	public function delete( $ids = [] ) {
		if ( ! is_array( $ids ) ) {
			$ids = [ $ids ];
		}

		if ( is_array( $ids ) && count( $ids ) > 0 ) {

			foreach ( $ids as $id ) {
				parent::delete( absint( $id ) );

				do_action( 'kc_us_tracking_pixel_deleted', $id );
			}
		}

	}

	/**
	 * Get UTM Presets by id
	 *
	 * @since 1.8.9
	 *
	 * @param  int  $id
	 *
	 * @return array|object|void|null
	 *
	 */
	public function get_by_id( $id = 0 ) {
		if ( empty( $id ) ) {
			return [];
		}

		return $this->get_by( 'id', $id );
	}

	/**
	 * Insert/ Update UTM Presets
	 *
	 * @since 1.8.9
	 *
	 * @param  null  $id
	 *
	 * @param  array  $data
	 *
	 * @return bool|int|void
	 *
	 */
	public function save( $data = [], $id = null ) {
		$saved = parent::save( $data, $id );

		if ( ! $saved ) {
			return false;
		}

		if ( is_null( $id ) ) {
			do_action( 'kc_us_tracking_pixel_created', $saved );
		} else {
			do_action( 'kc_us_tracking_pixel_updated', $id );
		}

		return true;
	}
}
