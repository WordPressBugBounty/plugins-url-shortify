<?php

namespace KaizenCoders\URL_Shortify\Admin\Controllers\Importers;

use KaizenCoders\URL_Shortify\Helper;

/**
 * Import short links from the URL Shortener by MyThemeShop plugin.
 *
 * @since 2.3.1
 */
class MtsShortLinksImporter extends BaseImporter {

	protected $source_key = 'mts_links';

	protected $default_group_name = 'MyThemeShop Short Links';

	protected function fetch_links() {
		global $wpdb;

		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}short_links", ARRAY_A );
	}

	protected function extract_slug( $record ) {
		return Helper::get_data( $record, 'link_name', '' );
	}

	protected function map_link( $record, $slug ) {
		$title = Helper::get_data( $record, 'link_title', '' );
		$owner = Helper::get_data( $record, 'link_owner', '' );

		return [
			'slug'              => $slug,
			'name'              => ! empty( $title ) ? $title : Helper::get_data( $record, 'link_url', '' ),
			'description'       => Helper::get_data( $record, 'link_description', '' ),
			'url'               => Helper::get_data( $record, 'link_url', '' ),
			'nofollow'          => ( 'nofollow' === Helper::get_data( $record, 'link_attr_rel', 'nofollow' ) ) ? 1 : 0,
			'track_me'          => 1,
			'sponsored'         => 0,
			'params_forwarding' => Helper::get_data( $record, 'link_forward_parameters', 0 ),
			'params_structure'  => null,
			'redirect_type'     => Helper::get_data( $record, 'link_redirection_method', 307 ),
			'status'            => ( 'publish' === Helper::get_data( $record, 'link_status', 'publish' ) ) ? 1 : 0,
			'type'              => 'direct',
			'type_id'           => null,
			'password'          => null,
			'expires_at'        => null,
			'cpt_id'            => null,
			'cpt_type'          => null,
			'rules'             => null,
			'created_at'        => Helper::get_data( $record, 'link_created', '' ),
			'created_by_id'     => $owner,
			'updated_at'        => Helper::get_data( $record, 'link_updated', '' ),
			'updated_by_id'     => $owner,
		];
	}

	protected function extract_groups( $record, $slug ) {
		$link_id = Helper::get_data( $record, 'link_id', 0 );

		if ( empty( $link_id ) ) {
			return [];
		}

		$category_ids = wp_get_object_terms( $link_id, 'short_link_category', [ 'fields' => 'ids' ] );
		$category_ids = array_unique( $category_ids );

		if ( ! Helper::is_forechable( $category_ids ) ) {
			return [];
		}

		$names = [];
		foreach ( $category_ids as $category_id ) {
			$term = get_term( $category_id, 'short_link_category' );
			if ( $term && ! is_wp_error( $term ) ) {
				$names[] = $term->name;
			}
		}

		return $names;
	}
}
