<?php

namespace KaizenCoders\URL_Shortify\EmailReports;

class EmailSender {

	/**
	 * Send the email report.
	 *
	 * @param array       $data           Report data from ReportGenerator.
	 * @param string|null $override_email Override recipient (for test emails).
	 */
	public function send( $data, $override_email = null ) {
		$to      = $this->get_recipients( $override_email );
		$subject = $this->get_subject( $data );
		$message = $this->generate_email_html( $data );
		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];

		wp_mail( $to, $subject, $message, $headers );
	}

	/**
	 * Generate HTML email body.
	 *
	 * @param array $data Report data.
	 *
	 * @return string
	 */
	public function generate_email_html( $data ) {
		ob_start();
		$email_template = KC_US_PLUGIN_DIR . 'lite/includes/EmailReports/EmailTemplate.php';
		if ( file_exists( $email_template ) ) {
			include $email_template;
		}
		return ob_get_clean();
	}

	/**
	 * Get list of recipient email addresses.
	 *
	 * @param string|null $override_email
	 *
	 * @return array
	 */
	private function get_recipients( $override_email = null ) {
		if ( ! empty( $override_email ) && is_email( $override_email ) ) {
			return [ $override_email ];
		}

		$all_settings   = US()->get_settings();
		$recipients_raw = isset( $all_settings['reports_email_digest_recipients'] ) ? $all_settings['reports_email_digest_recipients'] : '';

		$emails = [];
		if ( ! empty( $recipients_raw ) ) {
			$lines = preg_split( '/[\r\n,]+/', $recipients_raw );
			foreach ( $lines as $line ) {
				$email = sanitize_email( trim( $line ) );
				if ( is_email( $email ) ) {
					$emails[] = $email;
				}
			}
		}

		if ( empty( $emails ) ) {
			$emails = [ get_option( 'admin_email' ) ];
		}

		return array_unique( $emails );
	}

	/**
	 * Build email subject line.
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	private function get_subject( $data ) {
		$frequency = ! empty( $data['frequency'] ) ? ucfirst( $data['frequency'] ) : 'Weekly';
		$site_name = ! empty( $data['site_name'] ) ? $data['site_name'] : get_bloginfo( 'name' );
		$subject   = sprintf( 'URL Shortify %s Report — %s', $frequency, $site_name );

		if ( ! empty( $data['is_preview'] ) ) {
			$subject = '[Preview] ' . $subject;
		}

		return $subject;
	}
}
