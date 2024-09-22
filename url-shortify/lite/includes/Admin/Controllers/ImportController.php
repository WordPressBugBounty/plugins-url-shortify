<?php

namespace KaizenCoders\URL_Shortify\Admin\Controllers;

use KaizenCoders\URL_Shortify\Common\Utils;
use KaizenCoders\URL_Shortify\Helper;
use KaizenCoders\URL_Shortify\Option;

class ImportController extends BaseController {

	/**
	 * ImportController constructor.
	 *
	 * @since 1.3.4
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Import Links
	 *
	 * @since 1.4.8
	 * @return bool
	 *
	 */
	public function import_links( $action = '' ) {
		if ( empty( $action ) ) {
			return false;
		}

		// Please convert following code to switch case
		switch ( $action ) {
			case 'pretty_links':
				$do_import = $this->import_pretty_links();
				break;
			case 'mts_links':
				$do_import = $this->import_mts_short_links();
				break;
			case 'eps_301_redirects':
				$do_import = $this->import_eps_301_redirect();
				break;
			case 'simple_301_redirects':
				$do_import = $this->import_from_simple_301_redirect();
				break;
			case 'thirsty_affiliates':
				$do_import = $this->import_thirsty_affiliate_links();
				break;
			case 'shorten_url':
				$do_import = $this->import_from_shorten_url();
				break;
			case 'redirection':
				$do_import = $this->import_from_redirection();
				break;
			case 'csv':
				$do_import = $this->import_csv();
				break;
			default:
				$do_import = false;
				break;
		}

		return $do_import;
	}

	/**
	 * Import Groups
	 *
	 * @since 1.4.4
	 *
	 * @param array $groups
	 *
	 */
	public function import_groups( $groups = [] ) {
		if ( Helper::is_forechable( $groups ) ) {

			$existing_groups = US()->db->groups->get_id_name_map();

			$current_user_id = \get_current_user_id();

			$groups_to_import = [];

			$key = 0;
			foreach ( $groups as $group_name => $links ) {

				if ( ! in_array( $group_name, $existing_groups ) ) {
					$groups_to_import[ $key ]['name']          = $group_name;
					$groups_to_import[ $key ]['created_by_id'] = $current_user_id;

					$key ++;
				}
			}

			if ( Helper::is_forechable( $groups_to_import ) ) {
				US()->db->groups->bulk_insert( $groups_to_import );
			}
		}

	}

	/**
	 * Add links to group
	 *
	 * @since 1.4.4
	 *
	 * @param array $groups
	 *
	 */
	public function add_links_to_group( $groups = [] ) {

		if ( Helper::is_forechable( $groups ) ) {

			$create_by_id       = \get_current_user_id();
			$groups_name_id_map = US()->db->groups->get_columns_map( 'name', 'id' );

			$links_slug_id_map = US()->db->links->get_columns_map( 'slug', 'id' );

			$data_to_insert = [];

			$key = 0;

			foreach ( $groups as $group => $links ) {

				$group_id = Helper::get_data( $groups_name_id_map, $group, 0 );

				if ( 0 != $group_id ) {

					if ( Helper::is_forechable( $links ) ) {
						foreach ( $links as $slug ) {
							$link_id = Helper::get_data( $links_slug_id_map, $slug, 0 );

							if ( 0 != $link_id ) {
								$data_to_insert[ $key ]['link_id']       = $link_id;
								$data_to_insert[ $key ]['group_id']      = $group_id;
								$data_to_insert[ $key ]['created_by_id'] = $create_by_id;

								$key ++;
							}
						}
					}
				}
			}

			if ( Helper::is_forechable( $data_to_insert ) ) {
				US()->db->links_groups->bulk_insert( $data_to_insert );
			}
		}
	}

