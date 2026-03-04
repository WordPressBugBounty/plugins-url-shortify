<?php


namespace KaizenCoders\URL_Shortify\API\V1;

use KaizenCoders\URL_Shortify\API\Schema;
use KaizenCoders\URL_Shortify\API\Traits\Error;
use KaizenCoders\URL_Shortify\Helper;

class LinksRestController extends \WP_REST_Controller {

	use Schema, Error;

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
				'args'                => [
					'page'     => [
						'description'       => __( 'Current page of the collection.', 'url-shortify' ),
						'type'              => 'integer',
						'default'           => 1,
						'minimum'           => 1,
						'sanitize_callback' => 'absint',
					],
					'per_page' => [
						'description'       => __( 'Maximum number of items to return per page (1–100).', 'url-shortify' ),
						'type'              => 'integer',
						'default'           => 20,
						'minimum'           => 1,
						'maximum'           => 100,
						'sanitize_callback' => 'absint',
					],
				],
			],
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

	/**
	 * Get all links with group_ids, tag_ids, and short_url.
	 *
	 * @since 1.13.1
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 *
	 * @OA\Get(
	 *     path="/links",
	 *     operationId="getLinks",
	 *     tags={"Links"},
	 *     summary="List all links",
	 *     description="Returns a paginated list of short links with their associated group IDs, tag IDs, and resolved short URL.",
	 *     security={{"basicAuth": {}}, {"apiKeyHeader": {}, "apiKeyHeaderSecret": {}}},
	 *     @OA\Parameter(name="page", in="query", description="Current page of the collection.", @OA\Schema(type="integer", default=1, minimum=1)),
	 *     @OA\Parameter(name="per_page", in="query", description="Maximum number of items per page (1–100).", @OA\Schema(type="integer", default=20, minimum=1, maximum=100)),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Successful response",
	 *         headers={
	 *             @OA\Header(header="X-WP-Total", description="Total number of links.", @OA\Schema(type="integer")),
	 *             @OA\Header(header="X-WP-TotalPages", description="Total number of pages.", @OA\Schema(type="integer"))
	 *         },
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Link"))
	 *         )
	 *     ),
	 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=500, description="Server error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
	 * )
	 */
	public function get_items( $request ) {
		$per_page = $request->get_param( 'per_page' ) ?: 20;
		$page     = $request->get_param( 'page' ) ?: 1;

		$result = US()->db->links->get_paginated( $per_page, $page );
		$links  = $result['items'];
		$total  = $result['total'];

		if ( $links ) {
			$link_ids      = wp_list_pluck( $links, 'id' );
			$group_ids_map = US()->db->links_groups->get_group_ids_by_link_ids( $link_ids );
			$tag_ids_map   = US()->db->links_tags->get_tag_ids_by_link_ids( $link_ids );

			foreach ( $links as &$link ) {
				$link['group_ids'] = isset( $group_ids_map[ $link['id'] ] ) ? array_map( 'absint', $group_ids_map[ $link['id'] ] ) : [];
				$link['tag_ids']   = isset( $tag_ids_map[ $link['id'] ] ) ? array_map( 'absint', $tag_ids_map[ $link['id'] ] ) : [];
				$link['short_url'] = Helper::get_short_link( $link['slug'], $link );
			}
		}

		$total_pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 1;

		$response = new \WP_REST_Response(
			[
				'success' => true,
				'data'    => $links,
			],
			200
		);
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', $total_pages );

		return $response;
	}

	/**
	 * Get a single link with group_ids, tag_ids, and short_url.
	 *
	 * @since 1.13.1
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 *
	 * @OA\Get(
	 *     path="/links/{id}",
	 *     operationId="getLink",
	 *     tags={"Links"},
	 *     summary="Get a single link",
	 *     description="Returns a single short link by its ID, including group IDs, tag IDs, and resolved short URL.",
	 *     security={{"basicAuth": {}}, {"apiKeyHeader": {}, "apiKeyHeaderSecret": {}}},
	 *     @OA\Parameter(
	 *         name="id",
	 *         in="path",
	 *         required=true,
	 *         description="Unique identifier of the link.",
	 *         @OA\Schema(type="integer", example=1)
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Successful response",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", ref="#/components/schemas/Link")
	 *         )
	 *     ),
	 *     @OA\Response(response=400, description="Bad request", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=404, description="Link not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=500, description="Server error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
	 * )
	 */
	public function get_item( $request ) {
		$id = absint( $request->get_param( 'id' ) );

		if ( empty( $id ) ) {
			return $this->rest_bad_request_error( __( 'Invalid link ID.', 'url-shortify' ) );
		}

		$link = US()->db->links->get_by_id( $id );

		if ( empty( $link ) ) {
			return $this->rest_not_found_error( __( 'Link not found.', 'url-shortify' ) );
		}

		$link['group_ids'] = array_map( 'absint', US()->db->links_groups->get_group_ids_by_link_id( $id ) );
		$link['tag_ids']   = array_map( 'absint', US()->db->links_tags->get_tag_ids_by_link_id( $id ) );
		$link['short_url'] = Helper::get_short_link( $link['slug'], $link );

		return new \WP_REST_Response(
			[
				'success' => true,
				'data'    => $link,
			],
			200
		);
	}

	/**
	 * Create a link with optional group_ids and tag_ids.
	 *
	 * @since 1.13.1
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 *
	 * @OA\Post(
	 *     path="/links",
	 *     operationId="createLink",
	 *     tags={"Links"},
	 *     summary="Create a link",
	 *     description="Creates a new short link. The `url` field is required. Optionally assign to groups and tags.",
	 *     security={{"basicAuth": {}}, {"apiKeyHeader": {}, "apiKeyHeaderSecret": {}}},
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\JsonContent(
	 *             required={"url"},
	 *             @OA\Property(property="url", type="string", format="uri", description="The destination URL to shorten.", example="https://example.com/my-long-url"),
	 *             @OA\Property(property="title", type="string", description="A human-readable title for the link.", example="My Link"),
	 *             @OA\Property(property="group_ids", type="array", @OA\Items(type="integer"), description="IDs of groups to assign this link to.", example={1, 2}),
	 *             @OA\Property(property="tag_ids", type="array", @OA\Items(type="integer"), description="IDs of tags to assign this link to.", example={3, 4})
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=201,
	 *         description="Link created successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", ref="#/components/schemas/Link")
	 *         )
	 *     ),
	 *     @OA\Response(response=400, description="Bad request", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=500, description="Server error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
	 * )
	 */
	public function create_item( $request ) {
		$params = $request->get_params();

		$url = Helper::get_data( $params, 'url', '' );

		if ( empty( $url ) ) {
			return $this->rest_bad_request_error( __( 'URL is required.', 'url-shortify' ) );
		}

		$url = sanitize_url( $url );

		$link_data = [
			'url'  => $url,
			'name' => Helper::get_data( $params, 'title', '', true ),
		];

		$link_id = US()->db->links->create_link( $link_data );

		if ( ! $link_id ) {
			return $this->rest_server_error( __( 'Failed to create link.', 'url-shortify' ) );
		}

		$group_ids = Helper::get_data( $params, 'group_ids', null );
		$tag_ids   = Helper::get_data( $params, 'tag_ids', null );

		if ( is_array( $group_ids ) ) {
			US()->db->links_groups->add_link_to_groups( $link_id, array_map( 'absint', $group_ids ) );
		}

		if ( is_array( $tag_ids ) ) {
			US()->db->links_tags->add_link_to_tags( $link_id, array_map( 'absint', $tag_ids ) );
		}

		$link = US()->db->links->get_by_id( $link_id );

		$link['group_ids'] = array_map( 'absint', US()->db->links_groups->get_group_ids_by_link_id( $link_id ) );
		$link['tag_ids']   = array_map( 'absint', US()->db->links_tags->get_tag_ids_by_link_id( $link_id ) );
		$link['short_url'] = Helper::get_short_link( $link['slug'], $link );

		return new \WP_REST_Response(
			[
				'success' => true,
				'data'    => $link,
			],
			201
		);
	}

	/**
	 * Update a link with optional group_ids and tag_ids.
	 *
	 * @since 1.13.1
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 *
	 * @OA\Put(
	 *     path="/links/{id}",
	 *     operationId="updateLink",
	 *     tags={"Links"},
	 *     summary="Update a link",
	 *     description="Updates an existing short link. Only the fields provided in the request body are changed. Pass `group_ids` or `tag_ids` to replace the current associations (pass an empty array to clear all).",
	 *     security={{"basicAuth": {}}, {"apiKeyHeader": {}, "apiKeyHeaderSecret": {}}},
	 *     @OA\Parameter(
	 *         name="id",
	 *         in="path",
	 *         required=true,
	 *         description="Unique identifier of the link.",
	 *         @OA\Schema(type="integer", example=1)
	 *     ),
	 *     @OA\RequestBody(
	 *         @OA\JsonContent(
	 *             @OA\Property(property="url", type="string", format="uri", description="New destination URL.", example="https://example.com/updated-url"),
	 *             @OA\Property(property="title", type="string", description="New title for the link.", example="Updated Title"),
	 *             @OA\Property(property="redirect_type", type="string", enum={"301", "302", "307"}, description="HTTP redirect type.", example="302"),
	 *             @OA\Property(property="status", type="integer", enum={0, 1}, description="1 = active, 0 = inactive.", example=1),
	 *             @OA\Property(property="group_ids", type="array", @OA\Items(type="integer"), description="Replace group assignments (empty array clears all).", example={1}),
	 *             @OA\Property(property="tag_ids", type="array", @OA\Items(type="integer"), description="Replace tag assignments (empty array clears all).", example={})
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Link updated successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", ref="#/components/schemas/Link")
	 *         )
	 *     ),
	 *     @OA\Response(response=400, description="Bad request", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=404, description="Link not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=500, description="Server error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
	 * )
	 */
	public function update_item( $request ) {
		$params = $request->get_params();
		$id     = absint( Helper::get_data( $params, 'id', 0 ) );

		if ( empty( $id ) ) {
			return $this->rest_bad_request_error( __( 'Invalid link ID.', 'url-shortify' ) );
		}

		$link = US()->db->links->get( $id );

		if ( ! $link ) {
			return $this->rest_not_found_error( __( 'Link not found.', 'url-shortify' ) );
		}

		$group_ids = Helper::get_data( $params, 'group_ids', null );
		$tag_ids   = Helper::get_data( $params, 'tag_ids', null );

		// Remove non-link fields before updating
		$update_params = $params;
		unset( $update_params['id'], $update_params['group_ids'], $update_params['tag_ids'] );

		if ( ! empty( $update_params ) ) {
			foreach ( $update_params as $key => $value ) {
				$link[ $key ] = sanitize_text_field( $value );
			}

			$updated = US()->db->links->update( $id, $link );

			if ( false === $updated ) {
				return $this->rest_server_error( __( 'Failed to update link.', 'url-shortify' ) );
			}
		}

		// null = don't touch, array = set (empty array clears all)
		if ( is_array( $group_ids ) ) {
			US()->db->links_groups->add_link_to_groups( $id, array_map( 'absint', $group_ids ) );
		}

		if ( is_array( $tag_ids ) ) {
			US()->db->links_tags->add_link_to_tags( $id, array_map( 'absint', $tag_ids ) );
		}

		$link = US()->db->links->get_by_id( $id );

		$link['group_ids'] = array_map( 'absint', US()->db->links_groups->get_group_ids_by_link_id( $id ) );
		$link['tag_ids']   = array_map( 'absint', US()->db->links_tags->get_tag_ids_by_link_id( $id ) );
		$link['short_url'] = Helper::get_short_link( $link['slug'], $link );

		return new \WP_REST_Response(
			[
				'success' => true,
				'data'    => $link,
			],
			200
		);
	}

	/**
	 * Delete a link.
	 *
	 * @since 1.13.1
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 *
	 * @OA\Delete(
	 *     path="/links/{id}",
	 *     operationId="deleteLink",
	 *     tags={"Links"},
	 *     summary="Delete a link",
	 *     description="Permanently deletes a short link.",
	 *     security={{"basicAuth": {}}, {"apiKeyHeader": {}, "apiKeyHeaderSecret": {}}},
	 *     @OA\Parameter(
	 *         name="id",
	 *         in="path",
	 *         required=true,
	 *         description="Unique identifier of the link.",
	 *         @OA\Schema(type="integer", example=1)
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Link deleted successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array", @OA\Items(), example={})
	 *         )
	 *     ),
	 *     @OA\Response(response=400, description="Bad request", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=404, description="Link not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=500, description="Server error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
	 * )
	 */
	public function delete_item( $request ) {
		$params = $request->get_params();
		$id     = absint( Helper::get_data( $params, 'id', 0 ) );

		if ( empty( $id ) ) {
			return $this->rest_bad_request_error( __( 'Invalid link ID.', 'url-shortify' ) );
		}

		$link = US()->db->links->get_by_id( $id );

		if ( empty( $link ) ) {
			return $this->rest_not_found_error( __( 'Link not found.', 'url-shortify' ) );
		}

		US()->db->links->delete( $id );

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
	 * Check permissions for getting a single link.
	 *
	 * @since 1.13.1
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return bool
	 */
	public function get_item_permissions_check( $request ) {
		return apply_filters( 'url_shortify/api/links_get_item_permissions_check', US()->access->can( 'manage_links' ) );
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
}
