<?php

namespace KaizenCoders\URL_Shortify;


class Shortcode {
	/**
	 * Init Shortcode
	 *
	 * @since 1.7.0
	 */
	public function init() {
		\add_shortcode( 'shorturl', [ $this, 'render' ] );
	}

	/**
	 * Render public facing URL Shortener
	 *
	 * @since 1.7.0
	 */
	public function render() {
		global $post;

		if ( $post instanceof \WP_Post ) {

			$short_link = US()->db->links->get_by_cpt_id( $post->ID );

			if ( empty( $short_link ) ) {

				$link_data = [
					'cpt_id'      => $post->ID,
					'url'         => get_permalink( $post->ID ),
					'name'        => addslashes( $post->post_title ),
					'description' => addslashes( $post->post_excerpt ),
				];

				$created = US()->db->links->create_link( $link_data );

				if ( $created ) {
					$short_link = US()->db->links->get_by_cpt_id( $post->ID );
				}
			}

			$short_url = Helper::get_short_link( $short_link['slug'] );

			return sprintf( '<a href="%s" target="__blank">%s</a>', $short_url, $short_url );
		}
	}
}