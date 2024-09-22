<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://kaizencoders.com
 * @since      1.0.0
 *
 * @package    Url_Shortify
 * @subpackage Url_Shortify/includes
 */

namespace KaizenCoders\URL_Shortify;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Url_Shortify
 * @subpackage Url_Shortify/includes
 * @author     KaizenCoders <hello@kaizencoders.com>
 */
class Deactivator {
	/**
	 * @since 1.0.0
	 *
	 * @param $network_wide
	 *
	 */
	public static function deactivate( $network_wide ) {

		if ( is_multisite() && $network_wide ) {

			global $wpdb;

			$blog_ids = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM $wpdb->blogs WHERE deleted = %d", 0 ) );
			foreach ( $blog_ids as $blog_id ) {
				self::deactivate_on_blog( $blog_id );
			}

		} else {
			self::do_deactivation();
		}

	}

	/**
	 * @since 1.0.0
	 *
	 * @param $blog_id
	 *
	 */
	public static function deactivate_on_blog( $blog_id ) {
		switch_to_blog( $blog_id );
		self::do_deactivation();
		restore_current_blog();
	}

	/**
	 * Handle Deactivation
	 *
	 * @return bool
	 */
	public static function do_deactivation() {
		return true;
	}

}
