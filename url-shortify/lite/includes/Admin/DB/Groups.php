<?php

namespace KaizenCoders\URL_Shortify\Admin\DB;

use KaizenCoders\URL_Shortify\Helper;

class Groups extends Base_DB {
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
	 * ID Name map.
	 *
	 * @var
	 *
	 * @since 1.9.0
	 */
	private $id_name_map;

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

		$this->table_name = $wpdb->prefix . 'kc_us_groups';

		$this->version = '1.0';

		$this->primary_key = 'id';

		$this->id_name_map = $this->get_id_name_map();
	}

	/**
	 * Get columns and formats
	 *
	 * @since 1.1.3
	 */
	public function get_columns() {
		return [
			'id'            => '%d',
			'name'          => '%s',
			'description'   => '%s',
			'created_at'    => '%s',
			'created_by_id' => '%d',
			'updated_at'    => '%s',
			'updated_by_id' => '%d',
		];
	}

	/**
	 * Get default column values
	 *
	 * @since 1.1.3
	 */
	public function get_column_defaults() {
		return [
			'name'          => '',
			'description'   => '',
			'created_at'    => Helper::get_current_date_time(),
			'created_by_id' => null,
			'updated_at'    => null,
			'updated_by_id' => null,
		];
	}

	/**
	 * Prepare form data
	 *
	 * @since 1.1.3
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
			'name'        => Helper::get_data( $data, 'name', '', true ),
			'description' => sanitize_textarea_field( Helper::get_data( $data, 'description', '' ) ),
		];

		$current_user_id   = get_current_user_id();
		$current_date_time = Helper::get_current_date_time();
		// For Update, we want to update updated_at & updated_by_id field
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
	 * Delete Groups
	 *
	 * @since 1.1.3
	 *
	 * @param $ids
	 *
	 */
	public function delete( $ids = [] ) {
		if ( ! is_array( $ids ) ) {
			$ids = [ $ids ];
		}

		if ( is_array( $ids ) && count( $ids ) > 0 ) {

			foreach ( $ids as $id ) {
				parent::delete( absint( $id ) );

				/**
				 * Take necessary cleanup steps using this hook
				 *
				 * @since 1.1.3
				 */
				do_action( 'kc_us_group_deleted', $id );
			}
		}
	}

	/**
	 * Get group by id
	 *
	 * @since 1.1.7
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
	 * Insert/ Update Group
	 *
	 * @since 1.2.13
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
			do_action( 'kc_us_group_created', $saved );
		} else {
			do_action( 'kc_us_group_updated', $id );
		}

		return true;
	}

	/**
	 * Get ID Name Map.
	 *
	 * @return array
	 *
	 * @since 1.9.0
	 */
	public function get_all_id_name_map() {
		return $this->id_name_map;
	}
}
