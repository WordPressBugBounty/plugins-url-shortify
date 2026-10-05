<?php

namespace KaizenCoders\URL_Shortify\Admin\Controllers;

use KaizenCoders\URL_Shortify\Common\Utils;
use KaizenCoders\URL_Shortify\Helper;

class ClicksController extends BaseController {
	/**
	 * @since 1.1.5
	 * @var array
	 *
	 */
	public $columns = [];

	/**
	 * Return default table columns for clicks listings.
	 *
	 * @since 2.1.x
	 *
	 * @return array
	 */
	public static function get_table_columns( $single_link = false ) {
		$columns = [
			'visitor' => [
				'title'    => __( 'Visitor', 'url-shortify' ),
				'order_by' => 'ip',
			],
			'link' => [
				'title'    => __( 'Link', 'url-shortify' ),
				'order_by' => 'name',
			],
			'source' => [
				'title'    => __( 'Source', 'url-shortify' ),
				'order_by' => 'referer',
			],
			'device' => [
				'title'    => __( 'Device', 'url-shortify' ),
				'order_by' => 'device',
			],
			'clicked_on' => [
				'title'    => __( 'When', 'url-shortify' ),
				'order_by' => 'created_at',
				'class'    => 'kc-us-clicks__when',
			],
		];

		/*
		 * On a single link's statistics every row names the same link, so the
		 * column is a vertical stripe of the page title. The dashboard and the
		 * group, tag and report screens span several links, where it is the thing
		 * that tells the rows apart.
		 */
		if ( $single_link ) {
			unset( $columns['link'] );
		}

		return $columns;
	}

	/**
	 * Map DataTables column indexes onto the columns actually rendered.
	 *
	 * Ordering broke quietly whenever the column set changed, because the map
	 * lived in the ajax handler as a list of hardcoded positions. Deriving it
	 * from the same definition keeps the two in step.
	 *
	 * @since 2.7.0
	 *
	 * @param bool $single_link
	 *
	 * @return array
	 */
	public static function get_order_column_map( $single_link = false ) {
		$map = [];

		foreach ( array_values( self::get_table_columns( $single_link ) ) as $index => $column ) {
			$map[ $index ] = Helper::get_data( $column, 'order_by', 'created_at' );
		}

		return $map;
	}

	/**
	 * ClicksController constructor.
	 *
	 * @since 1.1.5
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Set columns
	 *
	 * @since 1.1.5
	 *
	 * @param array $columns
	 *
	 */
	public function set_columns( $columns = [] ) {
		$this->columns = $columns;
	}

	/**
	 * Render columns
	 *
	 * @since 1.1.5
	 */
	public function render_columns() {
		echo '<tr>';

		foreach ( $this->columns as $key => $column ) {
			printf(
				'<th data-key="%1$s" class="%2$s">%3$s</th>',
				esc_attr( $key ),
				esc_attr( Helper::get_data( $column, 'class', '' ) ),
				esc_html( $column['title'] )
			);
		}

		echo '</tr>';
	}

	/**
	 * Render header
	 *
	 * @since 1.1.5
	 */
	public function render_header() {
		$this->render_columns();
	}

	/**
	 * Visitor cell: where they were, and the address behind it.
	 *
	 * The old table carried IP and Host as separate columns showing the same
	 * value; country is the part a person reads, so it leads and the address
	 * sits under it.
	 *
	 * @since 1.1.5
	 *
	 * @param array $click
	 *
	 * @return string
	 */
	public function column_visitor( $click = [] ) {
		$country      = Helper::get_data( $click, 'country', '' );
		$country_name = $country ? Utils::get_country_name_from_iso_code( $country ) : '';
		$ip           = Helper::get_data( $click, 'ip', '' );

		$flag = '';

		if ( $country ) {
			$icon = Utils::get_country_icon_url( $country );

			if ( ! empty( $icon ) ) {
				$flag = sprintf(
					'<img src="%1$s" alt="" class="kc-us-clicks__flag" />',
					esc_url( $icon )
				);
			}
		}

		return sprintf(
			'<td data-search="%1$s"><span class="kc-us-clicks__visitor">%2$s<span class="kc-us-clicks__stack"><span class="kc-us-clicks__primary">%3$s</span><span class="kc-us-clicks__muted">%4$s</span></span></span></td>',
			esc_attr( $country_name . ' ' . $ip ),
			$flag,
			esc_html( '' !== $country_name ? $country_name : __( 'Unknown', 'url-shortify' ) ),
			esc_html( $ip )
		);
	}

	/**
	 * Source cell: the site that sent the click, not the whole URL.
	 *
	 * @since 2.7.0
	 *
	 * @param array $click
	 *
	 * @return string
	 */
	public function column_source( $click = [] ) {
		$referer = trim( (string) Helper::get_data( $click, 'referer', '' ) );

		if ( '' === $referer ) {
			return sprintf(
				'<td data-search="%1$s"><span class="kc-us-clicks__muted" title="%2$s">%1$s</span></td>',
				esc_html__( 'Direct', 'url-shortify' ),
				esc_attr__( 'No referrer: typed, bookmarked, or opened from an app or email', 'url-shortify' )
			);
		}

		$host = wp_parse_url( $referer, PHP_URL_HOST );
		$host = $host ? preg_replace( '/^www\./i', '', $host ) : $referer;

		return sprintf(
			'<td data-search="%1$s"><span class="kc-us-clicks__primary" title="%2$s">%1$s</span></td>',
			esc_html( $host ),
			esc_attr( $referer )
		);
	}