	/**
	 * Import link from CSV file.
	 *
	 * @since 1.6.0
	 * @return bool|void
	 *
	 */
	public function import_csv() {
		$nonce = Helper::get_request_data( '_wpnonce' );

		if ( ! wp_verify_nonce( $nonce, 'import_csv' ) ) {
			die( 'You do not have permission to import CSV' );
		}

		// Check if a file was uploaded
		if ( ! isset( $_FILES['csv_file'] ) || empty( $_FILES['csv_file']['tmp_name'] ) ) {
			wp_die( 'Please select a CSV file to import.' );
		}

		// Get the file path and name
		$csv_file_path = $_FILES['csv_file']['tmp_name'];
		$csv_file_name = $_FILES['csv_file']['name'];

		// Validate the file extension
		$file_extension = strtolower( pathinfo( $csv_file_name, PATHINFO_EXTENSION ) );
		if ( $file_extension !== 'csv' ) {
			wp_die( 'Invalid file format. Please upload a CSV file.' );
		}

		// Import the CSV file
		if ( ! file_exists( $csv_file_path ) ) {
			return false;
		}

		$csv_file = fopen( $csv_file_path, 'r' );

		if ( ! $csv_file ) {
			return false;
		}

		@set_time_limit( 0 );

		// Read the first row of the CSV file as the column names.
		$columns = fgetcsv( $csv_file );

		$columns = array_map('trim', $columns);

		$required_headings = [ 'Target URL' ];

		if ( count( array_intersect( $columns, $required_headings ) ) != count( $required_headings ) ) {
			wp_die( 'Invalid columns in CSV file. Please make sure Target URL column is available in CSV file.' );
		}

		$links = [];

		while ( ( $data = fgetcsv( $csv_file ) ) !== false ) {
			$links[] = array_combine( $columns, $data );
		}

		fclose( $csv_file );

		if ( Helper::is_forechable( $links ) ) {

			$settings = US()->get_settings();

			$default_nofollow          = Helper::get_data( $settings, 'links_default_link_options_enable_nofollow', 1 );
			$default_track_me          = Helper::get_data( $settings, 'links_default_link_options_enable_tracking', 1 );
			$default_sponsored         = Helper::get_data( $settings, 'links_default_link_options_enable_sponsored', 1 );
			$default_params_forwarding = Helper::get_data( $settings, 'links_default_link_options_enable_paramter_forwarding', 1 );
			$default_redirect_type     = Helper::get_data( $settings, 'links_default_link_options_redirection_type', 301 );

			$default_created_at = date( 'Y-m-d H:i:s' );

			$current_user_id = \get_current_user_id();

			$existing_links = US()->db->links->get_columns_map( 'id', 'slug' );

			$values = [];

			$key = 0;

			$groups_to_import = [];

			foreach ( $links as $link ) {

				$slug = Helper::get_data( $link, 'Slug', '' );

				$groups_str = Helper::get_data($link, 'Groups', '');

				$groups = [];
				if ( ! empty( $groups_str ) ) {
					$groups = explode( '|', $groups_str );
					$groups = array_map( 'trim', $groups );
				}

				if ( empty( $slug ) ) {
					$slug = Utils::generate_random_slug();
					$slug = Helper::get_slug_with_prefix( $slug );
				}


				if ( ! empty( $groups ) ) {
					foreach ( $groups as $group ) {
						$groups_to_import[ $group ][] = $slug;
					}
				}

				if ( in_array( $slug, $existing_links ) ) {
					continue;
				}

				$values[ $key ]['slug']              = $slug;
				$values[ $key ]['name']              = ! empty( Helper::get_data( $link, 'Title', '' ) ) ? Helper::get_data( $link, 'Title', '' ) : Helper::get_data( $link, 'Target URL', '' );
				$values[ $key ]['description']       = Helper::get_data( $link, 'Description', '' );
				$values[ $key ]['url']               = Helper::get_data( $link, 'Target URL', '' );
				$values[ $key ]['nofollow']          = Helper::get_data( $link, 'Nofollow', $default_nofollow );
				$values[ $key ]['track_me']          = Helper::get_data( $link, 'Track', $default_track_me );
				$values[ $key ]['sponsored']         = Helper::get_data( $link, 'Sponsored', $default_sponsored );
				$values[ $key ]['params_forwarding'] = Helper::get_data( $link, 'Parameter Forwarding', $default_params_forwarding );
				// $values[ $key ]['params_structure']  = Helper::get_data( $link, 'params_struct', '' );
				$values[ $key ]['redirect_type'] = Helper::get_data( $link, 'Redirect Type', $default_redirect_type );
				$values[ $key ]['status']        = 1;
				$values[ $key ]['type']          = 'direct';
				$values[ $key ]['type_id']       = null;
				$values[ $key ]['password']      = null;
				$values[ $key ]['expires_at']    = null;
				$values[ $key ]['cpt_id']        = null;
				$values[ $key ]['cpt_type']      = '';
				$values[ $key ]['rules']         = null;
				$values[ $key ]['created_at']    = Helper::get_data( $link, 'Created At', $default_created_at );
				$values[ $key ]['created_by_id'] = $current_user_id;
				$values[ $key ]['updated_at']    = Helper::get_data( $link, 'Updated At', '' );
				$values[ $key ]['updated_by_id'] = $current_user_id;

				$key ++;
			}

			// Import Links
			if ( Helper::is_forechable( $values ) ) {
				US()->db->links->bulk_insert( $values );
			}

			if ( ! empty( $groups_to_import ) ) {
				$this->import_groups( $groups_to_import );

				$this->add_links_to_group( $groups_to_import );
			}
		}

		return true;
	}


