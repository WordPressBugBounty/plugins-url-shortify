<?php

namespace KaizenCoders\URL_Shortify\Admin\Controllers\Importers;

use KaizenCoders\URL_Shortify\Admin\Controllers\ImportController;
use KaizenCoders\URL_Shortify\Helper;

/**
 * Abstract base class for one-click import sources.
 *
 * Implements the shared workflow (availability check, fetch, loop with
 * de-duplication, bulk insert, groups mapping). Concrete subclasses provide
 * per-source mapping by overriding the abstract / hook methods.
 *
 * @since 2.3.1
 */
abstract class BaseImporter {

	/**
	 * Source key — used by Helper::is_import_source_available().
	 *
	 * @var string
	 */
	protected $source_key = '';

	/**
	 * Default group name for the source plugin. When set, every imported link
	 * is added to this group automatically — making it easy to find / bulk-
	 * manage everything that came from a given migration. Set to empty to opt
	 * out.
	 *
	 * @var string
	 */
	protected $default_group_name = '';

	/**
	 * Parent controller — gives access to shared group / tag helpers.
	 *
	 * @var ImportController
	 */
	protected $controller;

	/**
	 * Cached current user id.
	 *
	 * @var int
	 */
	protected $current_user_id = 0;

	/**
	 * @param ImportController $controller
	 */
	public function __construct( ImportController $controller ) {
		$this->controller      = $controller;
		$this->current_user_id = get_current_user_id();
	}

	/**
	 * Run the import. Always returns true so callers see a consistent result
	 * even when the source isn't installed (which is not an error).
	 *
	 * @return bool
	 */
	public function run() {
		if ( ! $this->is_available() ) {
			return true;
		}

		$source_records = $this->fetch_links();

		if ( ! Helper::is_forechable( $source_records ) ) {
			return true;
		}

		$this->bump_time_limit();

		$existing_links = US()->db->links->get_columns_map( 'id', 'slug' );

		$this->process_records( $source_records, $existing_links );

		$this->after_import();

		return true;
	}

	/**
	 * Run the per-record map → bulk-insert → group mapping loop for the
	 * given batch of source records. Extracted so batched importers (e.g.
	 * LinkCentralImporter) can reuse the same logic across paged calls.
	 *
	 * @param array $source_records
	 * @param array $existing_slugs Slug → id (or id → slug) map used for dedupe.
	 *
	 * @return int Number of links actually inserted.
	 */
	protected function process_records( array $source_records, array $existing_slugs ) {
		$values = [];
		$groups = [];
		$key    = 0;

		foreach ( $source_records as $record ) {
			$slug = $this->extract_slug( $record );

			if ( '' === $slug || in_array( $slug, $existing_slugs, true ) ) {
				continue;
			}

			if ( $this->should_skip( $record, $slug ) ) {
				continue;
			}

			$values[ $key ] = $this->map_link( $record, $slug );

			// Always add to the source-plugin default group (if set), so a
			// migration is bulk-discoverable in one place.
			if ( '' !== $this->default_group_name ) {
				$groups[ $this->default_group_name ][] = $slug;
			}

			foreach ( $this->extract_groups( $record, $slug ) as $group_name ) {
				$groups[ $group_name ][] = $slug;
			}

			$key++;
		}

		if ( Helper::is_forechable( $values ) ) {
			US()->db->links->bulk_insert( $values );
		}

		if ( Helper::is_forechable( $groups ) ) {
			$this->controller->import_groups( $groups );
			$this->controller->add_links_to_group( $groups );
		}

		return $key;
	}

	/**
	 * Whether the import source is detectable on this site.
	 */
	protected function is_available() {
		return (bool) Helper::is_import_source_available( $this->source_key );
	}

	/**
	 * Raise PHP execution time limit if allowed.
	 */
	protected function bump_time_limit() {
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
	}

	/**
	 * Cached default-link options pulled from plugin settings — used by
	 * importers that don't carry their own preferences.
	 *
	 * @return array
	 */
	protected function get_default_link_options() {
		static $cache = null;

		if ( null !== $cache ) {
			return $cache;
		}

		$settings = US()->get_settings();

		$cache = [
			'nofollow'          => Helper::get_data( $settings, 'links_default_link_options_enable_nofollow', 1 ),
			'track_me'          => Helper::get_data( $settings, 'links_default_link_options_enable_tracking', 1 ),
			'sponsored'         => Helper::get_data( $settings, 'links_default_link_options_enable_sponsored', 1 ),
			'params_forwarding' => Helper::get_data( $settings, 'links_default_link_options_enable_paramter_forwarding', 1 ),
			'redirect_type'     => Helper::get_data( $settings, 'links_default_link_options_redirection_type', 301 ),
		];

		return $cache;
	}

	/**
	 * Fetch raw source records (DB rows, posts, option entries, etc.).
	 *
	 * @return array
	 */
	abstract protected function fetch_links();

	/**
	 * Extract the slug from a single source record.
	 *
	 * Return an empty string to skip the record.
	 *
	 * @param mixed $record
	 *
	 * @return string
	 */
	abstract protected function extract_slug( $record );

	/**
	 * Map a source record to the kc_us_links column array.
	 *
	 * @param mixed  $record
	 * @param string $slug
	 *
	 * @return array
	 */
	abstract protected function map_link( $record, $slug );

	/**
	 * Optional per-record skip rule (beyond duplicate slug).
	 *
	 * @param mixed  $record
	 * @param string $slug
	 *
	 * @return bool
	 */
	protected function should_skip( $record, $slug ) {
		return false;
	}

	/**
	 * Optional list of group names to associate this record with.
	 *
	 * @param mixed  $record
	 * @param string $slug
	 *
	 * @return string[]
	 */
	protected function extract_groups( $record, $slug ) {
		return [];
	}

	/**
	 * Optional post-import hook (e.g. saving plugin settings).
	 */
	protected function after_import() {
	}
}