	/**
	 * Device cell: icons with the words beside them.
	 *
	 * Icons alone made this column a row of unlabelled glyphs, which reads as
	 * decoration rather than data.
	 *
	 * @since 1.1.5
	 *
	 * @param array $click
	 *
	 * @return string
	 */
	public function column_device( $click = [] ) {
		$device  = (string) Helper::get_data( $click, 'device', '' );
		$browser = (string) Helper::get_data( $click, 'browser_type', '' );
		$os      = (string) Helper::get_data( $click, 'os', '' );

		$icons = '';

		$device_icon = $device ? Utils::get_device_icon_url( $device ) : '';

		if ( ! empty( $device_icon ) ) {
			$icons .= sprintf( '<img src="%1$s" alt="" title="%2$s" class="kc-us-clicks__icon" />', esc_url( $device_icon ), esc_attr( $device ) );
		}

		$browser_icon = $browser ? Utils::get_browser_icon_url( $browser ) : '';

		if ( ! empty( $browser_icon ) ) {
			$icons .= sprintf( '<img src="%1$s" alt="" title="%2$s" class="kc-us-clicks__icon" />', esc_url( $browser_icon ), esc_attr( $browser ) );
		}

		$label = $browser;

		if ( '' !== $os ) {
			$label = '' !== $browser
				/* translators: 1: browser name, 2: operating system. */
				? sprintf( __( '%1$s on %2$s', 'url-shortify' ), $browser, $os )
				: $os;
		}

		if ( '' === $label ) {
			$label = __( 'Unknown', 'url-shortify' );
		}

		$robot = '';

		if ( 1 === (int) Helper::get_data( $click, 'is_robot', 0 ) ) {
			$robot = sprintf(
				'<span class="kc-us-clicks__badge">%s</span>',
				esc_html__( 'Bot', 'url-shortify' )
			);
		}

		return sprintf(
			'<td data-search="%1$s"><span class="kc-us-clicks__device">%2$s<span class="kc-us-clicks__stack"><span class="kc-us-clicks__primary">%3$s</span><span class="kc-us-clicks__muted">%4$s</span></span>%5$s</span></td>',
			esc_attr( trim( $device . ' ' . $browser . ' ' . $os ) ),
			$icons,
			esc_html( $label ),
			esc_html( $device ),
			$robot
		);
	}

	/**
	 * When the click happened, said the way people ask about it.
	 *
	 * @since 2.7.0
	 *
	 * @param array $click
	 *
	 * @return string
	 */
	public function column_clicked_on( $click = [] ) {
		$created_at = (string) Helper::get_data( $click, 'created_at', '' );

		if ( '' === $created_at ) {
			return '<td></td>';
		}

		$timestamp = strtotime( $created_at );
		$exact     = Helper::format_date_time( $created_at );

		// Anything older than a week reads better as a date than as "63 days ago".
		$relative = ( $timestamp && ( time() - $timestamp ) < WEEK_IN_SECONDS )
			/* translators: %s: human readable time difference, e.g. "2 hours". */
			? sprintf( __( '%s ago', 'url-shortify' ), human_time_diff( $timestamp, time() ) )
			: $exact;

		return sprintf(
			'<td data-order="%1$s" class="kc-us-clicks__when"><span class="kc-us-clicks__primary" title="%2$s">%3$s</span></td>',
			esc_attr( $created_at ),
			esc_attr( $exact ),
			esc_html( $relative )
		);
	}

	/**
	 * Render ROW
	 *
	 * @since 1.1.5
	 *
	 * @param array $click
	 *
	 */
	public function render_row( $click = [] ) {
		echo '<tr>';

		foreach ( $this->columns as $key => $column ) {
			echo $this->get_column_html( $key, $click ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderers escape.
		}

		echo '</tr>';
	}

	/**
	 * Return the column HTML string for a given key.
	 *
	 * @param string $key
	 * @param array  $click
	 *
	 * @return string
	 */
	public function get_column_html( $key, $click ) {
		switch ( $key ) {
			case 'visitor':
				return $this->column_visitor( $click );
			case 'source':
				return $this->column_source( $click );
			case 'device':
				return $this->column_device( $click );
			case 'clicked_on':
				return $this->column_clicked_on( $click );
			case 'link':
				$link_id        = Helper::get_data( $click, 'link_id', 0 );
				$link_stats_url = Helper::get_link_action_url( $link_id, 'statistics' );

				return sprintf(
					'<td><a class="kc-us-clicks__link" href="%1$s">%2$s</a></td>',
					esc_url( $link_stats_url ),
					esc_html( Helper::get_data( $click, 'name', '' ) )
				);
			default:
				return '<td></td>';
		}
	}

	/**
	 * Return the raw cell contents (without <td> wrappers).
	 *
	 * @param array $click
	 *
	 * @return array
	 */
	public function get_row_cells( $click = [] ) {
		$cells = [];
		foreach ( $this->columns as $key => $column ) {
			$cell_html = $this->get_column_html( $key, $click );
			$cells[]   = preg_replace( array( '/^<td[^>]*>/', '/<\/td>$/' ), '', $cell_html );
		}
		return $cells;
	}

	/**
	 * Render Footer
	 *
	 * @since 1.1.5
	 */
	public function render_footer() {
		$this->render_columns();
	}
}