	/**
	 * Import links from prettylink WordPress plugin
	 *
	 * @since 1.3.4
	 * @return bool
	 *
	 */
	public function import_pretty_links() {
		global $wpdb;

		$current_user_id = get_current_user_id();
		$links_table     = "{$wpdb->prefix}prli_links";

		$links_table_exists = US()->is_table_exists( $links_table );

		if ( $links_table_exists > 0 ) {

			$query = "SELECT * FROM {$links_table}";

			$links = $wpdb->get_results( $query, ARRAY_A );

			if ( Helper::is_forechable( $links ) ) {

				@set_time_limit( 0 );

				$existing_links = US()->db->links->get_columns_map( 'id', 'slug' );

				$values = $groups = [];

				$key = 0;

				foreach ( $links as $link ) {

					$slug = Helper::get_data( $link, 'slug', '' );

					if ( in_array( $slug, $existing_links ) ) {
						continue;
					}

					$cpt_id = Helper::get_data( $link, 'link_cpt_id', '' );

					$values[ $key ]['slug']              = $slug;
					$values[ $key ]['name']              = ! empty( Helper::get_data( $link, 'name', '' ) ) ? Helper::get_data( $link, 'name', '' ) : Helper::get_data( $link, 'url', '' );
					$values[ $key ]['description']       = Helper::get_data( $link, 'description', '' );
					$values[ $key ]['url']               = Helper::get_data( $link, 'url', '' );
					$values[ $key ]['nofollow']          = Helper::get_data( $link, 'nofollow', '' );
					$values[ $key ]['track_me']          = Helper::get_data( $link, 'track_me', '' );
					$values[ $key ]['sponsored']         = Helper::get_data( $link, 'sponsored', '' );
					$values[ $key ]['params_forwarding'] = Helper::get_data( $link, 'param_forwarding', '' );
					$values[ $key ]['params_structure']  = Helper::get_data( $link, 'params_struct', '' );
					$values[ $key ]['redirect_type']     = Helper::get_data( $link, 'redirect_type', '' );
					$values[ $key ]['status']            = ( 'enabled' === Helper::get_data( $link, 'link_status', 'enabled' ) ) ? 1 : 0;
					$values[ $key ]['type']              = 'direct';
					$values[ $key ]['type_id']           = null;
					$values[ $key ]['password']          = null;
					$values[ $key ]['expires_at']        = null;
					$values[ $key ]['cpt_id']            = $cpt_id;
					$values[ $key ]['cpt_type']          = Helper::get_data( $link, 'link_cpt_type', '' );
					$values[ $key ]['rules']             = null;
					$values[ $key ]['created_at']        = Helper::get_data( $link, 'created_at', '' );
					$values[ $key ]['created_by_id']     = $current_user_id;
					$values[ $key ]['updated_at']        = Helper::get_data( $link, 'updated_at', '' );
					$values[ $key ]['updated_by_id']     = $current_user_id;

					// Collect all categories
					if ( ! empty( $cpt_id ) ) {
						$terms = get_the_terms( $cpt_id, 'pretty-link-category' );

						if ( Helper::is_forechable( $terms ) ) {
							foreach ( $terms as $term ) {
								$groups[ $term->name ][] = $slug;
							}
						}
					}

					$key ++;
				}

				// Import Links
				if ( Helper::is_forechable( $values ) ) {
					US()->db->links->bulk_insert( $values );
				}

				if ( Helper::is_forechable( $groups ) ) {

					// Import Groups
					$this->import_groups( $groups );

					// Map Link <-> Group
					$this->add_links_to_group( $groups );
				}

			}
		}

		return true;
	}

