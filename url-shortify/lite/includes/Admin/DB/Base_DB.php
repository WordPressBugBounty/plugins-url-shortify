<?php

namespace KaizenCoders\URL_Shortify\Admin\DB;

use KaizenCoders\URL_Shortify\Cache;

/**
 * Base_DB base class
 *
 * @since 1.0.0
 */
abstract class Base_DB {
	/**
	 * @since 1.0.0
	 * @var $table_name
	 *
	 */
	public $table_name;

	/**
	 * @since 1.0.0
	 * @var $version
	 *
	 */
	public $version;

	/**
	 * @since 1.0.0
	 * @var $primary_key
	 *
	 */
	public $primary_key;

	/**
	 * Base_DB constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Get default columns
	 *
	 * @since 1.0.0
	 * @return array
	 *
	 */
	public function get_columns() {
		return [];
	}

	/**
	 * @since 1.0.0
	 * @return array
	 *
	 */
	public function get_column_defaults() {
		return [];
	}

	/**
	 * Get by Query
	 *
	 * @since 1.4.0
	 *
	 * @param string $output
	 *
	 * @param        $query
	 *
	 * @return array|object|null
	 *
	 */
	public function get_by_query( $query, $output = ARRAY_A ) {
		global $wpdb;

		return $wpdb->get_results( $query, $output );
	}

	/**
	 * Retrieve a row by the primary key
	 *
	 * @since 1.0.0
	 *
	 * @param string $output
	 *
	 * @param        $row_id
	 *
	 * @return array|object|void|null
	 *
	 */
	public function get( $row_id, $output = ARRAY_A ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $this->primary_key = %s LIMIT 1;", $row_id ), $output );
	}

	/**
	 * Retrieve a row by a specific column / value
	 *
	 * @since 1.0.0
	 *
	 * @param        $row_id
	 * @param string $output
	 *
	 * @param        $column
	 *
	 * @return array|object|void|null
	 *
	 */
	public function get_by( $column, $row_id, $output = ARRAY_A, $case_sensitive = false ) {
		global $wpdb;
		$column = esc_sql( $column );

		if ( $case_sensitive ) {
			return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE BINARY $column = %s LIMIT 1;", $row_id ), $output );
		}

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $column = %s LIMIT 1;", $row_id ), $output );
	}

	/**
	 * Get rows by conditions
	 *
	 * @since 1.0.0
	 *
	 * @param string $output
	 *
	 * @param string $where
	 *
	 * @return array
	 *
	 */
	public function get_by_conditions( $where = '', $output = ARRAY_A ) {
		global $wpdb;

		$query = "SELECT * FROM $this->table_name";

		if ( ! empty( $where ) ) {
			$query .= " WHERE $where";
		}

		return $wpdb->get_results( $query, $output );
	}

	/**
	 * Get all data from table without any condition
	 *
	 * @since 1.0.0
	 * @return array|object|null
	 *
	 */
	public function get_all() {
		return $this->get_by_conditions();
	}

	/**
	 * Retrieve a specific column's value by the primary key
	 *
	 * @since 1.0.0
	 *
	 * @param $row_id
	 *
	 * @param $column
	 *
	 * @return null|string|array
	 *
	 */
	public function get_column( $column, $row_id = 0 ) {
		global $wpdb;

		$column = esc_sql( $column );

		if ( $row_id ) {
			return $wpdb->get_var( $wpdb->prepare( "SELECT $column FROM $this->table_name WHERE $this->primary_key = %s LIMIT 1;", $row_id ) );
		} else {
			return $wpdb->get_col( "SELECT $column FROM $this->table_name" );
		}
	}

	/**
	 * Retrieve a specific column's value by the the specified column / value
	 *
	 * @since 1.0.0
	 *
	 * @param      $column_where
	 * @param      $column_value
	 * @param bool $only_one
	 *
	 * @param      $column
	 *
	 * @return array|string|null
	 *
	 */
	public function get_column_by( $column, $column_where, $column_value, $only_one = true ) {
		global $wpdb;

		$column_where = esc_sql( $column_where );
		$column       = esc_sql( $column );

		if ( $only_one ) {
			return $wpdb->get_var( $wpdb->prepare( "SELECT $column FROM $this->table_name WHERE $column_where = %s LIMIT 1;", $column_value ) );
		} else {
			return $wpdb->get_col( $wpdb->prepare( "SELECT $column FROM $this->table_name WHERE $column_where = %s;", $column_value ) );
		}
	}

