<?php

namespace KaizenCoders\URL_Shortify\Admin\Controllers;

use KaizenCoders\URL_Shortify\Helper;

class LinksTableController extends BaseController {
	/**
	 * @since 1.1.7
	 * @var array
	 *
	 */
	public $columns = [];

	/**
	 * LinksTableController constructor.
	 *
	 * @since 1.1.7
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Set columns
	 *
	 * @since 1.1.7
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
	 * @since 1.1.7
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
	 * @since 1.1.7
	 */
	public function render_header() {
		$this->render_columns();
	}

	/**
	 * Prepare name column
	 *
	 * @since 1.1.7
	 *
	 * @param $item
	 *
	 * @return string
	 *
	 */
	public function column_name( $item ) {
		$domain = $item['url'];

		return '<td><span class="flex w-full"><img class="h-6 w-6 mr-2" src="https://www.google.com/s2/favicons?domain=' . $domain . '" title="' . $domain . '"/><strong>' . stripslashes( $item['name'] ) . '</strong></span></td>';
	}

	/**
	 * Prepare Clicks column
	 *
	 * @since 1.1.7
	 *
	 * @param $item
	 *
	 * @return string
	 *
	 */
	public function column_clicks( $item ) {
		$link_id = $item['id'];

		$stats_url = Helper::create_link_stats_url( $link_id );

		return '<td>' . Helper::prepare_clicks_column( $link_id, $stats_url ) . '</td>';
	}

	/**
	 * Prepare created_at column
	 *
	 * @since 1.1.7
	 *
	 * @param $item
	 *
	 * @return string
	 *
	 */
	public function column_created_at( $item ) {
		return "<td data-key='{$item['created_at']}' data-order='{$item['created_at']}'>" . Helper::format_date_time( $item['created_at'] ) . '</td>';
	}

	/**
	 * Prepare link column
	 *
	 * @since 1.1.7
	 *
	 * @param $item
	 *
	 * @return string
	 *
	 */
	public function column_link( $item ) {
		$slug = Helper::get_data( $item, 'slug', '' );

		if ( empty( $slug ) ) {
			return '';
		}

		$id = Helper::get_data( $item, 'id', 0 );

		$link = esc_url( Helper::get_short_link( $slug, $item ) );

		$input_html = '<input type="text" readonly="true" style="width: 65%;" onclick="this.select();" value="' . $link . '" class="kc-us-link" />';

		$html = Helper::create_copy_short_link_html( $link, $id, $input_html );

		$html .= '';

		return '<td>' . $html . '</td>';
	}

	/**
	 * Render ROW
	 *
	 * @since 1.1.7
	 *
	 * @param array $link
	 *
	 */
	public function render_row( $link = [] ) {
		echo '<tr>';

		foreach ( $this->columns as $key => $column ) {

			$fn_name = "column_$key";
			switch ( $key ) {
				default:
					echo $this->$fn_name( $link );
					break;
			}

		}

		echo '</tr>';
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
