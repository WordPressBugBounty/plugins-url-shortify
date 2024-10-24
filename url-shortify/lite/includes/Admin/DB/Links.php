<?php

namespace KaizenCoders\URL_Shortify\Admin\DB;

use KaizenCoders\URL_Shortify\Common\Utils;
use KaizenCoders\URL_Shortify\Helper;

class Links extends Base_DB {
	/**
	 * Table Name
	 *
	 * @since 1.0.0
	 * @var string
	 *
	 */
	public $table_name;

	/**
	 * Table Version
	 *
	 * @since 1.0.0
	 * @var string
	 *
	 */
	public $version;

	/**
	 * Primary key
	 *
	 * @since 1.0.0
	 * @var string
	 *
	 */
	public $primary_key;

	/**
	 * Initialize
	 *
	 * constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		global $wpdb;

		parent::__construct();

		$this->table_name = $wpdb->prefix . 'kc_us_links';

		$this->version = '1.0';

		$this->primary_key = 'id';
	}

	/**
	 * Get columns and formats
	 *
	 * @since 1.0.0
	 */
	public function get_columns() {
		return [
			'id'                => '%d',
			'name'              => '%s',
			'slug'              => '%s',
			'url'               => '%s',
			'description'       => '%s',
			'nofollow'          => '%d',
			'track_me'          => '%d',
			'sponsored'         => '%d',
			'params_forwarding' => '%d',
			'params_structure'  => '%s',
			'redirect_type'     => '%s',
			'status'            => '%d',
			'type'              => '%s',
			'type_id'           => '%d',
			'password'          => '%s',
			'expires_at'        => '%s',
			'total_clicks'      => '%d',
			'unique_clicks'     => '%d',
			'cpt_id'            => '%d',
			'cpt_type'          => '%s',
			'rules'             => '%s',
			'created_at'        => '%s',
			'created_by_id'     => '%d',
			'updated_at'        => '%s',
			'updated_by_id'     => '%d',
		];
	}

	/**
	 * Get default column values
	 *
	 * @since 1.0.0
	 */
	public function get_column_defaults() {
		return [
			'name'              => '',
			'slug'              => '',
			'description'       => '',
			'url'               => null,
			'nofollow'          => 0,
			'track_me'          => 1,
			'sponsored'         => 0,
			'params_forwarding' => 0,
			'params_structure'  => null,
			'redirect_type'     => 307,
			'status'            => 1,
			'type'              => 'direct',
			'type_id'           => null,
			'password'          => null,
			'expires_at'        => null,
			'total_clicks'      => null,
			'unique_clicks'     => null,
			'cpt_id'            => null,
			'cpt_type'          => null,
			'rules'             => null,
			'created_at'        => Helper::get_current_date_time(),
			'created_by_id'     => null,
			'updated_at'        => null,
			'updated_by_id'     => null,
		];
	}

	/**
	 * Get link data based on link_id
	 *
	 * @since 1.1.3
	 *
	 * @param  string  $output
	 *
	 * @param  null  $link_id
	 *
	 * @return array|object|void|null
	 *
	 */
	public function get( $link_id = null, $output = ARRAY_A ) {
		if ( empty( $link_id ) ) {
			return [];
		}

		$link_data = parent::get( $link_id, $output );

		$groups = US()->db->links_groups->get_group_ids_by_link_ids( $link_id );

		$group_ids = Helper::get_data( $groups, $link_id, [] );

		$link_data['group_ids'] = $group_ids;

		return $link_data;
	}

	/**
	 * Get link by slug
	 *
	 * @since 1.0.0
	 *
	 * @param  null  $slug
	 *
	 * @return array|object|void|null
	 *
	 */
	public function get_by_slug( $slug = null ) {
		if ( empty( $slug ) ) {
			return [];
		}

		$case_sensitive = false;
		if ( US()->is_pro() ) {
			$settings       = US()->get_settings();
			$case_sensitive = Helper::get_data( $settings, 'general_settings_case_sensitive_slug', 0 );
		}

		return $this->get_by( 'slug', $slug, ARRAY_A, $case_sensitive );
	}

