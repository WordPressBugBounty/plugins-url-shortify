<?php
/**
 * Functions for updating data, used by the background updater.
 */

defined( 'ABSPATH' ) || exit;

use KaizenCoders\URL_Shortify\Cache;
use KaizenCoders\URL_Shortify\Common\Utils;
use KaizenCoders\URL_Shortify\Helper;
use KaizenCoders\URL_Shortify\Install;
use KaizenCoders\URL_Shortify\Option;
use KaizenCoders\URL_Shortify\Cron;

/**************** 1.0.1 *******************/

/**
 * Alter `links` table
 *
 * @since 1.0.1
 */
function kc_us_update_101_alter_links_table() {

	global $wpdb;

	$links_table_exists = $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}kc_us_links'" );
	if ( $links_table_exists > 0 ) {

		$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}kc_us_links" );

		// If column doesn't exists, then insert it
		if ( ! in_array( 'sponsored', $cols ) ) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}kc_us_links ADD COLUMN sponsored tinyint(1) DEFAULT 0 AFTER `track_me` " );
		}

		// Modify params_forwarding Column
		if ( in_array( 'params_forwarding', $cols ) ) {
			$query = "ALTER TABLE {$wpdb->prefix}kc_us_links MODIFY `params_forwarding` tinyint(1) DEFAULT 0";
			$wpdb->query( $query );
		}

		// Modify status Column
		if ( in_array( 'status', $cols ) ) {

			$query = "UPDATE {$wpdb->prefix}kc_us_links set status = 1 WHERE status IS NULL";
			$wpdb->query( $query );

			$query = "ALTER TABLE {$wpdb->prefix}kc_us_links MODIFY `status` tinyint(1) DEFAULT 0";
			$wpdb->query( $query );

		}

	}

}

/**
 * Create Clicks table
 */
function kc_us_update_101_create_tables() {
	Install::create_tables( '1.0.1' );
}

/**************** 1.0.4 *******************/

/**
 * Alter `links` table
 *
 * @since 1.0.4
 */
function kc_us_update_104_alter_links_table() {

	global $wpdb;

	$links_table_exists = $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}kc_us_links'" );
	if ( $links_table_exists > 0 ) {

		$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}kc_us_links" );

		// If column doesn't exists, then insert it
		if ( ! in_array( 'rules', $cols ) ) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}kc_us_links ADD COLUMN `rules` text DEFAULT '' AFTER `cpt_id` " );
		}

		// If column doesn't exists, then insert it
		if ( ! in_array( 'expires_at', $cols ) ) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}kc_us_links ADD COLUMN `expires_at` DATETIME DEFAULT NULL AFTER `status` " );
		}

		if ( ! in_array( 'password', $cols ) ) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}kc_us_links ADD COLUMN `password` varchar(255) DEFAULT NULL AFTER `status` " );
		}

		if ( ! in_array( 'type_id', $cols ) ) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}kc_us_links ADD COLUMN `type_id` int(11) DEFAULT NULL AFTER `status` " );
		}

		// If column doesn't exists, then insert it
		if ( ! in_array( 'type', $cols ) ) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}kc_us_links ADD COLUMN `type` varchar(30) DEFAULT 'direct' AFTER `status` " );
		}

		// If column exists, delete it
		if ( in_array( 'group_id', $cols ) ) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}kc_us_links DROP COLUMN `group_id` " );
		}
	}

}

/**************** 1.1.3 *******************/

/**
 * Create tables (Groups & Links_Groups)
 */
function kc_us_update_113_create_tables() {
	Install::create_tables( '1.1.3' );
}

/**************** 1.1.4 *******************/

/**
 * Create tables (Groups & Links_Groups)
 */
function kc_us_update_114_create_tables() {
	/**
	 * At this stage we want to check authenticity of tables. If any tables are missing
	 * due to any reason, we will create a new table now.
	 */
	Install::create_tables();
}

/**************** 1.2.5 *******************/

function kc_us_update_125_alter_links_table() {

	global $wpdb;

	$links_table_exists = $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}kc_us_links'" );

	if ( $links_table_exists > 0 ) {

		$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}kc_us_links" );

		// If column doesn't exists, then insert it
		if ( ! in_array( 'cpt_type', $cols ) ) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}kc_us_links ADD COLUMN cpt_type varchar(20) DEFAULT NULL AFTER `cpt_id` " );
		}

	}

}

/**************** 1.2.13 *******************/

function kc_us_update_1213_delete_cache() {
	Cache::delete_transient( 'dashboard_stats' );
}

/**************** 1.2.14 *******************/

