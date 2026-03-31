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

use KaizenCoders\URL_Shortify\Admin\Controllers\ClicksController;
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
			'get_dashboard_clicks_page',
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

//		if ( ! current_user_can( 'edit_posts' ) ) {
//			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'url-shortify' ) ) );
//		}

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

	/**
	 * Server-side callback for DataTables click history.
	 *
	 * @param array $params
	 */
	public function get_dashboard_clicks_page( $params = [] ) {
		try {
			if ( ! current_user_can( 'read' ) ) {
				wp_send_json_error( [ 'message' => __( 'Permission denied.', 'url-shortify' ) ] );
			}

			$draw = absint( Helper::get_data( $params, 'draw', 0 ) );

			$start  = max( 0, absint( Helper::get_data( $params, 'start', 0 ) ) );
			$length = max( 1, min( 100, absint( Helper::get_data( $params, 'length', 10 ) ) ) );

			$order        = Helper::get_data( $params, 'order', [] );
			$order_index  = isset( $order[0]['column'] ) ? absint( $order[0]['column'] ) : 5;
			$order_dir    = isset( $order[0]['dir'] ) ? $order[0]['dir'] : 'desc';
			$search_value = Helper::get_data( $params, 'search', [] );
			$search_term  = Helper::get_data( $search_value, 'value', '' );

			$days = absint( Helper::get_data( $params, 'days', 0 ) );
			if ( 0 === $days ) {
				$days = apply_filters( 'kc_us_clicks_info_for_days', 365 );
			}

			$link_id = absint( Helper::get_data( $params, 'link_id', 0 ) );
			$link_ids = Helper::get_data( $params, 'link_ids', '' );

			if ( $link_id > 0 ) {
				$link_ids = [ $link_id ];
			} elseif ( is_string( $link_ids ) && ! empty( $link_ids ) ) {
				$link_ids = array_filter( array_map( 'absint', explode( ',', $link_ids ) ) );
			}

			$column_map = [
				0 => 'ip',
				1 => 'uri',
				2 => 'name',
				3 => 'host',
				4 => 'referer',
				5 => 'created_at',
				6 => 'created_at',
			];

			$order_by = isset( $column_map[ $order_index ] ) ? $column_map[ $order_index ] : 'created_at';

			// Default to last 12 months so dashboard table has data; site owners can override.
			$days = apply_filters( 'kc_us_clicks_info_for_days', 365 );

			$total_records    = US()->db->clicks->count_clicks_for_dashboard( $days, '', $link_ids );
			$filtered_records = US()->db->clicks->count_clicks_for_dashboard( $days, $search_term, $link_ids );
			$items            = US()->db->clicks->get_clicks_for_dashboard( $days, $length, $start, $search_term, $order_by, $order_dir, $link_ids );

			$columns       = ClicksController::get_table_columns();
			$click_history = new ClicksController();
			$click_history->set_columns( $columns );

			$data = [];
			if ( ! empty( $items ) ) {
				foreach ( $items as $click ) {
					$data[] = $click_history->get_row_cells( $click );
				}
			}

			wp_send_json_success( [
				'draw'            => $draw,
				'recordsTotal'    => $total_records,
				'recordsFiltered' => $filtered_records,
				'data'            => $data,
			] );
		} catch ( \Throwable $e ) {
			error_log( '[url-shortify] get_dashboard_clicks_page failed: ' . $e->getMessage() );
			$message = __( 'Failed to load clicks. Please try again.', 'url-shortify' );
			if ( current_user_can( 'manage_options' ) ) {
				$message .= ' [' . $e->getMessage() . ']';
			}
			wp_send_json_error( [ 'message' => $message ] );
		}
	}

	/**
	 * @return void
	 */
	public function handle_plugin_management() {
		check_ajax_referer( 'url-shortify-plugin-management', 'nonce' );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'url-shortify' ) ] );
		}

		$action = sanitize_text_field( wp_unslash( $_POST['plugin_action'] ) );
		$plugin = sanitize_text_field( wp_unslash( $_POST['plugin'] ) );
		$slug   = sanitize_text_field( wp_unslash( $_POST['slug'] ) );

		if ( ! preg_match( '/^[a-z0-9-]+$/', $slug ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid plugin slug.', 'url-shortify' ) ) );
		}

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
