<?php

namespace KaizenCoders\URL_Shortify\API;

use KaizenCoders\URL_Shortify\API\Traits\Error;
use KaizenCoders\URL_Shortify\Helper;

/**
 * REST API Authentication.
 *
 * @sinc 1.9.5
 */
class Authentication {

	use Error;

	/**
	 * Authenticated API key for the current request.
	 *
	 * @var array
	 */
	protected $api_key = null;

	/**
	 * Authentication error object.
	 *
	 * @var \WP_Error
	 */
	protected $error = null;

	/**
	 * Constructor
	 *
	 * @since 1.9.5
	 */
	public function init() {
		/**
		 * Disable URL Shortify REST API Key authentication in favor of a custom authentication solution.
		 *
		 * @param  bool  $use_auth  When true, URL Shortify Basic (or header) authorization will be used.
		 */
		$use_auth = apply_filters( 'kc_us_rest_use_authentication', true );
		if ( $use_auth ) {
			add_filter( 'determine_current_user', [ $this, 'authenticate' ], 15 );
			add_filter( 'rest_authentication_errors', [ $this, 'check_authentication_error' ], 15 );
			add_filter( 'rest_post_dispatch', [ $this, 'send_unauthorized_headers' ], 50 );
			add_filter( 'rest_pre_dispatch', [ $this, 'check_permissions' ], 10, 3 );
		}

	}

	/**
	 * Authenticate an API Request
	 *
	 * @param  int|false  $user_id  WP_User ID of an already authenticated user or false.
	 *
	 * @return int|false
	 */
	public function authenticate( $user_id ) {

		if ( ! empty( $user_id ) || ! is_ssl() || ! $this->is_rest_request() ) {
			return $user_id;
		}

		$credentials = $this->locate_credentials();

		if ( ! $credentials ) {
			return false;
		}
		$key = $this->find_key( $credentials['key'] );

		if ( ! $key ) {
			return false;
		}

		if ( ! hash_equals( $key['consumer_secret'], $credentials['secret'] ) ) {
			$this->set_error( $this->rest_authorization_required_error( '', false ) );

			return false;
		}

		$this->api_key = $key;

		$user_id = $key['user_id'];

		do_action( 'kc_us_rest_basic_auth_success', $user_id );

		return $user_id;
	}

	/**
	 * Check for authentication error.
	 *
	 * @since 1.9.5
	 *
	 * @param  \WP_Error|null|bool  $error  Existing error data.
	 *
	 * @return \WP_Error|null|bool
	 * @link https://developer.wordpress.org/reference/hooks/rest_authentication_errors/
	 *
	 */
	public function check_authentication_error( $error ) {

		// Pass through existing errors.
		if ( ! empty( $error ) ) {
			return $error;
		}

		return $this->get_error();
	}

	/**
	 * Check if the API Key can perform the request.
	 *
	 * @since 1.9.5
	 *
	 * @param  mixed  $result  Response to replace the requested version with.
	 * @param  $server  Server instance.
	 * @param  $request   used to generate the response.
	 *
	 * @return mixed
	 */
	public function check_permissions( $result, $server, $request ) {

		if ( $this->api_key ) {

			$allowed = Helper::has_permissions( $this->api_key, $request->get_method() );

			if ( ! $allowed ) {
				return $this->rest_authorization_required_error( '', false );
			}

			// Update the API key's last access time.
			US()->db->api_keys->update( $this->api_key['id'], [ 'last_access' => current_time( 'mysql' ) ] );
		}

		return $result;
	}

	/**
	 * Find a key via unhashed consumer key
	 *
	 * @since 1.9.5
	 *
	 * @param  string  $consumer_key  An unhashed consumer key.
	 *
	 * @return array|false
	 */
	protected function find_key( $consumer_key ) {
		global $wpdb;

		$consumer_key = Helper::rest_api_hash( $consumer_key );

		$key = US()->db->api_keys->get_by( 'consumer_key', $consumer_key );

		if ( ! empty( $key ) ) {
			return $key;
		}

		return false;
	}

	/**
	 * Locate credentials in the $_SERVER superglobal.
	 *
	 * @since 1.9.5
	 *
	 * @param  string  $key_var  Variable name for the consumer key.
	 * @param  string  $secret_var  Variable name for the consumer secret.
	 *
	 * @return array|false
	 */
	private function get_credentials( $key_var, $secret_var ) {
		$key    = isset( $_SERVER[ $key_var ] ) ? sanitize_text_field( filter_var( wp_unslash( $_SERVER[ $key_var ] ) ) ) : null;
		$secret = isset( $_SERVER[ $secret_var ] ) ? sanitize_text_field( filter_var( wp_unslash( $_SERVER[ $secret_var ] ) ) ) : null;

		if ( ! $key || ! $secret ) {
			return false;
		}

		return compact( 'key', 'secret' );
	}

	/**
	 * Retrieve the auth error object.
	 *
	 * @since 1.9.5
	 *
	 * @return \WP_Error|null
	 */
	protected function get_error() {
		return $this->error;
	}

	/**
	 * Determine if the request is a request to a URL Shortify REST API endpoint.
	 *
	 * @since 1.9.5
	 *
	 * @return bool
	 */
	protected function is_rest_request() {
		$request = isset( $_SERVER['REQUEST_URI'] ) ? filter_var( wp_unslash( $_SERVER['REQUEST_URI'] ),
			FILTER_SANITIZE_URL ) : null;

		if ( empty( $request ) ) {
			return false;
		}

		$request = esc_url_raw( wp_unslash( $request ) );
		$prefix  = trailingslashit( rest_get_url_prefix() );

		$core = ( false !== strpos( $request, $prefix . 'url-shortify/' ) );

		// Allow 3rd parties to use core auth.
		$external = ( false !== strpos( $request, $prefix . 'url-shortify-' ) );

		return apply_filters( 'kc_us_is_rest_request', $core || $external, $request );

	}

	/**
	 * Get api credentials from headers and then basic auth.
	 *
	 * @since 1.9.5
	 *
	 * @return array|false
	 */
	protected function locate_credentials() {

		// Attempt to get credentials from headers.
		$credentials = $this->get_credentials( 'HTTP_X_URL_SHORTIFY_CONSUMER_KEY',
			'HTTP_X_URL_SHORTIFY_CONSUMER_SECRET' );

		if ( $credentials ) {
			return $credentials;
		}

		// Attempt to get credentials from basic auth.
		$credentials = $this->get_credentials( 'PHP_AUTH_USER', 'PHP_AUTH_PW' );
		if ( $credentials ) {
			return $credentials;
		}


		return false;
	}

	/**
	 * Return a WWW-Authenticate header error message when incorrect creds are supplied
	 *
	 * @since 1.9.5
	 *
	 * @param  \WP_REST_Response  $response  Current response being served.
	 *
	 * @return \WP_REST_Response
	 *
	 */
	public function send_unauthorized_headers( $response ) {

		if ( is_wp_error( $this->get_error() ) ) {
			$auth_message = __( 'URL Shortify REST API', 'url-shortify' );
			$response->header( 'WWW-Authenticate', 'Basic realm="' . $auth_message . '"', true );
		}

		return $response;
	}

	/**
	 * Set authentication error object.
	 *
	 * @since 1.0.0-beta.1
	 *
	 * @param  \WP_Error|null  $err  Error object or null to clear an error.
	 *
	 * @return void
	 */
	protected function set_error( $err ) {
		$this->error = $err;
	}

}