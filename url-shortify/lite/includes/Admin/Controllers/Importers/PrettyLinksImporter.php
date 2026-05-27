<?php

namespace KaizenCoders\URL_Shortify\Admin\Controllers\Importers;

use KaizenCoders\URL_Shortify\Helper;

/**
 * Import short links from the Pretty Links plugin.
 *
 * @since 2.3.1
 */
class PrettyLinksImporter extends BaseImporter {

	protected $source_key = 'pretty_links';

	protected $default_group_name = 'Pretty Links';

	protected function fetch_links() {
		global $wpdb;

		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}prli_links", ARRAY_A );
	}

	protected function extract_slug( $record ) {
		return Helper::get_data( $record, 'slug', '' );
	}

	protected function map_link( $record, $slug ) {
		$cpt_id = Helper::get_data( $record, 'link_cpt_id', '' );
		$name   = Helper::get_data( $record, 'name', '' );

		return [
			'slug'              => $slug,
			'name'              => ! empty( $name ) ? $name : Helper::get_data( $record, 'url', '' ),
			'description'       => Helper::get_data( $record, 'description', '' ),
			'url'               => Helper::get_data( $record, 'url', '' ),
			'nofollow'          => Helper::get_data( $record, 'nofollow', '' ),
			'track_me'          => Helper::get_data( $record, 'track_me', '' ),
			'sponsored'         => Helper::get_data( $record, 'sponsored', '' ),
			'params_forwarding' => Helper::get_data( $record, 'param_forwarding', '' ),
			'params_structure'  => Helper::get_data( $record, 'params_struct', '' ),
			'redirect_type'     => Helper::get_data( $record, 'redirect_type', '' ),
			'status'            => ( 'enabled' === Helper::get_data( $record, 'link_status', 'enabled' ) ) ? 1 : 0,
			'type'              => 'direct',
			'type_id'           => null,
			'password'          => null,
			'expires_at'        => null,
			'cpt_id'            => $cpt_id,
			'cpt_type'          => Helper::get_data( $record, 'link_cpt_type', '' ),
			'rules'             => null,
			'created_at'        => Helper::get_data( $record, 'created_at', '' ),
			'created_by_id'     => $this->current_user_id,
			'updated_at'        => Helper::get_data( $record, 'updated_at', '' ),
			'updated_by_id'     => $this->current_user_id,
		];
	}

	protected function extract_groups( $record, $slug ) {
		$cpt_id = Helper::get_data( $record, 'link_cpt_id', '' );

		if ( empty( $cpt_id ) ) {
			return [];
		}

		$terms = get_the_terms( $cpt_id, 'pretty-link-category' );

		if ( ! Helper::is_forechable( $terms ) ) {
			return [];
		}

		$names = [];
		foreach ( $terms as $term ) {
			$names[] = $term->name;
		}

		return $names;
	}
}
