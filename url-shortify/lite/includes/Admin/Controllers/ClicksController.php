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
	public static function get_table_columns() {
		return [
			'ip'         => [ 'title' => __( 'IP', 'url-shortify' ) ],
			'uri'        => [ 'title' => __( 'URI', 'url-shortify' ) ],
			'link'       => [ 'title' => __( 'Link', 'url-shortify' ) ],
			'host'       => [ 'title' => __( 'Host', 'url-shortify' ) ],
			'referrer'   => [ 'title' => __( 'Referrer', 'url-shortify' ) ],
			'clicked_on' => [ 'title' => __( 'Clicked On', 'url-shortify' ) ],
			'info'       => [ 'title' => __( 'Info', 'url-shortify' ) ],
		];
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
			echo "<th data-key='" . $key . "'>" . $column['title'] . '</th>';
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
	 * Render IP column
	 *
	 * @since 1.1.5
	 *
	 * @param array $click
	 *
	 */
	public function column_ip( $click = [] ) {
		$country = $click['country'];

		$country_name = '';
		if ( $country ) {
			$country_name = Utils::get_country_name_from_iso_code( $country );
		}

		$td = '';

		$td .= "<td data-search='" . $country_name . "'>";

		$td .= "<span class='flex inline'>";
		$td .= "<p class='w-6 mr-3'>";
		if ( $country ) {

			$country_icon = Utils::get_country_icon_url( $click['country'] );

			if ( ! empty( $country_icon ) ) {
				$td .= "<img src='{$country_icon}' title='{$country_name}' alt='{$country_name}' class='h-6 w-6 mr-4'/>";
			}
		}

		$td .= "</p><p class='pt-1'>";
		$td .= $click['ip'];

		$td .= '</p></span>';

		$td .= '</td>';

		return $td;
	}

	/**
	 * Render info column
	 *
	 * @since 1.1.5
	 *
	 * @param array $click
	 *
	 */
	public function column_info( $click = [] ) {
		$device = esc_attr( $click['device'] );

		$browser = esc_attr( $click['browser_type'] );

		$td = '';

		$td .= "<td data-search='" . $device . '|' . $browser . "'";

		$td .= "<span class='flex inline'>";

		$device_icon = Utils::get_device_icon_url( $device );

		$td .= "<img src='{$device_icon}' title='{$device}' alt='{$device}' class='h-4 w-4 mr-4'/>";

		$browser_icon = Utils::get_browser_icon_url( $browser );

		$td .= "<img src='{$browser_icon}' title='{$browser}' alt='{$browser}' class='h-4 w-4 mr-4'/>";

		if ( $click['is_robot'] == 1 ) {
			$robot_icon = KC_US_PLUGIN_ASSETS_DIR_URL . '/images/browsers/robot.svg';

			$td .= "<img src='{$robot_icon}' title='Robot' alt='Robot' class='h-4 w-4' />";
		}

		$td .= '</span>';

		$td .= '</td>';

		return $td;
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
			echo $this->get_column_html( $key, $click );
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
	protected function get_column_html( $key, $click ) {
		switch ( $key ) {
			case 'ip':
				return $this->column_ip( $click );
			case 'host':
				return '<td>' . esc_html( $click['host'] ) . '</td>';
			case 'referrer':
				return sprintf(
					"<td class='cursor-default' title='%s'>%s</td>",
					esc_attr( $click['referer'] ),
					esc_html( Helper::str_limit( $click['referer'], 50 ) )
				);
			case 'uri':
				return sprintf(
					"<td class='cursor-default' title='%s'><b>%s</b></td>",
					esc_url( $click['uri'] ),
					esc_url( Helper::str_limit( $click['uri'], 50 ) )
				);
			case 'link':
				$link_id        = $click['link_id'];
				$link_stats_url = Helper::get_link_action_url( $link_id, 'statistics' );
				return sprintf(
					"<td><a href='%s'>%s</a></td>",
					esc_url( $link_stats_url ),
					esc_html( $click['name'] )
				);
			case 'clicked_on':
				return "<td data-order='" . esc_attr( $click['created_at'] ) . "'>" . esc_html( Helper::format_date_time( $click['created_at'] ) ) . '</td>';
			case 'info':
				return $this->column_info( $click );
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