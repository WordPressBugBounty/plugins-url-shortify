<?php

namespace KaizenCoders\URL_Shortify\Admin\Controllers\Importers;

use KaizenCoders\URL_Shortify\Helper;
use KaizenCoders\URL_Shortify\Option;

/**
 * Import affiliate links from the Thirsty Affiliates plugin.
 *
 * Source records are WP_Post objects of CPT 'thirstylink'.
 *
 * @since 2.3.1
 */
class ThirstyAffiliatesImporter extends BaseImporter {

	protected $source_key = 'thirsty_affiliates';

	protected $default_group_name = 'Thirsty Affiliates';

	/**
	 * Cached link prefix — read once in fetch_links() and reused in map_link()
	 * and after_import() so we don't call get_option() repeatedly.
	 *
	 * @var string
	 */
	private $link_prefix = '';

	protected function fetch_links() {
		$this->link_prefix = (string) get_option( 'ta_link_prefix_custom', true );

		return get_posts( [
			'posts_per_page' => -1,
			'post_type'      => 'thirstylink',
			'post_status'    => 'publish',
		] );
	}

	protected function extract_slug( $record ) {
		if ( ! ( $record instanceof \WP_Post ) ) {
			return '';
		}

		$slug = $record->post_name;

		if ( ! empty( $this->link_prefix ) ) {
			$slug = trim( $this->link_prefix, '/' ) . '/' . $slug;
		}

		return $slug;
	}

	protected function map_link( $record, $slug ) {
		$post_id = $record->ID;

		$nofollow = get_post_meta( $post_id, '_ta_no_follow', true );
		$nofollow = ( 'global' === $nofollow ) ? get_option( 'ta_no_follow', true ) : $nofollow;

		$redirect_type = get_post_meta( $post_id, '_ta_redirect_type', true );
		$redirect_type = ( 'global' === $redirect_type ) ? get_option( 'ta_link_redirect_type', true ) : $redirect_type;

		$param_forwarding = get_post_meta( $post_id, '_ta_pass_query_str', true );
		$param_forwarding = ( 'global' === $param_forwarding ) ? get_option( 'ta_pass_query_str', true ) : $param_forwarding;

		return [
			'slug'              => $slug,
			'name'              => $record->post_title,
			'description'       => '',
			'url'               => get_post_meta( $post_id, '_ta_destination_url', true ),
			'nofollow'          => ( 'yes' === $nofollow ) ? 1 : 0,
			'track_me'          => 1,
			'sponsored'         => 0,
			'params_forwarding' => ( 'yes' === $param_forwarding ) ? 1 : 0,
			'params_structure'  => null,
			'redirect_type'     => $redirect_type,
			'status'            => 1,
			'type'              => 'direct',
			'type_id'           => null,
			'password'          => null,
			'expires_at'        => get_post_meta( $post_id, '_ta_link_expire_date', true ),
			'cpt_id'            => null,
			'cpt_type'          => null,
			'rules'             => null,
			'created_at'        => Helper::get_current_date_time(),
			'created_by_id'     => $this->current_user_id,
			'updated_at'        => '',
			'updated_by_id'     => '',
		];
	}

	protected function extract_groups( $record, $slug ) {
		$terms = get_the_terms( $record->ID, 'thirstylink-category' );

		if ( ! Helper::is_forechable( $terms ) ) {
			return [];
		}

		$names = [];
		foreach ( $terms as $term ) {
			$names[] = $term->name;
		}

		return $names;
	}

	protected function after_import() {
		// Persist the original link prefix as the default for new short links.
		$settings                                          = Option::get( 'settings' );
		$settings['links_default_link_options_link_prefix'] = $this->link_prefix;
		Option::set( 'settings', $settings );
	}
}
