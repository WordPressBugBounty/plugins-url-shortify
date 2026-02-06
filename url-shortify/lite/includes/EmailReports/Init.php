<?php

namespace KaizenCoders\URL_Shortify\EmailReports;

use KaizenCoders\URL_Shortify\Option;

class Init {
	const EMAIL_REPORT_HOOK = 'url_shortify_email_report';

	public function init() {
		// Schedule weekly report if not already scheduled.
		add_action( self::EMAIL_REPORT_HOOK, [ $this, 'process_report' ] );

		add_action( 'init', [ $this, 'schedule_tasks' ] );
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
	 */
	public function send_report() {
		$report = new ReportGenerator();
		$email  = new EmailSender();

		$data = $report->generate();
		$email->send( $data );

		Option::set( 'email_report_last_sent', current_time( 'timestamp' ) );
	}

	/**
	 * Determine whether we should send the weekly email report *right now*.
	 *
	 * Returns TRUE only when:
	 * - Today is the configured day
	 * - The current time is equal to or after the configured time
	 * - A report for this week has NOT already been sent
	 */
	public function can_send_now() {
		/* --------------------------------------------------------------
		 * 1. Get current timestamp (in site timezone)
		 * -------------------------------------------------------------- */
		$now = current_time( 'timestamp' ); // WP local time

		/* --------------------------------------------------------------
		 * 2. Determine configured weekly day (1 = Monday, 7 = Sunday)
		 * -------------------------------------------------------------- */
		$day_opt = get_option( 'url_shortify_email_report_day', 'monday' );

		$day_map = [
			'monday'    => 1,
			'tuesday'   => 2,
			'wednesday' => 3,
			'thursday'  => 4,
			'friday'    => 5,
			'saturday'  => 6,
			'sunday'    => 7,
		];

		// Convert day name or numeric value
		if ( is_numeric( $day_opt ) ) {
			$configured_day = max( 1, min( 7, intval( $day_opt ) ) );
		} else {
			$day_opt        = strtolower( trim( $day_opt ) );
			$configured_day = $day_map[ $day_opt ] ?? 1; // default: Monday
		}

		// Today's day number according to WordPress-local time
		$today_day = (int) date( 'N', $now ); // 1 = Monday

		// Not the right day yet → return FALSE
		if ( $today_day !== $configured_day ) {
			return false;
		}

		/* --------------------------------------------------------------
		 * 3. Determine configured time (hour + minute)
		 * -------------------------------------------------------------- */
		$time_opt = get_option( 'url_shortify_email_report_time', '08:00' );
		$time_opt = trim( (string) $time_opt );

		$hour   = 8;    // default fallback
		$minute = 0;

		// Accepts: "H", "HH", "H:MM", "HH:MM"
		if ( preg_match( '/^(\d{1,2})(?::(\d{1,2}))?$/', $time_opt, $matches ) ) {
			$hour   = (int) $matches[1];
			$minute = isset( $matches[2] ) ? (int) $matches[2] : 0;
		}

		// Clamp values.
		$hour   = min( 23, max( 0, $hour ) );
		$minute = min( 59, max( 0, $minute ) );

		$current_hour   = (int) date( 'G', $now );
		$current_minute = (int) date( 'i', $now );

		// If current time has not reached the configured time → return FALSE
		if ( $current_hour < $hour || ( $current_hour === $hour && $current_minute < $minute ) ) {
			return false;
		}

		/* --------------------------------------------------------------
		 * 4. Prevent sending more than once per ISO week
		 * -------------------------------------------------------------- */
		$last_sent = (int) Option::get( 'email_report_last_sent', current_time( 'timestamp' ) );

		if ( $last_sent > 0 ) {
			// Compare ISO year-week format, e.g. 202548
			if ( date( 'oW', $last_sent ) === date( 'oW', $now ) ) {
				return false; // already sent this week
			}
		}

		/* --------------------------------------------------------------
		 * 5. All conditions satisfied → it is OK to send right now
		 * -------------------------------------------------------------- */
		return true;
	}

}
