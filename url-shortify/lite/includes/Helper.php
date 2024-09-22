<?php

namespace KaizenCoders\URL_Shortify;

use KaizenCoders\URL_Shortify\Admin\Stats;
use KaizenCoders\URL_Shortify\Common\Utils;

/**
 * Plugin_Name
 *
 * @package   Url_Shortify
 * @author    KaizenCoders <hello@kaizencoders.com>
 * @link      https://kaizencoders.com
 */

/**
 * Helper Class
 */
class Helper {
	/**
	 * Whether given user is an administrator.
	 *
	 * @param  \WP_User  $user  The given user.
	 *
	 * @return bool
	 */
	public static function is_user_admin( \WP_User $user = null ) {
		if ( is_null( $user ) ) {
			$user = wp_get_current_user();
		}

		if ( ! $user instanceof WP_User ) {
			_doing_it_wrong( __METHOD__, 'To check if the user is admin is required a WP_User object.', '1.0.0' );
		}

		return is_multisite() ? user_can( $user, 'manage_network' ) : user_can( $user, 'manage_options' );
	}

	/**
	 * What type of request is this?
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $type  admin, ajax, cron, cli or frontend.
	 *
	 * @return bool
	 */
	public function request( $type ) {
		switch ( $type ) {
			case 'admin_backend':
				return $this->is_admin_backend();
			case 'ajax':
				return $this->is_ajax();
			case 'installing_wp':
				return $this->is_installing_wp();
			case 'rest':
				return $this->is_rest();
			case 'cron':
				return $this->is_cron();
			case 'frontend':
				return $this->is_frontend();
			case 'cli':
				return $this->is_cli();
			default:
				_doing_it_wrong( __METHOD__, esc_html( sprintf( 'Unknown request type: %s', $type ) ), '1.0.0' );

				return false;
		}
	}

	/**
	 * Is installing WP
	 *
	 * @return boolean
	 */
	public function is_installing_wp() {
		return defined( 'WP_INSTALLING' );
	}