	/**
	 * Get link by id
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $id
	 *
	 * @return array|object|void|null
	 *
	 */
	public function get_by_id( $id = 0 ) {
		if ( empty( $id ) ) {
			return [];
		}

		return $this->get_by( 'id', $id );
	}

	/**
	 * Get links by IDs
	 *
	 * @since 1.1.7
	 *
	 * @param  array  $ids
	 *
	 * @return array|object|void|null
	 *
	 */
	public function get_by_ids( $ids = [] ) {
		if ( empty( $ids ) ) {
			return [];
		}

		if ( is_scalar( $ids ) ) {
			$ids = [ $ids ];
		}

		if ( ! is_array( $ids ) ) {
			return [];
		}

		$ids_str = $this->prepare_for_in_query( $ids );
		$where   = "id IN ($ids_str)";

		return $this->get_by_conditions( $where );
	}

	/**
	 * Get link by cpt_id
	 *
	 * @since 1.1.0
	 *
	 * @param  int  $cpt_id
	 *
	 * @return array|object|void|null
	 *
	 */
	public function get_by_cpt_id( $cpt_id = 0 ) {
		if ( empty( $cpt_id ) ) {
			return [];
		}

		return $this->get_by( 'cpt_id', $cpt_id );
	}

	/**
	 * Get my link ids.
	 *
	 * @since 1.6.1
	 *
	 * @param  int  $created_by_id
	 *
	 * @return array|object|void|null
	 *
	 */
	public function get_my_link_ids( $created_by_id = 0 ) {
		if ( empty( $created_by_id ) ) {
			return [];
		}

		return $this->get_column_by( 'id', 'created_by_id', $created_by_id );
	}

	/**
	 * Delete Links
	 *
	 * @since 1.0.0
	 *
	 * @param $ids array
	 *
	 */
	public function delete( $ids = [] ) {
		if ( ! is_array( $ids ) ) {
			$ids = [ $ids ];
		}

		if ( is_array( $ids ) && count( $ids ) > 0 ) {

			foreach ( $ids as $id ) {
				parent::delete( absint( $id ) );

				/**
				 * Take necessary cleanup steps using this hook
				 *
				 * @since 1.0.0
				 */
				do_action( 'kc_us_link_deleted', $id );
			}
		}

		// Clean up on link deletion
		do_action( 'kc_us_links_deleted' );
	}

	/**
	 * Reset links statistics
	 *
	 * @since 1.4.10
	 *
	 * @param  array  $ids
	 *
	 */
	public function reset_stats( $ids = [] ) {
		if ( ! is_array( $ids ) ) {
			$ids = [ $ids ];
		}

		if ( is_array( $ids ) && count( $ids ) > 0 ) {

			foreach ( $ids as $id ) {
				US()->db->clicks->delete_by_link_id( $id );

				US()->db->links->update( $id, [ 'total_clicks' => 0, 'unique_clicks' => 0 ] );

				/**
				 * Take necessary cleanup steps using this hook
				 *
				 * @since 1.4.10
				 */
				do_action( 'kc_us_link_stats_reset', $id );
			}
		}
	}

