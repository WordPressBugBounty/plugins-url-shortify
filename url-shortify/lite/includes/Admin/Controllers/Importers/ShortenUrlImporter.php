<?php

namespace KaizenCoders\URL_Shortify\Admin\Controllers\Importers;

use KaizenCoders\URL_Shortify\Helper;

/**
 * Import short links from the Shorten URL plugin.
 *
 * https://wordpress.org/plugins/shorten-url/
 *
 * @since 2.3.1
 */
class ShortenUrlImporter extends BaseImporter {

	protected $source_key = 'shorten_url';

	protected $default_group_name = 'Shorten URL';

	/**
	 * Memo for resolve_url_and_title() — same record is resolved twice per
	 * row (once in should_skip, once in map_link); without memoization a
	 * 10 000-row migration runs 20 000 get_post() lookups.
	 *
	 * @var array
	 */
	private $resolved_cache = [];

	protected function fetch_links() {
		global $wpdb;

		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}pluginSL_shorturl", ARRAY_A );
	}

	protected function extract_slug( $record ) {
		return Helper::get_data( $record, 'short_url', '' );
	}

	protected function should_skip( $record, $slug ) {
		// We need a usable URL — either directly on the record or resolvable
		// from the linked post. We resolve it again in map_link(); doing it
		// here keeps the bulk insert clean.
		return '' === $this->resolve_url_and_title( $record )['url'];
	}

	protected function map_link( $record, $slug ) {
		$resolved = $this->resolve_url_and_title( $record );

		return [
			'slug'              => $slug,
			'name'              => $resolved['title'],
			'description'       => Helper::get_data( $record, 'comment', '' ),
			'url'               => $resolved['url'],
			'nofollow'          => ( 'nofollow' === Helper::get_data( $record, 'link_attr_rel', 'nofollow' ) ) ? 1 : 0,
			'track_me'          => 1,
			'sponsored'         => 0,
			'params_forwarding' => 0,
			'params_structure'  => null,
			'redirect_type'     => Helper::get_data( $record, 'link_redirection_method', 307 ),
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
	 * Source records may store the destination URL directly or as a post id —
	 * resolve to a single (url, title, post_id) triple.
	 *
	 * @param array $record
	 *
	 * @return array{url:string,title:string,post_id:int}
	 */
	private function resolve_url_and_title( $record ) {
		$post_id   = (int) Helper::get_data( $record, 'id_post', 0 );
		$source    = (string) Helper::get_data( $record, 'url_externe', '' );
		$cache_key = $post_id . '|' . $source;

		if ( isset( $this->resolved_cache[ $cache_key ] ) ) {
			return $this->resolved_cache[ $cache_key ];
		}

		$title = $source;
		$url   = $source;

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
