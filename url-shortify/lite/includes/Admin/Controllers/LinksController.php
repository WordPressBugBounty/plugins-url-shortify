<?php

namespace KaizenCoders\URL_Shortify\Admin\Controllers;

use KaizenCoders\URL_Shortify\Admin\DB\Links;
use KaizenCoders\URL_Shortify\Common\Utils;
use KaizenCoders\URL_Shortify\Helper;

class LinksController extends BaseController {
	/**
	 * @since 1.1.5
	 * @var Links|null
	 *
	 */
	public $db = null;

	/**
	 * LinksController constructor.
	 *
	 * @since 1.1.3
	 */
	public function __construct() {
		$this->db = new Links();

		parent::__construct();
	}

	/**
	 * Create Short Link
	 *
	 * @since 1.1.3
	 *
	 * @param array $data
	 *
	 * @return array|string[]
	 *
	 */
	public function create( $data = [] ) {
		$post_id = Helper::get_data( $data, 'post_id', 0 );

		$url       = $title = '';
		$link_data = [];
		if ( ! empty( $post_id ) ) {
			$slug    = Utils::get_valid_slug();
			$slug    = Helper::get_slug_with_prefix( $slug );
			$link_id = $this->create_link_from_post( $post_id, $slug );
		} else {
			$url = Helper::get_data( $data, 'url', '' );

			$title = Utils::get_title_from_url( $url );

			$link_data = [
				'url'  => $url,
				'name' => $title,
			];

			if ( US()->is_pro() ) {
				$domain = Helper::get_data( $data, 'domain', '' );

				if ( ! empty( $domain ) ) {
					$link_data['rules']['domain'] = $domain;
				}
			}

			$slug = Helper::get_data( $data, 'slug', '' );

			if ( empty( $slug ) ) {
				$slug = Utils::get_valid_slug();
			}

			$slug = Helper::get_slug_with_prefix( $slug );

			if ( Utils::is_slug_exists( $slug ) ) {
				$response = [
					'status'  => 'error',
					'message' => 'Slug already exist',
				];

				return $response;
			}

			$link_id = $this->create_link_from_data( $link_data, $slug );
		}

		if ( $link_id ) {

			$link_data = US()->db->links->get_by_id( $link_id );

			$link = Helper::get_short_link( $slug, $link_data );

			$response = [
				'status'     => 'success',
				'link'       => $link,
				'target_url' => $url,
				'title'      => $title,
				'html'       => Helper::create_copy_short_link_html( $link, $post_id ),
			];

		} else {
			$response = [
				'status' => 'error',
			];
		}

		return $response;
	}

	/**
	 * Get Short Link by CPT id
	 *
	 * @since 1.1.5
	 *
	 * @param int $cpt_id
	 *
	 * @return bool|string
	 *
	 */
	public function get_short_link_by_cpt_id( $cpt_id = 0 ) {

		$link = $this->db->get_by_cpt_id( $cpt_id );

		if ( ! empty( $link ) ) {
			return Helper::get_short_link( $link['slug'] );
		}

		return false;
	}

	/**
	 * Generate link from post
	 *
	 * @since 1.2.5
	 *
	 * @param string $slug
	 *
	 * @param string $post
	 *
	 * @return boolean
	 *
	 */
	public function create_link_from_post( $post = '', $slug = '' ) {

		$post = get_post( $post );

		if ( $post instanceof \WP_Post ) {

			$link_data = [
				'cpt_id'      => $post->ID,
				'url'         => get_permalink( $post->ID ),
				'name'        => addslashes( $post->post_title ),
				'description' => addslashes( $post->post_excerpt ),
			];

			return $this->db->create_link( $link_data, $slug );
		}

		return false;
	}

	/**
	 * @since 1.2.5
	 *
	 * @param string $slug
	 *
	 * @param array  $link_data
	 *
	 * @return bool|int
	 *
	 */
	public function create_link_from_data( $link_data = [], $slug = '' ) {
		return $this->db->create_link( $link_data, $slug );
	}
}