	/**
	 * Prepare formdata
	 *
	 * @since 1.1.0
	 *
	 * @param  null  $id
	 *
	 * @param  array  $data
	 *
	 * @return array
	 *
	 */
	public function prepare_form_data( $data = [], $id = null ) {
		$default_redirection_type = $default_nofollow = $default_sponsored = $default_parameter_forwarding = $default_track_me = 0;

		$slug = Helper::get_data( $data, 'slug', '', true );
		if ( is_null( $id ) ) {

			$default_settings = US()->get_settings();

			$default_redirection_type     = Helper::get_data( $default_settings,
				'links_default_link_options_redirection_type', 307 );
			$default_nofollow             = Helper::get_data( $default_settings,
				'links_default_link_options_enable_nofollow', 1 );
			$default_sponsored            = Helper::get_data( $default_settings,
				'links_default_link_options_enable_sponsored', 0 );
			$default_parameter_forwarding = Helper::get_data( $default_settings,
				'links_default_link_options_enable_paramter_forwarding', 0 );
			$default_track_me             = Helper::get_data( $default_settings,
				'links_default_link_options_enable_tracking', 1 );

			$slug = Helper::get_slug_with_prefix( $slug );
		}

		$geo_data = Helper::get_data( $data, 'rules|dynamic_redirect|geo', '' );

		$mapped_geo_rules = [];
		if ( ! empty( $geo_data ) ) {
			$total = count( $geo_data['countries'] );
			for ( $i = 0; $i < $total; $i ++ ) {
				$mapped_geo_rules[ $geo_data['countries'][ $i ] ] = $geo_data['urls'][ $i ];
			}
		}

		$data['rules']['mapped_geo_rules'] = $mapped_geo_rules;

		$is_link_rotation = Helper::get_data( $data, 'rules|dynamic_redirect_type', '' );
		if ( 'link-rotation' == $is_link_rotation ) {
			$split_test         = Helper::get_data( $data, 'rules|dynamic_redirect|link_rotation|split_test', 0 );
			$default_target_url = Helper::get_data( $data, 'url', '' );
			$urls               = Helper::get_data( $data, 'rules|dynamic_redirect|link_rotation|urls', [] );
			$urls               = array_merge( [ $default_target_url ], $urls );

			$data['rules']['dynamic_redirect']['link_rotation']['urls'] = $urls;
		}

		// Store pixel ids in rules.
		$pixel_ids = Helper::get_data( $data, 'tracking_pixel_ids', [] );
		if ( ! empty( $pixel_ids ) ) {
			$data['rules']['tracking_pixel_ids'] = $pixel_ids;
		}

		$expires_at = Helper::get_data( $data, 'expires_at', null );
		if ( empty( $expires_at ) ) {
			$expires_at = null;
		}

		$password = Helper::get_data( $data, 'password', null );
		if ( empty( $password ) ) {
			$password = null;
		}

		$form_data = [
			'name'              => Helper::get_data( $data, 'name', '', true ),
			'url'               => Helper::get_data( $data, 'url', '' ),
			'slug'              => trim( $slug, '/' ),
			'redirect_type'     => Helper::get_data( $data, 'redirect_type', $default_redirection_type, true ),
			'description'       => sanitize_textarea_field( Helper::get_data( $data, 'description', '' ) ),
			'nofollow'          => Helper::get_data( $data, 'nofollow', $default_nofollow ),
			'params_forwarding' => Helper::get_data( $data, 'params_forwarding', $default_parameter_forwarding ),
			'sponsored'         => Helper::get_data( $data, 'sponsored', $default_sponsored ),
			'track_me'          => Helper::get_data( $data, 'track_me', $default_track_me ),
			'status'            => Helper::get_data( $data, 'status', 1 ),
			'cpt_id'            => Helper::get_data( $data, 'cpt_id', null ),
			'cpt_type'          => Helper::get_data( $data, 'cpt_type', null ),
			'expires_at'        => $expires_at,
			'total_clicks'      => Helper::get_data( $data, 'total_clicks', null ),
			'unique_clicks'     => Helper::get_data( $data, 'unique_clicks', null ),
			'password'          => $password,
			'rules'             => maybe_serialize( Helper::get_data( $data, 'rules', [] ) ),
		];

		$current_user_id   = get_current_user_id();
		$current_date_time = Helper::get_current_date_time();
		// For Updaate, we want to update updated_at & updated_by_id field
		if ( ! empty( $id ) ) {
			$form_data['updated_at']    = $current_date_time;
			$form_data['updated_by_id'] = $current_user_id;
		} else {
			// For Insert, we don't need to add updated_at & updated_by_id field
			// We just need to add created_at & created_by_id field.
			$form_data['created_at']    = $current_date_time;
			$form_data['created_by_id'] = $current_user_id;
		}

		return $form_data;
	}

	/**
	 * Default Form Data
	 *
	 * @since 1.1.3
	 * @return array
	 *
	 */
	public function default_form_data() {
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

		$rules = [];
		if ( US()->is_pro() ) {
			$default_domain = Helper::get_data( $default_settings, 'links_default_link_options_default_custom_domain',
				'home' );

			$rules = [
				'domain'                => $default_domain,
				'utm_params'            => [],
				'dynamic_redirect_type' => 'off',
				'dynamic_redirect'      => [],
			];
		}

		return [
			'slug'              => Utils::get_valid_slug(),
			'redirection_type'  => $default_redirection_type,
			'nofollow'          => $default_nofollow,
			'params_forwarding' => $default_paramter_forwarding,
			'sponsored'         => $default_sponsored,
			'track_me'          => $default_track_me,
			'rules'             => $rules,
		];
	}

