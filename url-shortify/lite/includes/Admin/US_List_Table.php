<?php

namespace KaizenCoders\URL_Shortify\Admin;

use KaizenCoders\URL_Shortify\Admin\DB\Base_DB;
use KaizenCoders\URL_Shortify\Helper;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class US_List_Table extends \WP_List_Table {

	/**
	 * @var object|Base_DB
	 *
	 */
	public $db = null;

	/**
	 * Perpage items
	 *
	 * @since 1.0.4
	 * @var int
	 *
	 */
	public $per_page = 10;

	/**
	 * Prepare Items
	 *
	 * @since 1.0.0
	 */
	public function prepare_items() {
		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		$search_str = Helper::get_request_data( 's' );

		$this->search_box( $search_str, 'form-search-input' );

		$per_page = $this->get_items_per_page( static::$option_per_page, 10 );

		$current_page = $this->get_pagenum();
		$total_items  = $this->get_lists( 0, 0, true );

		$this->set_pagination_args( [
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page, //WE have to determine how many items to show on a page
		] );

		$this->items = $this->get_lists( $per_page, $current_page );
	}

	/**
	 * @param  int    $current_page
	 * @param  false  $do_count_only
	 *
	 * @param  int    $per_page
	 *
	 * @since 1.0.0
	 *
	 */
	public function get_lists( $per_page = 10, $current_page = 1, $do_count_only = false ) {
	}

	/**
	 * @since 1.0.0
	 */
	public function process_bulk_action() {
	}

	/**
	 * Hide default search box
	 *
	 * @param  string  $input_id
	 *
	 * @param  string  $text
	 *
	 * @since 1.0.3
	 *
	 */
	public function search_box( $text, $input_id ) {
	}


	/**
	 * Hide top pagination
	 *
	 * @param  string  $which
	 *
	 * @since 1.0.3
	 *
	 */
	public function pagination( $which ) {
		if ( $which == 'bottom' ) {
			parent::pagination( $which );
		}
	}

	/**
	 * Add extra table nav.
	 *
	 * @param $which
	 *
	 * @return void
	 */
	protected function extra_tablenav( $which ) {
		if ( $which == 'bottom' ) {
			parent::extra_tablenav( $which );
		}

	}

	/**
	 * Overriding the parent method to support grouped bulk actions (optgroups)
	 *
	 * @since 1.12.3
	 */
	protected function bulk_actions( $which = '' ) {
		if ( is_null( $this->_actions ) ) {
			$this->_actions = $this->get_bulk_actions();

			/**
			 * Filters the items in the bulk actions menu of the list table.
			 *
			 * The dynamic portion of the hook name, `$this->screen->id`, refers
			 * to the ID of the current screen.
			 *
			 * @param  array  $actions  An array of the available bulk actions.
			 *
			 * @since 5.6.0 A bulk action can now contain an array of options in order to create an optgroup.
			 *
			 * @since 3.1.0
			 */
			$this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

			$two = '';
		} else {
			$two = '2';
		}

		if ( empty( $this->_actions ) ) {
			return;
		}

		echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' .
			 /* translators: Hidden accessibility text. */
			 __( 'Select bulk action' ) .
			 '</label>';
		echo '<select name="action' . $two . '" id="bulk-action-selector-' . esc_attr( $which ) . "\">\n";
		echo '<option value="-1">' . __( 'Bulk actions' ) . "</option>\n";

		foreach ( $this->_actions as $key => $group ) {
			if ( is_array( $group ) && isset( $group['values'] ) ) {
				echo '<optgroup label="' . esc_attr( $group['label'] ) . "\">\n";
				foreach ( $group['values'] as $action_key => $action_label ) {
					echo "\t" . '<option value="' . esc_attr( $action_key ) . '">' . $action_label . "</option>\n";
				}
				echo "</optgroup>\n";
			} else {
				echo "\t" . '<option value="' . esc_attr( $key ) . '">' . $group . "</option>\n";
			}
		}
		echo "</select>\n";
		submit_button( __( 'Apply' ), 'action', '', false, [ 'id' => 'doaction' . $two ] );
	}

	/**
	 * Add Row action
	 *
	 * @param  bool      $always_visible
	 * @param  string    $class
	 *
	 * @param  string[]  $actions
	 *
	 * @return string
	 *
	 * @since  1.0.4
	 *
	 * @modify 1.1.3 Added third argument $class
	 *
	 */
	protected function row_actions( $actions, $always_visible = false, $class = '' ) {
		$action_count = count( $actions );
		$i            = 0;

		if ( ! $action_count ) {
			return '';
		}

		$out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions ' . $class ) . '">';
		foreach ( $actions as $action => $link ) {
			++ $i;
			( $i == $action_count ) ? $sep = '' : $sep = ' | ';
			$out .= "<span class='$action'>$link$sep</span>";
		}
		$out .= '</div>';

		$out .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __( 'Show more details',
				'url-shortify' ) . '</span></button>';

		return $out;
	}

	/**
	 * Save Form Data
	 *
	 * @param  null   $id
	 *
	 * @param  array  $data
	 *
	 * @return bool|int
	 *
	 * @since 1.0.0
	 *
	 */
	public function save( $data = [], $id = null ) {
		if ( empty( $id ) ) {
			return $this->db->insert( $data );
		} else {
			return $this->db->update( $id, $data );
		}
	}

}
