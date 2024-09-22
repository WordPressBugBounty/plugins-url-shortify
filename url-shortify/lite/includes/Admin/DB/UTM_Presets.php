<?php

namespace KaizenCoders\URL_Shortify\Admin\DB;

use KaizenCoders\URL_Shortify\Helper;

class UTM_Presets extends Base_DB {
	/**
	 * Table Name
	 *
	 * @since 1.5.12
	 * @var string
	 *
	 */
	public $table_name;

	/**
	 * Table Version
	 *
	 * @since 1.5.12
	 * @var string
	 *
	 */
	public $version;

	/**
	 * Primary key
	 *
	 * @since 1.5.12
	 * @var string
	 *
	 */
	public $primary_key;

	/**
	 * Initialize
	 *
	 * constructor.
	 *
	 * @since 1.5.12
	 */
	public function __construct() {
		global $wpdb;

		parent::__construct();

		$this->table_name = $wpdb->prefix . 'kc_us_utm_presets';

		$this->version = '1.0';

		$this->primary_key = 'id';
	}

	/**
	 * Get columns and formats
	 *
	 * @since 1.5.12
	 */
	public function get_columns() {
		return [
			'id'            => '%d',
			'name'          => '%s',
			'description'   => '%s',
			'utm_id'        => '%s',
			'utm_source'    => '%s',
			'utm_medium'    => '%s',
			'utm_campaign'  => '%s',
			'utm_term'      => '%s',
			'utm_content'   => '%s',
			'created_at'    => '%s',
			'created_by_id' => '%d',
			'updated_at'    => '%s',
			'updated_by_id' => '%d',
		];
	}

	/**
	 * Get default column values
	 *
	 * @since 1.5.12
	 */
	public function get_column_defaults() {
		return [
			'name'          => '',
			'description'   => '',
			'utm_id'        => '',
			'utm_source'    => '',
			'utm_medium'    => '',
			'utm_campaign'  => '',
			'utm_term'      => '',
			'utm_content'   => '',
			'created_at'    => Helper::get_current_date_time(),
			'created_by_id' => null,
			'updated_at'    => null,
			'updated_by_id' => null,
		];
	}

	/**
	 * Prepare form data
	 *
	 * @since 1.3.8
	 *
	 * @param null  $id
	 *
	 * @param array $data
	 *
	 * @return array
	 *
	 */
	public function prepare_form_data( $data = [], $id = null ) {
		$form_data = [
			'name'         => Helper::get_data( $data, 'name', '', true ),
			'description'  => Helper::get_data( $data, 'description', '', true ),
			'utm_id'       => Helper::get_data( $data, 'utm_id', '', true ),
			'utm_source'   => Helper::get_data( $data, 'utm_source', '', true ),
			'utm_medium'   => Helper::get_data( $data, 'utm_medium', '', true ),
			'utm_campaign' => Helper::get_data( $data, 'utm_campaign', '', true ),
			'utm_term'     => Helper::get_data( $data, 'utm_term', '', true ),
			'utm_content'  => Helper::get_data( $data, 'utm_content', '', true ),
		];

		$current_user_id   = get_current_user_id();
		$current_date_time = Helper::get_current_date_time();
		// For Updaate, we want to update updated_at & updated_by_id field
		if ( ! empty( $id ) ) {
			$form_data['updated_at']    = $current_date_time;
			$form_data['updated_by_id'] = $current_user_id;
		} else {
			// For Insert, we don't need to add updated_at & updated_by_id field
			// We just need to add created_at & created_by_id field.
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

				do_action( 'kc_us_utm_presets_deleted', $id );
			}
		}

	}

	/**
	 * Get UTM Presets by id
	 *
	 * @since 1.5.12
	 *
	 * @param int $id
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
	 * @since 1.5.12
	 *
	 * @param null  $id
	 *
	 * @param array $data
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
			do_action( 'kc_us_utm_presets_created', $saved );
		} else {
			do_action( 'kc_us_utm_presets_updated', $id );
		}

		return true;
	}
}
