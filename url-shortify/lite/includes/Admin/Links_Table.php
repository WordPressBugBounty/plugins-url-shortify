<?php

namespace KaizenCoders\URL_Shortify\Admin;

use KaizenCoders\URL_Shortify\Admin\Controllers\LinkStatsController;
use KaizenCoders\URL_Shortify\Admin\DB\Links;
use KaizenCoders\URL_Shortify\Cache;
use KaizenCoders\URL_Shortify\Common\Export;
use KaizenCoders\URL_Shortify\Common\Utils;
use KaizenCoders\URL_Shortify\Helper;

/**
 * Class Links_Table
 *
 * @since   1.0.0
 * @package KaizenCoders\URL_Shortify\Admin
 *
 */
class Links_Table extends US_List_Table {
	/**
	 * @since 1.0.0
	 * @var string
	 *
	 */
	public static $option_per_page = 'us_links_per_page';

	/**
	 * @var Links
	 */
	public $db;

	/**
	 * Group ID Name Map
	 *
	 * @since 1.3.7
	 */
	public $group_id_name_map;

	/**
	 * Map of clicks data.
	 *
	 * @since 1.9.0
	 * @var array
	 *
	 */
	public $link_ids_clicks_data = [];

	/**
	 * Get groups of all links.
	 *
	 * @since 1.9.0
	 * @var array
	 *
	 */
	public $links_ids_group_ids = [];

	/**
	 * Links ids shown on current page.
	 *
	 * @since 1.9.0
	 * @var array
	 *
	 */
	public $link_ids = [];

	/**
	 * Links_Table constructor.
	 */
	public function __construct() {

		parent::__construct( [
			'singular' => __( 'Link', 'url-shortify' ), //singular name of the listed records
			'plural'   => __( 'Links', 'url-shortify' ), //plural name of the listed records
			'ajax'     => false, //does this table support ajax?
			'screen'   => 'us_links',
		] );

		$this->db = new Links();

		$this->group_id_name_map = US()->db->groups->get_all_id_name_map();

		$link_id = Helper::get_request_data( 'id', null );

		if ( ! is_null( $link_id ) ) {
			if ( US()->access->can( 'create_links' ) && ! US()->access->can( 'manage_links' ) ) {
				$link            = $this->db->get_by_id( $link_id );
				$created_by_id   = Helper::get_data( $link, 'created_by_id', 0 );
				$current_user_id = get_current_user_id();
				if ( $created_by_id != $current_user_id ) {
					die( 'You do not have permission to access this page' );
				}
			}
		}
	}

	/**
	 * Add Screen Option
	 *
	 * @since 1.0.0
	 */
	public static function screen_options() {
		$action = Helper::get_request_data( 'action' );

		$restricted_actions = [ 'new', 'edit', 'statistics', 'export' ];

		if ( ! in_array( $action, $restricted_actions ) ) {

			$option = 'per_page';
			$args   = [
				'label'   => __( 'Number of Links per page', 'url-shortify' ),
				'default' => 10,
				'option'  => self::$option_per_page,
			];

			add_screen_option( $option, $args );
		}

	}

