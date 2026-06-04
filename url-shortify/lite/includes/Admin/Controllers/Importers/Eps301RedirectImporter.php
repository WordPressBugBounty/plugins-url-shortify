<?php

namespace KaizenCoders\URL_Shortify\Admin\Controllers\Importers;

use KaizenCoders\URL_Shortify\Helper;

/**
 * Import short links from 301 Redirect — Easy Redirect Manager.
 *
 * https://wordpress.org/plugins/eps-301-redirects/
 *
 * @since 2.3.1
 */
class Eps301RedirectImporter extends BaseImporter {

	protected $source_key = 'eps_301_redirects';

	protected $default_group_name = 'Easy 301 Redirect';

	protected function fetch_links() {
		global $wpdb;

		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}redirects", ARRAY_A );
	}

	protected function extract_slug( $record ) {
		return Helper::get_data( $record, 'url_from', '' );
	}

	protected function should_skip( $record, $slug ) {
		if ( '*' === $slug ) {
			return true;
		}

		// EPS only ships 301/302/307 — anything else is unsupported.
		$status = Helper::get_data( $record, 'status', 'off' );

		return ! in_array( $status, [ '301', '302', '307' ], true );
	}

	protected function map_link( $record, $slug ) {
		$defaults = $this->get_default_link_options();

		return [
			'slug'              => $slug,
			'name'              => $this->default_group_name . ' - ' . $slug,
			'description'       => 'Imported From ' . $this->default_group_name,
			'url'               => Helper::get_data( $record, 'url_to', '' ),
			'nofollow'          => $defaults['nofollow'],
			'track_me'          => $defaults['track_me'],
			'sponsored'         => $defaults['sponsored'],
			'params_forwarding' => $defaults['params_forwarding'],
			'redirect_type'     => Helper::get_data( $record, 'status', $defaults['redirect_type'] ),
			'status'            => 1,
			'type'              => 'direct',
			'created_at'        => Helper::get_current_date_time(),
			'created_by_id'     => $this->current_user_id,
		];
	}
}
