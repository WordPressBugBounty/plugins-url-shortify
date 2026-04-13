<?php

namespace KaizenCoders\URL_Shortify\Admin\Controllers;

use KaizenCoders\URL_Shortify\Cache;
use KaizenCoders\URL_Shortify\Common\Export;
use KaizenCoders\URL_Shortify\Common\Utils;
use KaizenCoders\URL_Shortify\Helper;

class LinkStatsController extends StatsController {
	/**
	 * Link ID
	 *
	 * @var null
	 *
	 * @since 1.0.4
	 */
	public $link_id = null;

	/**
	 * Link_Stats constructor.
	 *
	 * @param null $link_id
	 *
	 * @since 1.0.4
	 */
	public function __construct( $link_id = null ) {
		$this->link_id = $link_id;

		parent::__construct();
	}

	/**
	 * Render Link stats page
	 *
	 * @since 1.0.4
	 */
	public function render() {
		$data = $this->prepare_data( false );

		$data['icon_url'] = "https://www.google.com/s2/favicons?domain={$data['url']}";

		$data['short_url'] = Helper::get_short_link( $data['slug'], $data );

		include KC_US_ADMIN_TEMPLATES_DIR . '/link-stats.php';
	}

	/**
	 * Prepare data for report
	 *
	 * @param bool $include_click_history Keep signature compatible with parent.
	 *
	 * @return array|object|void|null
	 *
	 * @since 1.0.4
	 */
	public function prepare_data( $include_click_history = true ) {
		$refresh = (int) Helper::get_request_data( 'refresh', 0 );
		$filter_context = $this->get_clicks_filter_context();
		$time_filter    = $filter_context['time_filter'];
		$days           = $filter_context['days'];
		$start_date     = $filter_context['start_date'];
		$end_date       = $filter_context['end_date'];

		// If we have the data in cache, get it from it.
		// We store data in cache for 3 hours
		$cache_key = 'link_stats_' . $this->link_id . '_' . sanitize_key( $time_filter );
		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$cache_key .= '_' . sanitize_key( $start_date . '_' . $end_date );
		}
		$data      = Cache::get_transient( $cache_key );

		if ( ! empty( $data ) && ( 1 !== $refresh ) ) {
			return $data;
		}

		$data = US()->db->links->get_by_id( $this->link_id );

		// Click History for the selected range.
		$history_days = apply_filters( 'kc_us_clicks_info_for_days', $days );

		$clicks_data = $this->get_clicks_info( $history_days, array( $this->link_id ), $start_date, $end_date );

		$data['reports']['clicks'] = $clicks_data;

		$spline_data_filled = $this->fill_missing_dates_in_spline_data(
			US()->db->clicks->get_spline_chart_data( $days, array( $this->link_id ), $start_date, $end_date ),
			$days,
			$start_date,
			$end_date
		);

		$heatmap_data = US()->db->clicks->get_heatmap_intensity_data( 365, array( $this->link_id ), $start_date, $end_date );
		$heatmap_map   = [];
		foreach ( $heatmap_data as $row ) {
			$date = Helper::get_data( $row, 'date' );
			if ( $date ) {
				$heatmap_map[ $date ] = (int) Helper::get_data( $row, 'count' );
			}
		}

		$heatmap = $this->build_heatmap_chart_data( $heatmap_map );

		$data['chart_data'] = [
			'dates'                => array_column( $spline_data_filled, 'date' ),
			'total_series'         => array_map( 'intval', array_column( $spline_data_filled, 'total_clicks' ) ),
			'unique_series'        => array_map( 'intval', array_column( $spline_data_filled, 'unique_clicks' ) ),
			'heatmap_series'       => $heatmap['heatmap_series'],
			'has_clicks_data'      => ! empty( $heatmap_map ),
			'heatmap_week_starts'  => $heatmap['week_starts'],
			'heatmap_day_labels'   => $heatmap['day_labels'],
			'heatmap_month_labels' => $heatmap['month_labels'],
			'heatmap_color_ranges' => $heatmap['color_ranges'],
		];

		$data['click_data_for_graph'] = array_combine(
			array_column( $spline_data_filled, 'date' ),
			array_map( 'intval', array_column( $spline_data_filled, 'total_clicks' ) )
		) ?: [];

		$data['browser_info'] = $this->get_browser_info_for_graph( array( $this->link_id ) );
		$data['device_info']  = $this->get_device_info_for_graph( array( $this->link_id ) );
		$data['os_info']      = $this->get_os_info_for_graph( array( $this->link_id ) );

		$countries_data = $this->get_country_info_for_graph( array( $this->link_id ) );

		$country_info = array();

