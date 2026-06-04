<?php

namespace KaizenCoders\URL_Shortify\Admin\Controllers\Importers;

use KaizenCoders\URL_Shortify\Helper;

/**
 * Import URL redirects from the Redirection plugin.
 *
 * Only `action_type = 'url'` rows are imported — other action types (e.g.
 * pass-through, error pages) don't map to short links.
 *
 * @since 2.3.1
 */
class RedirectionImporter extends BaseImporter {

	protected $source_key = 'redirection';

	protected $default_group_name = 'Redirection';

	/**
	 * Memo for resolve_url_and_title() — same record is resolved twice per
	 * row (once in should_skip, once in map_link).
	 *
	 * @var array
	 */
	private $resolved_cache = [];

	protected function fetch_links() {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}redirection_items WHERE `action_type` = 'url'",
			ARRAY_A
		);
	}

	protected function extract_slug( $record ) {
		return trim( Helper::get_data( $record, 'url', '' ), '/' );
	}

	protected function should_skip( $record, $slug ) {
		return '' === $this->resolve_url_and_title( $record )['url'];
	}

	protected function map_link( $record, $slug ) {
		$defaults = $this->get_default_link_options();
		$resolved = $this->resolve_url_and_title( $record );

		return [
			'slug'              => $slug,
			'name'              => $resolved['title'],
			'description'       => Helper::get_data( $record, 'comment', '' ),
			'url'               => $resolved['url'],
			'nofollow'          => $defaults['nofollow'],
			'track_me'          => $defaults['track_me'],
			'sponsored'         => $defaults['sponsored'],
			'params_forwarding' => $defaults['params_forwarding'],
			'params_structure'  => null,
			'redirect_type'     => Helper::get_data( $record, 'action_code', 307 ),
			'status'            => 1,
			'type'              => 'direct',
			'type_id'           => null,
			'password'          => null,
			'expires_at'        => null,
			'cpt_id'            => $resolved['post_id'],
			'cpt_type'          => null,
			'rules'             => null,
			'created_at'        => Helper::get_current_date_time(),
			'created_by_id'     => $this->current_user_id,
			'updated_at'        => '',
			'updated_by_id'     => '',
		];
	}

	/**
	 * @param array $record
	 *
	 * @return array{url:string,title:string,post_id:int|null}
	 */
	private function resolve_url_and_title( $record ) {
		$post_id   = (int) Helper::get_data( $record, 'id_post', 0 );
		$title     = (string) Helper::get_data( $record, 'title', '' );
		$url       = (string) Helper::get_data( $record, 'action_data', '' );
		$cache_key = $post_id . '|' . $url;

		if ( isset( $this->resolved_cache[ $cache_key ] ) ) {
			return $this->resolved_cache[ $cache_key ];
		}

		if ( $post_id > 0 ) {
			$post = get_post( $post_id );
			if ( $post instanceof \WP_Post ) {
				$url   = get_permalink( $post );
				$title = get_the_title( $post );
			}
		}

		return $this->resolved_cache[ $cache_key ] = [
			'url'     => (string) $url,
			'title'   => (string) $title,
			'post_id' => $post_id ?: null,
		];
	}
}
