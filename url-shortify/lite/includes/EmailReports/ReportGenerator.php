<?php

namespace KaizenCoders\URL_Shortify\EmailReports;

class ReportGenerator {
	public function generate() {
		global $wpdb;

		/**
		 * @TODO
		 *
		 *      - Get the start of the week from settings (default to Monday)
		 *      - Fetch start and end date of the last week based on the start of the week
		 *      - Fetch data from the database based on the start and end date
		 *      - Format the data for the email
		 *      - Return the formatted data
		 *      - Data to fetch:
		 *      - Number of new links created
		 *      - Total clicks
		 *      - Top locations (countries)
		 *      - Top devices
		 *      - Top links
		 */
		$time_range = $this->get_time_range_for_last_week();

		$start = $time_range[0];
		$end   = $time_range[1];

		return [
			'start_date'   => $start,
			'end_date'     => $end,
			'new_links'     => $this->get_new_links_count( $start, $end ),
			'total_clicks'  => $this->get_total_clicks( $start, $end ),
			'top_locations' => $this->get_top_locations( $start, $end ),
			'top_devices'   => $this->get_top_devices( $start, $end ),
			'top_links'     => $this->get_top_links( $start, $end ),
		];
	}

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

	private function get_new_links_count( $since, $until ) {
		return US()->db->links->get_new_links_count_by_time_range( $since, $until );
	}

	private function get_total_clicks( $since, $until ) {
		return US()->db->clicks->get_total_clicks_by_time_range( $since, $until );
	}

	private function get_top_locations( $since, $until, $limit = 1 ) {
		$results = US()->db->clicks->get_top_locations_by_time_range( $since, $until, $limit );

		return $results;
	}

	private function get_top_devices( $since, $until, $limit = 5 ) {
		$results = US()->db->clicks->get_top_devices_by_time_range( $since, $until, $limit );

		return $results;
	}

	private function get_top_links( $since, $until, $limit = 5 ) {
		$results = US()->db->clicks->get_top_links_by_time_range( $since, $until, $limit );

		return $results;
	}
}
