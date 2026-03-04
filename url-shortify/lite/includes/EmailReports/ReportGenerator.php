<?php

namespace KaizenCoders\URL_Shortify\EmailReports;

class ReportGenerator {

	/**
	 * Generate report data.
	 *
	 * @param string $frequency  'daily', 'weekly', or 'monthly'.
	 * @param bool   $is_preview Whether this is a preview (uses last 7 days regardless).
	 *
	 * @return array
	 */
	public function generate( $frequency = 'weekly', $is_preview = false ) {
		$time_range = $this->get_time_range( $frequency, $is_preview );

		$start = $time_range[0];
		$end   = $time_range[1];

		return [
			'start_date'       => $start,
			'end_date'         => $end,
			'date_range_label' => date( 'M j, Y', $start ) . ' – ' . date( 'M j, Y', $end ),
			'frequency'        => $frequency,
			'is_preview'       => $is_preview,
			'site_name'        => get_bloginfo( 'name' ),
			'site_url'         => home_url(),
			'new_links'        => $this->get_new_links_count( $start, $end ),
			'total_clicks'     => $this->get_total_clicks( $start, $end ),
			'top_locations'    => $this->get_top_locations( $start, $end ),
			'top_devices'      => $this->get_top_devices( $start, $end ),
			'top_links'        => $this->get_top_links( $start, $end ),
		];
	}

	/**
	 * Get time range based on frequency.
	 *
	 * @param string $frequency
	 * @param bool   $is_preview
	 *
	 * @return array [ start_timestamp, end_timestamp ]
	 */
	private function get_time_range( $frequency, $is_preview ) {
		if ( $is_preview ) {
			$end   = current_time( 'timestamp' );
			$start = $end - 7 * DAY_IN_SECONDS;
			return [ $start, $end ];
		}

		switch ( $frequency ) {
			case 'daily':
				return $this->get_time_range_for_yesterday();
			case 'monthly':
				return $this->get_time_range_for_last_month();
			default:
				return $this->get_time_range_for_last_week();
		}
	}

	/**
	 * Get time range for yesterday (full calendar day 00:00:00–23:59:59).
	 *
	 * @return array
	 */
	private function get_time_range_for_yesterday() {
		$today = current_time( 'timestamp' );
		$start = strtotime( 'yesterday 00:00:00', $today );
		$end   = strtotime( 'yesterday 23:59:59', $today );

		return [ $start, $end ];
	}

	/**
	 * Get time range for the last full ISO week.
	 *
	 * @return array
	 */
	private function get_time_range_for_last_week() {
		$start_of_week            = get_option( 'start_of_week', 1 );
		$today                    = current_time( 'timestamp' );
		$day_of_week              = (int) date( 'w', $today );
		$days_since_start_of_week = ( $day_of_week - $start_of_week + 7 ) % 7;
		$last_week_start          = strtotime( "-$days_since_start_of_week days", $today );
		$last_week_start          = strtotime( 'last week', $last_week_start );
		$last_week_start          = strtotime( '00:00:00', $last_week_start );
		$last_week_end            = strtotime( '+6 days', $last_week_start );
		$last_week_end            = strtotime( '23:59:59', $last_week_end );

		return [ $last_week_start, $last_week_end ];
	}

	/**
	 * Get time range for the last calendar month.
	 *
	 * @return array
	 */
	private function get_time_range_for_last_month() {
		$today = current_time( 'timestamp' );
		$start = strtotime( 'first day of last month 00:00:00', $today );
		$end   = strtotime( 'last day of last month 23:59:59', $today );

		return [ $start, $end ];
	}

	private function get_new_links_count( $since, $until ) {
		return US()->db->links->get_new_links_count_by_time_range( $since, $until );
	}

	private function get_total_clicks( $since, $until ) {
		return US()->db->clicks->get_total_clicks_by_time_range( $since, $until );
	}

	private function get_top_locations( $since, $until, $limit = 5 ) {
		return US()->db->clicks->get_top_locations_by_time_range( $since, $until, $limit );
	}

	private function get_top_devices( $since, $until, $limit = 5 ) {
		return US()->db->clicks->get_top_devices_by_time_range( $since, $until, $limit );
	}

	private function get_top_links( $since, $until, $limit = 5 ) {
		return US()->db->clicks->get_top_links_by_time_range( $since, $until, $limit );
	}
}