	/**
	 * Delete clicks by cpt id
	 *
	 * @since 1.1.0
	 *
	 * @param  null  $cpt_id
	 *
	 * @return bool
	 *
	 */
	public function delete_by_cpt_id( $cpt_id = null ) {
		if ( empty( $cpt_id ) ) {
			return false;
		}

		return $this->delete_by( 'cpt_id', $cpt_id );
	}

	/**
	 * Create link from post
	 *
	 * @since 1.1.3
	 *
	 * @param  string  $slug
	 *
	 * @param        $post
	 *
	 * @return bool|int
	 *
	 */
	public function create_link_from_post( $post, $slug = '' ) {
		$post = get_post( $post );

		if ( $post instanceof \WP_Post ) {

			$link_data = [
				'cpt_id'      => $post->ID,
				'cpt_type'    => $post->post_type,
				'url'         => get_permalink( $post->ID ),
				'name'        => addslashes( $post->post_title ),
				'description' => addslashes( $post->post_excerpt ),
			];

			return $this->create_link( $link_data, $slug );
		}

		return false;
	}

	/**
	 * Create Link
	 *
	 * @since 1.2.5
	 *
	 * @param  string  $slug
	 *
	 * @param  array  $link_data
	 *
	 * @return bool|int
	 *
	 */
	public function create_link( $link_data = [], $slug = '' ) {
		if ( empty( $slug ) ) {
			$slug = Utils::get_valid_slug();
		}

		$link_data['slug'] = $slug;

		$link_data = wp_parse_args( $link_data, $this->default_form_data() );

		$link_data = $this->prepare_form_data( $link_data );

		return $this->save( $link_data );
	}

	/**
	 * Insert/ Update link
	 *
	 * @since 1.2.13
	 *
	 * @param  null  $id
	 *
	 * @param  array  $data
	 *
	 * @return bool|int|void
	 *
	 */
	public function save( $data = [], $id = null ) {
		$saved = parent::save( $data, $id );

		if ( ! $saved ) {
			return false;
		}

		if ( is_null( $id ) ) {
			do_action( 'kc_us_link_created', $saved );
		} else {
			do_action( 'kc_us_link_updated', $id );
		}

		do_action( 'kc_us_link_saved' );

		return $saved;
	}

	/**
	 * Bulk add expiry date.
	 *
	 * @since 1.8.7
	 *
	 * @param $ids
	 * @param $expiry_date
	 *
	 * @return bool
	 */
	public function bulk_add_expiry( $ids, $expiry_date ) {
		if ( empty( $expiry_date ) ) {
			return false;
		}

		if ( empty( $ids ) ) {
			return false;
		}

		$ids_str = $this->prepare_for_in_query( $ids );
		$where   = "id IN ($ids_str)";

		return $this->update_by_condition( 'expires_at', $expiry_date, $where );
	}

	/**
	 * Get links.
	 *
	 * @since 1.9.1
	 * @return array
	 *
	 */
	public function get_links_for_dropdown() {
		$links = $this->get_columns_by_condition( [ 'id', 'slug', 'name' ] );

		$options = [];
		if ( ! empty( $links ) ) {
			foreach ( $links as $link ) {
				$options[ $link['id'] ] = "{$link['name']} ({$link['slug']})";
			}
		}

		return $options;
	}

	/**
	 * Bulk update parameter value.
	 *
	 * @param $ids
	 * @param $parameter
	 * @param $value
	 *
	 * @return bool
	 */
	public function bulk_update_parameters( $ids, $parameter, $value ) {
		if ( empty( $ids ) || empty( $parameter ) ) {
			return false;
		}

		$ids_str = $this->prepare_for_in_query( $ids );
		$where   = "id IN ($ids_str)";

		return $this->update_by_condition( $parameter, $value, $where );
	}

}