	/**
	 * Render links page
	 *
	 * @since 1.0.0
	 */
	public function render() {
		try {

			$action = Helper::get_request_data( 'action' );

			if ( 'new' === $action || 'edit' === $action ) {

				$link_id = Helper::get_request_data( 'id', null );

				$this->render_form( $link_id );

			} elseif ( 'statistics' === $this->current_action() ) {
				// In our file that handles the request, verify the nonce.
				$nonce = Helper::get_request_data( '_wpnonce' );

				if ( ! wp_verify_nonce( $nonce, 'us_action_nonce' ) ) {
					$message = __( 'You do not have permission to view statistics of this link.', 'url-shortify' );
					US()->notices->error( $message );
				} else {

					$link_id = Helper::get_request_data( 'id' );

					if ( ! empty( $link_id ) ) {
						$link = new LinkStatsController( $link_id );
						$link->render();
					}
				}
			} elseif ( 'export' === $this->current_action() ) {
				// In our file that handles the request, verify the nonce.
				$nonce = Helper::get_request_data( '_wpnonce' );

				if ( ! wp_verify_nonce( $nonce, 'us_action_nonce' ) ) {
					$message = __( 'You do not have permission to export statistics of this link.', 'url-shortify' );
					US()->notices->error( $message );
				} else {

					$link_id = Helper::get_request_data( 'id' );

					if ( ! empty( $link_id ) ) {
						$link = new LinkStatsController( $link_id );
						$link->export();
						die();
					}
				}
			} elseif ( 'export_links' === $this->current_action() ) {
				// In our file that handles the request, verify the nonce.
				$nonce = Helper::get_request_data( '_wpnonce' );

				if ( ! wp_verify_nonce( $nonce, 'us_action_nonce' ) ) {
					$message = __( 'You do not have permission to export links.', 'url-shortify' );
					US()->notices->error( $message );
				} else {
					$this->export_links();
					die();
				}
			} else {

				$template_data = [
					'object'       => $this,
					'title'        => __( 'Links', 'url-shortify' ),
					'add_new_link' => add_query_arg( 'action', 'new', admin_url( 'admin.php?page=us_links' ) ),
					'export_link'  => Helper::get_action_url( null, 'links', 'export_links' ),
				];

				ob_start();

				include KC_US_ADMIN_TEMPLATES_DIR . '/links.php';
			}


		} catch ( \Exception $e ) {

		}

	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {

		$columns = [
			'cb'         => '<input type="checkbox" />',
			'title'      => __( 'Title', 'url-shortify' ),
			'clicks'     => __( 'Clicks', 'url-shortify' ),
			'redirect'   => __( 'Redirect Type', 'url-shortify' ),
			'groups'     => __( 'Groups', 'url-shortify' ),
			// 'linked_post' => __( 'Linked Post', 'url-shortify' ),
			'meta_info'  => __( 'Meta Info', 'url-shortify' ),
			'created_at' => __( 'Created On', 'url-shortify' ),
			'link'       => __( 'Link', 'url-shortify' ),
		];

		return apply_filters( 'kc_us_filter_links_columns', $columns );

	}

	function prepare_groups_dropdown() {
		$data = '<label for="bulk-action-selector-top" class="screen-reader-text">Select bulk action</label><select name="group_id" id="group_id" class="group_select" style="display: none;">';
		$data .= Helper::prepare_group_dropdown_options();
		$data .= '</select>';

		echo wp_kses( $data, Helper::allowed_html_tags_in_esc() );
	}

	function prepare_expiry_datepicker() {
		echo '<input type="text" class="kc-us-date-picker" name="expiry_date"  style="display: none;"/>';
	}


	/**
	 * @since 1.0.0
	 *
	 * @param  string  $column_name
	 *
	 * @param  object  $item
	 *
	 * @return string|void
	 *
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'created_at':
				return Helper::format_date_time( $item[ $column_name ] );
				break;
			default:
				return '';
		}
	}

	/**
	 * Prepare items.
	 *
	 * @since 1.9.0
	 * @return void
	 *
	 */
	public function prepare_items() {
		parent::prepare_items();

		if ( ! empty( $this->items ) ) {
			$this->link_ids = array_map( function ( $item ) {
				return $item['id'];
			}, $this->items );
		}

		// We are preparing this map to avoid DB queries later for each link data.
		$this->link_ids_clicks_data = US()->db->clicks->get_total_clicks_and_unique_clicks_by_link_ids( $this->link_ids );

		$this->links_ids_group_ids = US()->db->links_groups->get_group_ids_by_link_ids( $this->link_ids );
	}

	/**
	 * @since 1.3.1
	 *
	 * @param $item
	 *
	 * @return string
	 *
	 */
	public function column_share( $item ) {

		$link_id = Helper::get_data( $item, 'id', 0 );

		return Helper::get_social_share_widget( $link_id );
	}

	/**
	 * Method for name column
	 *
	 * @param  array  $item  an array of DB data
	 *
	 * @return string
	 */
	function column_title( $item ) {

		$link_id = $item['id'];
		$url     = esc_url( $item['url'] );
		$name    = stripslashes( $item['name'] );
		$slug    = $item['slug'];

		$short_link = esc_attr( Helper::get_short_link( $slug, $item ) );

		$title = sprintf( '<span class="flex w-full"><img class="h-6 w-6 mr-2" style="min-width: 1.5rem;" src="https://www.google.com/s2/favicons?domain=%s" title="%s"/><strong>%s</strong></span>',
			$url, $url, $name );

		$actions = [
			'edit'   => sprintf( __( '<a href="%s" class="text-indigo-600">Edit</a>', 'url-shortify' ),
				Helper::get_link_action_url( $link_id, 'edit' ) ),
			'stats'  => sprintf( __( '<a href="%s">Statistics</a>', 'url-shortify' ),
				Helper::get_link_action_url( $link_id, 'statistics' ) ),
			'delete' => sprintf( __( '<a href="%s" onclick="return confirmDelete();" >Delete</a>', 'url-shortify' ),
				Helper::get_link_action_url( $link_id, 'delete' ) ),
			'reset'  => sprintf( __( '<a href="%s" onclick="return confirmReset();" >Reset Stats</a>', 'url-shortify' ),
				Helper::get_link_action_url( $link_id, 'reset' ) ),
		];

		$actions = apply_filters( 'kc_us_filter_links_actions', $actions, $item );

		$actions['link'] = sprintf( __( '<a href="%s" target="_blank" title="Visit Link"><i class="fa fa-external-link-square"></i></a>',
			'url-shortify' ), $short_link );

		return $title . $this->row_actions( $actions, false, 'ml-8' );
	}

	/**
	 * Render link column
	 *
	 * @since 1.0.0
	 *
	 * @param $item
	 *
	 * @return string
	 *
	 */
	function column_link( $item ) {

		$slug = esc_attr( Helper::get_data( $item, 'slug', '' ) );

		if ( empty( $slug ) ) {
			return '';
		}

		$id = Helper::get_data( $item, 'id', 0 );

		$link = Helper::get_short_link( $slug, $item );

		$input_html = '<input type="text" readonly="true" style="width: 50%;" onclick="this.select();" value="/' . $slug . '" class="kc-us-link" />';

		$html = Helper::create_copy_short_link_html( $link, $id, $input_html );

		$html .= '';

		return $html;

	}

	/**
	 * Render link column
	 *
	 * @since 1.0.0
	 *
	 * @param $item
	 *
	 * @return string
	 *
	 */
	function column_clicks( $item ) {
		$link_id = Helper::get_data( $item, 'id', 0 );

		$track_me = Helper::get_data( $item, 'track_me', 0 );

		// Get data only if tracking is enabled
		if ( $track_me ) {
			$stats_url = Helper::create_link_stats_url( $link_id );

			$unique_clicks = Helper::get_data( $this->link_ids_clicks_data, $link_id . '|unique_clicks', 0 );
			$total_clicks  = Helper::get_data( $this->link_ids_clicks_data, $link_id . '|total_clicks', 0 );

			return Helper::prepare_clicks_column_with_data( $unique_clicks, $total_clicks, $stats_url );
		} else {
			return '0 / 0';
		}
	}

	/**
	 * @since 1.2.5
	 *
	 * @param $item
	 *
	 * @return string
	 *
	 */
	function column_linked_post( $item ) {
		$cpt_id   = $item['cpt_id'];
		$id       = $item['id'];
		$cpt_type = $item['cpt_type'];

		if ( empty( $cpt_id ) ) {
			return '-';
		}

		if ( empty( $cpt_type ) ) {

			$cpt_type = Helper::get_cpt_type_from_cpt_id( $cpt_id );

			if ( ! empty( $cpt_type ) ) {

				$data = [
					'cpt_type' => $cpt_type,
				];

				$this->db->update( $id, $data );
			}

		}

		$cpt_info = Helper::get_cpt_info( $cpt_type );

		$title = $cpt_info['title'];
		$icon  = $cpt_info['icon'];

		$permalink = get_permalink( $cpt_id );

		return "<a href='{$permalink}' title='{$title}' target='_blank'><img src='{$icon}' alt='{$title}' /></a>";
	}

	/**
	 * Get Meta info.
	 *
	 * @since 1.7.7
	 *
	 * @param $item
	 *
	 * @return string
	 *
	 */
	function column_meta_info( $item ) {
		$id                    = $item['id'];
		$cpt_id                = $item['cpt_id'];
		$cpt_type              = $item['cpt_type'];
		$redirect_type         = $item['redirect_type'];
		$nofollow              = $item['nofollow'];
		$is_tracking           = $item['track_me'];
		$is_sponsored          = $item['sponsored'];
		$is_params_forwarding  = $item['params_forwarding'];
		$is_password_protected = $item['password'];
		$expires_at            = $item['expires_at'];

		$cpt_icon      = KC_US_PLUGIN_ASSETS_DIR_URL . '/images/meta/post.svg';
		$forward_icon  = KC_US_PLUGIN_ASSETS_DIR_URL . '/images/meta/forward.svg';
		$password_icon = KC_US_PLUGIN_ASSETS_DIR_URL . '/images/meta/password.svg';
		$expire_icon   = KC_US_PLUGIN_ASSETS_DIR_URL . '/images/meta/expire.svg';
		$tracking_icon = KC_US_PLUGIN_ASSETS_DIR_URL . '/images/meta/tracking.svg';
		$nofollow_icon = KC_US_PLUGIN_ASSETS_DIR_URL . '/images/meta/nofollow.svg';

		if ( $cpt_id ) {
			$cpt_info = Helper::get_cpt_info( $cpt_type );

			$title = $cpt_info['title'];

			$permalink = get_permalink( $cpt_id );

			$icons[] = "<a href='{$permalink}' title='{$title}' target='_blank'><img src='{$cpt_icon}' height='24px' width='24px' alt='Post' title='Linked Post' class='pr-2' /></a>";
		}

		if ( $is_params_forwarding ) {
			$icons[] = "<img src='{$forward_icon}' height='24px' width='24px' alt='Parameter Forwarding' title='Parameter Forwarding' class='pr-2'>";
		}

		if ( $is_password_protected ) {
			$icons[] = "<img src='{$password_icon}' height='24px' width='24px' alt='Password Protected' title='Password Protected' class='pr-2'>";
		}

		if ( $expires_at ) {
			$icons[] = "<img src='{$expire_icon}' height='24px' width='24px' alt='Time Bound' title='Time Bound' class='pr-2'>";
		}

		if ( $is_tracking ) {
			$icons[] = "<img src='{$tracking_icon}' height='24px' width='24px' alt='Click Tracking' title='Click Tracking' class='pr-2'>";
		}

		if ( $nofollow ) {
			$icons[] = "<img src='{$nofollow_icon}' height='24px' width='24px' alt='Nofollow' title='Nofollow' class='pr-2'>";
		}

		if ( ! empty( $icons ) ) {
			return "<p class='inline-flex'>" . implode( ' ', $icons ) . " </p>";
		}

		return "-";
		// return "<a href='{$permalink}' title='{$title}' target='_blank'><img src='{$icon}' alt='{$title}' /></a>";
	}

	/**
	 * Get the redirect type
	 *
	 * @since 1.3.7
	 *
	 * @param $item
	 *
	 * @return array|\KaizenCoders\URL_Shortify\data|string
	 *
	 */
	function column_redirect( $item ) {

		$type = Helper::get_data( $item, 'redirect_type', '' );

		$redirect_types = Helper::get_redirection_types();

		return Helper::get_data( $redirect_types, $type, '' );
	}

	function column_groups( $item ) {

		$link_id = Helper::get_data( $item, 'id', '' );

		if ( empty( $link_id ) ) {
			return '';
		}

		$group_ids = ! empty( $this->links_ids_group_ids[ $link_id ] ) ? $this->links_ids_group_ids[ $link_id ] : [];

		return Helper::get_group_str_from_ids( $group_ids, $this->group_id_name_map );
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $item
	 *
	 * @return string
	 *
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="link_ids[]" value="%s"/>', $item['id']
		);
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return [
			'title'      => [ 'name', true ],
			'created_at' => [ 'created_at', true ],
		];

	}

