<?php

/**
 * The Ajax functionality of the plugin.
 *
 * @link       https://kaizencoders.com
 * @since      1.0.0
 *
 * @package    KaizenCoders\URL_Shortify
 * @subpackage Ajax
 */

namespace KaizenCoders\URL_Shortify;

use KaizenCoders\URL_Shortify\Admin\Controllers\ImportController;
use KaizenCoders\URL_Shortify\Admin\Controllers\LinksController;
use KaizenCoders\URL_Shortify\Admin\DB\Links;

/**
 * Class Ajax
 *
 * Handle Ajax request
 *
 * @since   1.1.3
 * @package KaizenCoders\URL_Shortify
 *
 */
class Ajax {
	/**
	 * Init
	 *
	 * @since 1.1.3
	 */
	public function init() {
		add_action( 'wp_ajax_us_handle_request', [ $this, 'handle_request' ] );
		add_action( 'wp_ajax_nopriv_us_handle_request', [ $this, 'handle_request' ] );
	}

	/**
	 * Get accessible commands.
	 *
	 * @since 1.5.12
	 * @return mixed|void
	 *
	 */
	public function get_accessible_commands() {
		$accessible_commands = [
			'create_short_link',
		];

		return apply_filters( 'kc_us_accessible_commands', $accessible_commands );
	}

	/**
	 * Handle Ajax Request
	 *
	 * @since 1.1.3
	 */
	public function handle_request() {

		$params = Helper::get_request_data( '', '', false );

		if ( empty( $params ) || empty( $params['cmd'] ) ) {
			return;
		}

		check_ajax_referer( KC_US_AJAX_SECURITY, 'security' );

		$cmd = Helper::get_data( $params, 'cmd', '' );

		$ajax = US()->is_pro() ? new \KaizenCoders\URL_Shortify\PRO\Ajax() : $this;

		if ( in_array( $cmd, $this->get_accessible_commands() ) && is_callable( [ $ajax, $cmd ] ) ) {
			$ajax->$cmd( $params );
		}
	}

	/**
	 * Create Short Link
	 *
	 * @since 1.1.3
	 *
	 * @param array $data
	 *
	 */
	public function create_short_link( $data = [] ) {

		$link_controller = new LinksController();

		$response = $link_controller->create( $data );

		wp_send_json( $response );
	}
}
