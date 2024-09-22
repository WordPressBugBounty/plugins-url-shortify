<?php

namespace KaizenCoders\URL_Shortify\Admin;

use KaizenCoders\URL_Shortify\Admin\Controllers\GroupStatsController;
use KaizenCoders\URL_Shortify\Admin\DB\Groups;
use KaizenCoders\URL_Shortify\Cache;
use KaizenCoders\URL_Shortify\Helper;

/**
 * Class Groups
 *
 * @package KaizenCoders\URL_Shortify\Admin
 *
 * @since 1.1.3
 */
class Groups_Table extends US_List_Table {
	/**
	 * @since 1.0.0
	 * @var string
	 *
	 */
	public static $option_per_page = 'us_groups_per_page';

	/**
	 * @var Groups
	 */
	public $db;

	/**
     * Link IDs Group ID Map.
     *
	 * @var array
     *
     * @since 1.9.0
	 */
    public $links_ids_by_group_ids = [];

	/**
     * Group IDS.
     *
	 * @var array
     *
     * @since 1.9.0
	 */
    public $group_ids = [];

	/**
     * Group IDs clicks data.
     *
	 * @var array
     *
     * @since 1.9.0
	 */
    public $group_ids_clicks_data = [];

	/**
	 * Links_Table constructor.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => __( 'Group', 'url-shortify' ), //singular name of the listed records
			'plural'   => __( 'Groups', 'url-shortify' ), //plural name of the listed records
			'ajax'     => false, //does this table support ajax?
			'screen'   => 'us_groups'
		) );

		$this->db = US()->db->groups;
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
			$this->group_ids = array_map( function ( $item ) {
				return $item['id'];
			}, $this->items );
		}

		// We are preparing this map to avoid DB queries later for each link data.
		$this->group_ids_clicks_data = US()->db->clicks->get_total_clicks_and_unique_clicks_by_group_ids( $this->group_ids );

		$this->links_ids_by_group_ids = US()->db->links_groups->get_link_ids_by_group_ids( $this->group_ids );
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

				$group_id = Helper::get_request_data( 'id', null );

				$this->render_form( $group_id );

			} elseif ( 'statistics' === $this->current_action() ) {
				// In our file that handles the request, verify the nonce.
				$nonce = Helper::get_request_data( '_wpnonce' );

				if ( ! wp_verify_nonce( $nonce, 'us_action_nonce' ) ) {
					$message = __( 'You do not have permission to view statistics of this group.', 'url-shortify' );
					US()->notices->error( $message );
				} else {

					$group_id = Helper::get_request_data( 'id' );

					if ( ! empty( $group_id ) ) {
						$link = new GroupStatsController( $group_id );
						$link->render();
					}
				}
			} elseif ( 'export' === $this->current_action() ) {
				// In our file that handles the request, verify the nonce.
				$nonce = Helper::get_request_data( '_wpnonce' );

				if ( ! wp_verify_nonce( $nonce, 'us_action_nonce' ) ) {
					$message = __( 'You do not have permission to export statistics of this group.', 'url-shortify' );
					US()->notices->error( $message );
				} else {

					$group_id = Helper::get_request_data( 'id' );

					if ( ! empty( $group_id ) ) {
						$link = new GroupStatsController( $group_id );
						$link->export();
						die();
					}
				}
			} elseif ( 'export_links' === $this->current_action() ) {
				// In our file that handles the request, verify the nonce.
				$nonce = Helper::get_request_data( '_wpnonce' );

				if ( ! wp_verify_nonce( $nonce, 'us_action_nonce' ) ) {
					$message = __( 'You do not have permission to export links of this group.', 'url-shortify' );
					US()->notices->error( $message );
				} else {

					$group_id = Helper::get_request_data( 'id' );

					if ( ! empty( $group_id ) ) {
						$link = new GroupStatsController( $group_id );
						$link->export_links();
						die();
					}
				}
			} else {

				$template_data = array(
					'object'       => $this,
					'title'        => __( 'Groups', 'url-shortify' ),
					'add_new_link' => add_query_arg( 'action', 'new', admin_url( 'admin.php?page=us_groups' ) )
				);

				ob_start();

				include KC_US_ADMIN_TEMPLATES_DIR . '/groups.php';
			}


		} catch ( \Exception $e ) {

		}

	}

	/**
	 * Associative array of columns
	 *
	 * @return array
	 *
	 * @since 1.1.3
	 */
	function get_columns() {
		return array(
			'cb'         => '<input type="checkbox" />',
			'name'       => __( 'Name', 'url-shortify' ),
			'links'      => __( 'Links', 'url-shortify' ),
			'clicks'     => __( 'Clicks', 'url-shortify' ),
			'created_at' => __( 'Created On', 'url-shortify' )
		);

	}

