<?php


namespace KaizenCoders\URL_Shortify\Admin\Controllers;

use KaizenCoders\URL_Shortify\Helper;

class StatsController extends BaseController {

	/**
	 * Shape of the cached statistics payload.
	 *
	 * Bump this whenever prepare_data() starts producing a key the template
	 * reads. Cached entries are stamped with it and anything older is rebuilt,
	 * so a shape change cannot leave people looking at a screen with pieces
	 * missing until the transient expires. Versioning the cache key alone did
	 * not do this: a key bumped for one change stayed put through the next.
	 *
	 * @since 2.7.0
	 */
	const PAYLOAD_VERSION = 6;

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
	 * How many series a comparison chart draws before it stops.
	 *
	 * Eight is a readable number of lines and about as many colours as anyone
	 * can tell apart. Groups with more links than this are not truncated
	 * silently - the payload reports what was left out so the UI can say so.
	 *
	 * @since 2.6.0
	 * @var   int
	 */
	const COMPARE_SERIES_LIMIT = 8;

	/**
	 * Every date in a range, oldest first, with no gaps.
	 *
	 * Comparison series are padded against this, so it has to be built from the
	 * range rather than from whatever dates happen to have clicks - otherwise
	 * two links with different quiet days would end up on different axes.
	 *
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 *
	 * @return array<int, string>
	 *
	 * @since 2.6.0
	 */
	protected function build_date_axis( $start_date, $end_date ) {
		$dates = [];

		$start = strtotime( $start_date );
		$end   = strtotime( $end_date );

		if ( empty( $start ) || empty( $end ) || $start > $end ) {
			return $dates;
		}

		// A very wide range is usually a mistake rather than a request for six
		// thousand points. Cap it so one bad date cannot stall the page.
		//
		// The cap moves the start forward rather than cutting the end off: an
		// over-long range trimmed from the end would silently hide the most
		// recent clicks, which is the part anyone actually came to look at.
		$max_days = (int) apply_filters( 'kc_us_reports_max_axis_days', 1100 );

		if ( $max_days > 0 ) {
			$earliest = strtotime( '-' . ( $max_days - 1 ) . ' days', $end );

			if ( $start < $earliest ) {
				$start = $earliest;
			}
		}

		for ( $t = $start; $t <= $end; $t = strtotime( '+1 day', $t ) ) {
			$dates[] = gmdate( 'Y-m-d', $t );
		}

		return $dates;
	}

	/**
	 * Build one chart series per entity, for comparison mode.
	 *
	 * The normal chart adds every link in a group together and draws a single
	 * line. This keeps them apart, so a group can be read as "which of these is
	 * actually working" rather than just "how did the group do".
	 *
	 * Series are ordered by clicks in the range and cut to COMPARE_SERIES_LIMIT,
	 * because a group with fifty links is fifty unreadable lines. The count that
	 * was dropped travels with the payload.
	 *
	 * @param string $entity     link | group | tag.
	 * @param array  $ids        Entities to compare. Empty means "whatever the link scope covers".
	 * @param array  $dates      The date axis already built for the aggregate chart.
	 * @param string $metric     total | unique.
	 * @param array  $link_ids   Link scope, e.g. the links belonging to a group.
	 *
	 * @return array
	 *
	 * @since 2.6.0
	 */
	protected function build_compare_chart_data( $entity, $ids, $dates, $metric = 'total', $link_ids = [] ) {
		$empty = [
			'mode'      => $entity,
			'metric'    => $metric,
			'series'    => [],
			'truncated' => [ 'shown' => 0, 'of' => 0 ],
		];

		if ( empty( $dates ) ) {
			return $empty;
		}

		$start_date = reset( $dates );
		$end_date   = end( $dates );

		$raw = US()->db->clicks->get_series_by_entity( $entity, $ids, $start_date, $end_date, $metric, $link_ids );

		if ( empty( $raw ) ) {
			return $empty;
		}

		// Rank before naming, so the labels lookup only runs for what survives.
		$totals = [];
		foreach ( $raw as $entity_id => $by_date ) {
			$totals[ $entity_id ] = array_sum( $by_date );
		}

		arsort( $totals );

		$total_count = count( $totals );
		$keep        = array_slice( $totals, 0, self::COMPARE_SERIES_LIMIT, true );

		$labels = $this->get_compare_entity_labels( $entity, array_keys( $keep ) );

		$series = [];

		foreach ( $keep as $entity_id => $entity_total ) {
			$by_date = $raw[ $entity_id ];

			// Every series has to span the same axis, or ApexCharts lines up the
			// wrong points against the wrong dates.
			$data = [];
			foreach ( $dates as $date ) {
				$data[] = isset( $by_date[ $date ] ) ? (int) $by_date[ $date ] : 0;
			}

			$series[] = [
				'id'    => (int) $entity_id,
				'name'  => isset( $labels[ $entity_id ] ) ? $labels[ $entity_id ] : sprintf( '#%d', $entity_id ),
				'data'  => $data,
				'total' => (int) $entity_total,
			];
		}

		return [
			'mode'      => $entity,
			'metric'    => $metric,
			'series'    => $series,
			'truncated' => [
				'shown' => count( $series ),
				'of'    => $total_count,
			],
		];
	}

