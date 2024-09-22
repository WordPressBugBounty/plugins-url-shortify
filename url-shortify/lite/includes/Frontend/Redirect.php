<?php

namespace KaizenCoders\URL_Shortify\Frontend;

use KaizenCoders\URL_Shortify\Admin\Click;
use KaizenCoders\URL_Shortify\Helper;

class Redirect {

	/**
	 * @since 1.0.0
	 */
	public function init() {
		if ( $this->is_valid_request() ) {
			add_action( 'init', [ $this, 'redirect' ], 2 );
		}
	}

	/**
	 * Validate request.
	 *
	 * @since 1.5.13
	 * @return bool
	 *
	 */
	public function is_valid_request() {
		return ( isset( $_SERVER['REQUEST_METHOD'] ) &&
		         'GET' === $_SERVER['REQUEST_METHOD'] &&
		         ! US()->request->is_admin_backend() &&
		         ! US()->request->is_ajax() &&
		         ! US()->request->is_cron() &&
		         ! US()->request->is_rest() &&
		         ! US()->request->is_cli()
		);
	}

	/**
	 * Handle Redirection
	 *
	 * @since 1.0.0
	 */
	public function redirect() {
		// Remove the trailing slash if there is one.
		$request_uri = preg_replace( '#/(\?.*)?$#', '$1', rawurldecode( $_SERVER['REQUEST_URI'] ) );

		$link_data = Helper::is_us_link( $request_uri, false );

		if ( $link_data ) {
			//TODO: Handle params.

			if ( ! US()->is_qr_request() ) {
				$params = $_GET;

				if ( $this->can_redirect( $link_data ) ) {
					do_action( 'kc_us_before_redirect', $link_data );

					// $this->track_click( $link_data );

					$this->do_redirect( $link_data, $params );
				}
			}
		}
	}

	/**
	 * Track click
	 *
	 * @since 1.2.0
	 *
	 * @param  array  $link_data
	 */
	public function track_click( $link_data = [] ) {
		$track_me = Helper::get_data( $link_data, 'track_me', 0 );

		if ( $track_me ) {
			$link_id = Helper::get_data( $link_data, 'id', 0 );
			$slug    = Helper::get_data( $link_data, 'slug', '' );

			if ( $link_id ) {
				$click = new Click( $link_id, $slug );

				$track_me = apply_filters( 'kc_us_can_track_click', $track_me, $click );

				if ( $track_me ) {
					$click->track();
				}
			}
		}
	}

	/**
	 * Redirect to main URL
	 *
	 * @since 1.0.0
	 *
	 * @modified 1.0.1
	 *
	 * @param  array  $params
	 *
	 * @param  array  $link_data
	 */
	public function do_redirect( $link_data = [], $params = [] ) {
		/* Track Click */
		$track_me = Helper::get_data( $link_data, 'track_me', 0 );

		$click_data = [];
		$link_id = 0;
		if ( $track_me ) {
			$link_id = (int) Helper::get_data( $link_data, 'id', 0 );
			$slug    = Helper::get_data( $link_data, 'slug', '' );

			if ( $link_id ) {
				$click = new Click( $link_id, $slug, $link_data );

				$click_data = $click->get_prepared_data();

				$track_me = apply_filters( 'kc_us_can_track_click', $track_me, $click );
			}
		}

		$url = Helper::get_data( $link_data, 'url', '' );

		// Get the target URL based on all the considerations. Dynamic Redirection, UTM Params etc.
		$url = $this->get_the_target_url( $url, $link_data, $click_data );

		if ( $link_id && $track_me ) {
			$click_id = $click->track();

			if ( ! empty( $url ) ) {
				$click_data = [
					'click_id' => $click_id,
					'link_id'  => $link_id,
					'url'      => $url,
				];

				do_action( 'kc_us_track_click', $click_data, $link_data );
			}
		}

		$redirect_type     = Helper::get_data( $link_data, 'redirect_type', '' );
		$params_forwarding = Helper::get_data( $link_data, 'params_forwarding', 0 );
		$nofollow          = Helper::get_data( $link_data, 'nofollow', 0 );
		$sponsored         = Helper::get_data( $link_data, 'sponsored', 0 );

		if ( ! empty( $url ) ) {
			// Handle Params Forwarding.
			if ( ! empty( $params_forwarding ) && Helper::is_forechable( $params ) ) {

				$param_string = '';

				$params = explode( '?', $_SERVER['REQUEST_URI'] );

				if ( isset( $params[1] ) ) {
					$param_string = ( preg_match( '#\?#', $url ) ? '&' : '?' ) . $params[1];
				}

				$param_string = preg_replace( [ '#%5B#i', '#%5D#i' ], [ '[', ']' ], $param_string );

				$param_string = apply_filters( 'kc_us_redirect_params', $param_string );

				$url .= $param_string;
			}

			$tags = [];

			// Handle nofollow, noindex.
			if ( ! empty( $nofollow ) ) {
				$tags[] = 'noindex';
				$tags[] = 'nofollow';
			}

			// Handle Sponsored.
			if ( ! empty( $sponsored ) ) {
				$tags[] = 'sponsored';
			}

			if ( ! empty( $tags ) ) {
				header( 'X-Robots-Tag: ' . implode( ', ', $tags ), true );
			}

			header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
			header( 'Cache-Control: post-check=0, pre-check=0', false );
			header( 'Pragma: no-cache' );
			header( 'Expires: Mon, 10 Oct 1975 08:09:15 GMT' );
			header( 'X-Redirect-Powered-By: url-shortify ' . KC_US_PLUGIN_VERSION . ' https://kaizencoders.com' );

			if ( ! function_exists( 'wp_redirect' ) ) {
				require_once( ABSPATH . WPINC . '/pluggable.php' );
			}

			$url = $this->get_the_final_url( $url, $link_data, $click_data );

			$url = apply_filters( 'kc_us_filter_url_before_redirect', $url );

			switch ( $redirect_type ) {
				case '301':
					wp_redirect( "$url", 301 );
					exit;
					break;
				case '307':
					wp_redirect( "$url", 307 );
					exit;
					break;
				case '302':
					wp_redirect( "$url", 302 );
					exit;
					break;
				default:
					if ( US()->is_pro() ) {
						do_action( 'kc_us_pro_redirect', $redirect_type, $url, $link_data, $params );
					} else {
						wp_redirect( "$url", 302 );
						exit;
					}
					break;
			}

		}
	}

	/**
	 * @param $url
	 * @param $link_data
	 * @param $click_data
	 *
	 * @return mixed|void
	 */
	public function get_the_final_url( $url, $link_data, $click_data ) {
		return apply_filters( 'kc_us_get_the_final_url', $url, $link_data, $click_data );
	}

	/**
	 * Get the target URL.
	 *
	 * @since 1.9.1
	 *
	 * @param $link_data
	 * @param $click_data
	 *
	 * @param $url
	 *
	 * @return string
	 *
	 */
	public function get_the_target_url( $url, $link_data, $click_data ) {
		return apply_filters( 'kc_us_get_the_target_url', $url, $link_data, $click_data );
	}

	/**
	 * Should we redirect?
	 *
	 * @since 1.1.9
	 *
	 * @param  array  $link_data
	 *
	 * @return bool
	 *
	 */
	public function can_redirect( $link_data = [] ) {

		if ( US()->is_pro() ) {
			return apply_filters( 'kc_us_can_redirect', true, $link_data );
		} else {
			return Helper::is_request_from_same_domain();
		}

	}

}