	/**
	 * Import links from My theme shop short links WordPress plugin
	 *
	 * @since 1.3.4
	 * @return bool
	 *
	 */
	public function import_mts_short_links() {
		global $wpdb;

		$links_table = "{$wpdb->prefix}short_links";

		$links_table_exists = US()->is_table_exists( $links_table );

		if ( $links_table_exists > 0 ) {

			$query = "SELECT * FROM {$links_table}";

			$links = $wpdb->get_results( $query, ARRAY_A );

			if ( Helper::is_forechable( $links ) ) {

				@set_time_limit( 0 );

				$existing_links = US()->db->links->get_columns_map( 'id', 'slug' );

				$values = $groups = [];

				$key = 0;

				foreach ( $links as $link ) {

					$slug = Helper::get_data( $link, 'link_name', '' );

					if ( in_array( $slug, $existing_links ) ) {
						continue;
					}

					$link_id = Helper::get_data( $link, 'link_id', 0 );

					$values[ $key ]['slug']              = $slug;
					$values[ $key ]['name']              = ! empty( Helper::get_data( $link, 'link_title', '' ) ) ? Helper::get_data( $link, 'link_title', '' ) : Helper::get_data( $link, 'link_url', '' );
					$values[ $key ]['description']       = Helper::get_data( $link, 'link_description', '' );
					$values[ $key ]['url']               = Helper::get_data( $link, 'link_url', '' );
					$values[ $key ]['nofollow']          = ( "nofollow" === Helper::get_data( $link, 'link_attr_rel', 'nofollow' ) ) ? 1 : 0;
					$values[ $key ]['track_me']          = 1;
					$values[ $key ]['sponsored']         = 0;
					$values[ $key ]['params_forwarding'] = Helper::get_data( $link, 'link_forward_parameters', 0 );
					$values[ $key ]['params_structure']  = null;
					$values[ $key ]['redirect_type']     = Helper::get_data( $link, 'link_redirection_method', 307 );
					$values[ $key ]['status']            = ( 'publish' === Helper::get_data( $link, 'link_status', 'publish' ) ) ? 1 : 0;
					$values[ $key ]['type']              = 'direct';
					$values[ $key ]['type_id']           = null;
					$values[ $key ]['password']          = null;
					$values[ $key ]['expires_at']        = null;
					$values[ $key ]['cpt_id']            = null;
					$values[ $key ]['cpt_type']          = null;
					$values[ $key ]['rules']             = null;
					$values[ $key ]['created_at']        = Helper::get_data( $link, 'link_created', '' );
					$values[ $key ]['created_by_id']     = Helper::get_data( $link, 'link_owner', '' );
					$values[ $key ]['updated_at']        = Helper::get_data( $link, 'link_updated', '' );
					$values[ $key ]['updated_by_id']     = Helper::get_data( $link, 'link_owner', '' );

					// Collect all categories
					if ( ! empty( $link_id ) ) {
						$categories = wp_get_object_terms( $link_id, 'short_link_category', [ 'fields' => 'ids' ] );
						$categories = array_unique( $categories );

						if ( Helper::is_forechable( $categories ) ) {

							foreach ( $categories as $category ) {
								$term = get_term( $category, 'short_link_category' );

								$groups[ $term->name ][] = $slug;
							}
						}
					}

					$key ++;
				}

				// Import Links
				if ( Helper::is_forechable( $values ) ) {
					US()->db->links->bulk_insert( $values );
				}

				if ( Helper::is_forechable( $groups ) ) {

					// Import Groups
					$this->import_groups( $groups );

					// Map Link <-> Group
					$this->add_links_to_group( $groups );
				}
			}
		}

		return true;
	}