	/**
	 * Readable names for the entities being compared.
	 *
	 * @param string $entity link | group | tag.
	 * @param array  $ids    Entity ids.
	 *
	 * @return array<int, string>
	 *
	 * @since 2.6.0
	 */
	protected function get_compare_entity_labels( $entity, $ids ) {
		$labels = [];

		if ( empty( $ids ) ) {
			return $labels;
		}

		if ( 'group' === $entity ) {
			$map = US()->db->groups->get_all_id_name_map();
		} elseif ( 'tag' === $entity ) {
			$map = US()->db->tags->get_all_id_name_map();
		} else {
			$map = US()->db->links->get_id_label_map( $ids );
		}

		foreach ( $ids as $id ) {
			if ( ! empty( $map[ $id ] ) ) {
				$labels[ $id ] = $map[ $id ];
			}
		}

		return $labels;
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
					'y'      => isset( $heatmap_map[ $date_key ] ) ? $heatmap_map[ $date_key ] : 0,
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
	public function get_country_info( $link_ids = [], $days = 0, $start_date = '', $end_date = '' ) {
		return apply_filters( 'kc_us_link_country_info', $link_ids, $days, $start_date, $end_date );
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
	public function get_referrers_info( $link_ids = [], $days = 0, $start_date = '', $end_date = '' ) {
		return apply_filters( 'kc_us_link_referrers_info', $link_ids, $days, $start_date, $end_date );
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
	public function get_device_info( $link_ids = [], $days = 0, $start_date = '', $end_date = '' ) {
		return apply_filters( 'kc_us_link_device_info', $link_ids, $days, $start_date, $end_date );
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
	public function get_browser_info( $link_ids = [], $days = 0, $start_date = '', $end_date = '' ) {
		return apply_filters( 'kc_us_link_browser_info', $link_ids, $days, $start_date, $end_date );
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
	public function get_os_info( $link_ids = [], $days = 0, $start_date = '', $end_date = '' ) {
		return apply_filters( 'kc_us_link_os_info', $link_ids, $days, $start_date, $end_date );
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
	public function get_country_info_for_graph( $link_ids = [], $days = 0, $start_date = '', $end_date = '' ) {
		$results = $this->get_country_info( $link_ids, $days, $start_date, $end_date );

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
	public function get_referrers_info_for_graph( $link_ids = [], $days = 0, $start_date = '', $end_date = '' ) {
		$results = $this->get_referrers_info( $link_ids, $days, $start_date, $end_date );

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
	public function get_browser_info_for_graph( $link_ids = [], $days = 0, $start_date = '', $end_date = '' ) {
		$results = $this->get_browser_info( $link_ids, $days, $start_date, $end_date );

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
	public function get_device_info_for_graph( $link_ids = [], $days = 0, $start_date = '', $end_date = '' ) {
		$results = $this->get_device_info( $link_ids, $days, $start_date, $end_date );

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
	public function get_os_info_for_graph( $link_ids = [], $days = 0, $start_date = '', $end_date = '' ) {
		$results = $this->get_os_info( $link_ids, $days, $start_date, $end_date );

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

	/**
	 * The periods this plan may ask for.
	 *
	 * Free is capped at a week of history, which the screens say out loud. The
	 * cap was only ever applied to the custom range, so a free site could ask for
	 * all time through the query string and be given it.
	 *
	 * @since 2.7.0
	 *
	 * @return array
	 */
	public static function allowed_time_filters() {
		$all = [ 'today', 'last_7_days', 'last_30_days', 'last_60_days', 'all_time', 'custom' ];

		return US()->is_pro() ? $all : [ 'today', 'last_7_days' ];
	}

	/**
	 * A requested period, or the default when it is not one this plan may have.
	 *
	 * @since 2.7.0
	 *
	 * @param string $time_filter
	 *
	 * @return string
	 */
	public static function sanitize_time_filter( $time_filter ) {
		$time_filter = sanitize_key( (string) $time_filter );

		if ( in_array( $time_filter, self::allowed_time_filters(), true ) ) {
			return $time_filter;
		}

		return US()->is_pro() ? 'all_time' : 'last_7_days';
	}

	/**
	 * Turn the screen's filter into a concrete date range.
	 *
	 * Every panel is then asked for the same explicit range, so the headline
	 * figures, the chart and the breakdowns cannot disagree with each other.
	 * "All time" stays unbounded, which is the one case with nothing to
	 * compare against.
	 *
	 * @since 2.7.0
	 *
	 * @param array $filter_context
	 *
	 * @return array
	 */
	public static function resolve_stats_period( $filter_context ) {
		$days  = (int) Helper::get_data( $filter_context, 'days', 0 );
		$start = (string) Helper::get_data( $filter_context, 'start_date', '' );
		$end   = (string) Helper::get_data( $filter_context, 'end_date', '' );

		if ( '' !== $start && '' !== $end ) {
			$start_at = \DateTimeImmutable::createFromFormat( 'Y-m-d', $start );
			$end_at   = \DateTimeImmutable::createFromFormat( 'Y-m-d', $end );

			if ( $start_at && $end_at ) {
				return [
					'start'   => $start_at->format( 'Y-m-d' ),
					'end'     => $end_at->format( 'Y-m-d' ),
					'days'    => $start_at->diff( $end_at )->days + 1,
					'bounded' => true,
				];
			}
		}

		if ( $days > 0 ) {
			$end_at   = new \DateTimeImmutable( current_time( 'Y-m-d' ) );
			$start_at = $end_at->modify( '-' . ( $days - 1 ) . ' days' );

			return [
				'start'   => $start_at->format( 'Y-m-d' ),
				'end'     => $end_at->format( 'Y-m-d' ),
				'days'    => $days,
				'bounded' => true,
			];
		}

		return [ 'start' => '', 'end' => '', 'days' => 0, 'bounded' => false ];
	}

	/**
	 * The equally long window ending the day before the current one.
	 *
	 * @since 2.7.0
	 *
	 * @param array $period
	 *
	 * @return array Empty when there is nothing sensible to compare against.
	 */
	protected function get_comparison_period( $period ) {
		if ( empty( $period['bounded'] ) ) {
			return [];
		}

		$start_at = \DateTimeImmutable::createFromFormat( 'Y-m-d', $period['start'] );

		if ( ! $start_at ) {
			return [];
		}

		$days  = max( 1, (int) $period['days'] );
		$end   = $start_at->modify( '-1 day' );
		$start = $end->modify( '-' . ( $days - 1 ) . ' days' );

		return [
			'start'   => $start->format( 'Y-m-d' ),
			'end'     => $end->format( 'Y-m-d' ),
			'days'    => $days,
			'bounded' => true,
		];
	}

	/**
	 * Percentage change between two figures, with the direction it implies.
	 *
	 * Returns null where there is nothing honest to say - no previous period at
	 * all, or a previous period of zero, where "+100%" would read as growth
	 * when it only means "this is the first traffic there has ever been".
	 *
	 * @since 2.7.0
	 *
	 * @param int|null $current
	 * @param int|null $previous
	 * @param string   $better 'up', 'down' or '' when neither is good news.
	 *
	 * @return array|null
	 */
	protected function build_delta( $current, $previous, $better = 'up', $mode = 'pct' ) {
		if ( null === $previous || null === $current ) {
			return null;
		}

		/*
		 * A rate moves in percentage points, not in percent of itself: a repeat
		 * rate going 40% -> 42% is "+2 pts", and calling it "+5%" invites the
		 * reader to think clicks grew.
		 */
		if ( 'pts' === $mode ) {
			$difference = round( $current - $previous, 1 );

			$tone = 'neutral';

			if ( abs( $difference ) >= 0.05 && ! empty( $better ) ) {
				$tone = ( ( $difference > 0 ) === ( 'up' === $better ) ) ? 'good' : 'bad';
			}

			return [
				'percent' => null,
				'tone'    => $tone,
				'label'   => abs( $difference ) < 0.05
					? __( 'No change', 'url-shortify' )
					: sprintf(
						/* translators: %s: signed difference in percentage points. */
						__( '%s pts', 'url-shortify' ),
						( $difference > 0 ? '+' : '' ) . number_format_i18n( $difference, 1 )
					),
			];
		}

		if ( 0 === (int) $previous ) {
			return 0 === (int) $current ? null : [
				'percent' => null,
				'tone'    => 'neutral',
				/* translators: %s: number of clicks in the current period. */
				'label'   => __( 'First activity in this period', 'url-shortify' ),
			];
		}

		$percent = (int) round( ( ( $current - $previous ) * 100 ) / $previous );

		$tone = 'neutral';

		if ( 0 !== $percent && ! empty( $better ) ) {
			$tone = ( ( $percent > 0 ) === ( 'up' === $better ) ) ? 'good' : 'bad';
		}

		return [
			'percent' => $percent,
			'tone'    => $tone,
			'label'   => 0 === $percent
				? __( 'No change', 'url-shortify' )
				: sprintf( '%s%d%%', $percent > 0 ? '+' : '', $percent ),
		];
	}

	/**
	 * Everything the redesigned statistics screens show above the fold.
	 *
	 * @since 2.7.0
	 *
	 * @param array $link_ids
	 * @param array $filter_context
	 *
	 * @return array
	 */
	protected function build_stats_overview( $link_ids, $filter_context ) {
		$period   = $this->resolve_stats_period( $filter_context );
		$previous = $this->get_comparison_period( $period );

		$clicks = US()->db->clicks;

		$current = $clicks->get_stats_summary( $link_ids, 0, $period['start'], $period['end'] );
		$prior   = ! empty( $previous )
			? $clicks->get_stats_summary( $link_ids, 0, $previous['start'], $previous['end'] )
			: [];

		$overview = [
			'period'   => $period,
			'previous' => $previous,
			'current'  => $current,
			'prior'    => $prior,
		];

		$overview['kpis']     = $this->build_stats_kpis( $current, $prior, $period );
		$overview['channels'] = $this->build_channel_rows( $link_ids, $period );
		$overview['peak']     = $this->build_peak_times( $link_ids, $period );
		$overview['insights'] = $this->build_stats_insights( $overview );

		return $overview;
	}

	/**
	 * Headline tiles, each with its change against the previous period.
	 *
	 * @since 2.7.0
	 *
	 * @param array $current
	 * @param array $prior
	 * @param array $period
	 *
	 * @return array
	 */
	protected function build_stats_kpis( $current, $prior, $period ) {
		$has_prior = ! empty( $prior );

		$total    = (int) Helper::get_data( $current, 'total', 0 );
		$uniques  = (int) Helper::get_data( $current, 'unique', 0 );
		$visitors = (int) Helper::get_data( $current, 'visitors', 0 );

		$prior_total    = $has_prior ? (int) Helper::get_data( $prior, 'total', 0 ) : null;
		$prior_uniques  = $has_prior ? (int) Helper::get_data( $prior, 'unique', 0 ) : null;
		$prior_visitors = $has_prior ? (int) Helper::get_data( $prior, 'visitors', 0 ) : null;

		// Clicks beyond the first from the same visitor: the link being returned
		// to rather than merely reached.
		$repeat       = max( 0, $total - $uniques );
		$repeat_rate  = $total > 0 ? round( ( $repeat * 100 ) / $total, 1 ) : 0;
		$prior_repeat = ( $has_prior && $prior_total > 0 )
			? round( ( max( 0, $prior_total - $prior_uniques ) * 100 ) / $prior_total, 1 )
			: null;

		$days       = max( 1, (int) Helper::get_data( $period, 'days', 1 ) );
		$per_day    = $period['bounded'] ? round( $total / $days, 1 ) : null;

		$kpis = [
			'total' => [
				'label' => __( 'Clicks', 'url-shortify' ),
				'value' => number_format_i18n( $total ),
				'delta' => $this->build_delta( $total, $prior_total ),
				'help'  => __( 'Every visit, including repeats', 'url-shortify' ),
			],
			'unique' => [
				'label' => __( 'Unique clicks', 'url-shortify' ),
				'value' => number_format_i18n( $uniques ),
				'delta' => $this->build_delta( $uniques, $prior_uniques ),
				'help'  => __( 'First visit from each person, within 30 days', 'url-shortify' ),
			],
			'visitors' => [
				'label' => __( 'Visitors', 'url-shortify' ),
				'value' => number_format_i18n( $visitors ),
				'delta' => $this->build_delta( $visitors, $prior_visitors ),
				'help'  => __( 'Distinct people', 'url-shortify' ),
			],
			'repeat' => [
				'label' => __( 'Repeat rate', 'url-shortify' ),
				'value' => $repeat_rate . '%',
				'delta' => $this->build_delta( $repeat_rate, $prior_repeat, '', 'pts' ),
				'help'  => __( 'Share of clicks from someone coming back', 'url-shortify' ),
			],
		];

		/*
		 * QR scans only earn a tile on a link that has any - and on a site that
		 * ran a code before the marker existed the figure would read zero while
		 * the poster is plainly working, which is worse than silence.
		 */
		$qr_scans       = (int) Helper::get_data( $current, 'qr_scans', 0 );
		$prior_qr_scans = $has_prior ? (int) Helper::get_data( $prior, 'qr_scans', 0 ) : null;

		if ( $qr_scans > 0 || ( ! empty( $prior_qr_scans ) ) ) {
			$kpis['qr_scans'] = [
				'label' => __( 'QR scans', 'url-shortify' ),
				'value' => number_format_i18n( $qr_scans ),
				'delta' => $this->build_delta( $qr_scans, $prior_qr_scans ),
				'help'  => __( 'Clicks that came from scanning the QR code', 'url-shortify' ),
			];
		}

		if ( null !== $per_day ) {
			$kpis['per_day'] = [
				'label' => __( 'Clicks per day', 'url-shortify' ),
				'value' => number_format_i18n( $per_day, 1 ),
				'delta' => null,
				'help'  => sprintf(
					/* translators: %s: number of days in the selected period. */
					__( 'Averaged over %s days', 'url-shortify' ),
					number_format_i18n( $days )
				),
			];
		}

		return $kpis;
	}

	/**
	 * Traffic channels as display rows.
	 *
	 * @since 2.7.0
	 *
	 * @param array $link_ids
	 * @param array $period
	 *
	 * @return array
	 */
	protected function build_channel_rows( $link_ids, $period ) {
		$channels = US()->db->clicks->get_channel_breakdown( $link_ids, 0, $period['start'], $period['end'] );

		$total = array_sum( $channels );

		if ( $total <= 0 ) {
			return [];
		}

		$labels = [
			'qr'       => __( 'QR code', 'url-shortify' ),
			'search'   => __( 'Search', 'url-shortify' ),
			'social'   => __( 'Social', 'url-shortify' ),
			'email'    => __( 'Email', 'url-shortify' ),
			'referral' => __( 'Other websites', 'url-shortify' ),
			'direct'   => __( 'Direct & apps', 'url-shortify' ),
		];

		$rows = [];

		foreach ( $channels as $key => $count ) {
			$rows[] = [
				'key'   => $key,
				'label' => isset( $labels[ $key ] ) ? $labels[ $key ] : $key,
				'value' => (int) $count,
				'share' => round( ( $count * 100 ) / $total, 1 ),
			];
		}

		return $rows;
	}

	/**
	 * Weekday and hour grid, plus the busiest slot in words.
	 *
	 * @since 2.7.0
	 *
	 * @param array $link_ids
	 * @param array $period
	 *
	 * @return array
	 */
	protected function build_peak_times( $link_ids, $period ) {
		$peak = US()->db->clicks->get_peak_times( $link_ids, 0, $period['start'], $period['end'] );

		$peak['best_label'] = '';
		$peak['best_day']   = 0;
		$peak['best_hour']  = 0;

		if ( empty( $peak['max'] ) ) {
			return $peak;
		}

		$day_names = [
			1 => __( 'Monday', 'url-shortify' ),
			2 => __( 'Tuesday', 'url-shortify' ),
			3 => __( 'Wednesday', 'url-shortify' ),
			4 => __( 'Thursday', 'url-shortify' ),
			5 => __( 'Friday', 'url-shortify' ),
			6 => __( 'Saturday', 'url-shortify' ),
			7 => __( 'Sunday', 'url-shortify' ),
		];

		foreach ( $peak['grid'] as $day => $hours ) {
			foreach ( $hours as $hour => $count ) {
				if ( $count === $peak['max'] ) {
					$peak['best_day']  = $day;
					$peak['best_hour'] = $hour;

					$peak['best_label'] = sprintf(
						/* translators: 1: weekday name, 2: hour range such as "16:00-17:00". */
						__( '%1$s, %2$s', 'url-shortify' ),
						$day_names[ $day ],
						sprintf( '%02d:00-%02d:00', $hour, ( $hour + 1 ) % 24 )
					);

					break 2;
				}
			}
		}

		return $peak;
	}

	/**
	 * The handful of sentences worth putting above the charts.
	 *
	 * Only observations that suggest an action, and only ones the data actually
	 * supports - a quiet link says so rather than padding the strip out.
	 *
	 * @since 2.7.0
	 *
	 * @param array $overview
	 *
	 * @return array
	 */
	protected function build_stats_insights( $overview ) {
		$insights = [];

		$current = $overview['current'];
		$total   = (int) Helper::get_data( $current, 'total', 0 );

		if ( $total <= 0 ) {
			return $insights;
		}

		$trend = Helper::get_data( $overview['kpis'], 'total', [] );
		$delta = Helper::get_data( $trend, 'delta', null );

		if ( ! empty( $delta ) && null !== $delta['percent'] && 0 !== $delta['percent'] ) {
			$insights[] = [
				'tone' => $delta['tone'],
				'text' => sprintf(
					/* translators: 1: percentage such as "18", 2: number of days. */
					$delta['percent'] > 0
						? __( 'Clicks are up %1$s%% on the previous %2$s days.', 'url-shortify' )
						: __( 'Clicks are down %1$s%% on the previous %2$s days.', 'url-shortify' ),
					number_format_i18n( abs( $delta['percent'] ) ),
					number_format_i18n( (int) $overview['period']['days'] )
				),
			];
		}

		$channels = $overview['channels'];

		if ( ! empty( $channels ) ) {
			$top = $channels[0];

			if ( $top['share'] >= 40 ) {
				$insights[] = [
					'tone' => 'neutral',
					'text' => sprintf(
						/* translators: 1: channel name, 2: percentage share. */
						__( '%1$s sends most of this traffic (%2$s%%).', 'url-shortify' ),
						$top['label'],
						number_format_i18n( $top['share'], 1 )
					),
				];
			}
		}

		if ( ! empty( $overview['peak']['best_label'] ) ) {
			$insights[] = [
				'tone' => 'neutral',
				'text' => sprintf(
					/* translators: %s: weekday and hour range. */
					__( 'Busiest window is %s - worth timing the next share for.', 'url-shortify' ),
					$overview['peak']['best_label']
				),
			];
		}

		/*
		 * Worth its own line rather than leaving it to the channel list: it is
		 * the one channel that maps to something physical, and knowing a poster
		 * or a packaging insert is working is what decides whether to print more.
		 */
		$qr_scans = (int) Helper::get_data( $current, 'qr_scans', 0 );

		if ( $qr_scans > 0 ) {
			$insights[] = [
				'tone' => 'good',
				'text' => sprintf(
					/* translators: 1: number of scans, 2: percentage share. */
					_n(
						'%1$s click came from a QR scan (%2$s%% of the total).',
						'%1$s clicks came from QR scans (%2$s%% of the total).',
						$qr_scans,
						'url-shortify'
					),
					number_format_i18n( $qr_scans ),
					number_format_i18n( round( ( $qr_scans * 100 ) / $total, 1 ), 1 )
				),
			];
		}

		$last_click = (string) Helper::get_data( $current, 'last_click', '' );

		if ( ! empty( $last_click ) ) {
			$quiet_days = (int) floor( ( time() - strtotime( $last_click ) ) / DAY_IN_SECONDS );

			if ( $quiet_days >= 7 ) {
				$insights[] = [
					'tone' => 'bad',
					'text' => sprintf(
						/* translators: %s: number of days. */
						_n(
							'No clicks for %s day.',
							'No clicks for %s days.',
							$quiet_days,
							'url-shortify'
						),
						number_format_i18n( $quiet_days )
					),
				];
			}
		}

		$bots = (int) Helper::get_data( $current, 'bots', 0 );

		if ( $bots > 0 && $total > 0 ) {
			$bot_share = round( ( $bots * 100 ) / $total, 1 );

			if ( $bot_share >= 5 ) {
				$insights[] = [
					'tone' => 'bad',
					'text' => sprintf(
						/* translators: %s: percentage of clicks identified as bots. */
						__( '%s%% of these clicks look like bots.', 'url-shortify' ),
						number_format_i18n( $bot_share, 1 )
					),
				];
			}
		}

		return array_slice( $insights, 0, 4 );
	}

	/**
	 * Per-link standings for a group or tag, plus what they say as a whole.
	 *
	 * A group screen that only aggregates its links answers "how is this group
	 * doing" and nothing else. The useful questions are which link is carrying
	 * it, whether that reliance is healthy, and which members are doing nothing
	 * and could be retired.
	 *
	 * @since 2.7.0
	 *
	 * @param array $links          Link rows, as stored.
	 * @param array $period         Resolved period from resolve_stats_period().
	 * @param bool  $with_audience  Whether to look up the device, browser and
	 *                              platform behind each link. Those columns are
	 *                              PRO, so a free site skips three queries.
	 *
	 * @return array
	 */
	protected function build_member_breakdown( $links, $period, $with_audience = true ) {
		$empty = [
			'rows'          => [],
			'total'         => 0,
			'members'       => 0,
			'dormant'       => 0,
			'top_share'     => 0,
			'top_label'     => '',
			'concentration' => '',
		];

		if ( empty( $links ) ) {
			return $empty;
		}

		$link_ids = [];

		foreach ( $links as $link ) {
			$link_ids[] = (int) Helper::get_data( $link, 'id', 0 );
		}

		$link_ids = array_filter( $link_ids );

		if ( empty( $link_ids ) ) {
			return $empty;
		}

		$clicks = US()->db->clicks;

		$current = $clicks->get_totals_by_links( $link_ids, $period['start'], $period['end'] );

		/*
		 * The most common device, browser and platform per link, the way the
		 * Smart Report performance table reports them - the audience behind a
		 * link matters as much as its volume when deciding what to do with it.
		 */
		$devices   = [];
		$browsers  = [];
		$platforms = [];

		if ( $with_audience ) {
			$devices   = $clicks->get_top_attribute_by_links( $link_ids, 'device', $period['start'], $period['end'] );
			$browsers  = $clicks->get_top_attribute_by_links( $link_ids, 'browser_type', $period['start'], $period['end'] );
			$platforms = $clicks->get_top_attribute_by_links( $link_ids, 'os', $period['start'], $period['end'] );
		}

		$previous = $this->get_comparison_period( $period );

		$prior = ! empty( $previous )
			? $clicks->get_totals_by_links( $link_ids, $previous['start'], $previous['end'] )
			: [];

		$total = 0;

		foreach ( $current as $totals ) {
			$total += (int) $totals['total'];
		}

		$rows    = [];
		$dormant = 0;

		foreach ( $links as $link ) {
			$id = (int) Helper::get_data( $link, 'id', 0 );

			if ( empty( $id ) ) {
				continue;
			}

			$link_total  = isset( $current[ $id ] ) ? (int) $current[ $id ]['total'] : 0;
			$link_unique = isset( $current[ $id ] ) ? (int) $current[ $id ]['unique'] : 0;

			if ( 0 === $link_total ) {
				$dormant ++;
			}

			$prior_total = ! empty( $previous )
				? ( isset( $prior[ $id ] ) ? (int) $prior[ $id ]['total'] : 0 )
				: null;

			$slug = (string) Helper::get_data( $link, 'slug', '' );

			$rows[] = [
				'id'        => $id,
				'label'     => (string) Helper::get_data( $link, 'name', '' ),
				'slug'      => $slug,
				'url'       => Helper::get_link_action_url( $id, 'statistics' ),
				'short_url' => ! empty( $slug ) ? Helper::get_short_link( $slug, $link ) : '',
				'value'     => $link_total,
				'unique'    => $link_unique,
				'share'     => $total > 0 ? round( ( $link_total * 100 ) / $total, 1 ) : 0,
				'delta'     => $this->build_delta( $link_total, $prior_total ),
				'device'    => Helper::get_data( $devices, $id, [] ),
				'browser'   => Helper::get_data( $browsers, $id, [] ),
				'platform'  => Helper::get_data( $platforms, $id, [] ),
			];
		}

		usort(
			$rows,
			function ( $a, $b ) {
				return $b['value'] - $a['value'];
			}
		);

		$top_share = ! empty( $rows ) ? (float) $rows[0]['share'] : 0;
		$top_label = ! empty( $rows ) ? $rows[0]['label'] : '';

		/*
		 * Concentration is only worth remarking on at the extremes, and only
		 * once there are enough members for a share to mean anything - "one link
		 * is 100% of this group" is noise when the group has one link.
		 */
		$concentration = '';

		if ( count( $rows ) >= 3 && $total > 0 ) {
			if ( $top_share >= 60 ) {
				$concentration = 'high';
			} elseif ( $top_share <= 25 ) {
				$concentration = 'even';
			}
		}

		return [
			'rows'          => $rows,
			'total'         => $total,
			'members'       => count( $rows ),
			'dormant'       => $dormant,
			'top_share'     => $top_share,
			'top_label'     => $top_label,
			'concentration' => $concentration,
		];
	}

	/**
	 * Observations that only make sense once a set of links is involved.
	 *
	 * @since 2.7.0
	 *
	 * @param array $members From build_member_breakdown().
	 *
	 * @return array
	 */
	protected function build_member_insights( $members ) {
		$insights = [];

		if ( empty( $members['members'] ) ) {
			return $insights;
		}

		if ( 'high' === $members['concentration'] ) {
			$insights[] = [
				'tone' => 'bad',
				'text' => sprintf(
					/* translators: 1: link name, 2: percentage share. */
					__( '%1$s alone is %2$s%% of these clicks - the rest are barely contributing.', 'url-shortify' ),
					$members['top_label'],
					number_format_i18n( $members['top_share'], 1 )
				),
			];
		} elseif ( 'even' === $members['concentration'] ) {
			$insights[] = [
				'tone' => 'good',
				'text' => __( 'Clicks are spread evenly across these links rather than resting on one.', 'url-shortify' ),
			];
		}

		if ( $members['dormant'] > 0 ) {
			$insights[] = [
				'tone' => 'neutral',
				'text' => sprintf(
					/* translators: 1: number of links with no clicks, 2: total number of links. */
					_n(
						'%1$s of %2$s links saw no clicks in this period.',
						'%1$s of %2$s links saw no clicks in this period.',
						$members['dormant'],
						'url-shortify'
					),
					number_format_i18n( $members['dormant'] ),
					number_format_i18n( $members['members'] )
				),
			];
		}

		return $insights;
	}

	/**
	 * Clicks over time and the activity heatmap for a set of links.
	 *
	 * The link, group, tag and dashboard screens all draw the same two things
	 * from the same two queries; this is that, in one place, so a fix to the
	 * series reaches every screen rather than three of four.
	 *
	 * @since 2.7.0
	 *
	 * @param array $link_ids Empty means every link, which is what the dashboard wants.
	 * @param int   $days     Trailing window, for the axis padding.
	 * @param array $period   Resolved period from resolve_stats_period().
	 *
	 * @return array {
	 *     @type array $chart_data           Series and heatmap, ready for the template.
	 *     @type array $click_data_for_graph date => total clicks.
	 * }
	 */
	protected function build_chart_payload( $link_ids, $days, $period ) {
		$start = $period['start'];
		$end   = $period['end'];

		$totals = $this->get_clicks_count_by_days( $days, $link_ids, $start, $end );

		/*
		 * The unique series is queried by explicit dates only, so an unbounded
		 * period needs bounds wide enough to mean "everything" rather than the
		 * empty strings that would return nothing.
		 */
		$unique_start = $start;
		$unique_end   = $end;

		if ( empty( $unique_start ) || empty( $unique_end ) ) {
			$unique_start = '2000-01-01';
			$unique_end   = current_time( 'Y-m-d' );
		}

		$uniques = US()->db->clicks->get_unique_clicks_count_by_days( $unique_start, $unique_end, $link_ids );

		$spline = [];

		foreach ( (array) $totals as $date => $count ) {
			$spline[ $date ] = [
				'date'          => $date,
				'total_clicks'  => (int) $count,
				'unique_clicks' => 0,
			];
		}

		foreach ( (array) $uniques as $date => $count ) {
			if ( ! isset( $spline[ $date ] ) ) {
				$spline[ $date ] = [
					'date'          => $date,
					'total_clicks'  => 0,
					'unique_clicks' => (int) $count,
				];

				continue;
			}

			$spline[ $date ]['unique_clicks'] = (int) $count;
		}

		$filled = $this->fill_missing_dates_in_spline_data( array_values( $spline ), $days, $start, $end );

		/*
		 * The heatmap is deliberately not tied to the selected period. It is a
		 * year of daily rhythm, which is what the card says and the only span
		 * the shape is good for - scoped to seven days it is a year-wide grid
		 * with one corner coloured in.
		 */
		$heatmap_rows = US()->db->clicks->get_heatmap_intensity_data( 365, $link_ids );

		$heatmap_map = [];

		foreach ( (array) $heatmap_rows as $row ) {
			$date = Helper::get_data( $row, 'date' );

			if ( $date ) {
				$heatmap_map[ $date ] = (int) Helper::get_data( $row, 'count' );
			}
		}

		$heatmap = $this->build_heatmap_chart_data( $heatmap_map );

		$chart_data = [
			'dates'                => array_column( $filled, 'date' ),
			'total_series'         => array_map( 'intval', array_column( $filled, 'total_clicks' ) ),
			'unique_series'        => array_map( 'intval', array_column( $filled, 'unique_clicks' ) ),
			'heatmap_series'       => $heatmap['heatmap_series'],
			'has_clicks_data'      => ! empty( $heatmap_map ),
			'heatmap_week_starts'  => $heatmap['week_starts'],
			'heatmap_day_labels'   => $heatmap['day_labels'],
			'heatmap_month_labels' => $heatmap['month_labels'],
			'heatmap_color_ranges' => $heatmap['color_ranges'],
		];

		$click_data_for_graph = array_combine(
			array_column( $filled, 'date' ),
			array_map( 'intval', array_column( $filled, 'total_clicks' ) )
		);

		return [
			'chart_data'           => $chart_data,
			'click_data_for_graph' => $click_data_for_graph ? $click_data_for_graph : [],
		];
	}
}