	/**
	 * Get column based on where condition
	 *
	 * @since 1.0.0
	 *
	 * @param string $where
	 *
	 * @param        $column
	 *
	 * @return array
	 *
	 */
	public function get_column_by_condition( $column, $where = '' ) {
		global $wpdb;

		$column = esc_sql( $column );

		if ( ! empty( $where ) ) {
			return $wpdb->get_col( "SELECT $column FROM $this->table_name WHERE $where" );
		} else {
			return $wpdb->get_col( "SELECT $column FROM $this->table_name" );
		}
	}

	/**
	 * Select few columns based on condition
	 *
	 * @since 1.0.0
	 *
	 * @param string $where
	 *
	 * @param array  $columns
	 *
	 * @return array|object|null
	 *
	 */
	public function get_columns_by_condition( $columns = [], $where = '', $output = ARRAY_A ) {
		global $wpdb;

		if ( ! is_array( $columns ) ) {
			return [];
		}

		$columns = esc_sql( $columns );

		$columns = implode( ', ', $columns );

		$query = "SELECT $columns FROM $this->table_name";
		if ( ! empty( $where ) ) {
			$query .= " WHERE $where";
		}

		return $wpdb->get_results( $query, $output );
	}

	/**
	 * Insert a new row
	 *
	 * @since 1.0.0
	 *
	 * @param string $type
	 *
	 * @param        $data
	 *
	 * @return int
	 *
	 */
	public function insert( $data, $type = '' ) {
		global $wpdb;

		// Set default values
		$data = wp_parse_args( $data, $this->get_column_defaults() );

		do_action( 'kc_us_pre_insert_' . $type, $data );

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		$wpdb->insert( $this->table_name, $data, $column_formats );
		$wpdb_insert_id = $wpdb->insert_id;

		do_action( 'kc_us_post_insert_' . $type, $wpdb_insert_id, $data );

		return $wpdb_insert_id;
	}