function kc_us_update_1214_set_default_settings() {

	$default_options = [
		'links_default_link_options_redirection_type'           => 307,
		'links_default_link_options_enable_nofollow'            => 1,
		'links_default_link_options_enable_sponsored'           => 0,
		'links_default_link_options_enable_paramter_forwarding' => 0,
		'links_default_link_options_enable_tracking'            => 1,
		'links_default_link_options_slug_character_count'       => 4,
		'links_auto_create_links_for_cpt'                       => [
			'page',
			'product',
			'post',
			'download',
			'event',
			'tribe_events',
			'docs',
			'kbe_knowledgebase',
			'mec-events',
			'kruchprodukte',
		],
	];

	Option::set( 'settings', $default_options );
}

/**************** 1.3.0 *******************/

function kc_us_update_130_set_default_qr_setting() {

	$settings = Option::get( 'settings' );

	$settings['links_default_link_options_enable_qr'] = 1;

	Option::set( 'settings', $settings );
}

/**************** 1.3.8 *******************/

function kc_us_update_138_create_tables() {
	Install::create_tables( '1.3.8' );
}

/**************** 1.4.1 *******************/

function kc_us_update_141_add_installed_on_option() {
	Option::add( 'installed_on', time() );
}

/**************** 1.5.1 *******************/

function kc_us_update_151_add_plugin_uniqueue_hash() {

	$hash = Utils::generate_hash( 16 );

	Option::add( 'plugin_secret', $hash, true );
}

function kc_us_update_151_create_files() {
	$files = Install::get_151_files();

	Install::create_files( $files );
}

function kc_us_update_151_generate_links_json() {
	Helper::regenerate_json_links();
}

/**************** 1.5.12 *******************/

function kc_us_update_1512_generate_create_utm_presets_table() {
	Install::create_tables( '1.5.12' );
}

/**************** 1.6.3 *******************/

function kc_us_update_163_set_default_slug_settings() {
	$settings = Option::get( 'settings' );

	$settings['links_slug_settings_slug_character_count'] = Helper::get_data( $settings,
		'links_default_link_options_slug_character_count', 4 );
	$settings['links_slug_settings_lower_case']           = 1;
	$settings['links_slug_settings_upper_case']           = 0;
	$settings['links_slug_settings_numeric']              = 1;
	$settings['links_slug_settings_excluded_characters']  = '';

	Option::set( 'settings', $settings );
}

/**************** 1.7.1 *******************/

function kc_us_update_171_set_default_display_settings() {
	$settings = Option::get( 'settings' );

	$html = "<div class='shorten_url'>
   The short URL of the present article is: %short_url%
</div>";


	$css = ".shorten_url { 
	   padding: 10px 10px 10px 10px ; 
	   border: 1px solid #AAAAAA ; 
	   background-color: #EEEEEE ;
}";

	$settings['display_options_html'] = $html;
	$settings['display_options_css']  = $css;

	Option::set( 'settings', $settings );
}

/**************** 1.8.0 *******************/

function kc_us_update_180_alter_links_table() {
	global $wpdb;

	$links_table_exists = $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}kc_us_links'" );

	if ( $links_table_exists > 0 ) {

		$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}kc_us_links" );

		// If column doesn't exist, insert it
		if ( ! in_array( 'total_clicks', $cols ) ) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}kc_us_links ADD COLUMN total_clicks int(11) DEFAULT NULL AFTER `expires_at` " );
		}

		if ( ! in_array( 'unique_clicks', $cols ) ) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}kc_us_links ADD COLUMN unique_clicks int(11) DEFAULT NULL AFTER `expires_at` " );
		}

	}
}

/**************** 1.8.9 *******************/

function kc_us_update_189_create_tracking_pixel_table() {
	Install::create_tables( '1.8.9' );
}

/**************** 1.9.1 *******************/

function kc_us_update_191_create_clicks_rotations_table() {
	Install::create_tables( '1.9.1' );
}

/**************** 1.9.5 *******************/

function kc_us_update_195_create_api_keys_table() {
	Install::create_tables( '1.9.5' );
}

/**************** 1.9.6 *******************/
function kc_us_update_196_update_link_display_options() {
	$settings = Option::get( 'settings' );

	$where_to_display = Helper::get_data( $settings, 'display_options_where_to_display', [] );

	if ( ! empty( $where_to_display ) ) {
		$mapping = [
			'top_posts'    => 'top_post',
			'top_pages'    => 'top_page',
			'bottom_posts' => 'bottom_post',
			'bottom_pages' => 'bottom_page',
		];
		foreach ( $where_to_display as $key => $where ) {
			if ( isset( $mapping[ $where ] ) ) {
				$where_to_display[ $key ] = $mapping[ $where ];
			}
		}

		$settings['display_options_where_to_display'] = $where_to_display;

		Option::set( 'settings', $settings );
	}
}