		if ( Helper::is_forechable( $countries_data ) ) {
			$tota_count = array_sum( array_values( $countries_data ) );

			foreach ( $countries_data as $country_iso_code => $total ) {

				if ( 'Others' === $country_iso_code ) {
					$country = __( 'Others', 'url-shortify' );
				} else {
					$country = Utils::get_country_name_from_iso_code( $country_iso_code );
				}

				$country_info[ $country_iso_code ]['name']       = $country;
				$country_info[ $country_iso_code ]['total']      = $total;
				$country_info[ $country_iso_code ]['percentage'] = round( ( $total * 100 ) / $tota_count, 2 );
				$country_info[ $country_iso_code ]['flag_url']   = Utils::get_country_icon_url( $country_iso_code );
			}
		}

		$data['country_info'] = $country_info;

		$data['referrers_info'] = $this->get_referrers_info_for_graph( array( $this->link_id ) );

		/**
		 * Split test results — populated by PRO via the kc_us_get_split_test_results filter.
		 * Returns an empty array for free users or links that don't use link-rotation.
		 */
		$data['split_test_results'] = apply_filters( 'kc_us_get_split_test_results', [], $this->link_id, $data );

		$data['last_updated_on'] = time();

		// Store data in cache for 3 hours
		Cache::set_transient( $cache_key, $data, HOUR_IN_SECONDS * 3 );