	/**
	 * @since 1.0.0
	 *
	 * @param  int  $page_number
	 * @param  bool  $do_count_only
	 *
	 * @param  int  $per_page
	 *
	 * @return array
	 *
	 */
	public function get_lists( $per_page = 10, $page_number = 1, $do_count_only = false ) {
		global $wpdb;

		$order_by  = sanitize_sql_orderby( Helper::get_request_data( 'orderby' ) );
		$order     = Helper::get_request_data( 'order' );
		$search    = Helper::get_request_data( 's' );
		$filter_by = Helper::get_request_data( 'filter_by', '' );

		$table = $this->db->table_name;

		if ( $do_count_only ) {
			$sql = "SELECT count(*) as total FROM {$table}";
		} else {
			$sql = "SELECT * FROM {$table}";
		}

		$args = $query = [];

		$add_where_clause = false;

		if ( ! empty( $search ) ) {
			$query[] = ' name LIKE %s OR slug LIKE %s OR url LIKE %s OR description LIKE %s';
			$args[]  = '%' . $wpdb->esc_like( $search ) . '%';
			$args[]  = '%' . $wpdb->esc_like( $search ) . '%';
			$args[]  = '%' . $wpdb->esc_like( $search ) . '%';
			$args[]  = '%' . $wpdb->esc_like( $search ) . '%';

			$add_where_clause = true;
		}

		if ( ! US()->access->can( 'manage_links' ) && US()->access->can( 'create_links' ) ) {
			$query[] = ' created_by_id = %d ';
			$args[]  = get_current_user_id();

			$add_where_clause = true;
		}

		// Filter links.
		if ( ! empty( $filter_by ) ) {
			// Filter by group.
			if ( strpos( $filter_by, 'group_id' ) !== false ) {
				$group_id = str_replace( 'group_id_', '', $filter_by );

				$links_group_table = US()->db->links_groups->table_name;
				$add_where_clause  = true;

				if ( 'none' == $group_id ) {
					$query[] = "id NOT IN (SELECT link_id FROM {$links_group_table})";
				} elseif ( $group_id > 0 ) {
					$filter_sql = $wpdb->prepare( "SELECT link_id FROM {$links_group_table} WHERE group_id = %d",
						$group_id );

					$query[] = "id IN ( $filter_sql )";
				}
			} elseif ( strpos( $filter_by, 'redirect_type' ) !== false ) { // Filter by redirect type.
				$add_where_clause = true;
				$redirect_type    = str_replace( 'redirect_type_', '', $filter_by );

				$query[] = "redirect_type = '{$redirect_type}'";
			} else {
				$query = [];
				$query = apply_filters( 'kc_us_links_filter_by_query', $query, $filter_by );

				if ( ! empty( $query ) ) {
					$add_where_clause = true;
				}
			}

		}

		if ( $add_where_clause ) {
			$sql .= ' WHERE ';

			if ( count( $query ) > 0 ) {
				$sql .= implode( ' AND ', $query );
				if ( count( $args ) > 0 ) {
					$sql = $wpdb->prepare( $sql, $args );
				}
			}
		}

		if ( ! $do_count_only ) {

			$order = ! empty( $order ) ? strtolower( $order ) : 'desc';

			$expected_order_values = [ 'asc', 'desc' ];

			if ( ! in_array( $order, $expected_order_values ) ) {
				$order = 'desc';
			}

			$default_order_by = esc_sql( 'created_at' );

			$expected_order_by_values = [ 'name', 'created_at' ];

			if ( ! in_array( $order_by, $expected_order_by_values ) ) {
				$order_by_clause = " ORDER BY {$default_order_by} DESC";
			} else {
				$order_by        = esc_sql( $order_by );
				$order_by_clause = " ORDER BY {$order_by} {$order}, {$default_order_by} DESC";
			}

			$sql .= $order_by_clause;
			$sql .= " LIMIT $per_page";
			$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

			$result = $wpdb->get_results( $sql, 'ARRAY_A' );
		} else {
			$result = $wpdb->get_var( $sql );
		}

		return $result;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @since 1.0.0
	 * @return array
	 *
	 */
	public function get_bulk_actions() {
		$bulk_action = [
			'bulk_delete'     => __( 'Delete', 'url-shortify' ),
			'bulk_group_add'  => __( 'Add to group', 'url-shortify' ),
			'bulk_group_move' => __( 'Move to group', 'url-shortify' ),
		];

		return apply_filters( 'kc_us_link_bulk_actions', $bulk_action );
	}

	/**
	 * Process bulk action
	 *
	 * @since 1.0.0
	 */
	public function process_bulk_action() {
		$action  = Helper::get_request_data( 'action' );
		$action2 = Helper::get_request_data( 'action2' );

		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = Helper::get_request_data( '_wpnonce' );

			if ( ! wp_verify_nonce( $nonce, 'us_action_nonce' ) ) {
				$message = __( 'You do not have permission to delete this link.', 'url-shortify' );
				US()->notices->error( $message );
			} else {

				$link_id = Helper::get_request_data( 'id' );

				if ( ! empty( $link_id ) ) {
					$this->db->delete( $link_id );

					$message = __( 'Link has been deleted successfully!', 'url-shortify' );
					US()->notices->success( $message );
				}
			}
		} elseif ( 'reset' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = Helper::get_request_data( '_wpnonce' );

			if ( ! wp_verify_nonce( $nonce, 'us_action_nonce' ) ) {
				$message = __( 'You do not have permission to reset statistics of this link.', 'url-shortify' );
				US()->notices->error( $message );
			} else {

				$link_id = Helper::get_request_data( 'id' );

				if ( ! empty( $link_id ) ) {
					$this->db->reset_stats( $link_id );

					$message = __( 'Link stats has been reset successfully!', 'url-shortify' );
					US()->notices->success( $message );
				}
			}

		} elseif ( ( 'bulk_delete' === $action ) || ( 'bulk_delete' === $action2 ) ) {

			// In our file that handles the request, verify the nonce.
			$nonce  = Helper::get_request_data( '_wpnonce' );
			$action = 'bulk-' . Helper::get_data( $this->_args, 'plural', '' );

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				$message = __( 'You do not have permission to delete link(s).', 'url-shortify' );
				US()->notices->error( $message );
			} else {

				$link_ids = Helper::get_request_data( 'link_ids' );

				if ( ! empty( $link_ids ) ) {
					$this->db->delete( $link_ids );
					$message = __( 'Link(s) have been deleted successfully!', 'url-shortify' );
					US()->notices->success( $message );
				} else {
					$message = __( 'Please select link(s) to delete.', 'url-shortify' );
					US()->notices->error( $message );

					return;
				}
			}

		} elseif ( ( 'bulk_reset' === $action ) || ( 'bulk_reset' === $action2 ) ) {

			// In our file that handles the request, verify the nonce.
			$nonce  = Helper::get_request_data( '_wpnonce' );
			$action = 'bulk-' . Helper::get_data( $this->_args, 'plural', '' );

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				$message = __( 'You do not have permission to reset stats.', 'url-shortify' );
				US()->notices->error( $message );
			} else {

				$link_ids = Helper::get_request_data( 'link_ids' );

				if ( ! empty( $link_ids ) ) {

					do_action( 'kc_us_bulk_reset_link_stats', $link_ids );

					$message = __( 'Link(s) stats have been reset successfully!', 'url-shortify' );
					US()->notices->success( $message );
				} else {
					$message = __( 'Please select link(s) to reset stats.', 'url-shortify' );
					US()->notices->error( $message );

					return;
				}
			}
		} elseif ( ( 'bulk_group_add' === $action ) || ( 'bulk_group_add' === $action2 ) ) {

			// In our file that handles the request, verify the nonce.
			$nonce  = Helper::get_request_data( '_wpnonce' );
			$action = 'bulk-' . Helper::get_data( $this->_args, 'plural', '' );

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				$message = __( 'You do not have permission to add links to group.', 'url-shortify' );
				US()->notices->error( $message );
			} else {

				$link_ids = Helper::get_request_data( 'link_ids' );

				$group_id = Helper::get_request_data( 'group_id' );;

				if ( empty( $link_ids ) ) {
					$message = __( 'Please select link(s) to add into group.', 'url-shortify' );
					US()->notices->error( $message );

					return;
				}

				if ( empty( $group_id ) ) {
					$message = __( 'Please select group to add links to.', 'url-shortify' );
					US()->notices->error( $message );

					return;
				}

				US()->db->links_groups->map_links_and_groups( $link_ids, $group_id );

				$message = __( 'Link(s) have been added to group successfully!', 'url-shortify' );

				US()->notices->success( $message );
			}
		} elseif ( ( 'bulk_group_move' === $action ) || ( 'bulk_group_move' === $action2 ) ) {
			// In our file that handles the request, verify the nonce.
			$nonce  = Helper::get_request_data( '_wpnonce' );
			$action = 'bulk-' . Helper::get_data( $this->_args, 'plural', '' );

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				$message = __( 'You do not have permission to move links to group.', 'url-shortify' );
				US()->notices->error( $message );
			} else {

				$link_ids = Helper::get_request_data( 'link_ids' );

				$group_id = Helper::get_request_data( 'group_id' );;

				if ( empty( $link_ids ) ) {
					$message = __( 'Please select link(s) to move into group.', 'url-shortify' );
					US()->notices->error( $message );

					return;
				}

				if ( empty( $group_id ) ) {
					$message = __( 'Please select group to move links to.', 'url-shortify' );
					US()->notices->error( $message );

					return;
				}

				US()->db->links_groups->map_links_and_groups( $link_ids, $group_id, true );

				$message = __( 'Link(s) have been moved to group successfully!', 'url-shortify' );

				US()->notices->success( $message );
			}
		} elseif ( ( 'bulk_add_expiry' === $action ) || ( 'bulk_add_expiry' === $action2 ) ) {
			// In our file that handles the request, verify the nonce.
			$nonce  = Helper::get_request_data( '_wpnonce' );
			$action = 'bulk-' . Helper::get_data( $this->_args, 'plural', '' );

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				$message = __( 'You do not have permission to add expiry to links.', 'url-shortify' );
				US()->notices->error( $message );
			} else {
				$link_ids    = Helper::get_request_data( 'link_ids' );
				$expiry_date = Helper::get_request_data( 'expiry_date' );

				if ( empty( $expiry_date ) ) {
					$message = __( 'Please select expiry date.', 'url-shortify' );
					US()->notices->error( $message );
				}

				if ( empty( $link_ids ) ) {
					$message = __( 'Please select link(s) to add expiry date.', 'url-shortify' );
					US()->notices->error( $message );

					return;
				}

				US()->db->links->bulk_add_expiry( $link_ids, $expiry_date );

				$message = __( 'Expiry date has been added to selected links.', 'url-shortify' );
				US()->notices->success( $message );
			}
		} elseif ( ( 'bulk_enable_nofollow' === $action ) || ( 'bulk_enable_nofollow' === $action2 ) ) {
			// In our file that handles the request, verify the nonce.
			$nonce  = Helper::get_request_data( '_wpnonce' );
			$action = 'bulk-' . Helper::get_data( $this->_args, 'plural', '' );

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				$message = __( 'You do not have permission to enable Nofollow parameter to links.', 'url-shortify' );
				US()->notices->error( $message );
			} else {
				$link_ids = Helper::get_request_data( 'link_ids' );

				if ( empty( $link_ids ) ) {
					$message = __( 'Please select link(s) to enable nofollow.', 'url-shortify' );
					US()->notices->error( $message );

					return;
				}

				US()->db->links->bulk_update_parameters( $link_ids, 'nofollow', 1 );

				$message = __( 'Nofollow has been added to selected links.', 'url-shortify' );
				US()->notices->success( $message );
			}
		} elseif ( ( 'bulk_disable_nofollow' === $action ) || ( 'bulk_disable_nofollow' === $action2 ) ) {
			// In our file that handles the request, verify the nonce.
			$nonce  = Helper::get_request_data( '_wpnonce' );
			$action = 'bulk-' . Helper::get_data( $this->_args, 'plural', '' );

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				$message = __( 'You do not have permission to disable Nofollow parameter to links.', 'url-shortify' );
				US()->notices->error( $message );
			} else {
				$link_ids = Helper::get_request_data( 'link_ids' );

				if ( empty( $link_ids ) ) {
					$message = __( 'Please select link(s) to disable nofollow.', 'url-shortify' );
					US()->notices->error( $message );

					return;
				}

				US()->db->links->bulk_update_parameters( $link_ids, 'nofollow', 0 );

				$message = __( 'Nofollow has been disabled to selected links.', 'url-shortify' );
				US()->notices->success( $message );
			}
		} elseif ( ( 'bulk_disable_sponsored' === $action ) || ( 'bulk_disable_sponsored' === $action2 ) ) {
			// In our file that handles the request, verify the nonce.
			$nonce  = Helper::get_request_data( '_wpnonce' );
			$action = 'bulk-' . Helper::get_data( $this->_args, 'plural', '' );

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				$message = __( 'You do not have permission to disable Sponsored parameter to links.', 'url-shortify' );
				US()->notices->error( $message );
			} else {
				$link_ids = Helper::get_request_data( 'link_ids' );

				if ( empty( $link_ids ) ) {
					$message = __( 'Please select link(s) to disable Sponsored.', 'url-shortify' );
					US()->notices->error( $message );

					return;
				}

				US()->db->links->bulk_update_parameters( $link_ids, 'sponsored', 0 );

				$message = __( 'Sponsored has been disabled to selected links.', 'url-shortify' );
				US()->notices->success( $message );
			}
		} elseif ( ( 'bulk_enable_sponsored' === $action ) || ( 'bulk_enable_sponsored' === $action2 ) ) {
			// In our file that handles the request, verify the nonce.
			$nonce  = Helper::get_request_data( '_wpnonce' );
			$action = 'bulk-' . Helper::get_data( $this->_args, 'plural', '' );

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				$message = __( 'You do not have permission to enable Sponsored parameter to links.', 'url-shortify' );
				US()->notices->error( $message );
			} else {
				$link_ids = Helper::get_request_data( 'link_ids' );

				if ( empty( $link_ids ) ) {
					$message = __( 'Please select link(s) to enable Sponsored.', 'url-shortify' );
					US()->notices->error( $message );

					return;
				}

				US()->db->links->bulk_update_parameters( $link_ids, 'nofollow', 1 );

				$message = __( 'Sponsored has been added to selected links.', 'url-shortify' );
				US()->notices->success( $message );
			}
		} elseif ( ( 'bulk_enable_tracking' === $action ) || ( 'bulk_enable_tracking' === $action2 ) ) {
			// In our file that handles the request, verify the nonce.
			$nonce  = Helper::get_request_data( '_wpnonce' );
			$action = 'bulk-' . Helper::get_data( $this->_args, 'plural', '' );

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				$message = __( 'You do not have permission to enable Tracking parameter to links.', 'url-shortify' );
				US()->notices->error( $message );
			} else {
				$link_ids = Helper::get_request_data( 'link_ids' );

				if ( empty( $link_ids ) ) {
					$message = __( 'Please select link(s) to enable Tracking.', 'url-shortify' );
					US()->notices->error( $message );

					return;
				}

				US()->db->links->bulk_update_parameters( $link_ids, 'track_me', 1 );

				$message = __( 'Tracking has been added to selected links.', 'url-shortify' );
				US()->notices->success( $message );
			}
		} elseif ( ( 'bulk_disable_tracking' === $action ) || ( 'bulk_disable_tracking' === $action2 ) ) {
			// In our file that handles the request, verify the nonce.
			$nonce  = Helper::get_request_data( '_wpnonce' );
			$action = 'bulk-' . Helper::get_data( $this->_args, 'plural', '' );

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				$message = __( 'You do not have permission to disable Tracking parameter to links.', 'url-shortify' );
				US()->notices->error( $message );
			} else {
				$link_ids = Helper::get_request_data( 'link_ids' );

				if ( empty( $link_ids ) ) {
					$message = __( 'Please select link(s) to disable Tracking.', 'url-shortify' );
					US()->notices->error( $message );

					return;
				}

				US()->db->links->bulk_update_parameters( $link_ids, 'track_me', 0 );

				$message = __( 'Tracking has been added to selected links.', 'url-shortify' );
				US()->notices->success( $message );
			}
		} elseif ( ( 'bulk_enable_params_forwarding' === $action ) || ( 'bulk_enable_params_forwarding' === $action2 ) ) {
			// In our file that handles the request, verify the nonce.
			$nonce  = Helper::get_request_data( '_wpnonce' );
			$action = 'bulk-' . Helper::get_data( $this->_args, 'plural', '' );

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				$message = __( 'You do not have permission to enable Parameters Forwarding to links.', 'url-shortify' );
				US()->notices->error( $message );
			} else {
				$link_ids = Helper::get_request_data( 'link_ids' );

				if ( empty( $link_ids ) ) {
					$message = __( 'Please select link(s) to enable Parameters Forwarding.', 'url-shortify' );
					US()->notices->error( $message );

					return;
				}

				US()->db->links->bulk_update_parameters( $link_ids, 'params_forwarding', 1 );

				$message = __( 'Parameters Forwarding has been added to selected links.', 'url-shortify' );
				US()->notices->success( $message );
			}
		} elseif ( ( 'bulk_disable_params_forwarding' === $action ) || ( 'bulk_disable_params_forwarding' === $action2 ) ) {
			// In our file that handles the request, verify the nonce.
			$nonce  = Helper::get_request_data( '_wpnonce' );
			$action = 'bulk-' . Helper::get_data( $this->_args, 'plural', '' );

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				$message = __( 'You do not have permission to disable Parameters Forwarding to links.', 'url-shortify' );
				US()->notices->error( $message );
			} else {
				$link_ids = Helper::get_request_data( 'link_ids' );

				if ( empty( $link_ids ) ) {
					$message = __( 'Please select link(s) to disable Parameters Forwarding.', 'url-shortify' );
					US()->notices->error( $message );

					return;
				}

				US()->db->links->bulk_update_parameters( $link_ids, 'params_forwarding', 0 );

				$message = __( 'Params Forwarding has been added to selected links.', 'url-shortify' );
				US()->notices->success( $message );
			}
		}
	}

	/**
	 * @since 1.0.0
	 *
	 * @param $link_id
	 *
	 */
	public function render_form( $link_id = null ) {

		$is_new = true;
		if ( ! empty( $link_id ) ) {
			$is_new = false;
		}

		$submitted = Helper::get_request_data( 'submitted' );

		$form_data = $this->get_form_data( $link_id );

		if ( 'submitted' === $submitted ) {

			$existing_slug = Helper::get_data( $form_data, 'slug', '' );

			$nonce = Helper::get_request_data( '_wpnonce' );

			$form_data = Helper::get_request_data( 'form_data', [], false );

			$form_data['existing_slug'] = $existing_slug;

			$form_data['nonce'] = $nonce;

			$response = $this->validate_data( $form_data );

			if ( 'error' === $response['status'] ) {
				$message = $response['message'];
				US()->notices->error( $message );
			} else {

				$save = $this->save( $form_data, $link_id );

				if ( $save ) {

					$value = [
						'status'  => 'success',
						'message' => __( 'Link data have been saved successfully!', 'url-shortify' ),
					];

					Cache::set_transient( 'notice', $value );
				}

				$url = admin_url( 'admin.php?page=us_links' );
				wp_redirect( $url );
				exit();
			}

		}

		$nonce = wp_create_nonce( 'us_link_form' );

		try {

			if ( $link_id ) {
				$title       = __( 'Edit Link', 'url-shortify' );
				$button_text = __( 'Save Changes', 'url-shortify' );

				$query_args = [
					'action'   => 'edit',
					'id'       => $link_id,
					'_wpnonce' => $nonce,
				];


			} else {
				$title       = __( 'New Link', 'url-shortify' );
				$button_text = __( 'Save Link', 'url-shortify' );

				$query_args = [
					'action'   => 'new',
					'_wpnonce' => $nonce,
				];

			}

			$form_action = add_query_arg( $query_args, admin_url( 'admin.php?page=us_links' ) );


			$template_data = [
				'title'             => $title,
				'link_id'           => $link_id,
				'button_text'       => $button_text,
				'form_action'       => $form_action,
				'form_data'         => $form_data,
				'blog_url'          => $is_new ? trailingslashit( Helper::get_blog_url( true ) ) : trailingslashit( Helper::get_blog_url() ),
				'redirection_types' => Helper::get_redirection_types(),
				'domains'           => Helper::get_domains(),
				'groups'            => US()->db->groups->get_id_name_map(),
			];

			include_once KC_US_ADMIN_TEMPLATES_DIR . '/link-form.php';

		} catch ( \Exception $e ) {

		}


	}

	/**
	 * Get Form data
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $link_id
	 *
	 * @return array
	 *
	 */
	public function get_form_data( $link_id = 0 ) {

		$results = [];

		if ( ! empty( $link_id ) ) {
			$results = $this->db->get( $link_id );
		}

		$default_settings = US()->get_settings();

		$default_redirection_type    = Helper::get_data( $default_settings,
			'links_default_link_options_redirection_type', 307 );
		$default_nofollow            = Helper::get_data( $default_settings,
			'links_default_link_options_enable_nofollow', 1 );
		$default_sponsored           = Helper::get_data( $default_settings,
			'links_default_link_options_enable_sponsored', 0 );
		$default_paramter_forwarding = Helper::get_data( $default_settings,
			'links_default_link_options_enable_paramter_forwarding', 0 );
		$default_track_me            = Helper::get_data( $default_settings,
			'links_default_link_options_enable_tracking', 1 );
		$default_domain              = Helper::get_data( $default_settings,
			'links_default_link_options_default_custom_domain', '' );

		return [
			'name'              => Helper::get_data( $results, 'name', '' ),
			'url'               => Helper::get_data( $results, 'url', '' ),
			'slug'              => Helper::get_data( $results, 'slug', Utils::get_valid_slug() ),
			'redirect_type'     => Helper::get_data( $results, 'redirect_type', $default_redirection_type ),
			'description'       => Helper::get_data( $results, 'description', '' ),
			'nofollow'          => Helper::get_data( $results, 'nofollow', $default_nofollow ),
			'sponsored'         => Helper::get_data( $results, 'sponsored', $default_sponsored ),
			'params_forwarding' => Helper::get_data( $results, 'params_forwarding', $default_paramter_forwarding ),
			'track_me'          => Helper::get_data( $results, 'track_me', $default_track_me ),
			'cpt_id'            => Helper::get_data( $results, 'cpt_id', '' ),
			'cpt_type'          => Helper::get_data( $results, 'cpt_type', '' ),
			'group_ids'         => Helper::get_data( $results, 'group_ids', [] ),
			'expires_at'        => Helper::get_data( $results, 'expires_at', '' ),
			'password'          => Helper::get_data( $results, 'password', '' ),
			'rules'             => maybe_unserialize( Helper::get_data( $results, 'rules', '' ) ),
			'default_domain'    => $default_domain,
		];

	}

	/**
	 * Validate data
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $data
	 *
	 * @return array
	 *
	 */
	public function validate_data( $data = [] ) {

		$status   = 'success';
		$error    = false;
		$messages = [];

		$nonce = Helper::get_data( $data, 'nonce', '' );
		if ( ! wp_verify_nonce( $nonce, 'us_link_form' ) ) {
			$messages[] = __( 'You do not have permission to edit this form.', 'url-shortify' );
			$error      = true;
		} else {
			$title         = Helper::get_data( $data, 'name', '' );
			$target_url    = Helper::get_data( $data, 'url', '' );
			$slug          = Helper::get_data( $data, 'slug', '' );
			$existing_slug = Helper::get_data( $data, 'existing_slug', '' );

			$no_equal_slug = $existing_slug != $slug;
			if ( US()->is_pro() ) {
				$settings       = US()->get_settings();
				$case_sensitive = (boolean) Helper::get_data( $settings, 'general_settings_case_sensitive_slug', 0 );
				$no_equal_slug  = $case_sensitive ? $existing_slug != $slug : strtolower( $existing_slug ) != strtolower( $slug );
			}

			if ( empty( $title ) ) {
				$messages[] = __( 'Please enter Title', 'url-shortify' );
				$error      = true;
			}

			if ( empty( $target_url ) ) {
				$messages[] = __( 'Please enter Target URL', 'url-shortify' );
				$error      = true;
			} elseif ( ! Utils::validate_url($target_url, true) ) {
				$messages[] = __( 'Please enter valid Target URL', 'url-shortify' );
				$error      = true;
			} elseif ( $no_equal_slug && Utils::is_slug_exists( $slug ) ) {
				$messages[] = __( 'Short URL already exists. Please use different Short URL.', 'url-shortify' );
				$error      = true;
			}

			/* Link Rotation Validation */
			if ( US()->is_pro() ) {
				$dynamic_redirection_type = Helper::get_data( $data, 'rules|dynamic_redirect_type', 'off' );
				if ( 'link-rotation' === $dynamic_redirection_type ) {
					$weights = Helper::get_data( $data, 'rules|dynamic_redirect|link_rotation|weights', [] );
					if ( ! empty( $weights ) ) {
						$total_weights = array_sum( $weights );
						if ( $total_weights > 100 ) {
							$messages[] = __( 'Your link rotations weights should be equal or less than 100%',
								'url-shortify' );
							$error      = true;
						}
					}

					$split_test = Helper::get_data( $data, 'rules|dynamic_redirect|link_rotation|split_test', 0 );
					if ( $split_test ) {
						$goal_link = Helper::get_data( $data, 'rules|dynamic_redirect|link_rotation|goal_link', 0 );
						if ( ! $goal_link ) {
							$messages[] = __( 'Split test is enable. Please select goal link',
								'url-shortify' );
							$error      = true;
						}
					}
				}
			}
		}

		$message = '';
		if ( $error ) {
			$message = implode( ', ', $messages );
			$status  = 'error';
		}

		return [
			'status'  => $status,
			'message' => $message,
		];
	}

	/**
	 * Insert/ Update form data
	 *
	 * @since 1.0.0
	 *
	 * @param  null  $id
	 *
	 * @param  array  $data
	 *
	 * @return bool|int
	 *
	 */
	public function save( $data = [], $id = null ) {

		$form_data = $this->db->prepare_form_data( $data, $id );

		$link_id = $this->db->save( $form_data, $id );

		if ( $id ) {
			$link_id = $id;
		}

		$group_ids = Helper::get_data( $data, 'group_ids', [] );

		// Add link to groups
		return US()->db->links_groups->add_link_to_groups( $link_id, $group_ids );
	}

	public function search_box( $text, $input_id ) {
		?>

        <p class="search-box">
            <label class="screen-reader-text"
                   for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_attr( $text ); ?>:</label>
            <input type="search" class="kc-us-links-search" id="<?php echo esc_attr( $input_id ); ?>" name="s"
                   value="<?php _admin_search_query(); ?>"/>
			<?php submit_button( __( 'Search Links', 'url-shortify' ), 'button', false, false,
				[ 'id' => 'search-submit' ] ); ?>
        </p>


		<?php
	}

	/**
	 * No items
	 *
	 * @since 1.0.0
	 */
	public function no_items() { ?>

        <div class="block ml-auto mr-auto" style="width:50%;">
            <img src="<?php echo KC_US_PLUGIN_ASSETS_DIR_URL . '/images/empty.svg' ?>"/>
        </div>


	<?php }

	public function export_links() {

		@set_time_limit( 0 );

		$links = US()->db->links->get_all();

		$export = new Export();

		$headers = $export->get_links_headers();

		$csv_data = $export->generate_csv( $headers, $links );

		$file_name = 'links.csv';

		$export->download_csv( $csv_data, $file_name );
	}

	/**
	 * Extra Table Navigation.
	 *
	 * @since 1.9.5
	 *
	 * @param $which
	 *
	 * @return void
	 *
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		?>

        <div class="alignleft actions">
            <p class="">
				<?php $filter_by = Helper::get_request_data( 'filter_by', '' ); ?>
                <select name="filter_by">
					<?php
					$allowed_tags = Helper::allowed_html_tags_in_esc();
					$groups       = Helper::prepare_links_filters_dropdown_options( $filter_by );
					echo wp_kses( $groups, $allowed_tags );
					?>
                </select>

				<?php

				submit_button( __( 'Filter' ), '', 'filter_action', false, [ 'id' => 'post-query-submit' ] );

				?>
            </p>
        </div>
		<?php
	}
}
