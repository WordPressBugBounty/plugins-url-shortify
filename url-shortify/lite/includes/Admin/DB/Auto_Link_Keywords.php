<?php

namespace KaizenCoders\URL_Shortify\Admin\DB;

use KaizenCoders\URL_Shortify\Helper;

class Auto_Link_Keywords extends Base_DB {

	public function __construct() {
		global $wpdb;
		parent::__construct();

		$this->table_name  = $wpdb->prefix . 'kc_us_auto_link_keywords';
		$this->primary_key = 'id';
	}

	/**
	 * Get columns and formats.
	 *
	 * @since 1.13.1
	 */
	public function get_columns() {
		return [
			'id'             => '%d',
			'keyword'        => '%s',
			'link_id'        => '%d',
			'post_types'     => '%s',
			'open_new_tab'   => '%d',
			'nofollow'       => '%d',
			'case_sensitive' => '%d',
			'status'         => '%d',
			'created_at'     => '%s',
			'updated_at'     => '%s',
		];
	}

	/**
	 * Get default column values.
	 *
	 * @since 1.13.1
	 */
	public function get_column_defaults() {
		return [
			'keyword'        => '',
			'link_id'        => 0,
			'post_types'     => '',
			'open_new_tab'   => 0,
			'nofollow'       => 0,
			'case_sensitive' => 0,
			'status'         => 1,
			'created_at'     => Helper::get_current_date_time(),
			'updated_at'     => Helper::get_current_date_time(),
		];
	}

	/**
	 * Prepare form data for insert/update.
	 *
	 * @param array    $data
	 * @param int|null $id
	 *
	 * @return array
	 *
	 * @since 1.13.1
	 */
	public function prepare_form_data( $data = [], $id = null ) {
		$post_types = Helper::get_data( $data, 'post_types', [] );
		if ( is_array( $post_types ) ) {
			$post_types = implode( ',', array_map( 'sanitize_key', $post_types ) );
		} else {
			$post_types = sanitize_text_field( (string) $post_types );
		}

		// Sanitize each comma-separated keyword individually and rejoin.
		$raw_keywords = Helper::get_data( $data, 'keyword', '' );
		$keywords     = array_filter( array_map( 'sanitize_text_field', explode( ',', $raw_keywords ) ) );
		$keyword      = implode( ', ', $keywords );

		$form_data = [
			'keyword'        => $keyword,
			'link_id'        => absint( Helper::get_data( $data, 'link_id', 0 ) ),
			'post_types'     => $post_types,
			'open_new_tab'   => (int) (bool) Helper::get_data( $data, 'open_new_tab', 0 ),
			'nofollow'       => (int) (bool) Helper::get_data( $data, 'nofollow', 0 ),
			'case_sensitive' => (int) (bool) Helper::get_data( $data, 'case_sensitive', 0 ),
			'status'         => absint( Helper::get_data( $data, 'status', 1 ) ),
		];

		$current_date_time = Helper::get_current_date_time();

		if ( ! empty( $id ) ) {
			$form_data['updated_at'] = $current_date_time;
		} else {
			$form_data['created_at'] = $current_date_time;
			$form_data['updated_at'] = $current_date_time;
		}

		return $form_data;
	}

	/**
	 * Delete one or more rules.
	 *
	 * @param array|int $ids
	 *
	 * @since 1.13.1
	 */
	public function delete( $ids = [] ) {
		if ( ! is_array( $ids ) ) {
			$ids = [ $ids ];
		}

		foreach ( $ids as $id ) {
			parent::delete( absint( $id ) );
		}
	}

	/**
	 * Get all active keyword rules, ordered longest-keyword-first to prevent
	 * shorter keywords from matching inside longer ones.
	 *
	 * @return array
	 *
	 * @since 1.13.1
	 */
	public function get_active_rules() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			"SELECT * FROM {$this->table_name} WHERE status = 1 ORDER BY LENGTH(keyword) DESC",
			ARRAY_A
		);
	}
}
