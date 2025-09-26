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
		add_action( 'wp_ajax_url_shortify_manage_plugin', [ $this, 'handle_plugin_management' ] );
	}

	/**
	 * Get accessible commands.
	 *
	 * @return mixed|void
	 *
	 * @since 1.5.12
	 */
	public function get_accessible_commands() {
		$accessible_commands = [
			'create_short_link',
			'handle_plugin_management',
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
	 * @param  array  $data
	 *
	 * @since 1.1.3
	 *
	 */
	public function create_short_link( $data = [] ) {
		$link_controller = new LinksController();

		$response = $link_controller->create( $data );

		wp_send_json( $response );
	}

	public function handle_plugin_management() {
		check_ajax_referer( 'url-shortify-plugin-management', 'nonce' );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( [ 'message' => 'Permission denied' ] );
		}

		$action = sanitize_text_field( $_POST['plugin_action'] );
		$plugin = sanitize_text_field( $_POST['plugin'] );
		$slug   = sanitize_text_field( $_POST['slug'] );

		switch ( $action ) {
			case 'install':
				include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
				include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

				$api = plugins_api( 'plugin_information', [ 'slug' => $slug ] );
				if ( is_wp_error( $api ) ) {
					wp_send_json_error( [ 'message' => $api->get_error_message() ] );
				}

				$upgrader = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );
				$result   = $upgrader->install( $api->download_link );

				if ( is_wp_error( $result ) ) {
					wp_send_json_error( [ 'message' => $result->get_error_message() ] );
				}
				break;

			case 'activate':
				$result = activate_plugin( $plugin );
				if ( is_wp_error( $result ) ) {
					wp_send_json_error( [ 'message' => $result->get_error_message() ] );
				}
				break;

			case 'deactivate':
				deactivate_plugins( [ $plugin ] );
				break;

			default:
				wp_send_json_error( [ 'message' => 'Invalid action' ] );
		}

		wp_send_json_success();
	}
}
