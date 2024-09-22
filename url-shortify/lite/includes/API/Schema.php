<?php

namespace KaizenCoders\URL_Shortify\API;


trait Schema {

	/**
	 * Links Schema
	 *
	 * @since 1.9.5
	 * @return array[]
	 *
	 */
	public function links_schema() {
		return [
			'id' => [
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			],

			'slug' => [
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],

			'name' => [
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],

			'url' => [
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
			],

			'description' => [
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],

			'nofollow' => [
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			],

			'track_me' => [
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			],

			'sponsored' => [
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			],

			'params_forwarding' => [
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			],

			'params_structure' => [
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],

			'redirect_type' => [
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],

			'status' => [
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			],

			'type' => [
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],

			'type_id' => [
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			],

			'password' => [
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],

			'expires_at' => [
				'type'   => 'string',
				'format' => 'date-time',
			],

			'cpt_id' => [
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			],

			'cpt_type' => [
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],

			'rules' => [
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],

			'created_at' => [
				'type'   => 'string',
				'format' => 'date-time',
			],

			'created_by_id' => [
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			],

			'updated_at' => [
				'type'   => 'string',
				'format' => 'date-time',
			],

			'updated_by_id' => [
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			],
		];
	}

	/**
	 * Create Link schema.
	 *
	 * @since 1.7.5
	 * @return array[]
	 *
	 */
	public function create_link_schema() {
		return [
			'url' => [
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'required'          => true,
			],
		];
	}

}