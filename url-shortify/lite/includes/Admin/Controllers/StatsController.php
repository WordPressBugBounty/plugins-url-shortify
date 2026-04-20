<?php


namespace KaizenCoders\URL_Shortify\Admin\Controllers;

use KaizenCoders\URL_Shortify\Helper;

class StatsController extends BaseController {

	/**
	 * StatsController constructor.
	 *
	 * @since 1.1.7
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Prepare data for report
	 *
	 * @since 1.1.7
	 * @return array|object|void|null
	 *
	 */
	public function prepare_data( $include_click_history = true ) {

		if ( $include_click_history ) {
			// Keep in sync with Ajax dashboard table (defaults to 12 months).
			$days = apply_filters( 'kc_us_clicks_info_for_days', 365 );

			$clicks_data = $this->get_clicks_info( $days );

			$data['reports']['clicks'] = $clicks_data;
		}

		$days = apply_filters( 'kc_us_clicks_count_for_days', 7 );

		$click_report = $this->get_clicks_count_by_days( $days );

		$data['click_data_for_graph'] = $click_report;

		return $data;
	}

	/**
	 * Build the heatmap payload expected by the shared admin chart script.
	 *
	 * @param array $heatmap_map
	 *
	 * @return array
	 */
	protected function build_heatmap_chart_data( $heatmap_map = [] ) {
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
			'color_ranges'   => $this->generate_dynamic_heatmap_color_ranges( $heatmap_map ),
		];
	}

	/**
	 * Generate dynamic heatmap color ranges based on quantile distribution.
	 *
	 * @param array $heatmap_map
	 *
	 * @return array
	 */
	protected function generate_dynamic_heatmap_color_ranges( $heatmap_map = [] ) {
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
	protected function build_heatmap_month_labels( $week_starts, \DateTimeImmutable $end_date ) {
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
	protected function format_range_label( $from, $to ) {
		if ( $from === $to ) {
			return $from . ' clicks';
		}

		if ( $from === 0 && $to === 0 ) {
			return '0 clicks';
		}

		return number_format_i18n( $from ) . '-' . number_format_i18n( $to ) . ' clicks';
	}

	/**
	 * Get clicks info
	 *
	 * @since 1.1.7
	 *
	 * @param array $link_ids
	 *
	 * @param int   $days
	 *
	 * @return array
	 *
	 */
	public function get_clicks_info( $days = 7, $link_ids = [], $start_date = '', $end_date = '' ) {
		return US()->db->clicks->get_clicks_info( $days, $link_ids, $start_date, $end_date );
	}

	/**
	 * Get all clicks info
	 *
	 * @since 1.6.3
	 *
	 * @param array $link_ids
	 *
	 * @param int   $days
	 *
	 * @return array
	 *
	 */
	public function get_all_clicks_info( $days = 7, $link_ids = [], $start_date = '', $end_date = '' ) {
		return US()->db->clicks->get_all_clicks_info( $days, $link_ids, $start_date, $end_date );
	}

	/**
	 * Get clicks count by day
	 *
	 * @since 1.1.7
	 *
	 * @param array $link_ids
	 *
	 * @param int   $days
	 *
	 * @return array
	 *
	 */
	public function get_clicks_count_by_days( $days = 7, $link_ids = [], $start_date = '', $end_date = '' ) {
		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			return US()->db->clicks->get_clicks_count_by_days( $start_date, $end_date, $link_ids );
		}

		if ( 0 === (int) $days ) {
			// All-time: span from a far-past date to today.
			return US()->db->clicks->get_clicks_count_by_days( '2000-01-01', date( 'Y-m-d' ), $link_ids );
		}

		$dates = Helper::get_start_and_end_date_from_last_days( $days );

		return US()->db->clicks->get_clicks_count_by_days( $dates['start_date'], $dates['end_date'], $link_ids );
	}

	/**
	 * Fill missing dates in spline chart data with zero values.
	 *
	 * @param array  $spline_data
	 * @param int    $days
	 * @param string $start_date
	 * @param string $end_date
	 *
	 * @return array
	 */
	protected function fill_missing_dates_in_spline_data( $spline_data = [], $days = 7, $start_date = '', $end_date = '' ) {
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
	 * Get country info
	 *
	 * @since 1.2.1
	 *
	 * @param array $link_ids
	 *
	 * @return mixed|void
	 *
	 */
	public function get_country_info( $link_ids = [] ) {
		return apply_filters( 'kc_us_link_country_info', $link_ids );
	}

	/**
	 * Get Referrers info
	 *
	 * @since 1.2.1
	 *
	 * @param array $link_ids
	 *
	 * @return mixed|void
	 *
	 */
	public function get_referrers_info( $link_ids = [] ) {
		return apply_filters( 'kc_us_link_referrers_info', $link_ids );
	}

	/**
	 * Get device info
	 *
	 * @since 1.2.1
	 *
	 * @param array $link_ids
	 *
	 * @return mixed|void
	 *
	 */
	public function get_device_info( $link_ids = [] ) {
		return apply_filters( 'kc_us_link_device_info', $link_ids );
	}

	/**
	 * Get browser info
	 *
	 * @since 1.2.1
	 *
	 * @param array $link_ids
	 *
	 * @return mixed|void
	 *
	 */
	public function get_browser_info( $link_ids = [] ) {
		return apply_filters( 'kc_us_link_browser_info', $link_ids );
	}

	/**
	 * Get OS info
	 *
	 * @since 1.2.1
	 *
	 * @param array $link_ids
	 *
	 * @return mixed|void
	 *
	 */
	public function get_os_info( $link_ids = [] ) {
		return apply_filters( 'kc_us_link_os_info', $link_ids );
	}

	/**
	 * Get Country info for graph
	 *
	 * @since 1.2.1
	 *
	 * @param array $link_ids
	 *
	 * @return array
	 *
	 */
	public function get_country_info_for_graph( $link_ids = [] ) {
		$results = $this->get_country_info( $link_ids );

		return $this->prepare_for_graph( $results, 5 );
	}

	/**
	 * Get Referrers info
	 *
	 * @since 1.2.1
	 *
	 * @param array $link_ids
	 *
	 * @return array
	 *
	 */
	public function get_referrers_info_for_graph( $link_ids = [] ) {
		$results = $this->get_referrers_info( $link_ids );

		return $this->prepare_for_graph( $results, 5 );
	}

	/**
	 * Get browser info for graph
	 *
	 * @since 1.2.1
	 *
	 * @param array $link_ids
	 *
	 * @return array
	 *
	 */
	public function get_browser_info_for_graph( $link_ids = [] ) {
		$results = $this->get_browser_info( $link_ids );

		return $this->prepare_for_graph( $results, 4 );
	}

	/**
	 * Get device info for graph
	 *
	 * @since 1.2.1
	 *
	 * @param array $link_ids
	 *
	 * @return array
	 *
	 */
	public function get_device_info_for_graph( $link_ids = [] ) {
		$results = $this->get_device_info( $link_ids );

		return $this->prepare_for_graph( $results, 4 );
	}

	/**
	 * Get OS Info for graph
	 *
	 * @since 1.2.1
	 *
	 * @param array $link_ids
	 *
	 * @return array
	 *
	 */
	public function get_os_info_for_graph( $link_ids = [] ) {
		$results = $this->get_os_info( $link_ids );

		return $this->prepare_for_graph( $results, 4 );
	}

	/**
	 * @since 1.2.1
	 *
	 * @param int $top_numbers
	 *
	 * @param     $results
	 *
	 * @return array
	 *
	 */
	public function prepare_for_graph( $results, $top_numbers = 3 ) {
		if ( empty( $results ) ) {
			return [];
		}

		$others_total = 0;
		if ( ! empty( $results['unknown'] ) ) {
			$others_total = $results['unknown'];
			unset( $results['unknown'] );
		}

		arsort( $results );

		if ( count( $results ) <= $top_numbers ) {
			if ( $others_total > 0 ) {
				$results['Others'] = $others_total;
			}

			return $results;
		} else {

			$i = 0;

			foreach ( $results as $key => $value ) {

				if ( $i >= $top_numbers ) {
					$others_total += $value;
				} else {
					$final_results[ $key ] = $value;
				}

				$i ++;
			}

			$final_results['Others'] = $others_total;
		}

		return $final_results;
	}
}
