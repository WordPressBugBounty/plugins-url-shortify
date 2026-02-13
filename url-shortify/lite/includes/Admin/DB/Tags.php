<?php
namespace KaizenCoders\URL_Shortify\Admin\DB;

use KaizenCoders\URL_Shortify\Helper;

class Tags extends Base_DB {
	public function __construct() {
		global $wpdb;
		parent::__construct();
		$this->table_name = $wpdb->prefix . 'kc_us_tags';
		$this->version = '1.0';
		$this->primary_key = 'id';
	}

	public function get_columns() {
		return [
			'id'            => '%d',
			'name'          => '%s',
			'description'   => '%s',
			'color'         => '%s',
			'created_at'    => '%s',
			'created_by_id' => '%d',
			'updated_at'    => '%s',
			'updated_by_id' => '%d',
		];
	}

	public function get_column_defaults() {
		return [
			'name'          => '',
			'description'   => '',
			'color'         => '#6366f1',
			'created_at'    => Helper::get_current_date_time(),
			'created_by_id' => null,
			'updated_at'    => null,
			'updated_by_id' => null,
		];
	}

    // This handles data before saving to the DB
	public function prepare_form_data( $data = [], $id = null ) {
		$form_data = [
			'name'        => Helper::get_data( $data, 'name', '', true ),
			'description' => sanitize_textarea_field( Helper::get_data( $data, 'description', '' ) ),
			'color'       => sanitize_hex_color( Helper::get_data( $data, 'color', '#6366f1' ) ),
		];

		$current_user_id   = get_current_user_id();
		$current_date_time = Helper::get_current_date_time();

		if ( ! empty( $id ) ) {
			$form_data['updated_at']    = $current_date_time;
			$form_data['updated_by_id'] = $current_user_id;
		} else {
			$form_data['created_at']    = $current_date_time;
			$form_data['created_by_id'] = $current_user_id;
		}

		return $form_data;
	}

	public function get_all_id_name_map() {
		$results = $this->get_all();
		$map = [];
		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				$map[ $row['id'] ] = $row['name'];
			}
		}
		return $map;
	}

	public function get_all_id_object_map() {
		$results = $this->get_all();
		$map = [];
		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				$map[ $row['id'] ] = [
					'name'  => $row['name'],
					'color' => $row['color'],
				];
			}
		}
		return $map;
	}
}