	/**
	 * Import links from 301 Redirect - Easy Redirect Manager WordPress plugin
	 *
	 * https://wordpress.org/plugins/eps-301-redirects/
	 *
	 * @since 1.3.4
	 * @return bool
	 *
	 */
	public function import_eps_301_redirect() {
		global $wpdb;

		$links_table = "{$wpdb->prefix}redirects";

		$links_table_exists = US()->is_table_exists( $links_table );

		if ( $links_table_exists > 0 ) {

			$query = "SELECT * FROM {$links_table}";

			$links = $wpdb->get_results( $query, ARRAY_A );

			if ( Helper::is_forechable( $links ) ) {

				@set_time_limit( 0 );

				$existing_links = US()->db->links->get_columns_map( 'id', 'slug' );

				$values = $groups = [];

				$key = 0;

				$settings = US()->get_settings();

				$default_nofollow          = Helper::get_data( $settings, 'links_default_link_options_enable_nofollow', 1 );
				$default_track_me          = Helper::get_data( $settings, 'links_default_link_options_enable_tracking', 1 );
				$default_sponsored         = Helper::get_data( $settings, 'links_default_link_options_enable_sponsored', 1 );
				$default_params_forwarding = Helper::get_data( $settings, 'links_default_link_options_enable_paramter_forwarding', 1 );
				$default_redirect_type     = Helper::get_data( $settings, 'links_default_link_options_redirection_type', 301 );

				$default_created_at    = date( 'Y-m-d H:i:s' );
				$default_created_by_id = \get_current_user_id();

				foreach ( $links as $link ) {

					$slug = Helper::get_data( $link, 'url_from', '' );

					if ( '*' === $slug || in_array( $slug, $existing_links ) ) {
						continue;
					}

					$status = Helper::get_data( $link, 'status', 'off' );

					// We know only these statuses of 301 Redirects
					if ( ! in_array( $status, [ '301', '302', '307' ] ) ) {
						continue;
					}

					$values[ $key ]['slug']              = $slug;
					$values[ $key ]['name']              = 'Easy 301 Redirect - ' . $slug;
					$values[ $key ]['description']       = 'Imported From Easy 301 Redirect';
					$values[ $key ]['url']               = Helper::get_data( $link, 'url_to', '' );
					$values[ $key ]['nofollow']          = $default_nofollow;
					$values[ $key ]['track_me']          = $default_track_me;
					$values[ $key ]['sponsored']         = $default_sponsored;
					$values[ $key ]['params_forwarding'] = $default_params_forwarding;
					$values[ $key ]['redirect_type']     = Helper::get_data( $link, 'status', $default_redirect_type );
					$values[ $key ]['status']            = 1;
					$values[ $key ]['type']              = 'direct';
					$values[ $key ]['created_at']        = $default_created_at;
					$values[ $key ]['created_by_id']     = $default_created_by_id;

					$groups['Easy 301 Redirect'][] = $slug;

					$key ++;
				}

				// Import Links
				if ( Helper::is_forechable( $values ) ) {
					US()->db->links->bulk_insert( $values );
				}

				if ( Helper::is_forechable( $groups ) ) {

					// Import Groups
					$this->import_groups( $groups );

					// Map Link <-> Group
					$this->add_links_to_group( $groups );
				}

			}
		}

		return true;
	}

