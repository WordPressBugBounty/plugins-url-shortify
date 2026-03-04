<?php

namespace KaizenCoders\URL_Shortify\API;

class Init {

	/**
	 * Initialize REST API controllers.
	 *
	 * @since 1.13.1
	 */
	public function init() {
		$classes = [
			'KaizenCoders\URL_Shortify\API\V1\LinksRestController',
			'KaizenCoders\URL_Shortify\API\V1\GroupsRestController',
		];

		foreach ( $classes as $class ) {
			if ( class_exists( $class ) ) {
				$object = new $class();
				$object->init();
			}
		}

		// Hook CORS handling before routes are registered so OPTIONS preflight
		// responses include our custom headers (needed for Swagger UI / browser clients).
		add_action( 'rest_api_init', [ $this, 'register_cors' ], 5 );
	}

	/**
	 * Register CORS hooks for url-shortify/v1 routes.
	 *
	 * Browsers send an OPTIONS preflight before any cross-origin request that uses
	 * a custom header (e.g. X-URL-SHORTIFY-CONSUMER-KEY) or the Authorization header.
	 * WordPress's built-in allow-list doesn't include our custom header, so we extend
	 * it here and also emit an explicit CORS header block on every matching request.
	 *
	 * @since 1.13.1
	 */
	public function register_cors() {
		// Add our custom auth header to the preflight allow-list.
		add_filter( 'rest_allowed_cors_headers', [ $this, 'add_cors_headers' ] );

		// Emit CORS headers on every request to a url-shortify route.
		add_filter( 'rest_pre_serve_request', [ $this, 'send_cors_headers' ], 5 );
	}

	/**
	 * Add URL Shortify-specific headers to the list of allowed CORS headers.
	 *
	 * This list appears in the OPTIONS preflight response as
	 * Access-Control-Allow-Headers, which the browser checks before
	 * sending the actual request.
	 *
	 * @since 1.13.1
	 *
	 * @param string[] $allow_headers Existing allowed header names.
	 *
	 * @return string[]
	 */
	public function add_cors_headers( $allow_headers ) {
		$allow_headers[] = 'X-URL-SHORTIFY-CONSUMER-KEY';
		$allow_headers[] = 'X-URL-SHORTIFY-CONSUMER-SECRET';
		return $allow_headers;
	}

	/**
	 * Send CORS response headers for url-shortify/v1 requests.
	 *
	 * Runs on `rest_pre_serve_request` (priority 5, before the default
	 * rest_send_cors_headers at priority 10).  We only act on requests
	 * to our own namespace so other REST routes are unaffected.
	 *
	 * @since 1.13.1
	 *
	 * @param mixed $value Passed through unchanged.
	 *
	 * @return mixed
	 */
	public function send_cors_headers( $value ) {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';

		if ( false === strpos( $request_uri, '/url-shortify/' ) ) {
			return $value;
		}

		$origin = get_http_origin();

		if ( ! $origin ) {
			return $value;
		}

		header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $origin ) );
		header( 'Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT, PATCH, DELETE' );
		header( 'Access-Control-Allow-Credentials: true' );
		header( 'Access-Control-Allow-Headers: Authorization, X-URL-SHORTIFY-CONSUMER-KEY, X-URL-SHORTIFY-CONSUMER-SECRET, Content-Type, Accept' );
		header( 'Vary: Origin' );

		return $value;
	}
}
