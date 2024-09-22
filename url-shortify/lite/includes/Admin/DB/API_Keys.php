<?php

namespace KaizenCoders\URL_Shortify\Admin\DB;

use KaizenCoders\URL_Shortify\Helper;

class API_Keys extends Base_DB {
	/**
	 * Table Name
	 *
	 * @since 1.3.8
	 * @var string
	 *
	 */
	public $table_name;

	/**
	 * Table Version
	 *
	 * @since 1.3.8
	 * @var string
	 *
	 */
	public $version;

	/**
	 * Primary key
	 *
	 * @since 1.3.8
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

		$this->table_name = $wpdb->prefix . 'kc_us_api_keys';

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
			'id'              => '%d',
			'user_id'         => '%d',
			'description'     => '%s',
			'permissions'     => '%s',
			'consumer_key'    => '%s',
			'consumer_secret' => '%s',
			'truncated_key'   => '%s',
			'last_access'     => '%s',
			'created_at'      => '%s',
			'created_by_id'   => '%d',
			'updated_at'      => '%s',
			'updated_by_id'   => '%d',
		];
	}

	/**
	 * Get default column values
	 *
	 * @since 1.9.5
	 */
	public function get_column_defaults() {
		return [
			'user_id'         => '',
			'description'     => null,
			'permissions'     => 'read',
			'consumer_key'    => '',
			'consumer_secret' => '',
			'truncated_key'   => '',
			'last_access'     => NULL,
			'created_at'      => Helper::get_current_date_time(),
			'created_by_id'   => null,
			'updated_at'      => null,
			'updated_by_id'   => null,
		];
	}

	/**
	 * Prepare form data
	 *
	 * @since 1.9.5
	 *
	 * @param null  $id
	 *
	 * @param array $data
	 *
	 * @return array
	 *
	 */
	public function prepare_form_data( $form_data = [], $id = null ) {
		$current_user_id   = get_current_user_id();
		$current_date_time = Helper::get_current_date_time();

		// For Update, we want to update `updated_at` & `updated_by_id` field.
		if ( ! empty( $id ) ) {
			$form_data['updated_at']    = $current_date_time;
			$form_data['updated_by_id'] = $current_user_id;
		} else {
			// For Insert, we don't need to add `updated_at` & `updated_by_id` field.
			// We just need to add `created_at` & `created_by_id` field..
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

		$delete = false;

		if ( is_array( $ids ) && count( $ids ) > 0 ) {

			foreach ( $ids as $id ) {
				$delete = parent::delete( absint( $id ) );

				/**
				 * Take necessary cleanup steps using this hook
				 *
				 * @since 1.3.8
				 */
				do_action( 'kc_us_api_key_deleted', $id );
			}
		}

		return $delete;
	}

	/**
	 * Get dromain by id
	 *
	 * @since 1.3.8
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
	 * Get ID Host Map
	 *
	 * @since 1.4.7
	 *
	 * @param string $where
	 *
	 * @return array
	 *
	 */
	public function get_id_description_map( $where = '' ) {
		return $this->get_columns_map( 'id', 'description', $where );
	}

	/**
	 * Insert/ Update Domain
	 *
	 * @since 1.3.8
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
			do_action( 'kc_us_api_key_created', $saved );
		} else {
			do_action( 'kc_us_api_key_updated', $id );
		}

		return $saved;
	}
}
