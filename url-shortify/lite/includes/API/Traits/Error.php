<?php

namespace KaizenCoders\URL_Shortify\API\Traits;

trait Error {

	/**
	 * Return a WP_Error with proper code, message and status for unauthorized requests.
	 *
	 * @since 1.9.5
	 *
	 * @param $check_authenticated
	 *
	 * @param $message
	 *
	 * @return \WP_Error
	 *
	 */
	function rest_authorization_required_error( $message = '', $check_authenticated = true ) {
		if ( $check_authenticated && is_user_logged_in() ) {
			// 403.
			$error_code = 'rest_forbidden_request';
			$_message   = __( 'You are not authorized to perform this request.', 'url-shortify' );
			$status     = \WP_Http::FORBIDDEN; // 403.
		} else {
			// 401.
			$error_code = 'rest_unauthorized_request';
			$_message   = __( 'The API credentials were invalid.', 'url-shortify' );
			$status     = \WP_Http::UNAUTHORIZED; // 401.
		}

		$message = ! $message ? $_message : $message;

		return new \WP_Error( $error_code, $message, [ 'status' => $status ] );
	}

	/**
	 * Return a WP_Error with proper code, message and status for invalid or malformed request syntax.
	 *
	 * @since 1.9.5
	 *
	 * @param $message
	 *
	 * @return \WP_Error
	 */
	function rest_bad_request_error( $message = '' ) {
		$message = ! $message ? __( 'Invalid or malformed request syntax.', 'url-shortify' ) : $message;

		return new \WP_Error( 'rest_bad_request', $message, [ 'status' => \WP_Http::BAD_REQUEST ] ); // 400.
	}

	/**
	 * Return a WP_Error with proper code, message and status for not found resources.
	 *
	 * @since 1.9.5
	 *
	 * @param $message
	 *
	 * @return \WP_Error
	 *
	 */
	function rest_not_found_error( $message = '' ) {
		$message = ! $message ? __( 'The requested resource could not be found.', 'url-shortify' ) : $message;

		return new \WP_Error( 'rest_not_found', $message, [ 'status' => \WP_Http::NOT_FOUND ] ); // 404.
	}

	/**
	 * Return a WP_Error for a 500 Internal Server Error.
	 *
	 * @since 1.9.5
	 *
	 * @param $message
	 *
	 * @return \WP_Error
	 *
	 */
	function rest_server_error( $message = '' ) {
		$message = ! $message ? __( 'Internal Server Error.', 'url-shortify' ) : $message;

		return new \WP_Error( 'rest_server_error', $message, [ 'status' => \WP_Http::INTERNAL_SERVER_ERROR ] ); // 500.
	}

	/**
	 * Checks whether or not the passed object is a 401 (permission) or 403 (authorization) error.
	 *
	 * @since 1.9.5
	 *
	 * @param $wp_error
	 *
	 * @return bool
	 *
	 */
	function rest_is_authorization_required_error( $wp_error ) {
		return ! empty( array_intersect( $this->rest_get_all_error_statuses( $wp_error ),
			[ \WP_Http::FORBIDDEN, \WP_Http::UNAUTHORIZED ] ) ); // 403, 401.
	}

	/**
	 * Checks whether or not the passed object is a 400 bad request error.
	 *
	 * @since 1.9.5
	 *
	 * @param $wp_error
	 *
	 * @return bool
	 *
	 */
	function rest_is_bad_request_error( $wp_error ) {
		return in_array( \WP_Http::BAD_REQUEST, $this->rest_get_all_error_statuses( $wp_error ), true ); // 400.
	}

	/**
	 * Checks whether or not the passed object is a 404 not found error.
	 *
	 * @since 1.9.5
	 *
	 * @param $wp_error
	 *
	 * @return bool
	 *
	 */
	function rest_is_not_found_error( $wp_error ) {
		return in_array( \WP_Http::NOT_FOUND, $this->rest_get_all_error_statuses( $wp_error ), true ); // 404.
	}

	/**
	 * Checks whether the passed object is a 500 internal server error.
	 *
	 * @since 1.9.5
	 *
	 * @param  \WP_Error  $wp_error  The WP_Error object to check.
	 *
	 * @return boolean
	 *
	 */
	function rest_is_server_error( $wp_error ) {
		return in_array( \WP_Http::INTERNAL_SERVER_ERROR, $this->rest_get_all_error_statuses( $wp_error ),
			true ); // 500.
	}

	/**
	 * Returns all the error statuses of a WP_Error.
	 *
	 * @since 1.9.5
	 *
	 * @param  \WP_Error  $wp_error  The WP_Error object.
	 *
	 * @return int[]
	 */
	function rest_get_all_error_statuses( $wp_error ) {
		$statuses = [];

		if ( is_wp_error( $wp_error ) && ! empty( $wp_error->has_errors() ) ) {
			/**
			 * The method `get_all_error_data()` has been introduced in wp 5.6.0.
			 * TODO: remove bw compatibility when min wp version will be raised above 5.6.0.
			 */
			global $wp_version;
			$func = ( version_compare( $wp_version, 5.6, '>=' ) ) ? 'get_all_error_data' : 'get_error_data';

			foreach ( $wp_error->get_error_codes() as $code ) {
				$status = $wp_error->{$func}( $code );
				$status = 'get_error_data' === $func ? [ $status ] : $status;
				/**
				 * Use native `array_column()` in place of `wp_list_pluck()` as:
				 * 1) `$status` is for sure an array (and not possibly an object);
				 * 2) `wp_list_pluck()` raises an error if the key ('status' in this case) is not found.
				 */
				$statuses = array_merge( $statuses, array_column( $status, 'status' ) );
			}
			$statuses = array_filter( array_unique( $statuses ) );
		}

		return $statuses;

	}



}
