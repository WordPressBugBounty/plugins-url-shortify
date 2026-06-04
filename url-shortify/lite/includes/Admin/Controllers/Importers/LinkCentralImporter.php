<?php

namespace KaizenCoders\URL_Shortify\Admin\Controllers\Importers;

use KaizenCoders\URL_Shortify\Helper;

/**
 * Import short links from the Link Central plugin.
 *
 * Differs from the other one-click importers in that the source data is
 * spread across WordPress posts + postmeta (rather than a custom table) and
 * the migration is driven by a UI that pages through batches — so this
 * importer exposes both:
 *
 *   - run()       : standard BaseImporter one-shot import (no pagination)
 *   - run_batch() : paged variant that returns a structured progress array
 *
 * @since 2.3.1
 */
class LinkCentralImporter extends BaseImporter {

	protected $source_key = '';

	protected $default_group_name = 'Link Central';

	const BATCH_SIZE      = 50;
	const SOURCE_META_KEY = '_lc_destination_url';

	/**
	 * Meta keys to consult for the redirect type, in priority order.
	 *
	 * @var string[]
	 */
	private $redirect_type_meta_keys = [
		'_lc_redirect_type',
		'_lc_redirection_type',
		'_lc_redirect_code',
		'_lc_redirect',
	];

	/**
	 * Link Central isn't a custom table or a plugin-file presence — it's
	 * detected by the existence of posts with `_lc_destination_url` postmeta.
	 */
	protected function is_available() {
		return $this->count_source_links() > 0;
	}

	/**
	 * One-shot fetch — used by BaseImporter::run() when called outside the
	 * paged AJAX flow. For large sites prefer run_batch().
	 */
	protected function fetch_links() {
		return $this->fetch_batch( 0, PHP_INT_MAX );
	}

	protected function extract_slug( $record ) {
		return sanitize_text_field( Helper::get_data( $record, 'post_name', '' ) );
	}

	protected function should_skip( $record, $slug ) {
		// No usable destination → skip.
		return '' === $this->resolve_destination_url( $record );
	}

	protected function map_link( $record, $slug ) {
		return [
			'name'          => sanitize_text_field( Helper::get_data( $record, 'post_title', '' ) ),
			'slug'          => $slug,
			'url'           => $this->resolve_destination_url( $record ),
			'redirect_type' => (string) $this->resolve_redirect_type( $record ),
			'status'        => 1,
			'type'          => 'direct',
			'track_me'      => 1,
			'created_at'    => Helper::get_current_date_time(),
			'created_by_id' => $this->current_user_id,
		];
	}

	/**
	 * Paged variant — used by the migration UI which calls back for each batch.
	 *
	 * Returns a progress array shaped to match what the existing migration
	 * AJAX handler emits, so it can be `wp_send_json_success`'d as-is.
	 *
	 * @param int $offset
	 * @param int $limit
	 *
	 * @return array{success:bool,migrated?:int,processed?:int,completed?:bool,offset?:int,message:string}
	 */
	public function run_batch( $offset = 0, $limit = self::BATCH_SIZE ) {
		$offset = max( 0, (int) $offset );
		$limit  = max( 1, (int) $limit );

		if ( $this->count_source_links() <= 0 ) {
			return [
				'success' => false,
				'message' => __( 'No Link Central links were found to migrate.', 'url-shortify' ),
			];
		}

		$records = $this->fetch_batch( $offset, $limit );

		if ( empty( $records ) ) {
			return [
				'success'   => true,
				'migrated'  => 0,
				'processed' => 0,
				'completed' => true,
				'offset'    => $offset,
				'message'   => __( 'Migration complete.', 'url-shortify' ),
			];
		}

		$this->bump_time_limit();

		$existing_links = US()->db->links->get_columns_map( 'id', 'slug' );

		$migrated = $this->process_records( $records, $existing_links );

		return [
			'success'   => true,
			'migrated'  => $migrated,
			'processed' => count( $records ),
			'completed' => count( $records ) < $limit,
			'offset'    => $offset + count( $records ),
			'message'   => sprintf(
				/* translators: %d number of imported links */
				__( '%d Link Central links migrated.', 'url-shortify' ),
				$migrated
			),
		];
	}

	/**
	 * Count posts that carry the Link Central destination meta key.
	 *
	 * @return int
	 */
	private function count_source_links() {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT COUNT( DISTINCT p.ID )
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm
					ON p.ID = pm.post_id
					AND pm.meta_key = %s
				WHERE p.post_status NOT IN ( 'auto-draft', 'trash', 'inherit' )
					AND p.post_type NOT IN ( 'revision', 'nav_menu_item' )
				",
				self::SOURCE_META_KEY
			)
		);
	}

	/**
	 * Fetch one page of Link Central posts.
	 *
	 * @param int $offset
	 * @param int $limit
	 *
	 * @return array
	 */
	private function fetch_batch( $offset, $limit ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"
			SELECT p.ID AS id, p.post_title, p.post_name
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm
				ON p.ID = pm.post_id
				AND pm.meta_key = %s
			WHERE p.post_status NOT IN ( 'auto-draft', 'trash', 'inherit' )
				AND p.post_type NOT IN ( 'revision', 'nav_menu_item' )
			ORDER BY p.ID ASC
			LIMIT %d OFFSET %d
			",
			self::SOURCE_META_KEY,
			$limit,
			$offset
		);

		$rows = $wpdb->get_results( $query, ARRAY_A );

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * Pull the canonical destination URL from postmeta. Runs once per record;
	 * cached for the duration of the request.
	 *
	 * @param array $record
	 *
	 * @return string
	 */
	private function resolve_destination_url( $record ) {
		static $cache = [];

		$post_id = (int) Helper::get_data( $record, 'id', 0 );

		if ( $post_id <= 0 ) {
			return '';
		}

		if ( ! isset( $cache[ $post_id ] ) ) {
			$cache[ $post_id ] = esc_url_raw( (string) get_post_meta( $post_id, self::SOURCE_META_KEY, true ) );
		}

		return $cache[ $post_id ];
	}

	/**
	 * Inspect known meta keys to find a usable HTTP redirect type. Defaults
	 * to 301 if none of them is set to one of {301, 302, 307}.
	 *
	 * @param array $record
	 *
	 * @return int
	 */
	private function resolve_redirect_type( $record ) {
		$post_id = (int) Helper::get_data( $record, 'id', 0 );

		foreach ( $this->redirect_type_meta_keys as $meta_key ) {
			$type = absint( get_post_meta( $post_id, $meta_key, true ) );
			if ( in_array( $type, [ 301, 302, 307 ], true ) ) {
				return $type;
			}
		}

		return 301;
	}
}
