<?php

namespace KaizenCoders\URL_Shortify\EmailReports;

class EmailSender {
	public function send( $data ) {
		$to      = $this->get_recipient_emails();
		$subject = 'URL Shortify Summary Report - ' . date( 'F j, Y' );
		$message = $this->generate_email_content( $data );
		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];

		wp_mail( $to, $subject, $message, $headers );
	}

	private function get_recipient_emails() {
		// Get admin email by default
		$emails = [ get_option( 'admin_email' ) ];

		// Add additional emails from settings if configured
		$additional_emails = get_option( 'url_shortify_report_emails', [] );
		if ( ! empty( $additional_emails ) ) {
			$emails = array_merge( $emails, $additional_emails );
		}

		return array_unique( $emails );
	}

	private function generate_email_content( $data ) {
		ob_start();
		?>
        <h2>URL Shortify Weekly Report</h2>
        <p>Period: <?php
			echo date( 'F j, Y', $data['start_date'] ); ?> - <?php
			echo date( 'F j, Y', $data['end_date'] ); ?></p>

        <h3>Summary</h3>
        <ul>
            <li>New Links Created: <?php
				echo $data['new_links']; ?></li>
            <li>Total Clicks: <?php
				echo $data['total_clicks']; ?></li>
        </ul>

        <h3>Top Locations</h3>
        <ul>
			<?php
			foreach ( $data['top_locations'] as $location ): ?>
                <li><?php
					echo esc_html( $location->country ); ?>: <?php
					echo $location->count; ?> clicks
                </li>
			<?php
			endforeach; ?>
        </ul>

        <h3>Top Devices</h3>
        <ul>
			<?php
			foreach ( $data['top_devices'] as $device ): ?>
                <li><?php
					echo esc_html( $device->device_type ); ?>: <?php
					echo $device->count; ?> clicks
                </li>
			<?php
			endforeach; ?>
        </ul>

        <h3>Top Performing Links</h3>
        <ul>
			<?php
			foreach ( $data['top_links'] as $link ): ?>
                <li><?php
					echo esc_html( $link->url ); ?>: <?php
					echo $link->clicks; ?> clicks
                </li>
			<?php
			endforeach; ?>
        </ul>
		<?php
		return ob_get_clean();
	}
}