	/**
	 * @param object $item
	 * @param string $column_name
	 *
	 * @return string|void
	 *
	 * @since 1.1.3
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
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_name( $item ) {

		$group_id = $item['id'];

		$total_links = US()->db->links_groups->count_by_group_id( $group_id );

		$title = '<span class="flex w-full"><strong>' . stripslashes( $item['name'] ) . '</strong></span>';

		$actions['edit'] = sprintf( __( '<a href="%s" class="text-indigo-600">Edit</a>', 'url-shortify' ), Helper::get_group_action_url( $group_id, 'edit' ) );

		if ( $total_links > 0 ) {
			$actions['stats'] = sprintf( __( '<a href="%s">Statistics</a>', 'url-shortify' ), Helper::get_group_action_url( $group_id, 'statistics' ) );
		}

		$actions['delete'] = sprintf( __( '<a href="%s">Delete</a>', 'url-shortify' ), Helper::get_group_action_url( $group_id, 'delete' ) );

		return $title . $this->row_actions( $actions );
	}

	/**
	 * Prepare links column
	 *
	 * @param $item
	 *
	 * @return int|string|null
	 *
	 * @since 1.1.3
	 */
	function column_links( $item ) {

		$stats_url = Helper::get_group_action_url( $item['id'], 'statistics' );

		$total_links = count( Helper::get_data( $this->links_ids_by_group_ids, $item['id'], [] ) );

		if ( $total_links > 0 ) {
			return sprintf( __( '<a href="%1$s"  title="%2$s" class="kc-us-group"/>%3$s</a>', 'url-shortify' ), $stats_url, __( 'Total Links', 'url-shortify' ), $total_links );
		} else {
			return 0;
		}

	}