	/**
	 * Update a specific row
	 *
	 * @since 1.0.0
	 *
	 * @param array  $data
	 * @param string $where
	 *
	 * @param        $row_id
	 *
	 * @return bool
	 *
	 */
	public function update( $row_id, $data = [], $where = '' ) {
		global $wpdb;

		// Row ID must be positive integer
		$row_id = absint( $row_id );

		if ( empty( $row_id ) ) {
			return false;
		}

		if ( empty( $where ) ) {
			$where = $this->primary_key;
		}

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		if ( false === $wpdb->update( $this->table_name, $data, [ $where => $row_id ], $column_formats ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Delete a row by primary key
	 *
	 * @since 1.0.0
	 *
	 * @param int $row_id
	 *
	 * @return bool
	 *
	 */
	public function delete( $row_id = 0 ) {
		global $wpdb;

		// Row ID must be positive integer
		$row_id = absint( $row_id );

		if ( empty( $row_id ) ) {
			return false;
		}

		$where = $wpdb->prepare( "$this->primary_key = %d", $row_id );

		if ( false === $this->delete_by_condition( $where ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Delete all rows.
	 *
	 * @since 1.8.0
	 * @return bool
	 */
	public function delete_all() {
		global $wpdb;

		if ( false === $wpdb->query( "DELETE FROM $this->table_name" ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Delete by column
	 *
	 * @since 1.1.0
	 *
	 * @param $value
	 *
	 * @param $column
	 *
	 * @return bool
	 *
	 */
	public function delete_by( $column, $value ) {
		global $wpdb;

		if ( empty( $column ) ) {
			return false;
		}

		$where = $wpdb->prepare( "$column = %s", $value );

		if ( false === $this->delete_by_condition( $where ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Delete rows by primary key
	 *
	 * @since 1.0.0
	 *
	 * @param array $row_ids
	 *
	 * @return bool
	 *
	 */
	public function bulk_delete( $row_ids = [] ) {
		if ( ! is_array( $row_ids ) && empty( $row_ids ) ) {
			return false;
		}

		$row_ids_str = $this->prepare_for_in_query( $row_ids );

		$where = "$this->primary_key IN( $row_ids_str )";

		if ( false === $this->delete_by_condition( $where ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Delete records based on $where
	 *
	 * @since 1.0.0
	 *
	 * @param string $where
	 *
	 * @return bool
	 *
	 */
	public function delete_by_condition( $where = '' ) {
		global $wpdb;

		if ( empty( $where ) ) {
			return false;
		}

		if ( false === $wpdb->query( "DELETE FROM $this->table_name WHERE $where" ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Update specific column by condition.
	 *
	 * @since 1.8.7
	 *
	 * @param $column
	 * @param $value
	 * @param $where
	 *
	 * @return bool
	 */
	public function update_by_condition( $column, $value, $where ) {
		global $wpdb;

		if ( empty( $where ) ) {
			return false;
		}

		$query = $wpdb->prepare( "UPDATE $this->table_name SET $column = %s WHERE $where", $value );

		if ( false === $wpdb->query( $query ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check whether table exists or not
	 *
	 * @since 1.0.0
	 *
	 * @param $table
	 *
	 * @return bool
	 *
	 */
	public function table_exists( $table ) {
		global $wpdb;

		$table = sanitize_text_field( $table );

		return $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE '%s'", $table ) ) === $table;
	}

	/**
	 * Check whether table installed
	 *
	 * @since 1.0.0
	 * @return bool
	 *
	 */
	public function installed() {
		return $this->table_exists( $this->table_name );
	}

	/**
	 * Get total count
	 *
	 * @since 1.0.0
	 * @return string|null
	 *
	 */
	public function count( $where = '' ) {
		global $wpdb;

		$query = "SELECT count(*) FROM $this->table_name";

		if ( ! empty( $where ) ) {
			$query .= " WHERE $where";
		}

		return $wpdb->get_var( $query );
	}

	/**
	 * Insert data into bulk
	 *
	 * @since 1.0.0
	 *
	 * @param int    $length
	 * @param string $type
	 *
	 * @param        $values
	 */
	public function bulk_insert( $values, $length = 100 ) {
		global $wpdb;

		if ( ! is_array( $values ) ) {
			return false;
		}

		// Get the first value from an array to check data structure
		$first_value = array_slice( $values, 0, 1 );

		$data = array_shift( $first_value );

		// Set default values
		$data = wp_parse_args( $data, $this->get_column_defaults() );

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Remove primary key as we don't require while inserting data
		unset( $column_formats[ $this->primary_key ] );

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data = wp_parse_args( $data, $this->get_column_defaults() );

		$data_keys = array_keys( $data );

		$fields = array_keys( array_merge( array_flip( $data_keys ), $column_formats ) );

		// Convert Batches into smaller chunk
		$batches = array_chunk( $values, $length );

		foreach ( $batches as $key => $batch ) {

			$place_holders = $final_values = [];

			foreach ( $batch as $value ) {

				$formats = [];
				foreach ( $column_formats as $column => $format ) {
					$final_values[] = isset( $value[ $column ] ) ? $value[ $column ] : $data[ $column ]; // set default if we don't have
					$formats[]      = $format;
				}

				$place_holders[] = '( ' . implode( ', ', $formats ) . ' )';
				$fields_str      = '`' . implode( '`, `', array_keys( $column_formats ) ) . '`';
			}

			$query = "INSERT INTO $this->table_name ({$fields_str}) VALUES ";
			$query .= implode( ', ', $place_holders );
			$sql   = $wpdb->prepare( $query, $final_values );

			if ( ! $wpdb->query( $sql ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param $table_name
	 * @param $fields
	 * @param $place_holders
	 * @param $values
	 *
	 * @return bool
	 *
	 * @sicne 1.0.0
	 */
	public static function do_insert( $table_name, $fields, $place_holders, $values ) {
		global $wpdb;

		$fields_str = '`' . implode( '`, `', $fields ) . '`';

		$query = "INSERT INTO $table_name ({$fields_str}) VALUES ";
		$query .= implode( ', ', $place_holders );
		$sql   = $wpdb->prepare( $query, $values );

		if ( $wpdb->query( $sql ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get ID, Name Map
	 *
	 * @since 1.0.0
	 *
	 * @param string $where
	 *
	 * @return array
	 *
	 */
	public function get_id_name_map( $where = '' ) {
		return $this->get_columns_map( $this->primary_key, 'name', $where );
	}

	/**
	 * Get map of two columns
	 *
	 * e.g array($column_1 => $column_2)
	 *
	 * @since 1.0.0
	 *
	 * @param string $column_2
	 * @param string $where
	 *
	 * @param string $column_1
	 *
	 * @return array
	 *
	 */
	public function get_columns_map( $column_1 = '', $column_2 = '', $where = '' ) {
		if ( empty( $column_1 ) || empty( $column_2 ) ) {
			return [];
		}

		$columns = [ $column_1, $column_2 ];

		$results = $this->get_columns_by_condition( $columns, $where );

		$map = [];
		if ( count( $results ) > 0 ) {
			foreach ( $results as $result ) {
				$map[ $result[ $column_1 ] ] = $result[ $column_2 ];
			}
		}

		return $map;
	}

	public static function prepare_data( $data, $column_formats, $column_defaults, $insert = true ) {
		// Set default values
		if ( $insert ) {
			$data = wp_parse_args( $data, $column_defaults );
		}

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		return [
			'data'           => $data,
			'column_formats' => $column_formats,
		];

	}

	/**
	 * Prepare string for SQL IN query
	 *
	 * @since 1.0.0
	 *
	 * @param array $array
	 *
	 * @return string
	 *
	 */
	public function prepare_for_in_query( $array = [] ) {
		$array = esc_sql( $array );

		if ( is_array( $array ) && count( $array ) > 0 ) {
			return "'" . implode( "', '", $array ) . "'";
		}

		return '';
	}

	/**
	 * Save Data
	 *
	 * @since 1.0.2
	 *
	 * @param null  $id
	 *
	 * @param array $data
	 *
	 * @return bool|int
	 *
	 */
	public function save( $data = [], $id = null ) {
		if ( is_null( $id ) ) {
			return $this->insert( $data );
		} else {
			return $this->update( $id, $data );
		}
	}

	/**
	 * Concert To Associative Array
	 *
	 * @since 1.2.1
	 *
	 * @param        $key
	 * @param        $value
	 * @param string $null_label
	 *
	 * @param        $results
	 *
	 * @return array
	 *
	 */
	public function convert_to_associative_array( $results, $key, $value, $null_label = 'unknown' ) {
		if ( empty( $results ) ) {
			return [];
		}

		$final_array = [];
		foreach ( $results as $result ) {
			if ( ! empty( $result[ $key ] ) ) {
				$final_array[ $result[ $key ] ] = $result[ $value ];
			} else {
				$final_array[ $null_label ] = $result[ $value ];
			}
		}

		return $final_array;
	}

	/**
	 * Get data from cache
	 *
	 * @since 1.4.7
	 *
	 * @param $found
	 *
	 * @param $key
	 *
	 * @return false|mixed
	 *
	 */
	public function get_cache( $key, &$found ) {
		return Cache::get( $key, 'query', false, $found );
	}

	/**
	 * Set data into cache
	 *
	 * @since 1.4.7
	 *
	 * @param string $value
	 *
	 * @param string $key
	 */
	public function set_cache( $key = '', $value = '' ) {
		if ( ! empty( $key ) ) {
			Cache::set( $key, $value, 'query' );
		}
	}

	/**
	 * Check if cache exists?
	 *
	 * @since 1.4.7
	 *
	 * @param string $key
	 *
	 * @return bool
	 *
	 */
	public function cache_exists( $key = '' ) {
		return Cache::exists( $key, 'query' );
	}

	/**
	 * Generate cache key
	 *
	 * @since 1.4.7
	 *
	 * @param string $str
	 *
	 * @return string
	 *
	 */
	public function generate_cache_key( $str = '' ) {
		return Cache::generate_key( $str );
	}
}
