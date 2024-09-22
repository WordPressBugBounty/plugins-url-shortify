<?php


namespace KaizenCoders\URL_Shortify\API\V1;

use KaizenCoders\URL_Shortify\API\Schema;
use KaizenCoders\URL_Shortify\Helper;

class LinksRestController extends \WP_REST_Controller {

	use Schema;

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'url-shortify/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'links';

	/**
	 * Initialize.
	 *
	 * @since 1.7.5
	 */
	public function init() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'permission_callback' => [ $this, 'get_items_permissions_check' ],
				'args'                => [],
			],
		] );

		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
				'args'                => $this->get_links_schema(),
			],
		] );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)',
			[
				'args' => [
					'id' => [
						'description' => __( 'Unique identifier of the link.', 'url-shortify' ),
						'type'        => 'integer',
					],
				],
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'get_item_permissions_check' ],
					'args'                => $this->get_links_schema(),
				],
				[
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_item' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
				],
				[
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => [ $this, 'delete_item_permissions_check' ],
				],
			]
		);


	}

	/**
	 * Get Links Schema.
	 *
	 * @since 1.7.5
	 * @return \string[][]
	 *
	 */
	public function get_links_schema() {
		return $this->create_link_schema();
	}


	public function get_items( $request ) {

		$links = US()->db->links->get_all();

		if ( $links ) {
			return new \WP_REST_Response(
				[
					'success' => true,
					'data'    => $links,
				],
				200
			);
		}

		return new \WP_REST_Response(
			[
				'success' => false,
				'data'    => [],
			],
			200
		);

	}

	/**
	 * Create a short URL.
	 *
	 * @param $request
	 *
	 * @return \WP_REST_Response
	 */
	public function create_item( $request ) {
		$params = $request->get_params();

		$url = Helper::get_data( $params, 'url', '' );

		if ( empty( $url ) ) {
			return new \WP_REST_Response(
				[
					'success' => false,
					'data'    => [
						'short_url' => '',
					],
				],
				200
			);
		}

		$url = sanitize_url( $url );

		$data = [
			'url'   => $url,
			'title' => Helper::get_data( $params, 'title', '', true ),
		];

		$short_url = Helper::generate_short_link( $data );

		if ( $short_url ) {
			return new \WP_REST_Response(
				[
					'success' => true,
					'data'    => [
						'short_url' => $short_url,
					],
				],
				200
			);
		}

		return new \WP_REST_Response(
			[
				'success' => false,
				'data'    => [
					'short_url' => '',
				],
			],
			200
		);
	}

	/**
	 * @since 1.8.4
	 *
	 * @param $request
	 *
	 * @return \WP_REST_Response
	 *
	 */
	public function update_item( $request ) {
		$params = $request->get_params();

		$id = Helper::get_data( $params, 'id', '' );

		if ( empty( $id ) ) {
			return new \WP_REST_Response(
				[
					'success' => false,
					'data'    => [
						'short_url' => '',
					],
				],
				200
			);
		}

		$link = US()->db->links->get( $id );

		if ( ! $link ) {
			return new \WP_REST_Response(
				[
					'success' => false,
					'data'    => [],
				],
				200
			);
		}

		foreach ( $params as $key => $value ) {
			$link[ $key ] = sanitize_text_field( $value );
		}

		$link = US()->db->links->update( $id, $link );

		if ( $link ) {
			return new \WP_REST_Response(
				[
					'success' => true,
					'data'    => [],
				],
				200
			);
		}

		return new \WP_REST_Response(
			[
				'success' => false,
				'data'    => [],
			],
			200
		);
	}

	/**
	 * Delete a short URL.
	 *
	 * @param $request
	 *
	 * @return void|\WP_REST_Response
	 */
	public function delete_item( $request ) {

		$params = $request->get_params();

		$id = Helper::get_data( $params, 'id', '' );

		if ( empty( $id ) ) {
			return new \WP_REST_Response(
				[
					'success' => false,
					'data'    => [
						'short_url' => '',
					],
				],
				200
			);
		}

		$link = US()->db->links->delete( $id );

		return new \WP_REST_Response(
			[
				'success' => true,
				'data'    => [],
			],
			200
		);

	}

	/**
	 * Can Access links?
	 *
	 * @since 1.7.5
	 *
	 * @param $request
	 *
	 * @return bool
	 *
	 */
	public function get_items_permissions_check( $request ) {
		return apply_filters( 'url_shortify/api/links_get_items_permissions_check', US()->access->can( 'manage_links' ) );
	}

	/**
	 * Can create links?
	 *
	 * @since 1.7.5
	 *
	 * @param $request
	 *
	 * @return bool
	 *
	 */
	public function create_item_permissions_check( $request ) {
		return apply_filters( 'url_shortify/api/links_create_item_permissions_check', US()->access->can( 'create_links' ) );
	}

	/**
	 * Delete link permissions check.
	 *
	 * @since 1.8.4
	 *
	 * @param $request
	 *
	 * @return bool|\WP_Error
	 *
	 */
	public function delete_item_permissions_check( $request ) {
		return apply_filters( 'url_shortify/api/links_delete_item_permissions_check', US()->access->can( 'manage_links' ) );
	}

	/**
	 * Can access API?
	 *
	 * @since 1.8.4
	 *
	 * @param $request
	 *
	 * @return bool|mixed|\WP_Error|null
	 *
	 */
	public function update_item_permissions_check( $request ) {
		return apply_filters( 'url_shortify/api/links_update_item_permissions_check', US()->access->can( 'manage_links' ) );
	}
}
