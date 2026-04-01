<?php

namespace KaizenCoders\URL_Shortify\EmailReports;

class ReportGenerator {

	/**
	 * Generate report data.
	 *
	 * @param  string  $frequency   'daily', 'weekly', or 'monthly'.
	 * @param  bool    $is_preview  Whether this is a preview (uses last 7 days regardless).
	 *
	 * @return array
	 */
	public function generate( $frequency = 'weekly', $is_preview = false ) {
		$time_range = $this->get_time_range( $frequency, $is_preview );

		$start = $time_range[0];
		$end   = $time_range[1];

		$total_clicks = $this->get_total_clicks( $start, $end );
		$click_trend  = $this->get_click_trend( $start, $end, $total_clicks, $is_preview );

		return [
			'start_date'       => $start,
			'end_date'         => $end,
			'date_range_label' => date( 'M j, Y', $start ) . ' – ' . date( 'M j, Y', $end ),
			'frequency'        => $frequency,
			'is_preview'       => $is_preview,
			'site_name'        => get_bloginfo( 'name' ),
			'site_url'         => home_url(),
			'new_links'        => $this->get_new_links_count( $start, $end ),
			'total_clicks'     => $total_clicks,
			'click_trend'      => $click_trend,
			'top_locations'    => $this->get_top_locations( $start, $end ),
			'top_devices'      => $this->get_top_devices( $start, $end ),
			'top_links'        => $this->get_top_links( $start, $end ),
			'recent_links'     => $this->get_recent_links( $start, $end ),
			'news_items'       => $this->get_news_items(),
		];
	}