	/**
	 * Import links from Simple 301 Redirect WordPress plugin
	 *
	 * https://wordpress.org/plugins/simple-301-redirects/
	 *
	 * @since 1.3.4
	 * @return bool
	 *
	 */
	public function import_from_simple_301_redirect() {
		global $wpdb;

		$plugin_installed = Helper::is_simple_301_redirect_plugin_installed();

		if ( $plugin_installed ) {

			$links = maybe_unserialize( get_option( '301_redirects' ) );

			if ( Helper::is_forechable( $links ) ) {

				@set_time_limit( 0 );

				$existing_links = US()->db->links->get_columns_map( 'id', 'slug' );

				$values = $groups = [];

				$key = 0;

				$settings = US()->get_settings();

				$default_nofollow          = Helper::get_data( $settings, 'links_default_link_options_enable_nofollow', 1 );
				$default_track_me          = Helper::get_data( $settings, 'links_default_link_options_enable_tracking', 1 );
				$default_sponsored         = Helper::get_data( $settings, 'links_default_link_options_enable_sponsored', 1 );
				$default_params_forwarding = Helper::get_data( $settings, 'links_default_link_options_enable_paramter_forwarding', 1 );

				$default_created_at    = date( 'Y-m-d H:i:s' );
				$default_created_by_id = \get_current_user_id();

				foreach ( $links as $slug => $target_url ) {
					$slug = ltrim( $slug, '/' );

					if ( '*' === $slug || in_array( $slug, $existing_links ) ) {
						continue;
					}

					$values[ $key ]['slug']              = $slug;
					$values[ $key ]['name']              = 'Simple 301 Redirect - ' . $slug;
					$values[ $key ]['description']       = 'Imported From Simple 301 Redirect';
					$values[ $key ]['url']               = $target_url;
					$values[ $key ]['nofollow']          = $default_nofollow;
					$values[ $key ]['track_me']          = $default_track_me;
					$values[ $key ]['sponsored']         = $default_sponsored;
					$values[ $key ]['params_forwarding'] = $default_params_forwarding;
					$values[ $key ]['redirect_type']     = 301;
					$values[ $key ]['status']            = 1;
					$values[ $key ]['type']              = 'direct';
					$values[ $key ]['created_at']        = $default_created_at;
					$values[ $key ]['created_by_id']     = $default_created_by_id;

					$groups['Simple 301 Redirect'][] = $slug;

					$key ++;
				}

				// Import Links
				if ( Helper::is_forechable( $values ) ) {
					US()->db->links->bulk_insert( $values );
				}

				if ( Helper::is_forechable( $groups ) ) {
					// Import Groups
					$this->import_groups( $groups );

					// Map Link <-> Group
					$this->add_links_to_group( $groups );
				}

			}
		}

		return true;
	}

	/**
	 * Import links from Thirsty Affiliate WordPress plugin
	 *
	 * @since 1.4.8
	 * @return bool
	 *
	 */
	public function import_thirsty_affiliate_links() {
		global $wpdb;

		$links = get_posts( [
			'posts_per_page' => - 1,
			'post_type'      => 'thirstylink',
			'post_status'    => 'publish',
		] );

		if ( Helper::is_forechable( $links ) ) {

			$current_user_id = get_current_user_id();

			$link_prefix = get_option( 'ta_link_prefix_custom', true );

			@set_time_limit( 0 );

			$existing_links = US()->db->links->get_columns_map( 'id', 'slug' );

			$values = $groups = [];

			$key = 0;

			foreach ( $links as $link ) {

				$slug = $link->post_name;

				if ( in_array( $slug, $existing_links ) ) {
					continue;
				}

				$nofollow = get_post_meta( $link->ID, '_ta_no_follow', true );
				$nofollow = ( $nofollow == 'global' ? get_option( 'ta_no_follow', true ) : $nofollow );

				$redirect_type = get_post_meta( $link->ID, '_ta_redirect_type', true );
				$redirect_type = ( $redirect_type == 'global' ? get_option( 'ta_link_redirect_type', true ) : $redirect_type );

				$param_forwarding = get_post_meta( $link->ID, '_ta_pass_query_str', true );
				$param_forwarding = ( $param_forwarding == 'global' ? get_option( 'ta_pass_query_str', true ) : $param_forwarding );

				// expire
				$expire_date = get_post_meta( $link->ID, '_ta_link_expire_date', true );

				$slug = $link->post_name;
				if ( ! empty( $link_prefix ) ) {
					$slug = trim( $link_prefix, '/' ) . '/' . $slug;
				}

				$values[ $key ]['slug']              = $slug;
				$values[ $key ]['name']              = $link->post_title;
				$values[ $key ]['description']       = '';
				$values[ $key ]['url']               = get_post_meta( $link->ID, '_ta_destination_url', true );
				$values[ $key ]['nofollow']          = ( $nofollow == 'yes' ? 1 : 0 );
				$values[ $key ]['track_me']          = 1;
				$values[ $key ]['sponsored']         = 0;
				$values[ $key ]['params_forwarding'] = ( $param_forwarding == 'yes' ? 1 : 0 );
				$values[ $key ]['params_structure']  = null;
				$values[ $key ]['redirect_type']     = $redirect_type;
				$values[ $key ]['status']            = 1;
				$values[ $key ]['type']              = 'direct';
				$values[ $key ]['type_id']           = null;
				$values[ $key ]['password']          = null;
				$values[ $key ]['expires_at']        = $expire_date;
				$values[ $key ]['cpt_id']            = null;
				$values[ $key ]['cpt_type']          = null;
				$values[ $key ]['rules']             = null;
				$values[ $key ]['created_at']        = Helper::get_current_date_time();
				$values[ $key ]['created_by_id']     = $current_user_id;
				$values[ $key ]['updated_at']        = '';
				$values[ $key ]['updated_by_id']     = '';

				// Collect all categories
				if ( ! empty( $link->ID ) ) {
					$terms = get_the_terms( $link->ID, 'thirstylink-category' );

					if ( Helper::is_forechable( $terms ) ) {
						foreach ( $terms as $term ) {
							$groups[ $term->name ][] = $slug;
						}
					}
				}

				$key ++;
			}

			// Import Links
			if ( Helper::is_forechable( $values ) ) {
				US()->db->links->bulk_insert( $values );
			}

			if ( Helper::is_forechable( $groups ) ) {
				// Import Groups
				$this->import_groups( $groups );

				// Map Link <-> Group
				$this->add_links_to_group( $groups );
			}

			//Import Settings.
			$settings = Option::get( 'settings' );

			$settings['links_default_link_options_link_prefix'] = $link_prefix;

			Option::set( 'settings', $settings );
		}

		return true;
	}