	/**
	 * Is admin
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_admin_backend() {
		return is_user_logged_in() && is_admin();
	}

	/**
	 * Is ajax
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_ajax() {
		return ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || defined( 'DOING_AJAX' );
	}

	/**
	 * Is rest
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_rest() {
		return defined( 'REST_REQUEST' );
	}

	/**
	 * Is cron
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_cron() {
		return ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) || defined( 'DOING_CRON' );
	}

	/**
	 * Is frontend
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_frontend() {
		return ( ! $this->is_admin_backend() || ! $this->is_ajax() ) && ! $this->is_cron() && ! $this->is_rest();
	}

	/**
	 * Is cli
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_cli() {
		return defined( 'WP_CLI' ) && WP_CLI;
	}

	/**
	 * Define constant
	 *
	 * @since 1.0.0
	 *
	 * @param $value
	 *
	 * @param $name
	 */
	public static function maybe_define_constant( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Get current date time
	 *
	 * @return false|string
	 */
	public static function get_current_date_time() {
		return gmdate( 'Y-m-d H:i:s' );
	}


	/**
	 * Get current date time
	 *
	 * @return false|string
	 *
	 */
	public static function get_current_gmt_timestamp() {
		return strtotime( gmdate( 'Y-m-d H:i:s' ) );
	}

	/**
	 * Get current date
	 *
	 * @return false|string
	 *
	 */
	public static function get_current_date() {
		return gmdate( 'Y-m-d' );
	}

	/**
	 * Format date time
	 *
	 * @since 1.0.0
	 *
	 * @param $date
	 *
	 * @return string
	 *
	 */
	public static function format_date_time( $date ) {
		$convert_date_format = get_option( 'date_format' );
		$convert_time_format = get_option( 'time_format' );

		return ( $date !== '0000-00-00 00:00:00' ) ? date_i18n( "$convert_date_format $convert_time_format",
			strtotime( get_date_from_gmt( $date ) ) ) : '<i class="dashicons dashicons-es dashicons-minus"></i>';
	}

	/**
	 * Clean String or array using sanitize_text_field
	 *
	 * @param $var data to sanitize
	 *
	 * @return array|string
	 *
	 * @sinc 1.0.0
	 *
	 */
	public static function clean( $var ) {
		if ( is_array( $var ) ) {
			return array_map( 'self::clean', $var );
		} else {
			return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
		}
	}

	/**
	 * Get IP
	 *
	 * @return mixed|string|void
	 *
	 */
	public static function get_ip() {

		$settings = maybe_unserialize( get_option( 'kc_us_settings' ) );

		$how_to = Helper::get_data( $settings, 'reports_reporting_options_how_to_get_ip', '' );

		if ( $how_to ) {
			return ! empty( $_SERVER[ $how_to ] ) ? $_SERVER[ $how_to ] : $_SERVER['REMOTE_ADDR'];
		} else {

			$fields = [
				'HTTP_CF_CONNECTING_IP',
				'HTTP_CLIENT_IP',
				'HTTP_X_FORWARDED_FOR',
				'HTTP_X_FORWARDED',
				'HTTP_FORWARDED_FOR',
				'HTTP_FORWARDED',
				'REMOTE_ADDR',
			];

			foreach ( $fields as $ip_field ) {
				if ( ! empty( $_SERVER[ $ip_field ] ) ) {
					return $_SERVER[ $ip_field ];
				}
			}
		}

		return $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * Determines if an IP address is valid.
	 *
	 * Handles both IPv4 and IPv6 addresses.
	 *
	 * @since 1.5.0
	 *
	 * @param $ip
	 *
	 * @return false|mixed
	 *
	 */
	public static function is_ip_address( $ip ) {
		$ipv4_pattern = '/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/';

		if ( ! preg_match( $ipv4_pattern, $ip ) && ! \Requests_IPv6::check_ipv6( $ip ) ) {
			return false;
		}

		return $ip;
	}

	/**
	 * Get GMT Offset
	 *
	 * @param  bool  $in_seconds
	 * @param  null  $timestamp
	 *
	 * @return float|int
	 *
	 */
	public static function get_gmt_offset( $in_seconds = false, $timestamp = null ) {

		$offset = get_option( 'gmt_offset' );

		if ( $offset == '' ) {
			$tzstring = get_option( 'timezone_string' );
			$current  = date_default_timezone_get();
			date_default_timezone_set( $tzstring );
			$offset = date( 'Z' ) / 3600;
			date_default_timezone_set( $current );
		}

		// check if timestamp has DST
		if ( ! is_null( $timestamp ) ) {
			$l = localtime( $timestamp, true );
			if ( $l['tm_isdst'] ) {
				$offset ++;
			}
		}

		return $in_seconds ? $offset * 3600 : (int) $offset;
	}

	/**
	 * Insert $new in $array after $key
	 *
	 * @param $array
	 * @param $key
	 * @param $new
	 *
	 * @return array
	 *
	 */
	public static function array_insert_after( $array, $key, $new ) {
		$keys  = array_keys( $array );
		$index = array_search( $key, $keys );
		$pos   = false === $index ? count( $array ) : $index + 1;

		return array_merge( array_slice( $array, 0, $pos ), $new, array_slice( $array, $pos ) );
	}

	/**
	 * Insert a value or key/value pair before a specific key in an array.  If key doesn't exist, value is prepended
	 * to the beginning of the array.
	 *
	 * @param  array  $array
	 * @param  string  $key
	 * @param  array  $new
	 *
	 * @return array
	 */
	public static function array_insert_before( array $array, $key, array $new ) {
		$keys = array_keys( $array );
		$pos  = (int) array_search( $key, $keys );

		return array_merge( array_slice( $array, 0, $pos ), $new, array_slice( $array, $pos ) );
	}


	/**
	 * Insert $new in $array after $key
	 *
	 * @param $array
	 *
	 * @return boolean
	 *
	 */
	public static function is_forechable( $array = [] ) {

		if ( ! is_array( $array ) ) {
			return false;
		}

		if ( empty( $array ) ) {
			return false;
		}

		if ( count( $array ) <= 0 ) {
			return false;
		}

		return true;

	}

	/**
	 * Get current db version
	 *
	 * @since 1.0.0
	 */
	public static function get_db_version() {
		return Option::get( 'db_version', '0.0.1' );
	}

	/**
	 * Get all Plugin admin screens
	 *
	 * @since 1.0.0
	 * @return array|mixed|void
	 *
	 */
	public static function get_plugin_admin_screens() {

		// TODO: Can be updated with a version check when https://core.trac.wordpress.org/ticket/18857 is fixed
		$prefix = sanitize_title( __( 'URL Shortify', 'url-shortify' ) );

		$screens = [
			'toplevel_page_url_shortify',
			"{$prefix}_page_us_links",
			"{$prefix}_page_us_groups",
			"{$prefix}_page_us_domains",
			"{$prefix}_page_us_utm_presets",
			"{$prefix}_page_us_tracking_pixels",
			"{$prefix}_page_us_tools",
			"{$prefix}_page_kc-us-settings",
			"{$prefix}_page_kc-us-tools-settings",
			"{$prefix}_page_url_shortify-account",
		];

		$screens = apply_filters( 'kc_us_admin_screens', $screens );

		return $screens;
	}

	/**
	 * Is es admin screen?
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $screen_id  Admin screen id
	 *
	 * @return bool
	 *
	 */
	public static function is_plugin_admin_screen( $screen_id = '' ) {

		$current_screen_id = self::get_current_screen_id();
		// Check for specific admin screen id if passed.
		if ( ! empty( $screen_id ) ) {
			if ( $current_screen_id === $screen_id ) {
				return true;
			} else {
				return false;
			}
		}

		$plugin_admin_screens = self::get_plugin_admin_screens();

		if ( in_array( $current_screen_id, $plugin_admin_screens ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get Current Screen Id
	 *
	 * @since 1.0.0
	 * @return string
	 *
	 */
	public static function get_current_screen_id() {

		$current_screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;

		if ( ! $current_screen instanceof \WP_Screen ) {
			return '';
		}

		$current_screen = get_current_screen();

		return ( $current_screen ? $current_screen->id : '' );
	}

	/**
	 * Get data from array
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $var
	 * @param  string  $default
	 * @param  bool  $clean
	 *
	 * @param  array  $array
	 *
	 * @return array|string
	 *
	 */
	public static function get_data( $array = [], $var = '', $default = '', $clean = false ) {

		if ( empty( $array ) ) {
			return $default;
		}

		if ( ! empty( $var ) || ( 0 === $var ) ) {
			if ( strpos( $var, '|' ) > 0 ) {
				$vars = array_map( 'trim', explode( '|', $var ) );
				foreach ( $vars as $var ) {
					if ( isset( $array[ $var ] ) ) {
						$array = $array[ $var ];
					} else {
						return $default;
					}
				}

				return wp_unslash( $array );
			} else {
				$value = isset( $array[ $var ] ) ? wp_unslash( $array[ $var ] ) : $default;
			}
		} else {
			$value = wp_unslash( $array );
		}

		if ( $clean ) {
			$value = self::clean( $value );
		}

		return $value;
	}

	/**
	 * Get POST | GET data from $_REQUEST
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $default
	 * @param  bool  $clean
	 *
	 * @param  string  $var
	 *
	 * @return array|string
	 *
	 */
	public static function get_request_data( $var = '', $default = '', $clean = true ) {
		return self::get_data( $_REQUEST, $var, $default, $clean );
	}

	/**
	 * Get POST data from $_POST
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $default
	 * @param  bool  $clean
	 *
	 * @param  string  $var
	 *
	 * @return array|string
	 *
	 */
	public static function get_post_data( $var = '', $default = '', $clean = true ) {
		return self::get_data( $_POST, $var, $default, $clean );
	}

	/**
	 * Get Current blog url with or without prefix.
	 *
	 * @since    1.0.0
	 *
	 * @modified 1.5.12
	 * @return string
	 *
	 */
	public static function get_blog_url( $with_prefix = false ) {
		$blog_id = null;
		if ( function_exists( 'is_multisite' ) && is_multisite() && function_exists( 'get_current_blog_id' ) ) {
			$blog_id = get_current_blog_id();
		}

		$blog_url = get_home_url( $blog_id );

		// Fix WPML adding the language code at the start of the URL
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			if ( empty( $prli_bid ) || ! function_exists( 'is_multisite' ) || ! is_multisite() ) {
				$blog_url = get_option( 'home' );
			} else {
				switch_to_blog( $prli_bid );
				$blog_url = get_option( 'home' );
				restore_current_blog();
			}
		}

		if ( $with_prefix ) {
			$prefix = self::get_link_prefix();
			if ( ! empty( $prefix ) ) {
				$blog_url = $blog_url . '/' . $prefix . '/';
			}
		}

		return $blog_url;
	}

	/**
	 * Get slug with prefix.
	 *
	 * @param  string  $slug
	 *
	 * @return string
	 *
	 * @sicne 1.5.12
	 */
	public static function get_slug_with_prefix( $slug = '' ) {
		if ( empty( $slug ) ) {
			return '';
		}

		$prefix = self::get_link_prefix();

		$slug = ltrim( $slug, $prefix );

		return ( empty( $prefix ) ? ltrim( $slug, '/' ) : trim( trim( $prefix, '/' ) . '/' . ltrim( $slug, '/' ) ) );
	}

	/**
	 * Get link prefix.
	 *
	 * @since 1.7.5
	 * @return array|data|string
	 *
	 */
	public static function get_link_prefix() {
		$settings = US()->get_settings();

		$default_prefix = Helper::get_data( $settings, 'links_default_link_options_link_prefix', '' );

		return apply_filters( 'kc_us_link_prefix', $default_prefix );
	}

	/**
	 * Get short link
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $link_data
	 *
	 * @param  string  $slug
	 *
	 * @return string
	 *
	 */
	public static function get_short_link( $slug = '', $link_data = [] ) {

		if ( empty( $slug ) ) {
			return '';
		}

		$link = trailingslashit( self::get_blog_url() ) . $slug;

		if ( empty( $link_data ) || ! US()->is_pro() ) {
			return $link;
		}

		return apply_filters( 'kc_us_generate_short_link', $link, $slug, $link_data );
	}

	/**
	 * Get short link by link id
	 *
	 * @since 1.2.10
	 *
	 * @param  string  $id
	 *
	 * @return string
	 *
	 */
	public static function get_short_link_by_id( $id = '' ) {
		if ( empty( $id ) ) {
			return '';
		}

		$link = US()->db->links->get_by_id( $id );

		return self::get_short_link( $link['slug'], $link );
	}

	/**
	 * Get redirection types
	 *
	 * @since 1.0.0
	 * @return mixed|void
	 *
	 */
	public static function get_redirection_types() {

		$types = [
			'307' => __( '307 (Temporary)', 'url-shortify' ),
			'302' => __( '302 (Temporary)', 'url-shortify' ),
			'301' => __( '301 (Permanent)', 'url-shortify' ),
		];

		$additional_types = apply_filters( 'kc_us_redirection_types', [] );

		if ( is_array( $additional_types ) && count( $additional_types ) > 0 ) {
			$types = $types + $additional_types;
		}

		return $types;
	}

	/**
	 * Get link prefixes
	 *
	 * @since 1.5.7
	 * @return array
	 *
	 */
	public static function get_link_prefixes() {

		$types = [
			''           => __( '-- No Prefix --', 'url-shortify' ),
			'recommends' => __( 'recommends', 'url-shortify' ),
			'go'         => __( 'go', 'url-shortify' ),
		];

		$additional_prefixes = apply_filters( 'kc_us_link_prefixes', [] );

		if ( is_array( $additional_prefixes ) && count( $additional_prefixes ) > 0 ) {
			$types = $types + $additional_prefixes;
		}

		return $types;
	}

	/**
	 * Get custom domains
	 *
	 * @since 1.3.8
	 * @return array|void
	 *
	 */
	public static function get_domains() {

		$domains = [
			'home' => site_url(),
		];

		$custom_domains = apply_filters( 'kc_us_custom_domains', [] );

		if ( is_array( $custom_domains ) && count( $custom_domains ) > 0 ) {
			$domains = Helper::array_insert_before( $domains, 'home',
				[ 'any' => __( 'All my domains', 'url-shortify' ) ] );
			$domains = $domains + $custom_domains;
		}

		return $domains;
	}

	/**
	 * Get custom domains.
	 *
	 * @since 1.8
	 * @return array
	 */
	public static function get_domains_for_select() {
		$domains = [
			'home' => site_url(),
		];

		$custom_domains = apply_filters( 'kc_us_custom_domains', [] );

		if ( is_array( $custom_domains ) && count( $custom_domains ) > 0 ) {
			$domains = $domains + $custom_domains;
		}

		return $domains;
	}

	/**
	 * Create Copy Link HTML
	 *
	 * @since 1.1.3
	 *
	 * @param        $id
	 * @param  string  $html
	 *
	 * @param        $link
	 *
	 * @return string
	 *
	 */
	public static function create_copy_short_link_html( $link, $id, $html = '' ) {
		if ( ! empty( $html ) ) {
			return '<span class="kc-flex kc-us-copy-to-clipboard" data-clipboard-text="' . $link . '" id="link-' . $id . '">' . $html . '<svg class="kc-us-link-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><title>' . __( 'Copy',
					'url-shortify' ) . '</title><path d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg><p id="copied-text-link-' . $id . '"></p></span>';
		} else {
			return '<span class="kc-flex kc-us-copy-to-clipboard" data-clipboard-text="' . $link . '" id="link-' . $id . '"><svg class="kc-us-link-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><title>' . __( 'Copy',
					'url-shortify' ) . '</title><path d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg><p id="copied-text-link-' . $id . '"></p></span>';
		}
	}

	/**
	 * Create Link Stats URL
	 *
	 * @since 1.1.3
	 *
	 * @param  int  $link_id
	 *
	 * @return string|void
	 *
	 */
	public static function create_link_stats_url( $link_id = 0 ) {

		if ( empty( $link_id ) ) {
			return '#';
		}

		return self::get_link_action_url( $link_id, 'statistics' );
	}

	/**
	 * Prpare clicks column
	 *
	 * @since    1.1.3
	 *
	 * @modified 1.2.4
	 *
	 * @param  string  $stats_url
	 *
	 * @param        $link_ids
	 *
	 * @return string
	 *
	 */
	public static function prepare_clicks_column( $link_ids, $stats_url = '' ) {
		$total_clicks  = Stats::get_total_clicks_by_link_ids( $link_ids );
		$unique_clicks = Stats::get_total_unique_clicks_by_link_ids( $link_ids );

		return self::prepare_clicks_column_with_data( $unique_clicks, $total_clicks, $stats_url );
	}

	/**
	 * Prepare clicks column with given data.
	 *
	 * @since 1.9.0
	 *
	 * @param $total_clicks
	 * @param $stas_url
	 *
	 * @param $unique_clicks
	 *
	 * @return string
	 *
	 */
	public static function prepare_clicks_column_with_data( $unique_clicks, $total_clicks, $stats_url ) {
		if ( 0 == $total_clicks || empty( $stats_url ) ) {
			return $unique_clicks . ' / ' . $total_clicks;
		} else {
			return '<a href="' . $stats_url . '"  title="' . __( 'Unique Clicks / Total Clicks',
					'url-shortify' ) . '" class="kc-us-link"/>' . $unique_clicks . ' / ' . $total_clicks . '</a>';
		}
	}

	/**
	 * Get link action url
	 *
	 * @since 1.1.5
	 *
	 * @param  string  $action
	 *
	 * @param  null  $link_id
	 *
	 * @return string
	 *
	 */
	public static function get_link_action_url( $link_id = null, $action = 'edit' ) {
		if ( empty( $link_id ) || empty( $action ) ) {
			return '#';
		}

		return self::get_action_url( $link_id, 'links', $action );
	}

	/**
	 * Get Group action url
	 *
	 * @since 1.1.7
	 *
	 * @param  string  $action
	 *
	 * @param  null  $group_id
	 *
	 * @return string
	 *
	 */
	public static function get_group_action_url( $group_id = null, $action = 'edit' ) {
		if ( empty( $group_id ) || empty( $action ) ) {
			return '#';
		}

		return self::get_action_url( $group_id, 'groups', $action );
	}

	/**
	 * Get max upload file size.
	 *
	 * @since 1.6.0
	 * @return int
	 *
	 */
	public static function get_max_upload_size() {
		// Allowed maximum 5MB file size.
		return min( 5242880, wp_max_upload_size() );
	}

	/**
	 * Get Group action url
	 *
	 * @since 1.3.8
	 *
	 * @param  string  $action
	 *
	 * @param  null  $group_id
	 *
	 * @return string
	 *
	 */
	public static function get_domain_action_url( $id = null, $action = 'edit' ) {
		if ( empty( $id ) || empty( $action ) ) {
			return '#';
		}

		return self::get_action_url( $id, 'domains', $action );
	}

	/**
	 * Get Tracking Pixel action URL.
	 *
	 * @since 1.8.9
	 *
	 * @param $action
	 *
	 * @param $id
	 *
	 * @return string
	 *
	 */
	public static function get_tracking_pixel_action_url( $id = null, $action = 'edit' ) {
		if ( empty( $id ) || empty( $action ) ) {
			return '#';
		}

		return self::get_action_url( $id, 'tracking_pixels', $action );
	}

	/**
	 * Get UTM Presets action url
	 *
	 * @since 1.3.8
	 *
	 * @param  string  $action
	 *
	 * @param  null  $group_id
	 *
	 * @return string
	 *
	 */
	public static function get_utm_presets_action_url( $id = null, $action = 'edit' ) {
		if ( empty( $id ) || empty( $action ) ) {
			return '#';
		}

		return self::get_action_url( $id, 'utm_presets', $action );
	}

	/**
	 * Get action url
	 *
	 * @since 1.1.7
	 *
	 * @param  string  $type
	 * @param  string  $action
	 *
	 * @param  null  $id
	 *
	 * @return string
	 *
	 */
	public static function get_action_url( $id = null, $type = 'links', $action = 'edit' ) {
		if ( empty( $action ) ) {
			return '#';
		}

		$nonce = $tab = '';
		if ( 'links' === $type ) {
			$page = 'us_links';
		} elseif ( 'groups' === $type ) {
			$page = 'us_groups';
		} elseif ( 'domains' === $type ) {
			$page = 'us_domains';
		} elseif ( 'utm_presets' === $type ) {
			$page = 'us_utm_presets';
		} elseif ( 'tracking_pixels' === $type ) {
			$page = 'us_tracking_pixels';
		} elseif ( 'main' === $type ) {
			$page = 'url_shortify';
		} elseif ( 'tools' === $type ) {
			$page  = 'us_tools';
			$nonce = wp_create_nonce( 'kc_us_import' );
		} elseif ( 'api-keys' === $type ) {
			$page = 'us_tools';
			$tab  = 'rest-api';
		} else {
			$page = 'us_links';
		}

		$args = [
			'page'     => $page,
			'action'   => $action,
			'_wpnonce' => ! empty( $nonce ) ? $nonce : wp_create_nonce( 'us_action_nonce' ),
		];

		if ( ! empty( $id ) ) {
			$args['id'] = $id;
		}

		if ( ! empty( $tab ) ) {
			$args['tab'] = $tab;
		}

		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * Get Start & End date based on $days
	 *
	 * @since 1.1.6
	 *
	 * @param  int  $days
	 *
	 * @return array
	 *
	 */
	public static function get_start_and_end_date_from_last_days( $days = 7 ) {
		$end_date = date( 'Y-m-d', time() );

		$start_date = date( 'Y-m-d', strtotime( "- $days days" ) );

		return [
			'start_date' => $start_date,
			'end_date'   => $end_date,
		];
	}

	/**
	 * Return string with specific length
	 *
	 * @since 1.2.0
	 *
	 * @param $length
	 *
	 * @param $x
	 *
	 * @return string
	 *
	 */
	public static function str_limit( $x, $length ) {
		if ( strlen( $x ) <= $length ) {
			return $x;
		} else {
			$y = substr( $x, 0, $length ) . '...';

			return $y;
		}
	}

	/**
	 * Get Post Type from Post ID
	 *
	 * @since 1.2.5
	 *
	 * @param  int  $cpt_id
	 *
	 * @return string
	 *
	 */
	public static function get_cpt_type_from_cpt_id( $cpt_id = 0 ) {

		if ( empty( $cpt_id ) ) {
			return '';
		}

		$post = get_post( $cpt_id );

		if ( $post instanceof \WP_Post ) {

			return $post->post_type;
		}

		return '';
	}

	/**
	 * Get CPT Info
	 *
	 * @since 1.2.5
	 *
	 * @param  string  $cpt_type
	 *
	 * @return array
	 *
	 */
	public static function get_cpt_info( $cpt_type = '' ) {

		$cpt_info = [

			'post' => [
				'title' => __( 'Post', 'url-shortify' ),
				'icon'  => KC_US_PLUGIN_ASSETS_DIR_URL . '/images/cpt/post-24x24.png',
			],

			'page' => [
				'title' => __( 'Page', 'url-shortify' ),
				'icon'  => KC_US_PLUGIN_ASSETS_DIR_URL . '/images/cpt/page-24x24.png',
			],

			'product' => [
				'title' => __( 'WooCommerce', 'url-shortify' ),
				'icon'  => KC_US_PLUGIN_ASSETS_DIR_URL . '/images/cpt/woocommerce-24x24.png',
			],

			'download' => [
				'title' => __( 'Easy Digital Download', 'url-shortify' ),
				'icon'  => KC_US_PLUGIN_ASSETS_DIR_URL . '/images/cpt/download-24x24.png',
			],

			'event' => [
				'title' => __( 'Events Manager', 'url-shortify' ),
				'icon'  => KC_US_PLUGIN_ASSETS_DIR_URL . '/images/cpt/event-24x24.png',
			],

			'tribe_events' => [
				'title' => __( 'The Events Calendar', 'url-shortify' ),
				'icon'  => KC_US_PLUGIN_ASSETS_DIR_URL . '/images/cpt/tribe_events-24x24.png',
			],

			'docs' => [
				'title' => __( 'Betterdocs', 'url-shortify' ),
				'icon'  => KC_US_PLUGIN_ASSETS_DIR_URL . '/images/cpt/docs-24x24.png',
			],

			'kbe_knowledgebase' => [
				'title' => __( 'WordPress Knowledgebase', 'url-shortify' ),
				'icon'  => KC_US_PLUGIN_ASSETS_DIR_URL . '/images/cpt/kbe_knowledgebase-24x24.png',
			],

			'mec-events' => [
				'title' => __( 'Modern Events', 'url-shortify' ),
				'icon'  => KC_US_PLUGIN_ASSETS_DIR_URL . '/images/cpt/mec-events-24x24.png',
			],

		];

		return ! empty( $cpt_info[ $cpt_type ] ) ? $cpt_info[ $cpt_type ] : $cpt_info['post'];
	}

	public static function get_all_cpt_data() {
		return get_post_types( [ '_builtin' => false, 'public' => true ], 'objects', 'and' );
	}

	/**
	 * Get all cpts.
	 *
	 * @since 1.7.2
	 * @return array
	 *
	 */
	public static function get_all_cpts() {
		$custom_post_types = self::get_all_cpt_data();

		$cpt_array = [ 'post', 'page' ];
		if ( Helper::is_forechable( $custom_post_types ) ) {
			foreach ( $custom_post_types as $cpt_key => $cpt_data ) {
				$cpt_array[] = $cpt_key;
			}
		}

        ksort( $cpt_array );

		return $cpt_array;
	}

	/**
	 * Check whether ip fall into excluded ips
	 *
	 * @since 1.3.0
	 *
	 * @param $range
	 *
	 * @param $ip
	 *
	 * @return bool
	 *
	 */
	public static function is_ip_in_range( $ip, $range ) {

		$ip    = trim( $ip );
		$range = trim( $range );

		if ( $ip === $range ) {
			return true;
		}

		if ( strpos( $range, '/' ) !== false ) {
			// $range is in IP/NETMASK format
			[ $range, $netmask ] = explode( '/', $range, 2 );
			if ( strpos( $netmask, '.' ) !== false ) {
				// $netmask is a 255.255.0.0 format
				$netmask     = str_replace( '*', '0', $netmask );
				$netmask_dec = ip2long( $netmask );

				return ( ( ip2long( $ip ) & $netmask_dec ) == ( ip2long( $range ) & $netmask_dec ) );
			} else {
				// $netmask is a CIDR size block
				// fix the range argument
				$x = explode( '.', $range );
				while ( count( $x ) < 4 ) {
					$x[] = '0';
				}
				[ $a, $b, $c, $d ] = $x;
				$range     = sprintf( "%u.%u.%u.%u", empty( $a ) ? '0' : $a, empty( $b ) ? '0' : $b,
					empty( $c ) ? '0' : $c, empty( $d ) ? '0' : $d );
				$range_dec = ip2long( $range );
				$ip_dec    = ip2long( $ip );

				# Strategy 1 - Create the netmask with 'netmask' 1s and then fill it to 32 with 0s
				#$netmask_dec = bindec(str_pad('', $netmask, '1') . str_pad('', 32-$netmask, '0'));

				# Strategy 2 - Use math to create it
				$wildcard_dec = pow( 2, ( 32 - $netmask ) ) - 1;
				$netmask_dec  = ~$wildcard_dec;

				return ( ( $ip_dec & $netmask_dec ) == ( $range_dec & $netmask_dec ) );
			}
		} else {
			// range might be 255.255.*.* or 1.2.3.0-1.2.3.255
			if ( strpos( $range, '*' ) !== false ) { // a.b.*.* format
				// Just convert to A-B format by setting * to 0 for A and 255 for B
				$lower = str_replace( '*', '0', $range );
				$upper = str_replace( '*', '255', $range );
				$range = "$lower-$upper";
			}

			if ( strpos( $range, '-' ) !== false ) { // A-B format
				[ $lower, $upper ] = explode( '-', $range, 2 );
				$lower_dec = (float) sprintf( "%u", ip2long( $lower ) );
				$upper_dec = (float) sprintf( "%u", ip2long( $upper ) );
				$ip_dec    = (float) sprintf( "%u", ip2long( $ip ) );

				return ( ( $ip_dec >= $lower_dec ) && ( $ip_dec <= $upper_dec ) );
			}


			return false;
		}

	}

	/**
	 * Prpeare Social share widget
	 *
	 * @since 1.3.2
	 *
	 * @param  string  $share_icon_size
	 *
	 * @param  null  $link_id
	 *
	 * @return string
	 *
	 */
	public static function get_social_share_widget( $link_id = null, $share_icon_size = '1' ) {

		$html = '';

		$socials = [];

		$socials = apply_filters( 'kc_us_filter_social_sharing', $socials, $link_id );

		if ( Helper::is_forechable( $socials ) ) {

			$html .= '<div class="share-button sharer pointer" style="display: block;">';
			$html .= '<span class="fa fa-share-alt text-indigo-600 fa-' . $share_icon_size . 'x share-btn cursor-pointer"></span>';
			$html .= '<div class="social bottom center networks-5 us-social" >';

			foreach ( $socials as $social => $data ) {

				$url   = Helper::get_data( $data, 'url', '' );
				$icon  = Helper::get_data( $data, 'icon', '' );
				$title = Helper::get_data( $data, 'title', '' );

				$html .= sprintf( '<a class="fbtn share %s" href="%s" title="%s" target="_blank">%s</i></a>', $social,
					$url, $title, $icon );
			}

			$html .= '</div></div>';
		}

		return $html;
	}

	/**
	 * Check Pretty Links Exists
	 *
	 * @since 1.3.4
	 * @return bool|int
	 *
	 */
	public static function is_pretty_links_table_exists() {
		global $wpdb;

		$links_table = "{$wpdb->prefix}prli_links";

		return US()->is_table_exists( $links_table );
	}

	/**
	 * Check MTS Short Links Exists
	 *
	 * @since 1.3.4
	 * @return bool|int
	 *
	 */
	public static function is_mts_short_links_table_exists() {
		global $wpdb;

		$links_table = "{$wpdb->prefix}short_links";

		return US()->is_table_exists( $links_table );
	}

	/**
	 * Check Easy 301 Redirect Plugin Installed
	 *
	 * @since 1.3.4
	 * @return bool|int
	 *
	 */
	public static function is_301_redirect_table_exists() {
		global $wpdb;

		$links_table = "{$wpdb->prefix}redirects";

		return US()->is_table_exists( $links_table );
	}

	/**
	 * Check Simple 301 Redirect plugin installed
	 *
	 * @since 1.4.8
	 * @return bool
	 *
	 */
	public static function is_simple_301_redirect_plugin_installed() {
		$plugins = Tracker::get_active_plugins();

		if ( in_array( 'simple-301-redirects/wp-simple-301-redirects.php', $plugins ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check Simple 301 Redirect plugin installed
	 *
	 * @since 1.4.8
	 * @return bool
	 *
	 */
	public static function is_thirstry_affiliates_installed() {
		$plugins = Tracker::get_active_plugins();

		if ( in_array( 'thirstyaffiliates/thirstyaffiliates.php', $plugins ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check Shorten URL Plugin Installed.
	 *
	 * @since 1.3.4
	 * @return bool|int
	 *
	 */
	public static function is_shorten_url_table_exists() {
		global $wpdb;

		$links_table = "{$wpdb->prefix}pluginSL_shorturl";

		return US()->is_table_exists( $links_table );
	}

	/**
	 * Check Redirection Plugin Installed.
	 *
	 * @since 1.8.6
	 * @return bool|int
	 */
	public static function is_redirection_installed() {
		global $wpdb;

		$links_table = "{$wpdb->prefix}redirection_items";

		return US()->is_table_exists( $links_table );
	}

	/**
	 * Gets the current action selected from the bulk actions dropdown.
	 *
	 * @since 1.3.4
	 *
	 * @return string|false The action name. False if no action was selected.
	 */
	public static function get_current_action() {
		if ( isset( $_REQUEST['filter_action'] ) && ! empty( $_REQUEST['filter_action'] ) ) {
			return false;
		}

		if ( isset( $_REQUEST['action'] ) && - 1 != $_REQUEST['action'] ) {
			return $_REQUEST['action'];
		}

		if ( isset( $_REQUEST['action2'] ) && - 1 != $_REQUEST['action2'] ) {
			return $_REQUEST['action2'];
		}

		return false;
	}

	/**
	 * Get group string
	 *
	 * @since 1.3.7
	 *
	 * @param $groups
	 *
	 * @param $group_ids
	 *
	 * @return string
	 *
	 */
	public static function get_group_str_from_ids( $group_ids, $groups ) {
		if ( empty( $group_ids ) ) {
			return '';
		}

		if ( is_int( $group_ids ) ) {
			$group_ids = [ $group_ids ];
		}

		if ( empty( $groups ) ) {
			$groups = US()->db->groups->get_id_name_map();
		}

		$group_str = [];
		foreach ( $group_ids as $group_id ) {
			$group_str[] = Helper::get_data( $groups, $group_id, '' );
		}

		return implode( ', ', $group_str );
	}

	/**
	 * Is shortlink request coming from same domain
	 *
	 * @return bool
	 *
	 * @sicne 1.3.8
	 */
	public static function is_request_from_same_domain() {

		$site_url = get_site_url();

		return self::is_request_from_specific_domain( $site_url );
	}

	/**
	 * Is request coming from specific domain?
	 *
	 * @since 1.3.8
	 *
	 * @param $domain
	 *
	 * @return bool
	 *
	 */
	public static function is_request_from_specific_domain( $domain ) {
		$current_page_url = Utils::get_current_page_url();

		$clean_site_host    = Utils::get_the_clean_domain( $domain );
		$clean_request_host = Utils::get_the_clean_domain( $current_page_url );

		return $clean_site_host === $clean_request_host;
	}

	/**
	 * Can show promotion message?
	 *
	 * @since 1.4.4
	 *
	 * @param  boolean  $force
	 *
	 * @param  array  $meta
	 *
	 * @return bool
	 *
	 */
	public static function can_show_promotion( $conditions = [], $force = false ) {

		if ( ! Helper::is_plugin_admin_screen() ) {
			return false;
		}

		if ( $force ) {
			return true;
		}

		$conditions = array_merge(
			[
				'show_plan'                     => 'pro',
				'meta'                          => [],
				'start_after_installation_days' => 7,
				'end_before_installation_days'  => 999999,
				'total_links'                   => 2,
				'start_date'                    => null,
				'end_date'                      => null,
				'promotion'                     => null,
			], $conditions
		);

		extract( $conditions );

		if ( 'pro' === $show_plan ) {
			if ( US()->is_pro() ) {
				return false;
			}
		}

		// Already seen this promotion?
		if ( ! is_null( $promotion ) && self::is_promotion_dismissed( $promotion ) ) {
			return false;
		}

		$today = Helper::get_current_date_time();

		// Don't show if start date is future.
		if ( ! is_null( $start_date ) && ( $today < $start_date ) ) {
			return false;
		}

		// Don't show if end date is past.
		if ( ! is_null( $end_date ) && ( $today > $end_date ) ) {
			return false;
		}

		// Check total links condition if it exists.
		if ( ! is_null( $total_links ) ) {
			if ( $total_links > US()->db->links->count() ) {
				return false;
			}
		}

		$installed_on = Option::get( 'installed_on', 0 );
		if ( 0 === $installed_on ) {
			Option::set( 'installed_on', time() );
		}

		$since_installed = ceil( ( time() - $installed_on ) / 86400 );

		if ( $since_installed >= $start_after_installation_days && $since_installed <= $end_before_installation_days ) {
			return true;
		}

		return false;
	}

	/**
	 * Prepare Tooltip html
	 *
	 * @since 1.4.7
	 *
	 * @param  string  $tooltip_text
	 *
	 * @return string
	 *
	 */
	public static function get_tooltip_html( $tooltip_text = '' ) {

		$tooltip_html = '';
		if ( ! empty( $tooltip_text ) ) {
			$tooltip_html = '<div class="inline-block kc-us-tooltip relative align-middle cursor-pointer ml-1 mb-1">
				<svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
				<span class="break-words invisible rounded-lg h-auto lg:w-48 xl:w-64 tracking-wide absolute z-70 kc-us-tooltip-text bg-black text-gray-300 text-xs rounded p-3 py-2">
					' . $tooltip_text . '
					<svg class="absolute mt-2 text-black text-opacity-100 h-2.5 left-0" x="0px" y="0px" viewBox="0 0 255 255" xml:space="preserve">
						<polygon class="fill-current" points="0,0 127.5,127.5 255,0"/>
					</svg>
				</span>
			</div>';
		}

		return $tooltip_html;
	}

	/**
	 * Can tools submenu menu visible.
	 *
	 * @since 1.5.9
	 * @return bool
	 *
	 */
	public static function can_show_tools_menu() {
		return true;
	}

	/**
	 * Get links json filename
	 *
	 * @since 1.5.1
	 * @return string
	 *
	 */
	public static function get_links_json_filename() {
		$hash = Option::get( 'plugin_secret' );

		return 'links-' . $hash . '.json';
	}

	/**
	 * Is links json file exists
	 *
	 * @since 1.5.1
	 * @return bool
	 *
	 */
	public static function is_links_json_file_exists() {
		$links_json_file = self::get_links_json_filename();

		return file_exists( KC_US_UPLOADS_DIR . $links_json_file );
	}

	/**
	 * Get all links from Json
	 *
	 * @since 1.5.1
	 * @return mixed
	 *
	 */
	public static function get_links_from_json() {
		$links_json_file = self::get_links_json_filename();

		return json_decode( file_get_contents( KC_US_UPLOADS_DIR . $links_json_file ), true );
	}

	/**
	 * Get link data based on request uri
	 *
	 * @since 1.5.1
	 *
	 * @param  string  $request_uri
	 *
	 * @return array|bool|data|object|string|null
	 *
	 */
	public static function get_link_data( $request_uri = '' ) {

		$request_uri = trim( $request_uri, '/' );

		/*
		 * TODO: Implement file structure to get the link data from the JSON file.
		 * TODO: Why? It may improve the performance. Will reduce one database query.
		 * TODO: Need to test this hypothesis.
		 *
		if ( self::is_links_json_file_exists() ) {
			$links_data = self::get_links_from_json();

			if ( isset( $links_data[ $request_uri ] ) ) {
				return self::get_data( $links_data, $request_uri, array() );
			}
		}
		*/

		// Even if JSON file exists but if the short URL is not there, check in the database.
		return US()->db->links->get_by_slug( $request_uri );
	}

	/**
	 * Get link data if is short link
	 *
	 * @since 1.5.0
	 *
	 * @param  bool  $check_domain
	 *
	 * @param      $url
	 *
	 * @return array|bool
	 *
	 */
	public static function is_us_link( $url, $check_domain = true ) {

		$blog_url = Helper::get_blog_url();

		if ( ! $check_domain || preg_match( '#^' . preg_quote( $blog_url ) . '#', $url ) ) {

			$uri = preg_replace( '#' . preg_quote( $blog_url ) . '#', '', $url );

			// Resolve WP installs in sub-directories
			preg_match( '#^(https?://.*?)(/.*)$#', $blog_url, $sub_directory );

			$struct = Utils::get_permalink_pre_slug_regex();

			$subdir_str = ( isset( $sub_directory[2] ) ? $sub_directory[2] : '' );

			$match_str = '#^' . $subdir_str . '(' . $struct . ')([^\?]*?)([\?].*?)?$#';

			if ( preg_match( $match_str, $uri, $match_val ) ) {
				// Match longest slug -- this is the most common
				$params = ( isset( $match_val[3] ) ? $match_val[3] : '' );

				if ( $link = self::get_link_data( $match_val[2] ) ) {
					return $link;
				}

				// Trim down the matched link
				$matched_link = preg_replace( '#/[^/]*?$#', '', $match_val[2], 1 );

				for ( $i = 0; ( $i < 25 ) && ! empty( $matched_link ) && ( $matched_link != $match_val[2] ); $i ++ ) {

					$new_match_str = "#^{$subdir_str}({$struct})({$matched_link})(.*?)?$#";

					$params = ( isset( $match_val[3] ) ? $match_val : '' );

					if ( $link = self::get_link_data( $match_val[2] ) ) {
						return $link;
					}

					// Trim down the matched link and try again
					$matched_link = preg_replace( '#/[^/]*$#', '', $match_val[2], 1 );
				}
			}
		}

		return false;
	}

	/**
	 * Regenerate JSON links
	 *
	 * @since 1.5.1
	 */
	public static function regenerate_json_links() {
		$links = US()->db->links->get_all();

		$links_data = [];
		if ( self::is_forechable( $links ) ) {

			foreach ( $links as $link ) {
				$links_data[ $link['slug'] ] = [
					'id'                => $link['id'],
					'slug'              => $link['slug'],
					'url'               => $link['url'],
					'nofollow'          => $link['nofollow'],
					'track_me'          => $link['track_me'],
					'sponsored'         => $link['sponsored'],
					'params_forwarding' => $link['params_forwarding'],
					'params_structure'  => $link['params_structure'],
					'redirect_type'     => $link['redirect_type'],
					'status'            => $link['status'],
					'type'              => $link['type'],
					'password'          => $link['password'],
					'expires_at'        => $link['expires_at'],
					'rules'             => maybe_unserialize( $link['rules'] ),
				];
			}

		}

		$links_json_file = self::get_links_json_filename();

		return file_put_contents( KC_US_UPLOADS_DIR . "/" . $links_json_file, json_encode( $links_data ) );
	}

	/**
	 * Get upgrade banner.
	 *
	 * @since 1.5.15
	 * @return void
	 *
	 */
	public static function get_upgrade_banner( $query_strings = [], $show_coupon = false, $data = [] ) {
		$message        = Helper::get_data( $data, 'message', '' );
		$title          = Helper::get_data( $data, 'title', 'Upgrade Now.' );
		$coupon_message = Helper::get_data( $data, 'coupon_message', '' );
		$pricing_url    = Helper::get_data( $data, 'pricing_url', US()->get_landing_page_url() );
		$dismiss_url    = Helper::get_data( $data, 'dismiss_url', US()->get_landing_page_url() );
		$show_upgrade   = Helper::get_data( $data, 'show_upgrade', true );

		if ( $query_strings ) {
			$pricing_url = add_query_arg( $query_strings, $pricing_url );
			$dismiss_url = add_query_arg( $query_strings, $dismiss_url );
		}

		?>

        <div class="rounded-md bg-green-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                              clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-green-800"><?php echo $title; ?></h3>
                    <div class="mt-2 text-sm">
                        <span class="text-base">
                                 <?php echo $message; ?>

	                        <?php if ( $show_coupon ) { ?>
                                <br/>
		                        <?php echo $coupon_message;
	                        } ?>
                        </span>
                    </div>
                    <div class="mt-4">
                        <div class="-mx-2 -my-1.5 flex">
							<?php if ( $show_upgrade ) { ?>
                                <button type="button"
                                        class="rounded-md border-2 border-green-800 bg-green-50 px-2 py-1.5 text-sm font-medium text-green-800 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 focus:ring-offset-green-50">
                                    <a href="<?php echo esc_url( $pricing_url ); ?>"
                                       class="text-green-800 hover:text-green-800">Upgrade
                                        Now</a></button>
							<?php } ?>
                            <button type="button"
                                    class="ml-3 rounded-md px-2 py-1.5 text-sm font-medium text-red-800 focus:outline-none focus:ring-2">
                                <a href="<?php echo esc_url( $dismiss_url ); ?>" class="text-red-500">Dismiss</a>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
		<?php
	}

	/**
	 * Is promotion dismissed?
	 *
	 * @since 1.5.15
	 *
	 * @param $promotion
	 *
	 * @return bool
	 *
	 */
	public static function is_promotion_dismissed( $promotion ) {
		if ( empty( $promotion ) ) {
			return false;
		}

		$promotion_dismissed_option = 'kc_us_' . trim( $promotion ) . '_dismissed';

		return 'yes' === get_option( $promotion_dismissed_option );
	}

	/**
	 * Prepare group dropdown options.
	 *
	 * @since 1.6.1
	 *
	 * @param $default_label
	 *
	 * @param $selected
	 *
	 * @return string
	 *
	 */
	public static function prepare_group_dropdown_options( $selected = '', $default_label = 'Select Group' ) {

		$default_option[0] = __( $default_label, 'url-shortify' );

		$groups = US()->db->groups->get_all_id_name_map();

		$groups = $default_option + $groups;

		$dropdown = '';

		if ( is_string( $selected ) && strpos( $selected, ',' ) > 0 ) {
			$selected = explode( ',', $selected );
		}

		foreach ( $groups as $key => $group ) {

			$dropdown .= '<option value="' . esc_attr( $key ) . '" ';

			if ( is_array( $selected ) ) {
				if ( in_array( $key, $selected ) ) {
					$dropdown .= 'selected = selected';
				}
			} else {
				if ( ! empty( $selected ) && $selected == $key ) {
					$dropdown .= 'selected = selected';
				}
			}

			$dropdown .= '>' . esc_html( $group ) . '</option>';
		}

		return $dropdown;
	}

	/**
	 * Prepare custom dropdown options.
	 *
	 * @since 1.7.5
	 *
	 * @param $default_label
	 *
	 * @param $selected
	 *
	 * @return string
	 *
	 */
	public static function prepare_domains_dropdown_options( $selected = '' ) {

		$domains = Helper::get_domains();

		$dropdown = '';

		if ( is_string( $selected ) && strpos( $selected, ',' ) > 0 ) {
			$selected = explode( ',', $selected );
		}

		foreach ( $domains as $key => $value ) {

			$dropdown .= '<option value="' . esc_attr( $key ) . '" ';

			if ( is_array( $selected ) ) {
				if ( in_array( $key, $selected ) ) {
					$dropdown .= 'selected = selected';
				}
			} else {
				if ( ! empty( $selected ) && $selected == $key ) {
					$dropdown .= 'selected = selected';
				}
			}

			$dropdown .= '>' . esc_html( $value ) . '</option>';
		}

		return $dropdown;
	}

	/**
	 * Allowed HTML Tags esc function.
	 *
	 * @since 1.6.1
	 * @return array
	 *
	 */
	public static function allowed_html_tags_in_esc() {
		$context_allowed_tags = wp_kses_allowed_html( 'post' );
		$custom_allowed_tags  = [
			'div'      => [
				'x-data' => true,
				'x-show' => true,
			],
			'select'   => [
				'class'    => true,
				'name'     => true,
				'id'       => true,
				'style'    => true,
				'title'    => true,
				'role'     => true,
				'data-*'   => true,
				'tab-*'    => true,
				'multiple' => true,
				'aria-*'   => true,
				'disabled' => true,
				'required' => 'required',
			],
			'optgroup' => [
				'label' => true,
			],
			'option'   => [
				'class'    => true,
				'value'    => true,
				'selected' => true,
				'name'     => true,
				'id'       => true,
				'style'    => true,
				'title'    => true,
				'data-*'   => true,
			],
			'input'    => [
				'class'          => true,
				'name'           => true,
				'type'           => true,
				'value'          => true,
				'id'             => true,
				'checked'        => true,
				'disabled'       => true,
				'selected'       => true,
				'style'          => true,
				'required'       => 'required',
				'min'            => true,
				'max'            => true,
				'maxlength'      => true,
				'size'           => true,
				'placeholder'    => true,
				'autocomplete'   => true,
				'autocapitalize' => true,
				'autocorrect'    => true,
				'tabindex'       => true,
				'role'           => true,
				'aria-*'         => true,
				'data-*'         => true,
			],
			'label'    => [
				'class' => true,
				'name'  => true,
				'type'  => true,
				'value' => true,
				'id'    => true,
				'for'   => true,
				'style' => true,
			],
			'form'     => [
				'class'  => true,
				'name'   => true,
				'value'  => true,
				'id'     => true,
				'style'  => true,
				'action' => true,
				'method' => true,
				'data-*' => true,
			],
			'svg'      => [
				'width'    => true,
				'height'   => true,
				'viewbox'  => true,
				'xmlns'    => true,
				'class'    => true,
				'stroke-*' => true,
				'fill'     => true,
				'stroke'   => true,
			],
			'path'     => [
				'd'               => true,
				'fill'            => true,
				'class'           => true,
				'fill-*'          => true,
				'clip-*'          => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'stroke-width'    => true,
				'fill-rule'       => true,
			],

			'main'     => [
				'align'    => true,
				'dir'      => true,
				'lang'     => true,
				'xml:lang' => true,
				'aria-*'   => true,
				'class'    => true,
				'id'       => true,
				'style'    => true,
				'title'    => true,
				'role'     => true,
				'data-*'   => true,
			],
			'textarea' => [
				'autocomplete' => true,
				'required'     => 'required',
				'placeholder'  => true,
			],
			'style'    => [],
			'link'     => [
				'rel'   => true,
				'id'    => true,
				'href'  => true,
				'media' => true,
			],
			'a'        => [
				'x-on:click' => true,
			],
			'polygon'  => [
				'class'  => true,
				'points' => true,
			],
		];

		return array_merge_recursive( $context_allowed_tags, $custom_allowed_tags );
	}

	/**
	 * Get dynamic redirect types.
	 *
	 * @since 1.7.4
	 * @return array
	 *
	 */
	public static function get_dynamic_redirect_types() {
		return [
			'off'           => __( 'Off', 'url-shortify' ),
			'geo'           => __( 'Geo Location', 'url-shortify' ),
			'technology'    => __( 'Technology', 'url-shortify' ),
			'link-rotation' => __( 'Link Rotation', 'url-shortify' ),
		];
	}

	/**
	 * Prepare country dropdown options.
	 *
	 * @since 1.7.4
	 *
	 * @param $any
	 *
	 * @param $selected
	 *
	 * @return void
	 *
	 */
	public static function prepare_country_dropdown_options( $selected = '', $default = true ) {


		if ( $default ) {
			$default_option['any'] = __( 'Any Country', 'url-shortify' );
		}


		$country_map = Utils::get_countries_iso_code_name_map();

		$country_map = $default_option + $country_map;

		$dropdown = '';

		if ( is_string( $selected ) && strpos( $selected, ',' ) > 0 ) {
			$selected = explode( ',', $selected );
		}

		foreach ( $country_map as $iso => $country ) {

			$dropdown .= '<option value="' . esc_attr( $iso ) . '" ';

			if ( is_array( $selected ) ) {
				if ( in_array( $iso, $selected ) ) {
					$dropdown .= 'selected = selected';
				}
			} else {
				if ( ! empty( $selected ) && $selected == $iso ) {
					$dropdown .= 'selected = selected';
				}
			}

			$dropdown .= '>' . esc_html( $country ) . '</option>';
		}

		return $dropdown;
	}

	/**
	 * Prepare device dropdown options.
	 *
	 * @since 1.7.4
	 *
	 * @param $default
	 *
	 * @param $selected
	 *
	 * @return string
	 *
	 */
	public static function prepare_device_dropdown_options( $selected = '', $default = true ) {

		if ( $default ) {
			$default_option['any'] = __( 'Any', 'url-shortify' );
		}

		$device_map = Utils::get_device_map();

		$device_map = $default_option + $device_map;

		$dropdown = '';

		if ( is_string( $selected ) && strpos( $selected, ',' ) > 0 ) {
			$selected = explode( ',', $selected );
		}

		foreach ( $device_map as $key => $value ) {

			$dropdown .= '<option value="' . esc_attr( $key ) . '" ';

			if ( is_array( $selected ) ) {
				if ( in_array( $key, $selected ) ) {
					$dropdown .= 'selected = selected';
				}
			} else {
				if ( ! empty( $selected ) && $selected == $key ) {
					$dropdown .= 'selected = selected';
				}
			}

			$dropdown .= '>' . esc_html( $value ) . '</option>';
		}

		return $dropdown;
	}

	/**
	 * Get weight dropdown options.
	 *
	 * @since 1.9.1
	 *
	 * @param $selected
	 *
	 * @return string
	 */
	public static function prepare_weight_dropdown_options( $selected = '' ) {
		$weight_options = [];
		for ( $i = 0; $i <= 100; $i ++ ) {
			$weight_options[ $i ] = $i . '%';
		}

		$dropdown = '';
		if ( is_string( $selected ) && strpos( $selected, ',' ) > 0 ) {
			$selected = explode( ',', $selected );
		}

		foreach ( $weight_options as $key => $value ) {
			$dropdown .= '<option value="' . esc_attr( $key ) . '" ';

			if ( is_array( $selected ) ) {
				if ( in_array( $key, $selected ) ) {
					$dropdown .= 'selected = selected';
				}
			} else {
				if ( ! empty( $selected ) && $selected == $key ) {
					$dropdown .= 'selected = selected';
				}
			}

			$dropdown .= '>' . esc_html( $value ) . '</option>';
		}

		return $dropdown;
	}

	/**
	 * Get browser dropdown options.
	 *
	 * @since 1.7.4
	 *
	 * @param $default
	 *
	 * @param $selected
	 *
	 * @return string
	 *
	 */
	public static function prepare_browser_dropdown_options( $selected = '', $default = true ) {

		if ( $default ) {
			$default_option['any'] = __( 'Any', 'url-shortify' );
		}

		$map = Utils::get_browser_map();

		$map = $default_option + $map;

		$dropdown = '';

		if ( is_string( $selected ) && strpos( $selected, ',' ) > 0 ) {
			$selected = explode( ',', $selected );
		}

		foreach ( $map as $key => $value ) {

			$dropdown .= '<option value="' . esc_attr( $key ) . '" ';

			if ( is_array( $selected ) ) {
				if ( in_array( $key, $selected ) ) {
					$dropdown .= 'selected = selected';
				}
			} else {
				if ( ! empty( $selected ) && $selected == $key ) {
					$dropdown .= 'selected = selected';
				}
			}

			$dropdown .= '>' . esc_html( $value ) . '</option>';
		}

		return $dropdown;
	}


	/**
	 * Get OS dropdown options.
	 *
	 * @since 1.7.4
	 *
	 * @param $default
	 *
	 * @param $selected
	 *
	 * @return string
	 *
	 */
	public static function prepare_os_dropdown_options( $selected = '', $default = true ) {

		if ( $default ) {
			$default_option['any'] = __( 'Any', 'url-shortify' );
		}

		$map = Utils::get_os_map();

		$map = $default_option + $map;

		$dropdown = '';

		if ( is_string( $selected ) && strpos( $selected, ',' ) > 0 ) {
			$selected = explode( ',', $selected );
		}

		foreach ( $map as $key => $value ) {

			$dropdown .= '<option value="' . esc_attr( $key ) . '" ';

			if ( is_array( $selected ) ) {
				if ( in_array( $key, $selected ) ) {
					$dropdown .= 'selected = selected';
				}
			} else {
				if ( ! empty( $selected ) && $selected == $key ) {
					$dropdown .= 'selected = selected';
				}
			}

			$dropdown .= '>' . esc_html( $value ) . '</option>';
		}

		return $dropdown;
	}

	/**
	 * Auto Generate Short Link based on provided data or default settings.
	 *
	 * @since 1.7.5
	 *
	 * @param $data
	 *
	 * @return string
	 *
	 */
	public static function generate_short_link( $data = [] ) {

		$short_url = '';

		$post_id = Helper::get_data( $data, 'post_id', 0 );
		$slug    = Helper::get_data( $data, 'slug', '' );

		if ( ! empty( $post_id ) ) {
			$link_id = US()->db->links->create_link_from_post( $post_id, $slug );
		} else {
			$url = Helper::get_data( $data, 'url', '' );

			$title = Helper::get_data( $data, 'title', '' );

			if ( empty( $title ) ) {
				$title = Utils::get_title_from_url( $url );
			}

			$link_data = [
				'url'  => $url,
				'name' => $title,
			];

			if ( US()->is_pro() ) {
				$domain = Helper::get_data( $data, 'domain', '' );

				if ( ! empty( $domain ) ) {
					$link_data['rules']['domain'] = $domain;
				}
			}

			$slug = Helper::get_data( $data, 'slug', '' );

			if ( empty( $slug ) ) {
				$slug = Utils::get_valid_slug();
			}

			$slug = Helper::get_slug_with_prefix( $slug );

			$link_id = US()->db->links->create_link( $link_data, $slug );
		}

		if ( $link_id ) {

			$link_data = US()->db->links->get_by_id( $link_id );

			$short_url = Helper::get_short_link( $link_data['slug'], $link_data );
		}

		return $short_url;
	}

	/**
	 * Get tracking pixel types.
	 *
	 * @since 1.8.9
	 * @return array
	 *
	 */
	public static function get_tracking_pixel_types() {
		return [
			'facebook'           => __( 'Facebook', 'url-shortify' ),
			'google-tag-manager' => __( 'Google Tag Manager', 'url-shortify' ),
			'twitter'            => __( 'Twitter', 'url-shortify' ),
			'linkedin'           => __( 'Linkedin', 'url-shortify' ),
			'pinterest'          => __( 'Pinterest', 'url-shortify' ),
			'adwords'            => __( 'Adwords', 'url-shortify' ),
			'bing'               => __( 'Bing', 'url-shortify' ),
			'quora'              => __( 'Quora', 'url-shortify' ),
			'adroll'             => __( 'Adroll', 'url-shortify' ),
			'nexus-segment'      => __( 'Nexus Segment', 'url-shortify' ),
		];
	}

	/**
	 * Prepare tracking pixel types dropdown.
	 *
	 * @since 1.8.9
	 *
	 * @param $default
	 *
	 * @param $selected
	 *
	 * @return string
	 *
	 */
	public static function prepare_traking_pixel_types_dropdown_options( $selected = '', $default = 'facebook' ) {
		$pixel_types = Helper::get_tracking_pixel_types();

		$dropdown = '';

		if ( is_string( $selected ) && strpos( $selected, ',' ) > 0 ) {
			$selected = explode( ',', $selected );
		}

		foreach ( $pixel_types as $key => $value ) {

			$dropdown .= '<option value="' . esc_attr( $key ) . '" ';

			if ( is_array( $selected ) ) {
				if ( in_array( $key, $selected ) ) {
					$dropdown .= 'selected = selected';
				}
			} else {
				if ( ! empty( $selected ) && $selected == $key ) {
					$dropdown .= 'selected = selected';
				}
			}

			$dropdown .= '>' . esc_html( $value ) . '</option>';
		}

		return $dropdown;
	}

	/**
	 * Prepare custom dropdown options for short links selection.
	 *
	 * @since 1.9.1
	 *
	 * @param $selected
	 *
	 * @param $selected
	 *
	 * @return string
	 */
	public static function prepare_short_links_dropdown_options( $selected = '', $default = true ) {

		if ( $default ) {
			$default_option[0] = __( 'Select Link', 'url-shortify' );
		}

		$links = US()->db->links->get_links_for_dropdown();

		$links = $default_option + $links;

		$dropdown = '';

		if ( is_string( $selected ) && strpos( $selected, ',' ) > 0 ) {
			$selected = explode( ',', $selected );
		}

		foreach ( $links as $key => $value ) {

			$dropdown .= '<option value="' . esc_attr( $key ) . '" ';

			if ( is_array( $selected ) ) {
				if ( in_array( $key, $selected ) ) {
					$dropdown .= 'selected = selected';
				}
			} else {
				if ( ! empty( $selected ) && $selected == $key ) {
					$dropdown .= 'selected = selected';
				}
			}

			$dropdown .= '>' . esc_html( $value ) . '</option>';
		}

		return $dropdown;
	}

	/**
	 * Prepare Links Filters Dropdown.
	 *
	 * @since 1.9.3
	 *
	 * @param $default
	 *
	 * @param $selected
	 *
	 * @return string
	 *
	 */
	public static function prepare_links_filters_dropdown_options( $selected = '', $default = true ) {
		$options['all'] = [
			'values' => [
				'' => __( 'All Links', 'url-shortify' ),
			],
		];

		// Prepare Group Dropdown.
		$group_options = [];
		$groups        = US()->db->groups->get_all_id_name_map();
		if ( ! empty( $groups ) ) {
            asort($groups);
			$groups['none'] = __( 'Not in Any Group', 'url-shortify' );
			foreach ( $groups as $key => $value ) {
				$group_options[ 'group_id_' . $key ] = esc_html( $value );
			}

			$options['groups'] = [
				'label'  => __( 'Groups', 'url-shortify' ),
				'values' => $group_options,
			];
		}

		// Prepare Redirect Types Dropdown.
		$redirect_type_options = [];
		$redirect_types        = Helper::get_redirection_types();
		if ( ! empty( $redirect_types ) ) {
			foreach ( $redirect_types as $key => $value ) {
				$redirect_type_options[ 'redirect_type_' . $key ] = esc_html( $value );
			}

			$options['redirect_types'] = [
				'label'  => __( 'Redirect Types', 'url-shortify' ),
				'values' => $redirect_type_options,
			];
		}

		$options = apply_filters( 'kc_us_links_filter_by_dropdown_options', $options );

		$dropdown = '';

		foreach ( $options as $option ) {
			if ( ! empty( $option['label'] ) ) {
				$dropdown .= "<optgroup label='{$option['label']}'>";
			}

			foreach ( $option['values'] as $value => $label ) {
				$dropdown .= "<option value='{$value}' ";

				if ( is_array( $selected ) ) {
					if ( in_array( $value, $selected ) ) {
						$dropdown .= 'selected = selected';
					}
				} else {
					if ( ! empty( $selected ) && $selected == $value ) {
						$dropdown .= 'selected = selected';
					}
				}

				$dropdown .= '>' . esc_html( $label ) . '</option>';
			}

			if ( ! empty( $option['label'] ) ) {
				$dropdown .= "</optgroup>";
			}
		}

		return $dropdown;
	}

	/**
	 * Get Users by roles.
	 *
	 * @since 1.9.5
	 *
	 * @param $roles
	 *
	 * @return array
	 *
	 */
	public static function get_users_by_roles( $roles ) {
		$args = [
			'role__in' => $roles,
			'orderby'  => 'display_name',
			'order'    => 'ASC',
		];

		$user_query = new \WP_User_Query( $args );

		// Get the results.
		$users = $user_query->get_results();

		// Initialize an empty array to store user details.
		$users_array = [];

		// Check for users.
		if ( ! empty( $users ) ) {
			foreach ( $users as $user ) {
				$users_array[ $user->ID ] = [
					'ID'           => $user->ID,
					'display_name' => $user->display_name,
					'user_email'   => $user->user_email,
					'roles'        => $user->roles,
				];
			}
		}

		return $users_array;
	}

	/**
	 * Prepare User dropdown Options.
	 *
	 * @since 1.9.5
	 *
	 * @param $default
	 *
	 * @param $selected
	 *
	 * @return string
	 *
	 */
	public static function prepare_user_dropdown_options( $selected = '', $default = true ) {

		if ( $default ) {
			$default_option[] = __( 'Select User', 'url-shortify' );
		}

		$roles = [ 'Administrator', 'Editor', 'Author' ];

		$users = self::get_users_by_roles( $roles );

		$users_array = [];
		if ( ! empty( $users ) ) {
			foreach ( $users as $user ) {
				$users_array[ $user['ID'] ] = $user['display_name'] . ' <' . $user['user_email'] . '>';
			}
		}

		$users_array = self::array_insert_before( $users_array, 0, $default_option );

		$dropdown = '';

		if ( is_string( $selected ) && strpos( $selected, ',' ) > 0 ) {
			$selected = explode( ',', $selected );
		}

		foreach ( $users_array as $key => $value ) {

			$dropdown .= '<option value="' . esc_attr( $key ) . '" ';

			if ( is_array( $selected ) ) {
				if ( in_array( $key, $selected ) ) {
					$dropdown .= 'selected = selected';
				}
			} else {
				if ( ! empty( $selected ) && $selected == $key ) {
					$dropdown .= 'selected = selected';
				}
			}

			$dropdown .= '>' . esc_html( $value ) . '</option>';
		}

		return $dropdown;
	}

	/**
	 * Get API Permissions.
	 *
	 * @since 1.9.5
	 * @return array
	 *
	 */
	public static function get_api_permissions() {
		return [
			'read'       => __( 'Read', 'url-shortify' ),
			'write'      => __( 'Write', 'url-shortify' ),
			'read_write' => __( 'Read & Write', 'url-shortify' ),
		];
	}

	/**
	 * Prepare API Permissions Dropdown Options.
	 *
	 * @since 1.9.5
	 *
	 * @param $selected
	 *
	 * @return string
	 *
	 */
	public static function prepare_api_permissions_dropdown_options( $selected = '' ) {

		$permissions = self::get_api_permissions();

		$dropdown = '';

		if ( is_string( $selected ) && strpos( $selected, ',' ) > 0 ) {
			$selected = explode( ',', $selected );
		}

		foreach ( $permissions as $key => $value ) {

			$dropdown .= '<option value="' . esc_attr( $key ) . '" ';

			if ( is_array( $selected ) ) {
				if ( in_array( $key, $selected ) ) {
					$dropdown .= 'selected = selected';
				}
			} else {
				if ( ! empty( $selected ) && $selected == $key ) {
					$dropdown .= 'selected = selected';
				}
			}

			$dropdown .= '>' . esc_html( $value ) . '</option>';
		}

		return $dropdown;
	}

	/**
	 * Generate random REST API Hash.
	 *
	 * @param $data
	 *
	 * @return false|string
	 */
	public static function rest_api_hash( $data ) {
		return hash_hmac( 'sha256', $data, 'url-shortify-rest-api' );
	}

	/**
	 * Handle Key Download.
	 *
	 * @since 1.9.5
	 * @return false|void
	 *
	 */
	public static function handle_key_download( $id, $ck ) {
		$info = self::prepare_key_download( $id, $ck );
		if ( ! $info ) {
			return false;
		}

		ob_clean();
		header( 'Content-type: text/plain' );
		header( 'Content-Disposition: attachment; filename="' . $info['file'] );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Translators: %s = Consumer Key.
		printf( __( 'Consumer Key: %s', 'url-shortify' ), $info['ck'] );
		echo "\r\n";
		// Translators: %s = Consumer Secret.
		printf( __( 'Consumer Secret: %s', 'url-shortify' ), $info['cs'] );
		die();
	}

	/**
	 * Prepare Key Download.
	 *
	 * @since 1.9.5
	 * @return array|false
	 *
	 */
	public static function prepare_key_download( $id, $ck ) {
		$key_id       = (int) $id;
		$consumer_key = sanitize_text_field( $ck );

		if ( ! $key_id || ! $consumer_key ) {
			return false;
		}

		$key = US()->db->api_keys->get( $key_id );
		if ( ! $key ) {
			return false;
		}

		// validate the decoded consumer key looks like the stored truncated key.
		$consumer_key = base64_decode( $consumer_key );
		if ( substr( $consumer_key, - 7 ) !== $key['truncated_key'] ) {
			return false;
		}

		return [
			'file' => sanitize_file_name( $key['description'] ) . '.txt',
			'ck'   => $consumer_key,
			'cs'   => $key['consumer_secret'],
		];

	}

	/**
     * Check API Key has specific permissions.
     *
     * @since 1.9.5
     *
	 * @param $api_key
	 * @param $method
	 *
	 * @return bool
	 */
    public static function has_permissions($api_key, $method) {
	    $permissions = Helper::get_data($api_key, 'permissions');

	    switch ( $method ) {
		    case 'HEAD':
		    case 'GET':
			    $ret = ( 'read' === $permissions || 'read_write' === $permissions );
			    break;

		    case 'POST':
		    case 'PUT':
		    case 'PATCH':
		    case 'DELETE':
			    $ret = ( 'write' === $permissions || 'read_write' === $permissions );
			    break;

		    case 'OPTIONS':
			    $ret = true;
			    break;

		    default:
			    $ret = false;
	    }

	    return $ret;
    }

	/**
     * Get display position options.
     *
	 * @return array
     *
     * @since 1.9.5
	 */
	public static function get_link_display_position_options() {
		$cpt_array = [
			'post' => __( 'Posts', 'url-shortify' ),
			'page' => __( 'Pages', 'url-shortify' ),
		];

		$cpt_array = apply_filters( 'kc_us_get_custom_post_types', $cpt_array );

        ksort( $cpt_array );

		$cpt_array['excerpt'] = __( 'Excerpt', 'url-shortify' );

        $options = [];
		foreach ( $cpt_array as $key => $value ) {
			foreach ( [ 'top', 'bottom' ] as $position ) {
				$options[ $position . '_' . $key ] = sprintf(__( 'At the %1$s of <b>%2$s</b>', 'url-shortify' ), $position,
					$value );
			}
		}


        return $options;

	}

	/**
     * Generate Post Short URL.
     *
	 * @param $post
	 * @param $auto_create_short_link
	 *
	 * @return string
     *
     * @since 1.10.0
	 */
	public static function get_post_short_url( $post, $auto_create_short_link = false ) {
		$short_url = '';

		if ( ! $post instanceof \WP_Post ) {
			return $short_url;
		}

		$post_id = $post->ID;
		$short   = US()->db->links->get_by_cpt_id( $post_id );

		if ( ! empty( $short ) ) {
			$short_url = Helper::get_short_link( $short['slug'], $short );
		} elseif ( $auto_create_short_link ) {
			$data = [
				'post_id' => $post_id,
			];

			$short_url = Helper::generate_short_link( $data );
		}

		return $short_url;
	}
}
