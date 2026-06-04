<?php

namespace KaizenCoders\URL_Shortify\Admin\Controllers\Importers;

use KaizenCoders\URL_Shortify\Helper;

/**
 * Import short links from the Simple 301 Redirect plugin.
 *
 * Source data lives in a serialized option, not a custom table — so the
 * "record" iterated by the base class is a [slug => target_url] pair, where
 * the slug is the array key. We normalise via fetch_links() so that
 * extract_slug() and map_link() see uniform records.
 *
 * @since 2.3.1
 */
class Simple301RedirectImporter extends BaseImporter {

	protected $source_key = 'simple_301_redirects';

	protected $default_group_name = 'Simple 301 Redirect';

	protected function fetch_links() {
		$raw = maybe_unserialize( get_option( '301_redirects' ) );

		if ( ! Helper::is_forechable( $raw ) ) {
			return [];
		}

		$normalised = [];
		foreach ( $raw as $slug => $target_url ) {
			$normalised[] = [
				'slug' => ltrim( $slug, '/' ),
				'url'  => $target_url,
			];
		}

		return $normalised;
	}

	protected function extract_slug( $record ) {
		return Helper::get_data( $record, 'slug', '' );
	}

	protected function should_skip( $record, $slug ) {
		return '*' === $slug;
	}

	protected function map_link( $record, $slug ) {
		$defaults = $this->get_default_link_options();

		return [
			'slug'              => $slug,
			'name'              => $this->default_group_name . ' - ' . $slug,
			'description'       => 'Imported From ' . $this->default_group_name,
			'url'               => Helper::get_data( $record, 'url', '' ),
			'nofollow'          => $defaults['nofollow'],
			'track_me'          => $defaults['track_me'],
			'sponsored'         => $defaults['sponsored'],
			'params_forwarding' => $defaults['params_forwarding'],
			'redirect_type'     => 301,
			'status'            => 1,
			'type'              => 'direct',
			'created_at'        => Helper::get_current_date_time(),
			'created_by_id'     => $this->current_user_id,
		];
	}

}
