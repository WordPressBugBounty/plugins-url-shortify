<?php

namespace KaizenCoders\URL_Shortify\Admin;

use KaizenCoders\URL_Shortify\Common\Utils;
use KaizenCoders\URL_Shortify\Helper;

/**
 * Markup for the pieces the statistics screens are built from.
 *
 * The link, group and tag screens show the same kinds of thing, so they share
 * these rather than each keeping its own copy of a bar chart. Everything here
 * escapes its own output and copes with being handed nothing at all, because an
 * empty panel is the normal state of a link nobody has clicked yet.
 *
 * @since 2.7.0
 */
class StatsRenderer {

	/**
	 * Rows shown per page in the group/tag member table.
	 *
	 * @since 2.7.0
	 */
	const MEMBER_PAGE_SIZE = 10;

	/**
	 * Headline tiles with their change against the previous period.
	 *
	 * @since 2.7.0
	 *
	 * @param array $kpis From StatsController::build_stats_kpis().
	 *
	 * @return void
	 */
	public static function kpis( $kpis ) {
		if ( empty( $kpis ) ) {
			return;
		}

		echo '<div class="kc-us-st-kpis">';

		foreach ( $kpis as $kpi ) {
			$delta = Helper::get_data( $kpi, 'delta', null );

			echo '<div class="kc-us-st-kpi">';
			echo '<span class="kc-us-st-kpi__label">' . esc_html( $kpi['label'] ) . '</span>';
			echo '<span class="kc-us-st-kpi__value">' . esc_html( $kpi['value'] ) . '</span>';
			echo '<span class="kc-us-st-kpi__foot">';

			if ( ! empty( $delta ) ) {
				printf(
					'<span class="kc-us-st-delta kc-us-st-delta--%1$s">%2$s%3$s</span>',
					esc_attr( $delta['tone'] ),
					self::delta_arrow( $delta ),
					esc_html( $delta['label'] )
				);

				echo '<span class="kc-us-st-delta__vs">' . esc_html__( 'vs previous', 'url-shortify' ) . '</span>';
			} else {
				// Repeating "nothing to compare against" on every tile is noise;
				// the screen says it once, so each tile explains itself instead.
				echo '<span class="kc-us-st-kpi__help">' . esc_html( Helper::get_data( $kpi, 'help', '' ) ) . '</span>';
			}

			echo '</span>';
			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * A small triangle, so direction survives when the colour does not.
	 *
	 * @since 2.7.0
	 *
	 * @param array $delta
	 *
	 * @return string
	 */
	private static function delta_arrow( $delta ) {
		$percent = Helper::get_data( $delta, 'percent', null );

		if ( null === $percent || 0 === $percent ) {
			return '';
		}

		return $percent > 0 ? '&#9650;&nbsp;' : '&#9660;&nbsp;';
	}

	/**
	 * The plain-language strip above the charts.
	 *
	 * @since 2.7.0
	 *
	 * @param array $insights
	 *
	 * @return void
	 */
	public static function insights( $insights ) {
		if ( empty( $insights ) ) {
			return;
		}

		echo '<div class="kc-us-st-insights">';

		foreach ( $insights as $insight ) {
			printf(
				'<p class="kc-us-st-insight kc-us-st-insight--%1$s"><span class="kc-us-st-insight__dot"></span><span>%2$s</span></p>',
				esc_attr( Helper::get_data( $insight, 'tone', 'neutral' ) ),
				esc_html( Helper::get_data( $insight, 'text', '' ) )
			);
		}

		echo '</div>';
	}

	/**
	 * Horizontal bars.
	 *
	 * Bars rather than a pie: shares are read by comparing lengths against a
	 * shared baseline, which a pie makes impossible past about three slices,
	 * and a bar list keeps working when there are twelve.
	 *
	 * @since 2.7.0
	 *
	 * @param array $rows  Each ['label' => string, 'value' => int, 'share' => float|null, 'icon' => string|null].
	 * @param array $args  'empty' => string shown when there is nothing.
	 *
	 * @return void
	 */
	public static function bars( $rows, $args = [] ) {
		$rows = array_values( array_filter( (array) $rows ) );

		if ( empty( $rows ) ) {
			self::empty_state(
				Helper::get_data( $args, 'empty', __( 'Nothing recorded in this period.', 'url-shortify' ) )
			);

			return;
		}

		$max = 0;

		foreach ( $rows as $row ) {
			$max = max( $max, (int) Helper::get_data( $row, 'value', 0 ) );
		}

		$max = max( 1, $max );

		echo '<ul class="kc-us-st-bars">';

		foreach ( $rows as $row ) {
			$value = (int) Helper::get_data( $row, 'value', 0 );
			$share = Helper::get_data( $row, 'share', null );
			$icon  = Helper::get_data( $row, 'icon', '' );

			// A bar of literally zero width reads as a rendering fault, so the
			// smallest non-zero value still shows a sliver.
			$width = $value > 0 ? max( 1.5, ( $value * 100 ) / $max ) : 0;

			echo '<li class="kc-us-st-bar">';

			echo '<span class="kc-us-st-bar__label">';

			if ( ! empty( $icon ) ) {
				printf( '<img src="%s" alt="" aria-hidden="true" />', esc_url( $icon ) );
			}

			echo '<span class="truncate">' . esc_html( Helper::get_data( $row, 'label', '' ) ) . '</span>';
			echo '</span>';

			printf(
				'<span class="kc-us-st-bar__track"><span class="kc-us-st-bar__fill" style="width:%s%%"></span></span>',
				esc_attr( round( $width, 2 ) )
			);

			echo '<span class="kc-us-st-bar__value">' . esc_html( number_format_i18n( $value ) );

			if ( null !== $share ) {
				echo '<span class="kc-us-st-bar__share">' . esc_html( number_format_i18n( $share, 1 ) . '%' ) . '</span>';
			}

			echo '</span>';
			echo '</li>';
		}

		echo '</ul>';
	}

	/**
	 * Weekday by hour grid.
	 *
	 * @since 2.7.0
	 *
	 * @param array $peak From StatsController::build_peak_times().
	 *
	 * @return void
	 */
	public static function hours_grid( $peak ) {
		$max = (int) Helper::get_data( $peak, 'max', 0 );

		if ( $max <= 0 ) {
			self::empty_state( __( 'Not enough clicks yet to show when your audience is active.', 'url-shortify' ) );

			return;
		}

		$days = [
			1 => __( 'Mon', 'url-shortify' ),
			2 => __( 'Tue', 'url-shortify' ),
			3 => __( 'Wed', 'url-shortify' ),
			4 => __( 'Thu', 'url-shortify' ),
			5 => __( 'Fri', 'url-shortify' ),
			6 => __( 'Sat', 'url-shortify' ),
			7 => __( 'Sun', 'url-shortify' ),
		];

		echo '<div class="kc-us-st-hours" role="img" aria-label="' . esc_attr__( 'Clicks by weekday and hour', 'url-shortify' ) . '">';

		foreach ( (array) Helper::get_data( $peak, 'grid', [] ) as $day => $hours ) {
			if ( ! isset( $days[ $day ] ) ) {
				continue;
			}

			echo '<span class="kc-us-st-hours__day">' . esc_html( $days[ $day ] ) . '</span>';

			foreach ( $hours as $hour => $count ) {
				$level = $count > 0 ? (int) ceil( ( $count * 5 ) / $max ) : 0;

				printf(
					'<span class="kc-us-st-cell kc-us-st-cell--%1$d" title="%2$s"></span>',
					absint( $level ),
					esc_attr(
						sprintf(
							/* translators: 1: weekday, 2: hour range, 3: click count. */
							__( '%1$s %2$s — %3$s clicks', 'url-shortify' ),
							$days[ $day ],
							sprintf( '%02d:00-%02d:00', $hour, ( $hour + 1 ) % 24 ),
							number_format_i18n( $count )
						)
					)
				);
			}
		}

		// Hour ruler, labelled every three hours so it stays readable when the
		// grid is squeezed.
		echo '<span></span>';

		for ( $hour = 0; $hour < 24; $hour ++ ) {
			echo '<span class="kc-us-st-hours__hour">' . ( 0 === $hour % 3 ? esc_html( sprintf( '%02d', $hour ) ) : '' ) . '</span>';
		}

		echo '</div>';

		echo '<div class="kc-us-st-legend" aria-hidden="true">';
		echo '<span>' . esc_html__( 'Quieter', 'url-shortify' ) . '</span>';

		for ( $level = 1; $level <= 5; $level ++ ) {
			printf( '<span class="kc-us-st-cell kc-us-st-cell--%d"></span>', absint( $level ) );
		}

		echo '<span>' . esc_html__( 'Busier', 'url-shortify' ) . '</span>';
		echo '</div>';
	}

	/**
	 * An export action for a card header.
	 *
	 * Three screens offer one of these and the markup was drifting - one carried
	 * an icon, the others did not - so it lives in one place.
	 *
	 * @since 2.7.0
	 *
	 * @param string $url
	 * @param string $label
	 *
	 * @return string Escaped markup, for passing to card_head().
	 */
	public static function export_action( $url, $label ) {
		if ( empty( $url ) ) {
			return '';
		}

		$icon = '<svg class="kc-us-st-card__action-icon" fill="none" stroke="currentColor" stroke-linecap="round"'
			. ' stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
			. '<path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414'
			. ' 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';

		return sprintf(
			'<a class="kc-us-st-card__action" href="%1$s">%2$s<span>%3$s</span></a>',
			esc_url( $url ),
			$icon,
			esc_html( $label )
		);
	}

	/**
	 * Card header.
	 *
	 * @since 2.7.0
	 *
	 * @param string $title
	 * @param string $note
	 * @param string $aside Raw HTML already escaped by the caller.
	 *
	 * @return void
	 */
	public static function card_head( $title, $note = '', $aside = '' ) {
		echo '<div class="kc-us-st-card__head"><div>';
		echo '<h2 class="kc-us-st-card__title">' . esc_html( $title ) . '</h2>';

		if ( '' !== $note ) {
			echo '<span class="kc-us-st-card__note">' . esc_html( $note ) . '</span>';
		}

		echo '</div>';

		if ( '' !== $aside ) {
			echo '<div class="kc-us-st-card__aside">' . $aside . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- caller escapes.
		}

		echo '</div>';
	}

	/**
	 * Nothing-to-show state.
	 *
	 * @since 2.7.0
	 *
	 * @param string $message
	 * @param string $heading
	 *
	 * @return void
	 */
	public static function empty_state( $message, $heading = '' ) {
		echo '<div class="kc-us-st-empty">';

		if ( '' !== $heading ) {
			echo '<strong>' . esc_html( $heading ) . '</strong>';
		}

		echo '<span>' . esc_html( $message ) . '</span>';
		echo '</div>';
	}

	/**
	 * Overlay for a panel the current plan does not include.
	 *
	 * Shows the shape of what is behind it rather than an empty box, so the
	 * offer is legible without pretending to be data.
	 *
	 * @since 2.7.0
	 *
	 * @param string $title
	 * @param string $note
	 *
	 * @return void
	 */
	public static function locked_veil( $title, $note = '' ) {
		echo '<div class="kc-us-st-locked__veil">';
		echo '<span class="kc-us-st-locked__title">' . esc_html( $title ) . '</span>';

		if ( '' !== $note ) {
			echo '<span class="kc-us-st-locked__note">' . esc_html( $note ) . '</span>';
		}

		$upgrade_url = US()->get_landing_page_url( true );

		if ( ! empty( $upgrade_url ) ) {
			printf(
				'<a class="kc-us-primary-button px-3 py-1.5 text-sm" href="%1$s">%2$s</a>',
				esc_url( $upgrade_url ),
				esc_html__( 'Upgrade to PRO', 'url-shortify' )
			);
		}

		echo '</div>';
	}

	/**
	 * Per-link standings for a group or tag.
	 *
	 * Laid out like the Smart Report performance table, which answers the same
	 * question about a set of links: how much each one carries, and who is behind
	 * those clicks. Share is kept on top of that, because here the links are a
	 * defined set and "12% of this group" means something a report's arbitrary
	 * selection cannot say.
	 *
	 * @since 2.7.0
	 *
	 * @param array $members        From StatsController::build_member_breakdown().
	 * @param bool  $show_delta     Change against the previous period. PRO.
	 * @param bool  $show_audience  Share, device, browser and platform. PRO.
	 *
	 * @return void
	 */
	public static function member_table( $members, $show_delta = true, $show_audience = true ) {
		$rows = Helper::get_data( $members, 'rows', [] );

		if ( empty( $rows ) ) {
			self::empty_state( __( 'No links to show for this period.', 'url-shortify' ) );

			return;
		}

		/*
		 * A group of a dozen links reads fine as a list; a group of a hundred is a
		 * wall you scroll past to reach the rest of the screen. Past a page's worth
		 * the table pages and gains a search box; below that the controls would be
		 * more chrome than table.
		 */
		$paginate = count( $rows ) > self::MEMBER_PAGE_SIZE;

		echo '<div class="kc-us-st-members-wrap">';

		printf(
			'<table id="members-data" class="kc-us-st-members" data-page-length="%1$d"%2$s>',
			absint( self::MEMBER_PAGE_SIZE ),
			$paginate ? ' data-paginate="true"' : ''
		);

		/*
		 * Columns the reader cannot usefully sort are marked here rather than by
		 * position in the script, because the free table is four columns and the
		 * PRO one is eight - an index that is "platform" in one is off the end of
		 * the other.
		 */
		echo '<thead><tr>';
		echo '<th class="kc-us-st-members__rank kc-us-st-nosort">' . esc_html__( '#', 'url-shortify' ) . '</th>';
		echo '<th>' . esc_html__( 'Link', 'url-shortify' ) . '</th>';
		echo '<th class="kc-us-st-members__num">' . esc_html__( 'Total Clicks', 'url-shortify' ) . '</th>';
		echo '<th class="kc-us-st-members__num">' . esc_html__( 'Unique Clicks', 'url-shortify' ) . '</th>';

		if ( $show_audience ) {
			echo '<th class="kc-us-st-members__num kc-us-st-members__gap-after">' . esc_html__( 'Share', 'url-shortify' ) . '</th>';
			echo '<th class="kc-us-st-nosort">' . esc_html__( 'Device', 'url-shortify' ) . '</th>';
			echo '<th class="kc-us-st-nosort">' . esc_html__( 'Browser', 'url-shortify' ) . '</th>';
			echo '<th class="kc-us-st-nosort">' . esc_html__( 'Platform', 'url-shortify' ) . '</th>';
		}

		echo '</tr></thead><tbody>';

		$rank = 0;

		foreach ( $rows as $row ) {
			$rank ++;

			$value  = (int) Helper::get_data( $row, 'value', 0 );
			$unique = (int) Helper::get_data( $row, 'unique', 0 );
			$share  = (float) Helper::get_data( $row, 'share', 0 );
			$delta  = Helper::get_data( $row, 'delta', null );

			printf( '<tr class="%s">', 0 === $value ? 'is-dormant' : '' );

			printf(
				'<td class="kc-us-st-members__rank">%s</td>',
				esc_html( number_format_i18n( $rank ) )
			);

			// Link: title, with the slug beneath it pointing at the short URL.
			echo '<td>';
			printf(
				'<a class="kc-us-st-members__name" href="%1$s">%2$s</a>',
				esc_url( Helper::get_data( $row, 'url', '' ) ),
				esc_html( Helper::get_data( $row, 'label', '' ) )
			);

			$slug      = (string) Helper::get_data( $row, 'slug', '' );
			$short_url = (string) Helper::get_data( $row, 'short_url', '' );

			if ( '' !== $slug ) {
				echo '<span class="kc-us-st-members__sub">';

				if ( '' !== $short_url ) {
					printf(
						'<a href="%1$s" target="_blank" rel="noopener noreferrer" title="%1$s">/%2$s</a>',
						esc_url( $short_url ),
						esc_html( $slug )
					);

					/*
					 * The short link is the thing people came here to hand out,
					 * so it is copyable from the row rather than only from the
					 * link's own screen.
					 */
					echo Helper::create_copy_short_link_html( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
						esc_attr( $short_url ),
						'member-' . absint( Helper::get_data( $row, 'id', 0 ) )
					);
				} else {
					echo '/' . esc_html( $slug );
				}

				echo '</span>';
			}

			echo '</td>';

			// Total clicks, with the change against the previous period beneath.
			printf(
				'<td class="kc-us-st-members__num" data-order="%1$d"><span class="kc-us-st-members__value">%2$s</span>',
				absint( $value ),
				esc_html( number_format_i18n( $value ) )
			);

			if ( $show_delta && ! empty( $delta ) ) {
				printf(
					'<span class="kc-us-st-members__sub kc-us-st-members__sub--%1$s">%2$s</span>',
					esc_attr( $delta['tone'] ),
					esc_html( $delta['label'] )
				);
			}

			echo '</td>';

			printf(
				'<td class="kc-us-st-members__num" data-order="%1$d">%2$s</td>',
				absint( $unique ),
				esc_html( number_format_i18n( $unique ) )
			);

			if ( ! $show_audience ) {
				echo '</tr>';

				continue;
			}

			// Share: the bar earns its place here because the set is fixed, so the
			// lengths are comparable in a way a report's selection is not.
			printf(
				'<td class="kc-us-st-members__num kc-us-st-members__gap-after" data-order="%1$s">'
					. '<span class="kc-us-st-members__share-value">%2$s</span>'
					. '<span class="kc-us-st-bar__track"><span class="kc-us-st-bar__fill" style="width:%3$s%%"></span></span>'
					. '</td>',
				esc_attr( $share ),
				esc_html( number_format_i18n( $share, 1 ) . '%' ),
				esc_attr( $share > 0 ? max( 2, $share ) : 0 )
			);

			$attributes = [
				[ Helper::get_data( $row, 'device', [] ), [ Utils::class, 'get_device_icon_url' ] ],
				[ Helper::get_data( $row, 'browser', [] ), [ Utils::class, 'get_browser_icon_url' ] ],
				[ Helper::get_data( $row, 'platform', [] ), [ Utils::class, 'get_platform_icon_url' ] ],
			];

			foreach ( $attributes as $attribute ) {
				list( $info, $icon_callback ) = $attribute;

				if ( empty( $info ) || '' === (string) Helper::get_data( $info, 'value', '' ) ) {
					echo '<td><span class="kc-us-st-members__muted">&mdash;</span></td>';

					continue;
				}

				printf(
					'<td><span class="kc-us-st-members__attr">'
						. '<img class="kc-us-st-members__icon" src="%1$s" alt="" />'
						. '<span><span class="kc-us-st-members__value">%2$s</span>'
						. '<span class="kc-us-st-members__sub">%3$s</span></span>'
						. '</span></td>',
					esc_url( call_user_func( $icon_callback, Helper::get_data( $info, 'value', '' ) ) ),
					esc_html( Helper::get_data( $info, 'value', '' ) ),
					esc_html( number_format_i18n( (float) Helper::get_data( $info, 'share', 0 ), 1 ) . '%' )
				);
			}

			echo '</tr>';
		}

		echo '</tbody></table>';
		echo '</div>';
	}

	/**
	 * Shapes for the panels a free site cannot fill.
	 *
	 * Deliberately plausible but obviously generic, and always behind a veil
	 * that says so: the point is to show what the panel looks like when it has
	 * something in it, never to pass illustration off as this site's data.
	 *
	 * @since 2.7.0
	 *
	 * @param string $panel
	 *
	 * @return array
	 */
	public static function sample_rows( $panel ) {
		$samples = [
			'devices' => [
				[ 'label' => __( 'Desktop', 'url-shortify' ), 'value' => 64 ],
				[ 'label' => __( 'Mobile', 'url-shortify' ), 'value' => 31 ],
				[ 'label' => __( 'Tablet', 'url-shortify' ), 'value' => 5 ],
			],
			'browsers' => [
				[ 'label' => 'Chrome', 'value' => 52 ],
				[ 'label' => 'Safari', 'value' => 23 ],
				[ 'label' => 'Edge', 'value' => 14 ],
				[ 'label' => 'Firefox', 'value' => 11 ],
			],
			'platforms' => [
				[ 'label' => 'Windows', 'value' => 37 ],
				[ 'label' => 'Android', 'value' => 24 ],
				[ 'label' => 'iOS', 'value' => 21 ],
				[ 'label' => 'macOS', 'value' => 18 ],
			],
			'locations' => [
				[ 'label' => __( 'United States', 'url-shortify' ), 'value' => 48 ],
				[ 'label' => __( 'India', 'url-shortify' ), 'value' => 22 ],
				[ 'label' => __( 'United Kingdom', 'url-shortify' ), 'value' => 17 ],
				[ 'label' => __( 'Germany', 'url-shortify' ), 'value' => 13 ],
			],
			'referrers' => [
				[ 'label' => 'google.com', 'value' => 44 ],
				[ 'label' => 'facebook.com', 'value' => 26 ],
				[ 'label' => 'x.com', 'value' => 18 ],
				[ 'label' => 'linkedin.com', 'value' => 12 ],
			],
		];

		return Helper::get_data( $samples, $panel, [] );
	}

	/**
	 * A standings table shaped like the real one, for the locked dashboard panel.
	 *
	 * Behind a veil that says what it is, like every other sample here.
	 *
	 * @since 2.7.0
	 *
	 * @return array
	 */
	public static function sample_members() {
		$sample = [
			[ __( 'Spring campaign', 'url-shortify' ), 'spring-sale', 1840, 1206, 28.4, 'Desktop', 'Chrome', 'Windows' ],
			[ __( 'Pricing page', 'url-shortify' ), 'pricing', 1322, 905, 20.4, 'Mobile', 'Safari', 'iOS' ],
			[ __( 'Getting started', 'url-shortify' ), 'getting-started', 964, 701, 14.9, 'Desktop', 'Chrome', 'macOS' ],
			[ __( 'Newsletter', 'url-shortify' ), 'newsletter', 612, 448, 9.4, 'Mobile', 'Chrome', 'Android' ],
		];

		$rows = [];

		foreach ( $sample as $row ) {
			list( $label, $slug, $total, $uniques, $share, $device, $browser, $platform ) = $row;

			$rows[] = [
				'id'        => 0,
				'label'     => $label,
				'slug'      => $slug,
				'url'       => '',
				'short_url' => '',
				'value'     => $total,
				'unique'    => $uniques,
				'share'     => $share,
				'delta'     => null,
				'device'    => [ 'value' => $device, 'share' => 64.0 ],
				'browser'   => [ 'value' => $browser, 'share' => 52.0 ],
				'platform'  => [ 'value' => $platform, 'share' => 37.0 ],
			];
		}

		return [ 'rows' => $rows, 'members' => count( $rows ), 'dormant' => 0 ];
	}

	/**
	 * A year-shaped grid for the locked activity panel.
	 *
	 * @since 2.7.0
	 *
	 * @return void
	 */
	public static function sample_heatmap() {
		echo '<div class="kc-us-st-yearmap" aria-hidden="true">';

		// A fixed pattern rather than random values, so the panel does not
		// flicker into a different shape on every page load.
		for ( $cell = 0; $cell < 371; $cell ++ ) {
			$level = ( $cell * 7 + (int) floor( $cell / 9 ) ) % 6;

			printf( '<span class="kc-us-st-cell kc-us-st-cell--%d"></span>', absint( $level ) );
		}

		echo '</div>';
	}

	/**
	 * Turn a label => count map into bar rows, largest first.
	 *
	 * @since 2.7.0
	 *
	 * @param array         $map
	 * @param int           $limit
	 * @param callable|null $icon_callback Given a label, returns an icon URL.
	 *
	 * @return array
	 */
	public static function rows_from_map( $map, $limit = 8, $icon_callback = null ) {
		$map = array_filter(
			(array) $map,
			function ( $value, $key ) {
				return is_numeric( $value ) && '' !== (string) $key;
			},
			ARRAY_FILTER_USE_BOTH
		);

		if ( empty( $map ) ) {
			return [];
		}

		$map = array_map( 'intval', $map );

		arsort( $map );

		$total = array_sum( $map );
		$rows  = [];

		foreach ( array_slice( $map, 0, $limit, true ) as $label => $value ) {
			$rows[] = [
				'label' => $label,
				'value' => $value,
				'share' => $total > 0 ? round( ( $value * 100 ) / $total, 1 ) : null,
				'icon'  => is_callable( $icon_callback ) ? call_user_func( $icon_callback, $label ) : '',
			];
		}

		return $rows;
	}
}