	/**
	 * Prepare Clicks column
	 *
	 * @param $item
	 *
	 * @return string
	 *
	 * @since 1.2.4
	 */
	public function column_clicks( $item ) {
		$group_id = (int) $item['id'];

		$stats_url = Helper::get_group_action_url( $group_id, 'statistics' );

		$unique_clicks = Helper::get_data( $this->group_ids_clicks_data, $group_id . '|unique_clicks', 0 );
		$total_clicks  = Helper::get_data( $this->group_ids_clicks_data, $group_id . '|total_clicks', 0 );

		return Helper::prepare_clicks_column_with_data( $unique_clicks, $total_clicks, $stats_url );
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 *
	 * @since 1.1.3
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="group_ids[]" value="%s"/>', $item['id']
		);
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'title'      => array( 'name', true ),
			'created_at' => array( 'created_at', true )
		);

	}

	/**
	 * @param int $per_page
	 * @param int $page_number
	 * @param bool $do_count_only
	 *
	 * @return array
	 *
	 * @since 1.1.3
	 */
	public function get_lists( $per_page = 10, $page_number = 1, $do_count_only = false ) {
		global $wpdb;

		$order_by = sanitize_sql_orderby( Helper::get_request_data( 'orderby' ) );
		$order    = Helper::get_request_data( 'order' );
		$search   = Helper::get_request_data( 's' );

		$table = $this->db->table_name;

		if ( $do_count_only ) {
			$sql = "SELECT count(*) as total FROM {$table}";
		} else {
			$sql = "SELECT * FROM {$table}";
		}

		$args = $query = array();

		$add_where_clause = false;

		if ( ! empty( $search ) ) {
			$query[] = ' name LIKE %s ';
			$args[]  = '%' . $wpdb->esc_like( $search ) . '%';

			$add_where_clause = true;
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

			$order                 = ! empty( $order ) ? strtolower( $order ) : 'desc';
			$expected_order_values = array( 'asc', 'desc' );
			if ( ! in_array( $order, $expected_order_values ) ) {
				$order = 'desc';
			}

			$default_order_by = esc_sql( 'created_at' );

			$expected_order_by_values = array( 'name', 'created_at' );

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
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function get_bulk_actions() {
		return array(
			'bulk_delete' => __( 'Delete', 'url-shortify' )
		);
	}

	/**
	 * Process bulk action
	 *
	 * @since 1.0.0
	 */
	public function process_bulk_action() {

		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = Helper::get_request_data( '_wpnonce' );

			if ( ! wp_verify_nonce( $nonce, 'us_action_nonce' ) ) {
				$message = __( 'You do not have permission to delete this group.', 'url-shortify' );
				US()->notices->error( $message );
			} else {

				$group_id = Helper::get_request_data( 'id' );

				if ( ! empty( $group_id ) ) {
					$this->db->delete( $group_id );

					$message = __( 'Group has been deleted successfully!', 'url-shortify' );
					US()->notices->success( $message );
				}
			}
		}

		$action  = Helper::get_request_data( 'action' );
		$action2 = Helper::get_request_data( 'action2' );
		// If the delete bulk action is triggered
		if ( ( 'bulk_delete' === $action ) || ( 'bulk_delete' === $action2 ) ) {

			// In our file that handles the request, verify the nonce.
			$nonce  = Helper::get_request_data( '_wpnonce' );
			$action = 'bulk-' . Helper::get_data( $this->_args, 'plural', '' );

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				$message = __( 'You do not have permission to delete group(s).', 'url-shortify' );
				US()->notices->error( $message );
			} else {

				$group_ids = Helper::get_request_data( 'group_ids' );

				if ( ! empty( $group_ids ) > 0 ) {
					$this->db->delete( $group_ids );
					$message = __( 'Groups(s) have been deleted successfully!', 'url-shortify' );
					US()->notices->success( $message );
				} else {
					$message = __( 'Please select group(s) to delete.', 'url-shortify' );
					US()->notices->error( $message );

					return;
				}
			}
		}
	}

	/**
	 * @param $group_id
	 *
	 * @since 1.1.3
	 */
	public function render_form( $group_id = null ) {

		$is_new = true;
		if ( ! empty( $group_id ) ) {
			$is_new = false;
		}

		$submitted = Helper::get_request_data( 'submitted' );

		$form_data = $this->get_form_data( $group_id );

		if ( 'submitted' === $submitted ) {

			$nonce = Helper::get_request_data( '_wpnonce' );

			$form_data = Helper::get_request_data( 'form_data', array(), false );

			$form_data['nonce'] = $nonce;

			$response = $this->validate_data( $form_data );

			if ( 'error' === $response['status'] ) {
				$message = $response['message'];
				US()->notices->error( $message );

			} else {

				$save = $this->save( $form_data, $group_id );

				if ( $save ) {

					$value = array(
						'status'  => 'success',
						'message' => __( 'Group has been saved successfully!', 'url-shortify' )
					);

					Cache::set_transient( 'notice', $value );
				}

				$url = admin_url( 'admin.php?page=us_groups' );
				wp_redirect( $url );
				exit();
			}

		}

		$nonce = wp_create_nonce( 'us_group_form' );

		try {

			if ( $group_id ) {
				$title       = __( 'Edit Group', 'url-shortify' );
				$button_text = __( 'Save Changes', 'url-shortify' );

				$query_args = array(
					'action'   => 'edit',
					'id'       => $group_id,
					'_wpnonce' => $nonce
				);

			} else {
				$title       = __( 'New Group', 'url-shortify' );
				$button_text = __( 'Save Group', 'url-shortify' );

				$query_args = array(
					'action'   => 'new',
					'_wpnonce' => $nonce
				);

			}

			$form_action = add_query_arg( $query_args, admin_url( 'admin.php?page=us_groups' ) );

			$template_data = array(
				'title'       => $title,
				'button_text' => $button_text,
				'form_action' => $form_action,
				'form_data'   => $form_data
			);

			include_once KC_US_ADMIN_TEMPLATES_DIR . '/group-form.php';

		} catch ( \Exception $e ) {

		}


	}

	/**
	 * Get Form data
	 *
	 * @param int $link_id
	 *
	 * @return array
	 *
	 * @since 1.1.3
	 */
	public function get_form_data( $group_id = 0 ) {

		$results = array();

		if ( ! empty( $group_id ) ) {
			$results = $this->db->get( $group_id );
		}

		return array(
			'name'        => Helper::get_data( $results, 'name', '' ),
			'description' => Helper::get_data( $results, 'description', '' ),
		);

	}

	/**
	 * Validate data
	 *
	 * @param array $data
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function validate_data( $data = array() ) {

		$status   = 'success';
		$error    = false;
		$messages = array();

		$nonce = Helper::get_data( $data, 'nonce', '' );
		if ( ! wp_verify_nonce( $nonce, 'us_group_form' ) ) {
			$messages[] = __( 'You do not have permission to edit this group.', 'url-shortify' );
			$error      = true;
		} else {
			$title      = Helper::get_data( $data, 'name', '' );
			$target_url = Helper::get_data( $data, 'url', '' );

			if ( empty( $title ) ) {
				$messages[] = __( 'Please Enter Name', 'url-shortify' );
				$error      = true;
			}

		}

		$message = '';
		if ( $error ) {
			$message = implode( ', ', $messages );
			$status  = 'error';
		}

		return array(
			'status'  => $status,
			'message' => $message
		);
	}

	/**
	 * Insert/ Update form data
	 *
	 * @param array $data
	 * @param null $id
	 *
	 * @return bool|int
	 *
	 * @since 1.1.3
	 */
	public function save( $data = array(), $id = null ) {

		$form_data = $this->db->prepare_form_data( $data, $id );

		return $this->db->save( $form_data, $id );
	}

	public function search_box( $text, $input_id ) {
		?>

        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_attr( $text ); ?>:</label>
            <input type="search" class="kc-us-groups-search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>"/>
			<?php submit_button( __( 'Search Groups', 'url-shortify' ), 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
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
}
