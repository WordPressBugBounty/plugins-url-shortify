<?php

namespace KaizenCoders\URL_Shortify\API\V1;

use KaizenCoders\URL_Shortify\API\Schema;
use KaizenCoders\URL_Shortify\API\Traits\Error;
use KaizenCoders\URL_Shortify\Helper;

class GroupsRestController extends \WP_REST_Controller {

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
	protected $rest_base = 'groups';

	/**
	 * Initialize.
	 *
	 * @since 1.13.1
	 */
	public function init() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @since 1.13.1
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
				'args'                => $this->create_group_schema(),
			],
		] );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)',
			[
				'args' => [
					'id' => [
						'description' => __( 'Unique identifier of the group.', 'url-shortify' ),
						'type'        => 'integer',
					],
				],
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'get_item_permissions_check' ],
				],
				[
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_item' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args'                => $this->update_group_schema(),
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
	 * Get all groups.
	 *
	 * @since 1.13.1
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 *
	 * @OA\Get(
	 *     path="/groups",
	 *     operationId="getGroups",
	 *     tags={"Groups"},
	 *     summary="List all groups",
	 *     description="Returns a paginated list of link groups including their link count.",
	 *     security={{"basicAuth": {}}, {"apiKeyHeader": {}, "apiKeyHeaderSecret": {}}},
	 *     @OA\Parameter(name="page", in="query", description="Current page of the collection.", @OA\Schema(type="integer", default=1, minimum=1)),
	 *     @OA\Parameter(name="per_page", in="query", description="Maximum number of items per page (1–100).", @OA\Schema(type="integer", default=20, minimum=1, maximum=100)),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Successful response",
	 *         headers={
	 *             @OA\Header(header="X-WP-Total", description="Total number of groups.", @OA\Schema(type="integer")),
	 *             @OA\Header(header="X-WP-TotalPages", description="Total number of pages.", @OA\Schema(type="integer"))
	 *         },
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Group"))
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

		$result = US()->db->groups->get_paginated( $per_page, $page );
		$groups = $result['items'];
		$total  = $result['total'];

		foreach ( $groups as &$group ) {
			$group['links_count'] = (int) US()->db->links_groups->count_by_group_id( $group['id'] );
		}

		$total_pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 1;

		$response = new \WP_REST_Response(
			[
				'success' => true,
				'data'    => $groups,
			],
			200
		);
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', $total_pages );

		return $response;
	}

	/**
	 * Get a single group.
	 *
	 * @since 1.13.1
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 *
	 * @OA\Get(
	 *     path="/groups/{id}",
	 *     operationId="getGroup",
	 *     tags={"Groups"},
	 *     summary="Get a single group",
	 *     description="Returns a single group by its ID, including its link count.",
	 *     security={{"basicAuth": {}}, {"apiKeyHeader": {}, "apiKeyHeaderSecret": {}}},
	 *     @OA\Parameter(
	 *         name="id",
	 *         in="path",
	 *         required=true,
	 *         description="Unique identifier of the group.",
	 *         @OA\Schema(type="integer", example=1)
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Successful response",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", ref="#/components/schemas/Group")
	 *         )
	 *     ),
	 *     @OA\Response(response=400, description="Bad request", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=404, description="Group not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=500, description="Server error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
	 * )
	 */
	public function get_item( $request ) {
		$id = absint( $request->get_param( 'id' ) );

		if ( empty( $id ) ) {
			return $this->rest_bad_request_error( __( 'Invalid group ID.', 'url-shortify' ) );
		}

		$group = US()->db->groups->get_by_id( $id );

		if ( empty( $group ) ) {
			return $this->rest_not_found_error( __( 'Group not found.', 'url-shortify' ) );
		}

		$group['links_count'] = (int) US()->db->links_groups->count_by_group_id( $id );

		return new \WP_REST_Response(
			[
				'success' => true,
				'data'    => $group,
			],
			200
		);
	}

	/**
	 * Create a group.
	 *
	 * @since 1.13.1
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 *
	 * @OA\Post(
	 *     path="/groups",
	 *     operationId="createGroup",
	 *     tags={"Groups"},
	 *     summary="Create a group",
	 *     description="Creates a new link group. The `name` field is required.",
	 *     security={{"basicAuth": {}}, {"apiKeyHeader": {}, "apiKeyHeaderSecret": {}}},
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\JsonContent(
	 *             required={"name"},
	 *             @OA\Property(property="name", type="string", description="Name of the group.", example="Marketing"),
	 *             @OA\Property(property="description", type="string", description="Optional description.", example="All marketing campaign links")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=201,
	 *         description="Group created successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", ref="#/components/schemas/Group")
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

		$name = Helper::get_data( $params, 'name', '' );

		if ( empty( $name ) ) {
			return $this->rest_bad_request_error( __( 'Group name is required.', 'url-shortify' ) );
		}

		$data = [
			'name'        => sanitize_text_field( $name ),
			'description' => sanitize_text_field( Helper::get_data( $params, 'description', '' ) ),
		];

		$form_data = US()->db->groups->prepare_form_data( $data );

		$id = US()->db->groups->insert( $form_data );

		if ( ! $id ) {
			return $this->rest_server_error( __( 'Failed to create group.', 'url-shortify' ) );
		}

		$group = US()->db->groups->get_by_id( $id );

		$group['links_count'] = 0;

		return new \WP_REST_Response(
			[
				'success' => true,
				'data'    => $group,
			],
			201
		);
	}

	/**
	 * Update a group.
	 *
	 * @since 1.13.1
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 *
	 * @OA\Put(
	 *     path="/groups/{id}",
	 *     operationId="updateGroup",
	 *     tags={"Groups"},
	 *     summary="Update a group",
	 *     description="Updates an existing group. At least one of `name` or `description` must be provided.",
	 *     security={{"basicAuth": {}}, {"apiKeyHeader": {}, "apiKeyHeaderSecret": {}}},
	 *     @OA\Parameter(
	 *         name="id",
	 *         in="path",
	 *         required=true,
	 *         description="Unique identifier of the group.",
	 *         @OA\Schema(type="integer", example=1)
	 *     ),
	 *     @OA\RequestBody(
	 *         @OA\JsonContent(
	 *             @OA\Property(property="name", type="string", description="New name for the group.", example="Social Media"),
	 *             @OA\Property(property="description", type="string", description="New description.", example="Social media campaign links")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Group updated successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", ref="#/components/schemas/Group")
	 *         )
	 *     ),
	 *     @OA\Response(response=400, description="Bad request", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=404, description="Group not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=500, description="Server error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
	 * )
	 */
	public function update_item( $request ) {
		$id     = absint( $request->get_param( 'id' ) );
		$params = $request->get_params();

		if ( empty( $id ) ) {
			return $this->rest_bad_request_error( __( 'Invalid group ID.', 'url-shortify' ) );
		}

		$group = US()->db->groups->get_by_id( $id );

		if ( empty( $group ) ) {
			return $this->rest_not_found_error( __( 'Group not found.', 'url-shortify' ) );
		}

		$data = [];

		if ( isset( $params['name'] ) ) {
			$data['name'] = $params['name'];
		}

		if ( isset( $params['description'] ) ) {
			$data['description'] = $params['description'];
		}

		if ( empty( $data ) ) {
			return $this->rest_bad_request_error( __( 'No data to update.', 'url-shortify' ) );
		}

		$form_data = US()->db->groups->prepare_form_data( $data, $id );

		$updated = US()->db->groups->update( $id, $form_data );

		if ( false === $updated ) {
			return $this->rest_server_error( __( 'Failed to update group.', 'url-shortify' ) );
		}

		$group = US()->db->groups->get_by_id( $id );

		$group['links_count'] = (int) US()->db->links_groups->count_by_group_id( $id );

		return new \WP_REST_Response(
			[
				'success' => true,
				'data'    => $group,
			],
			200
		);
	}

	/**
	 * Delete a group.
	 *
	 * @since 1.13.1
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 *
	 * @OA\Delete(
	 *     path="/groups/{id}",
	 *     operationId="deleteGroup",
	 *     tags={"Groups"},
	 *     summary="Delete a group",
	 *     description="Permanently deletes a group and removes all link-group associations for it.",
	 *     security={{"basicAuth": {}}, {"apiKeyHeader": {}, "apiKeyHeaderSecret": {}}},
	 *     @OA\Parameter(
	 *         name="id",
	 *         in="path",
	 *         required=true,
	 *         description="Unique identifier of the group.",
	 *         @OA\Schema(type="integer", example=1)
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Group deleted successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array", @OA\Items(), example={})
	 *         )
	 *     ),
	 *     @OA\Response(response=400, description="Bad request", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=404, description="Group not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
	 *     @OA\Response(response=500, description="Server error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
	 * )
	 */
	public function delete_item( $request ) {
		$id = absint( $request->get_param( 'id' ) );

		if ( empty( $id ) ) {
			return $this->rest_bad_request_error( __( 'Invalid group ID.', 'url-shortify' ) );
		}

		$group = US()->db->groups->get_by_id( $id );

		if ( empty( $group ) ) {
			return $this->rest_not_found_error( __( 'Group not found.', 'url-shortify' ) );
		}

		US()->db->links_groups->delete_links_by_group_id( $id );
		US()->db->groups->delete( $id );

		return new \WP_REST_Response(
			[
				'success' => true,
				'data'    => [],
			],
			200
		);
	}

	/**
	 * Check permissions for getting groups.
	 *
	 * @since 1.13.1
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return bool
	 */
	public function get_items_permissions_check( $request ) {
		return apply_filters( 'url_shortify/api/groups_get_items_permissions_check', US()->access->can( 'manage_groups' ) );
	}

	/**
	 * Check permissions for getting a single group.
	 *
	 * @since 1.13.1
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return bool
	 */
	public function get_item_permissions_check( $request ) {
		return apply_filters( 'url_shortify/api/groups_get_item_permissions_check', US()->access->can( 'manage_groups' ) );
	}

	/**
	 * Check permissions for creating a group.
	 *
	 * @since 1.13.1
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return bool
	 */
	public function create_item_permissions_check( $request ) {
		return apply_filters( 'url_shortify/api/groups_create_item_permissions_check', US()->access->can( 'manage_groups' ) );
	}

	/**
	 * Check permissions for updating a group.
	 *
	 * @since 1.13.1
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return bool
	 */
	public function update_item_permissions_check( $request ) {
		return apply_filters( 'url_shortify/api/groups_update_item_permissions_check', US()->access->can( 'manage_groups' ) );
	}

	/**
	 * Check permissions for deleting a group.
	 *
	 * @since 1.13.1
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return bool
	 */
	public function delete_item_permissions_check( $request ) {
		return apply_filters( 'url_shortify/api/groups_delete_item_permissions_check', US()->access->can( 'manage_groups' ) );
	}
}
