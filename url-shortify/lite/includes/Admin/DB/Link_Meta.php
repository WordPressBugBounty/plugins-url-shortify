<?php

namespace KaizenCoders\URL_Shortify\Admin\DB;

use KaizenCoders\URL_Shortify\Helper;

class Link_Meta extends Base_DB {
	/**
	 * Table Name
	 *
	 * @since 1.0.0
	 * @var string
	 *
	 */
	public $table_name;

	/**
	 * Table Version
	 *
	 * @since 1.0.0
	 * @var string
	 *
	 */
	public $version;

	/**
	 * Primary key
	 *
	 * @since 1.0.0
	 * @var string
	 *
	 */
	public $primary_key;

	/**
	 * Initialize
	 *
	 * constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		global $wpdb;

		parent::__construct();

		$this->table_name = $wpdb->prefix . 'kc_us_linkmeta';

		$this->version = '1.0';

		$this->primary_key = 'meta_id';
	}

	/**
	 * Get columns and formats
	 *
	 * @since 1.0.0
	 */
	public function get_columns() {
		return [
			'meta_id'    => '%d',
			'link_id'    => '%d',
			'meta_key'   => '%s',
			'meta_value' => '%s',
		];
	}

	/**
	 * Get default column values
	 *
	 * @since 1.0.0
	 */
	public function get_column_defaults() {
		return [
			'link_id'    => 0,
			'meta_key'   => '',
			'meta_value' => '',
		];
	}


}
