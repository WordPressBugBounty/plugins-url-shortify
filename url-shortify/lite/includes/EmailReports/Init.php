<?php

namespace KaizenCoders\URL_Shortify\EmailReports;

use KaizenCoders\URL_Shortify\Option;

class Init {
	const EMAIL_REPORT_HOOK = 'url_shortify_email_report';

	public function init() {
		add_action( self::EMAIL_REPORT_HOOK, [ $this, 'process_report' ] );
		add_action( 'init', [ $this, 'schedule_tasks' ] );
		// admin-post.php handler — outputs full HTML and exits cleanly.
		add_action( 'admin_post_kc_us_email_preview', [ $this, 'handle_preview' ] );
		// Admin AJAX handler for sending test emails.
		add_action( 'wp_ajax_kc_us_email_test', [ $this, 'handle_test_email_ajax' ] );
		// REST route kept for external/API usage.
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Schedule recurring tasks.
	 */
	public function schedule_tasks() {
		if ( ! as_next_scheduled_action( self::EMAIL_REPORT_HOOK ) ) {
			as_schedule_recurring_action( time(), 4 * HOUR_IN_SECONDS, self::EMAIL_REPORT_HOOK );
		}
	}

	/**
	 * Process the report sending.
	 */
	public function process_report() {
		if ( $this->can_send_now() ) {
			$this->send_report();
		}
	}

	/**
	 * Send the email report.
	 *
	 * @param bool        $is_preview     Whether this is a preview send.
	 * @param string|null $override_email Override recipient email address.
	 */
	public function send_report( $is_preview = false, $override_email = null ) {
		$settings  = $this->get_settings();
		$frequency = isset( $settings['frequency'] ) ? $settings['frequency'] : 'weekly';

		$report = new ReportGenerator();
		$email  = new EmailSender();

		$data = $report->generate( $frequency, $is_preview );
		$email->send( $data, $override_email );

		if ( ! $is_preview ) {
			Option::set( 'email_report_last_sent', current_time( 'timestamp' ) );
		}
	}

	/**
	 * Determine whether we should send the email report right now.
	 *
	 * @return bool
	 */
	public function can_send_now() {
		$settings = $this->get_settings();

		// Must be enabled.
		if ( empty( $settings['enabled'] ) || ! $settings['enabled'] ) {
			return false;
		}

		$now       = current_time( 'timestamp' );
		$frequency = isset( $settings['frequency'] ) ? $settings['frequency'] : 'weekly';
		$day       = isset( $settings['day'] ) ? (int) $settings['day'] : 1;
		$time_opt  = isset( $settings['time'] ) ? trim( (string) $settings['time'] ) : '14:00';

		$hour   = 14;
		$minute = 0;

		if ( preg_match( '/^(\d{1,2})(?::(\d{1,2}))?$/', $time_opt, $matches ) ) {
			$hour   = (int) $matches[1];
			$minute = isset( $matches[2] ) ? (int) $matches[2] : 0;
		}

		$hour   = min( 23, max( 0, $hour ) );
		$minute = min( 59, max( 0, $minute ) );

		$last_sent = (int) Option::get( 'email_report_last_sent', 0 );

		if ( 'daily' === $frequency ) {
			$scheduled_send = mktime(
				$hour,
				$minute,
				0,
				(int) date( 'n', $now ),
				(int) date( 'j', $now ),
				(int) date( 'Y', $now )
			);

			if ( $now < $scheduled_send ) {
				return false;
			}

			// Prevent sending more than once per calendar day.
			if ( $last_sent > 0 && date( 'Ymd', $last_sent ) === date( 'Ymd', $now ) ) {
				return false;
			}

			return true;
		}

		if ( 'monthly' === $frequency ) {
			$configured_day = max( 1, min( 28, $day ) );

			$scheduled_send = mktime(
				$hour,
				$minute,
				0,
				(int) date( 'n', $now ),
				$configured_day,
				(int) date( 'Y', $now )
			);

			if ( $now < $scheduled_send ) {
				return false;
			}

			// Prevent sending more than once per calendar month.
			if ( $last_sent > 0 && date( 'Ym', $last_sent ) === date( 'Ym', $now ) ) {
				return false;
			}

			return true;
		}

		// Weekly (default).
		$configured_day = max( 1, min( 7, $day ) );

		$today_dow       = (int) date( 'N', $now );
		$monday_midnight = mktime(
			0, 0, 0,
			(int) date( 'n', $now ),
			(int) date( 'j', $now ) - ( $today_dow - 1 ),
			(int) date( 'Y', $now )
		);

		$scheduled_send = $monday_midnight
			+ ( $configured_day - 1 ) * DAY_IN_SECONDS
			+ $hour * HOUR_IN_SECONDS
			+ $minute * MINUTE_IN_SECONDS;

		if ( $now < $scheduled_send ) {
			return false;
		}

		// Prevent sending more than once per ISO week.
		if ( $last_sent > 0 && date( 'oW', $last_sent ) === date( 'oW', $now ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Handle email preview request.
	 * Hooked to admin_post_kc_us_email_preview.
	 */
	public function handle_preview() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'url-shortify' ) );
		}

		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'kc_us_email_preview' ) ) {
			wp_die( esc_html__( 'Invalid nonce.', 'url-shortify' ) );
		}

		$settings  = $this->get_settings();
		$frequency = isset( $settings['frequency'] ) ? $settings['frequency'] : 'weekly';

		$report = new ReportGenerator();
		$email  = new EmailSender();

		$data = $report->generate( $frequency, true );
		$html = $email->generate_email_html( $data );

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Handle test email admin AJAX request.
	 * Hooked to wp_ajax_kc_us_email_test.
	 */
	public function handle_test_email_ajax() {
		check_ajax_referer( 'kc_us_email_digest_test', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'url-shortify' ) ] );
		}

		$email = sanitize_email( isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '' );

		if ( empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_error( [ 'message' => __( 'Please provide a valid email address.', 'url-shortify' ) ] );
		}

		$this->send_report( true, $email );

		wp_send_json_success(
			[
				/* translators: %s: email address */
				'message' => sprintf( __( 'Test email sent to %s', 'url-shortify' ), $email ),
			]
		);
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		register_rest_route(
			'url-shortify/v1',
			'/email-digest/test',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_test_email' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);
	}

	/**
	 * Handle test email REST request.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_test_email( \WP_REST_Request $request ) {
		$email = sanitize_email( (string) $request->get_param( 'email' ) );

		if ( empty( $email ) || ! is_email( $email ) ) {
			return new \WP_Error(
				'invalid_email',
				__( 'Please provide a valid email address.', 'url-shortify' ),
				[ 'status' => 400 ]
			);
		}

		$this->send_report( true, $email );

		return rest_ensure_response(
			[
				'success' => true,
				/* translators: %s: email address */
				'message' => sprintf( __( 'Test email sent to %s', 'url-shortify' ), $email ),
			]
		);
	}

	/**
	 * Get email digest settings from kc_us_settings option.
	 *
	 * @return array
	 */
	private function get_settings() {
		$all_settings = US()->get_settings();

		$defaults = [
			'enabled'    => 0,
			'frequency'  => 'weekly',
			'day'        => 1,
			'time'       => '14:00',
			'recipients' => '',
		];

		$result = [];
		foreach ( $defaults as $key => $default ) {
			$settings_key   = 'reports_email_digest_' . $key;
			$result[ $key ] = isset( $all_settings[ $settings_key ] ) ? $all_settings[ $settings_key ] : $default;
		}

		return $result;
	}
}