	/**
	 * Get time range based on frequency.
	 *
	 * @param  string  $frequency
	 * @param  bool    $is_preview
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

	/**
	 * Compute click trend percentage vs the equivalent previous period.
	 *
	 * Returns an integer (can be negative), or null when not applicable
	 * (preview mode, or both periods have zero clicks).
	 *
	 * @param  int   $start        Current period start timestamp.
	 * @param  int   $end          Current period end timestamp.
	 * @param  int   $curr_clicks  Already-fetched current period click count.
	 * @param  bool  $is_preview   Skip trend for preview sends.
	 *
	 * @return int|null
	 */
	private function get_click_trend( $start, $end, $curr_clicks, $is_preview ) {
		if ( $is_preview ) {
			return null;
		}

		$duration   = $end - $start;
		$prev_start = $start - $duration - 1;
		$prev_end   = $start - 1;
		$prev       = (int) $this->get_total_clicks( $prev_start, $prev_end );

		if ( $prev === 0 && $curr_clicks === 0 ) {
			return null;
		}

		if ( $prev === 0 ) {
			return 100;
		}

		return (int) round( ( $curr_clicks - $prev ) / $prev * 100 );
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

	private function get_recent_links( $since, $until, $limit = 5 ) {
		return US()->db->links->get_recent_links_by_time_range( $since, $until, $limit );
	}

	/**
	 * Get news, tips, and announcement items for the digest email.
	 *
	 * Filterable via 'kc_us_email_digest_news_items'. Each item is an array with:
	 *   'badge'       => string  — 'Tip', 'News', or 'Announcement'
	 *   'title'       => string  — Short headline
	 *   'description' => string  — Optional body text
	 *   'url'         => string  — Optional learn-more URL
	 *   'url_label'   => string  — Optional link label (defaults to 'Learn more →')
	 *
	 * @return array
	 */
	private function get_news_items() {
		$items = [
			[
				'badge'       => 'Tip',
				'title'       => __( 'Link Rotation in URL Shortify', 'url-shortify' ),
				'description' => __( 'Link Rotation enables you to assign multiple destination URLs to a single short link. This feature distributes traffic across various pages, facilitates A/B testing, or directs users based on predetermined traffic weightage.', 'url-shortify' ),
				'url'         => 'https://docs.kaizencoders.com/url-shortify/link-rotations',
				'url_label'   => __( 'Learn more →', 'url-shortify' ),
			],

			[
				'badge'       => 'Tip',
				'title'       => __( 'How to implement parameter forwarding in URL Shortify?', 'url-shortify' ),
				'description' => __( 'Parameter forwarding in URL Shortify is a feature that allows any parameters added to a short URL to be automatically appended to the target URL when the link is accessed.', 'url-shortify' ),
				'url'         => 'https://docs.kaizencoders.com/url-shortify/parameter-forwarding',
				'url_label'   => __( 'Learn more →', 'url-shortify' ),
			],

			[
				'badge'       => 'Tip',
				'title'       => __( 'How to use link tracking?', 'url-shortify' ),
				'description' => __( 'Link tracking involves monitoring how your URLs perform by collecting data on clicks, their sources, and user behavior.', 'url-shortify' ),
				'url'         => 'https://docs.kaizencoders.com/url-shortify/link-tracking',
				'url_label'   => __( 'Learn more →', 'url-shortify' ),
			],

			[
				'badge'       => 'Tip',
				'title'       => __( 'How to use the Broken Link Checker.', 'url-shortify' ),
				'description' => __( 'The Broken Link Checker is a powerful tool that automatically scans all your short links to verify their target URLs are still reachable.', 'url-shortify' ),
				'url'         => 'https://docs.kaizencoders.com/url-shortify/broken-link-checker',
				'url_label'   => __( 'Learn more →', 'url-shortify' ),
			],
			[
				'badge'       => 'Tip',
				'title'       => __( 'Organise links with Tags', 'url-shortify' ),
				'description' => __( 'Organize your short links with color-coded tags. Filter, sort, and group links by tag directly from the links table.', 'url-shortify' ),
				'url'         => 'https://docs.kaizencoders.com/url-shortify/link-tags',
				'url_label'   => __( 'Learn more →', 'url-shortify' ),
			],

			[
				'badge'       => 'Tip',
				'title'       => __( 'How to setup Auto Link Keywords', 'url-shortify' ),
				'description' => __( 'Automatically convert keywords or phrases in your post content into clickable short links — without editing each post manually.', 'url-shortify' ),
				'url'         => 'https://docs.kaizencoders.com/url-shortify/how-to-setup-auto-link-keywords',
				'url_label'   => __( 'Learn more →', 'url-shortify' ),
			],

			[
				'badge'       => 'Announcement',
				'title'       => __( 'REST API is available since URL Shortify 2.0', 'url-shortify' ),
				'description' => __( 'Generate short links programatically using URL Shortify API.', 'url-shortify' ),
				'url'         => 'https://docs.kaizencoders.com/url-shortify/api-reference',
				'url_label'   => __( 'Learn more →', 'url-shortify' ),
			],

			[
				'badge'       => 'Tip',
				'title'       => __( 'Monitor WordPress Activity using Logify', 'url-shortify' ),
				'description' => __( 'Gain clear insights into events happening on your site, track changes effortlessly, and ensure accountability.', 'url-shortify' ),
				'url'         => 'https://kaizencoders.com/logify',
				'url_label'   => __( 'Learn more →', 'url-shortify' ),
			],
			[
				'badge'       => 'Tip',
				'title'       => __( 'Do you want passwordless login for your WordPress site?', 'url-shortify' ),
				'description' => __( 'Say goodbye to forgotten passwords. Enable a secure, passwordless login experience for your WordPress site using Magic Link', 'url-shortify' ),
				'url'         => 'https://wordpress.org/plugins/magic-link/',
				'url_label'   => __( 'Learn more →', 'url-shortify' ),
			],

			[
				'badge'       => 'Tip',
				'title'       => __( 'Ever wanted to do search and replace in WordPress?', 'url-shortify' ),
				'description' => __( 'Search & Replace Everything – Quick and Easy Way to Find and Replace Text, Links using Update URLS', 'url-shortify' ),
				'url'         => 'https://wordpress.org/plugins/update-urls/',
				'url_label'   => __( 'Learn more →', 'url-shortify' ),
			],

			[
				'badge'       => 'Tip',
				'title'       => __( 'See When Your Links Come Alive — Link Activity Intensity Heatmap', 'url-shortify' ),
				'description' => __( 'Numbers tell you how many clicks you got. The Link Activity Intensity heatmap tells you when — at a glance, across an entire year.', 'url-shortify' ),
				'url'         => 'https://docs.kaizencoders.com/url-shortify/link-activity-intensity-heatmap',
				'url_label'   => __( 'Learn more →', 'url-shortify' ),
			],
		];

		$items = [ $items[ array_rand( $items ) ] ];

		return (array) apply_filters( 'kc_us_email_digest_news_items', $items );
	}
}