	/**
	 * Import links from Shorten URL plugin
	 *
	 * https://wordpress.org/plugins/shorten-url/
	 *
	 * @since 1.5.6
	 */
	public function import_from_shorten_url() {
		global $wpdb;

		$links_table = "{$wpdb->prefix}pluginSL_shorturl";

		if ( Helper::is_shorten_url_table_exists() ) {

			$query = "SELECT * FROM {$links_table}";

			$links = $wpdb->get_results( $query, ARRAY_A );

			if ( Helper::is_forechable( $links ) ) {

				$current_user_id = get_current_user_id();

				@set_time_limit( 0 );

				$existing_links = US()->db->links->get_columns_map( 'id', 'slug' );

				$values = $groups = [];

				$key = 0;

				foreach ( $links as $link ) {

					$slug = Helper::get_data( $link, 'short_url', '' );

					if ( in_array( $slug, $existing_links ) ) {
						continue;
					}

					$link_title = $url = Helper::get_data( $link, 'url_externe', '' );
					$post_id    = Helper::get_data( $link, 'id_post', 0 );
					if ( $post_id ) {
						$post = get_post( $post_id );
						if ( $post instanceof \WP_Post ) {
							$url        = get_permalink( $post );
							$link_title = get_the_title( $post );
						}
					}

					if ( ! empty( $url ) ) {

						$values[ $key ]['slug']              = $slug;
						$values[ $key ]['name']              = $link_title;
						$values[ $key ]['description']       = Helper::get_data( $link, 'comment', '' );
						$values[ $key ]['url']               = $url;
						$values[ $key ]['nofollow']          = ( "nofollow" === Helper::get_data( $link, 'link_attr_rel', 'nofollow' ) ) ? 1 : 0;
						$values[ $key ]['track_me']          = 1;
						$values[ $key ]['sponsored']         = 0;
						$values[ $key ]['params_forwarding'] = 0;
						$values[ $key ]['params_structure']  = null;
						$values[ $key ]['redirect_type']     = Helper::get_data( $link, 'link_redirection_method', 307 );
						$values[ $key ]['status']            = 1;
						$values[ $key ]['type']              = 'direct';
						$values[ $key ]['type_id']           = null;
						$values[ $key ]['password']          = null;
						$values[ $key ]['expires_at']        = null;
						$values[ $key ]['cpt_id']            = $post_id;
						$values[ $key ]['cpt_type']          = null;
						$values[ $key ]['rules']             = null;
						$values[ $key ]['created_at']        = Helper::get_current_date_time();
						$values[ $key ]['created_by_id']     = $current_user_id;
						$values[ $key ]['updated_at']        = '';
						$values[ $key ]['updated_by_id']     = '';

						$key ++;
					}
				}

				// Import Links
				if ( Helper::is_forechable( $values ) ) {
					US()->db->links->bulk_insert( $values );
				}

				if ( Helper::is_forechable( $groups ) ) {
					// Import Groups
					$this->import_groups( $groups );

					// Map Link <-> Group
					$this->add_links_to_group( $groups );
				}
			}
		}

		return true;
	}

