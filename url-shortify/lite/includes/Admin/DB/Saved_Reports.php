<?php

namespace KaizenCoders\URL_Shortify\Admin\DB;

use KaizenCoders\URL_Shortify\Helper;

/**
 * Saved Smart Reports.
 *
 * A saved report is a named filter - which links, over what range, measured
 * how - rather than a snapshot of numbers. Opening one re-runs the query, so
 * a report saved in March still reads correctly in August.
 *
 * Reports are per-user: two people on the same site keep separate lists.
 *
 * @since 2.6.0
 */
class Saved_Reports extends Base_DB {

	public function __construct() {
		global $wpdb;
		parent::__construct();

		$this->table_name = $wpdb->prefix . 'kc_us_saved_reports';

		$this->primary_key = 'id';
	}

	/**
	 * Get columns and formats
	 *
	 * @since 2.6.0
	 */
	public function get_columns() {
		return [
			'id'          => '%d',
			'user_id'     => '%d',
			'name'        => '%s',
			'description' => '%s',
			'config'      => '%s',
			'created_at' => '%s',
			'updated_at' => '%s',
		];
	}

	/**
	 * Get default column values
	 *
	 * @since 2.6.0
	 */
	public function get_column_defaults() {
		return [
			'user_id'     => 0,
			'name'        => '',
			'description' => '',
			'config'      => '',
			'created_at' => Helper::get_current_date_time(),
			'updated_at' => Helper::get_current_date_time(),
		];
	}

	/**
	 * Reports belonging to one user, newest first.
	 *
	 * @param int $user_id
	 *
	 * @return array
	 *
	 * @since 2.6.0
	 */
	public function get_by_user( $user_id ) {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE user_id = %d ORDER BY id DESC",
				absint( $user_id )
			),
			ARRAY_A
		);

		if ( ! Helper::is_forechable( $rows ) ) {
			return [];
		}

		foreach ( $rows as $index => $row ) {
			$rows[ $index ]['config'] = $this->decode_config( $row['config'] );
		}

		return $rows;
	}

	/**
	 * One report, but only if it belongs to this user.
	 *
	 * Ownership is checked in the query rather than after the fetch, so a
	 * guessed id cannot load someone else's report.
	 *
	 * @param int $id
	 * @param int $user_id
	 *
	 * @return array|null
	 *
	 * @since 2.6.0
	 */
	public function get_owned( $id, $user_id ) {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE id = %d AND user_id = %d",
				absint( $id ),
				absint( $user_id )
			),
			ARRAY_A
		);

		if ( empty( $row ) ) {
			return null;
		}

		$row['config'] = $this->decode_config( $row['config'] );

		return $row;
	}

	/**
	 * Delete a report, but only the owner's own.
	 *
	 * @param int $id
	 * @param int $user_id
	 *
	 * @return bool
	 *
	 * @since 2.6.0
	 */
	public function delete_owned( $id, $user_id ) {
		global $wpdb;

		// Not delete_by_condition(): that reports success whenever the query did
		// not error, so deleting someone else's id would look like it worked.
		// $wpdb->delete() returns the row count, which tells the two apart.
		$deleted = $wpdb->delete(
			$this->table_name,
			[
				'id'      => absint( $id ),
				'user_id' => absint( $user_id ),
			],
			[ '%d', '%d' ]
		);

		return ! empty( $deleted );
	}

	/**
	 * Save a report under a name, replacing this user's report of the same name.
	 *
	 * Saving twice under one name is a rename of the filter, not a second entry
	 * in the list - which is what "save" means to someone tweaking a report.
	 *
	 * @param int    $user_id
	 * @param string $name
	 * @param array  $config
	 * @param string $description
	 *
	 * @return int Report id, or 0 on failure.
	 *
	 * @since 2.6.0
	 */
	public function save_for_user( $user_id, $name, $config, $description = '' ) {
		global $wpdb;

		$user_id = absint( $user_id );
		$name    = trim( wp_strip_all_tags( (string) $name ) );

		if ( empty( $user_id ) || '' === $name ) {
			return 0;
		}

		// varchar(191) - the column is indexed, so an over-long name would be
		// truncated by MySQL anyway. Cut it here so the stored value matches
		// what the uniqueness check compared.
		$name = function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 191 ) : substr( $name, 0, 191 );

		$encoded = wp_json_encode( $config );

		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$this->table_name} WHERE user_id = %d AND name = %s",
				$user_id,
				$name
			)
		);

		$description = $this->clean_description( $description );

		if ( ! empty( $existing_id ) ) {
			$this->update(
				absint( $existing_id ),
				[
					'description' => $description,
					'config'      => $encoded,
					'updated_at'  => Helper::get_current_date_time(),
				]
			);

			return absint( $existing_id );
		}

		return absint(
			$this->insert(
				[
					'user_id'     => $user_id,
					'name'        => $name,
					'description' => $description,
					'config'      => $encoded,
				]
			)
		);
	}

	/**
	 * Update one of this user's reports in place.
	 *
	 * Editing works on the id rather than the name, so renaming a report changes
	 * that report instead of forking a second one - which is what save_for_user()
	 * would do, since it matches on name.
	 *
	 * @param int    $id
	 * @param int    $user_id
	 * @param string $name
	 * @param array  $config
	 * @param string $description
	 *
	 * @return bool
	 *
	 * @since 2.6.0
	 */
	public function update_owned( $id, $user_id, $name, $config, $description = '' ) {
		global $wpdb;

		$id      = absint( $id );
		$user_id = absint( $user_id );
		$name    = trim( wp_strip_all_tags( (string) $name ) );

		if ( empty( $id ) || empty( $user_id ) || '' === $name ) {
			return false;
		}

		$name = function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 191 ) : substr( $name, 0, 191 );

		// Scoped by user_id so a guessed id cannot rewrite someone else's report.
		$updated = $wpdb->update(
			$this->table_name,
			[
				'name'        => $name,
				'description' => $this->clean_description( $description ),
				'config'      => wp_json_encode( $config ),
				'updated_at'  => Helper::get_current_date_time(),
			],
			[
				'id'      => $id,
				'user_id' => $user_id,
			],
			[ '%s', '%s', '%s', '%s' ],
			[ '%d', '%d' ]
		);

		// update() returns 0 when the row matched but nothing changed, which is
		// still a successful save from the caller's point of view.
		return false !== $updated && null !== $this->get_owned( $id, $user_id );
	}

	/**
	 * Tidy a report description.
	 *
	 * Plain text only - it is printed back into the list and the report header,
	 * and there is no reason for a report note to carry markup.
	 *
	 * @param string $description
	 *
	 * @return string
	 *
	 * @since 2.6.0
	 */
	protected function clean_description( $description ) {
		$description = trim( wp_strip_all_tags( (string) $description ) );

		return function_exists( 'mb_substr' ) ? mb_substr( $description, 0, 500 ) : substr( $description, 0, 500 );
	}

	/**
	 * Stored config back to an array.
	 *
	 * @param string $config
	 *
	 * @return array
	 *
	 * @since 2.6.0
	 */
	protected function decode_config( $config ) {
		if ( empty( $config ) ) {
			return [];
		}

		$decoded = json_decode( $config, true );

		return is_array( $decoded ) ? $decoded : [];
	}
}
