<?php

namespace KaizenCoders\URL_Shortify\Email;

class Report {

	public function init() {
		// add_action( 'init', array( $this, 'send' ) );
	}

	public function send() {

		if ( $this->can_send_email_now() ) {

			$subject = $this->get_subject();

			$to_email = $this->get_to_email();

			add_filter( 'wp_mail_content_type', [ $this, 'set_html_content_type' ] );

			wp_mail( $to_email, $subject, $this->get_content() );

			remove_filter( 'wp_mail_content_type', [ $this, 'set_html_content_type' ] );
		}
	}

	public function get_content() {

		$content = $this->get_header_html();
		$content .= $this->get_main_html();
		$content .= $this->get_footer_html();

		return $content;
	}

	public function can_send_email_now() {
		return false;
	}

	public function get_to_email() {
		return get_option( 'admin_email' );
	}

	public function get_subject() {
		$home_url    = wp_parse_url( home_url() );
		$site_domain = $home_url['host'];

		if ( is_multisite() && isset( $home_url['path'] ) ) {
			$site_domain .= $home_url['path'];
		}

		return sprintf(
			esc_html__( 'Your Weekly URL Shortify Link Summary for %s', 'url-shortify' ),
			$site_domain
		);
	}

	public function get_header_html() {
		return 'This is Header';
	}

	public function get_main_html() {
		return 'This is Main';
	}

	public function get_footer_html() {
		return 'This is Footer';
	}

	public function set_html_content_type() {
		return 'text/html';
	}
}