	/**
	 * Import short URLs from redirection plugin.
	 *
	 * @since 1.8.6
	 *
	 * @return true
	 */
	public function import_from_redirection() {
		global $wpdb;

		$links_table = "{$wpdb->prefix}redirection_items";

		if ( Helper::is_shorten_url_table_exists() ) {

			$query = "SELECT * FROM {$links_table} WHERE `action_type` = 'url'";

			$links = $wpdb->get_results( $query, ARRAY_A );

			if ( Helper::is_forechable( $links ) ) {

				$settings = US()->get_settings();

				$default_nofollow          = Helper::get_data( $settings, 'links_default_link_options_enable_nofollow', 1 );
				$default_track_me          = Helper::get_data( $settings, 'links_default_link_options_enable_tracking', 1 );
				$default_sponsored         = Helper::get_data( $settings, 'links_default_link_options_enable_sponsored', 1 );
				$default_params_forwarding = Helper::get_data( $settings, 'links_default_link_options_enable_paramter_forwarding', 1 );

				$current_user_id = get_current_user_id();

				@set_time_limit( 0 );

				$existing_links = US()->db->links->get_columns_map( 'id', 'slug' );

				$values = $groups = [];

				$key = 0;

				foreach ( $links as $link ) {
					$slug = trim( Helper::get_data( $link, 'url', '' ), '/' );

					if ( empty( $slug ) || in_array( $slug, $existing_links ) ) {
						continue;
					}

					$link_title = Helper::get_data( $link, 'title', '' );
					$url        = Helper::get_data( $link, 'action_data', '' );
					$post_id    = Helper::get_data( $link, 'id_post', 0 );
					if ( $post_id ) {
						$post = get_post( $post_id );
						if ( $post instanceof \WP_Post ) {
							$url        = get_permalink( $post );
							$link_title = get_the_title( $post );
						}
					}

					if ( ! empty( $url ) ) {

						$values[ $key ]['slug']              = $slug;
						$values[ $key ]['name']              = $link_title;
						$values[ $key ]['description']       = Helper::get_data( $link, 'comment', '' );
						$values[ $key ]['url']               = $url;
						$values[ $key ]['nofollow']          = $default_nofollow;
						$values[ $key ]['track_me']          = $default_track_me;
						$values[ $key ]['sponsored']         = $default_sponsored;
						$values[ $key ]['params_forwarding'] = $default_params_forwarding;
						$values[ $key ]['params_structure']  = null;
						$values[ $key ]['redirect_type']     = Helper::get_data( $link, 'action_code', 307 );
						$values[ $key ]['status']            = 1;
						$values[ $key ]['type']              = 'direct';
						$values[ $key ]['type_id']           = null;
						$values[ $key ]['password']          = null;
						$values[ $key ]['expires_at']        = null;
						$values[ $key ]['cpt_id']            = $post_id;
						$values[ $key ]['cpt_type']          = null;
						$values[ $key ]['rules']             = null;
						$values[ $key ]['created_at']        = Helper::get_current_date_time();
						$values[ $key ]['created_by_id']     = $current_user_id;
						$values[ $key ]['updated_at']        = '';
						$values[ $key ]['updated_by_id']     = '';

						$key ++;
					}
				}

				// Import Links
				if ( Helper::is_forechable( $values ) ) {
					US()->db->links->bulk_insert( $values );
				}

				if ( Helper::is_forechable( $groups ) ) {
					// Import Groups
					$this->import_groups( $groups );

					// Map Link <-> Group
					$this->add_links_to_group( $groups );
				}
			}
		}

		return true;
	}
}