		return $data;
	}

	/**
	 * Prepare chart data for AJAX refreshes.
	 *
	 * @return array
	 */
	public function get_chart_data_response() {
		$data = $this->prepare_data( false );

		return [
			'chart_data'       => $data['chart_data'],
			'clicks_total'     => array_sum( array_map( 'intval', Helper::get_data( $data, 'click_data_for_graph', [] ) ) ),
			'time_filter'      => Helper::get_request_data( 'time_filter', '' ),
			'start_date'       => Helper::get_request_data( 'start_date', '' ),
			'end_date'         => Helper::get_request_data( 'end_date', '' ),
		];
	}

	/**
	 * Map the selected time filter to the number of days to query.
	 *
	 * @param string $time_filter
	 *
	 * @return int
	 */
	private function get_days_from_time_filter( $time_filter ) {
		switch ( $time_filter ) {
			case 'today':
				return 1;
			case 'last_7_days':
				return 7;
			case 'last_30_days':
				return 30;
			case 'last_60_days':
				return 60;
			case 'all_time':
				return 0;
			default:
				return US()->is_pro() ? 0 : 7;
		}
	}

	/**
	 * Resolve the filter context from the current request.
	 *
	 * @return array
	 */
	private function get_clicks_filter_context() {
		$default_time_filter = US()->is_pro() ? 'all_time' : 'last_7_days';
		$time_filter         = sanitize_key( Helper::get_request_data( 'time_filter', '' ) );

		if ( empty( $time_filter ) ) {
			$time_filter = $default_time_filter;
		}

		$context = [
			'time_filter' => $time_filter,
			'days'        => $this->get_days_from_time_filter( $time_filter ),
			'start_date'  => '',
			'end_date'    => '',
		];

		if ( 'custom' !== $time_filter ) {
			return $context;
		}

		$start_date = sanitize_text_field( Helper::get_request_data( 'start_date', '' ) );
		$end_date   = sanitize_text_field( Helper::get_request_data( 'end_date', '' ) );
		$range      = $this->normalize_custom_date_range( $start_date, $end_date );

		if ( empty( $range ) ) {
			$context['time_filter'] = $default_time_filter;
			$context['days']        = $this->get_days_from_time_filter( $default_time_filter );
			return $context;
		}

		return array_merge( $context, $range );
	}

	/**
	 * Normalize a custom range into safe dates.
	 *
	 * @param string $start_date
	 * @param string $end_date
	 *
	 * @return array
	 */
	private function normalize_custom_date_range( $start_date, $end_date ) {
		$start = \DateTimeImmutable::createFromFormat( 'Y-m-d', $start_date );
		$end   = \DateTimeImmutable::createFromFormat( 'Y-m-d', $end_date );

		if ( ! $start || ! $end ) {
			return [];
		}

		if ( $start > $end ) {
			$swap  = $start;
			$start = $end;
			$end   = $swap;
		}

		return [
			'start_date' => $start->format( 'Y-m-d' ),
			'end_date'   => $end->format( 'Y-m-d' ),
			'days'       => $start->diff( $end )->days + 1,
		];
	}

	/**
	 * Fill missing dates in the chart data with zero values.
	 *
	 * @param array $spline_data
	 * @param int   $days
	 *
	 * @return array
	 */
	private function fill_missing_dates_in_spline_data( $spline_data = [], $days = 7, $start_date = '', $end_date = '' ) {
		$data_map = [];
		foreach ( $spline_data as $row ) {
			$date = Helper::get_data( $row, 'date', '' );
			if ( $date ) {
				$data_map[ $date ] = [
					'total_clicks'  => (int) Helper::get_data( $row, 'total_clicks', 0 ),
					'unique_clicks' => (int) Helper::get_data( $row, 'unique_clicks', 0 ),
				];
			}
		}

		if ( empty( $data_map ) ) {
			return [];
		}

		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$start = \DateTimeImmutable::createFromFormat( 'Y-m-d', $start_date );
			$end   = \DateTimeImmutable::createFromFormat( 'Y-m-d', $end_date );

			if ( ! $start || ! $end ) {
				return [];
			}

			if ( $start > $end ) {
				$swap  = $start;
				$start = $end;
				$end   = $swap;
			}

			$start_date = $start;
			$end_date   = $end;
		} elseif ( $days > 0 ) {
			$end_date   = new \DateTimeImmutable( 'today' );
			$start_date = $end_date->sub( new \DateInterval( 'P' . absint( $days ) . 'D' ) );
		} else {
			$dates      = array_keys( $data_map );
			$start_date = new \DateTimeImmutable( reset( $dates ) );
			$end_date   = new \DateTimeImmutable( 'today' );
		}

		$final_data = [];
		for ( $cursor = $start_date; $cursor <= $end_date; $cursor = $cursor->add( new \DateInterval( 'P1D' ) ) ) {
			$date_key = $cursor->format( 'Y-m-d' );
			$final_data[] = [
				'date'          => $date_key,
				'total_clicks'  => (int) Helper::get_data( $data_map, $date_key . '|total_clicks', 0 ),
				'unique_clicks' => (int) Helper::get_data( $data_map, $date_key . '|unique_clicks', 0 ),
			];
		}

		return $final_data;
	}

	/**
	 * Build the heatmap payload expected by the shared admin chart script.
	 *
	 * @param array $heatmap_map
	 *
	 * @return array
	 */
	private function build_heatmap_chart_data( $heatmap_map = [] ) {
		$end_date   = new \DateTimeImmutable( 'today' );
		$start_date = $end_date->sub( new \DateInterval( 'P364D' ) )->modify( 'last monday' );
		$current_week_end = $end_date->modify( 'monday this week' );

		$day_labels = [ __( 'Mon', 'url-shortify' ), __( 'Tue', 'url-shortify' ), __( 'Wed', 'url-shortify' ), __( 'Thu', 'url-shortify' ), __( 'Fri', 'url-shortify' ), __( 'Sat', 'url-shortify' ), __( 'Sun', 'url-shortify' ) ];
		$heatmap_series = array_map(
			function ( $label ) {
				return [
					'name' => $label,
					'data' => [],
				];
			},
			$day_labels
		);

		$week_starts = [];
		$current_week = $start_date;

		while ( $current_week <= $current_week_end ) {
			$week_start_label = $current_week->format( 'Y-m-d' );
			$week_starts[]    = $week_start_label;

			for ( $day = 0; $day < 7; $day ++ ) {
				$day_date = $current_week->add( new \DateInterval( "P{$day}D" ) );
				$date_key = $day_date->format( 'Y-m-d' );
				$is_future = $day_date > $end_date;

				$heatmap_series[ $day ]['data'][] = [
					'x'      => $week_start_label,
					'y'      => $heatmap_map[ $date_key ] ?? 0,
					'meta'   => $date_key,
					'future' => $is_future,
				];
			}

			$current_week = $current_week->add( new \DateInterval( 'P1W' ) );
		}

		$month_labels = $this->build_heatmap_month_labels( $week_starts, $end_date );

		return [
			'heatmap_series' => $heatmap_series,
			'week_starts'    => $week_starts,
			'day_labels'     => $day_labels,
			'month_labels'   => $month_labels,
			'color_ranges'    => $this->generate_dynamic_heatmap_color_ranges( $heatmap_map ),
		];
	}

	/**
	 * Generate dynamic heatmap color ranges based on quantile distribution.
	 *
	 * @param array $heatmap_map
	 *
	 * @return array
	 */
	private function generate_dynamic_heatmap_color_ranges( $heatmap_map = [] ) {
		$colors = [
			'#f4f7fb',
			'#edf9f1',
			'#d9f3df',
			'#bdeaca',
			'#92ddb0',
			'#5fd18a',
			'#22c55e',
		];

		$values = array_values( $heatmap_map );
		if ( empty( $values ) ) {
			return [
				[
					'from'  => 0,
					'to'    => 0,
					'color' => $colors[0],
					'name'  => '0 clicks',
				],
			];
		}

		sort( $values );

		$ranges = [
			[
				'from'  => 0,
				'to'    => 0,
				'color' => $colors[0],
				'name'  => '0 clicks',
			],
		];

		$positive_colors = array_slice( $colors, 1 );
		$num_quantiles = min( count( $positive_colors ), max( 2, count( $positive_colors ) ) );
		$quantile_boundaries = [ 0 => 1 ];

		for ( $i = 1; $i < $num_quantiles; $i ++ ) {
			$position = ( $i / $num_quantiles ) * ( count( $values ) - 1 );
			$lower_index = (int) floor( $position );
			$upper_index = (int) ceil( $position );
			$fraction = $position - $lower_index;

			if ( $lower_index === $upper_index ) {
				$quantile_value = $values[ $lower_index ];
			} else {
				$quantile_value = $values[ $lower_index ] + ( $values[ $upper_index ] - $values[ $lower_index ] ) * $fraction;
			}

			$quantile_boundaries[ $i ] = max( 1, (int) ceil( $quantile_value ) );
		}

		$quantile_boundaries[ $num_quantiles ] = max( 1, $values[ count( $values ) - 1 ] );
		$quantile_boundaries = array_values( array_unique( $quantile_boundaries ) );

		$num_ranges = count( $quantile_boundaries ) - 1;

		for ( $i = 0; $i < $num_ranges && $i < count( $positive_colors ); $i ++ ) {
			$range_from = $quantile_boundaries[ $i ];
			$range_to   = $quantile_boundaries[ $i + 1 ];

			$ranges[] = [
				'from'  => (int) $range_from,
				'to'    => (int) $range_to,
				'color' => $positive_colors[ $i ],
				'name'  => $this->format_range_label( $range_from, $range_to ),
			];
		}

		return $ranges;
	}

	/**
	 * Build balanced month labels for the heatmap month row.
	 *
	 * @param array              $week_starts
	 * @param \DateTimeImmutable $end_date
	 *
	 * @return array
	 */
	private function build_heatmap_month_labels( $week_starts, \DateTimeImmutable $end_date ) {
		$month_weeks = [];

		foreach ( $week_starts as $index => $week_start ) {
			$week_start_date = new \DateTimeImmutable( $week_start );

			for ( $day = 0; $day < 7; $day ++ ) {
				$day_date = $week_start_date->add( new \DateInterval( "P{$day}D" ) );
				if ( $day_date > $end_date ) {
					continue;
				}

				$month_key = $day_date->format( 'Y-m' );
				if ( ! isset( $month_weeks[ $month_key ] ) ) {
					$month_weeks[ $month_key ] = [
						'label' => $day_date->format( 'M' ),
						'weeks' => [],
					];
				}

				if ( ! isset( $month_weeks[ $month_key ]['weeks'][ $index ] ) ) {
					$month_weeks[ $month_key ]['weeks'][ $index ] = 0;
				}

				$month_weeks[ $month_key ]['weeks'][ $index ] ++;
			}
		}

		$month_labels = array_fill( 0, count( $week_starts ), '' );

		foreach ( $month_weeks as $month_data ) {
			$weeks = $month_data['weeks'];
			$eligible_weeks = array_filter(
				$weeks,
				function ( $count ) {
					return $count >= 3;
				}
			);

			if ( empty( $eligible_weeks ) ) {
				continue;
			}

			$week_indexes = array_keys( $weeks );
			$week_midpoint = array_sum( $week_indexes ) / count( $week_indexes );
			$best_index = null;
			$best_count = 0;
			$best_distance = null;

			foreach ( $eligible_weeks as $index => $count ) {
				$distance = abs( $index - $week_midpoint );
				if (
					$count > $best_count ||
					( $count === $best_count && ( null === $best_distance || $distance < $best_distance ) ) ||
					( $count === $best_count && $distance === $best_distance && ( null === $best_index || $index < $best_index ) )
				) {
					$best_index = $index;
					$best_count = $count;
					$best_distance = $distance;
				}
			}

			if ( null !== $best_index ) {
				$month_labels[ $best_index ] = $month_data['label'];
			}
		}

		return $month_labels;
	}

	/**
	 * Format a readable heatmap range label.
	 *
	 * @param int $from
	 * @param int $to
	 *
	 * @return string
	 */
	private function format_range_label( $from, $to ) {
		if ( $from === $to ) {
			return $from . ' clicks';
		}

		if ( $from === 0 && $to === 0 ) {
			return '0 clicks';
		}

		return number_format_i18n( $from ) . '-' . number_format_i18n( $to ) . ' clicks';
	}

	/**
	 * Export click history.
	 *
	 * @return void
	 *
	 * @since 1.6.3
	 */
	public function export() {
		// Click History for last 7 days
		$days = apply_filters( 'kc_us_clicks_info_for_days', 7 );

		$clicks_data = $this->get_all_clicks_info( $days, array( $this->link_id ) );

		$export = new Export();

		$headers = $export->get_clicks_info_headers();

		$csv_data = $export->generate_csv( $headers, $clicks_data );

		$file_name = 'click-history.csv';

		$export->download_csv( $csv_data, $file_name );
	}

}
