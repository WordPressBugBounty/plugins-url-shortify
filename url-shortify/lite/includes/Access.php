<?php

/**
 * The file that defines the permission
 *
 * @link       https://kaizencoders.com
 * @since      1.3.10
 *
 * @package    KaizenCoders\URL_Shortify
 * @subpackage Access
 */

namespace KaizenCoders\URL_Shortify;

/**
 * The Permission class.
 *
 * This is used to define internationalization, dashboard-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Url_Shortify
 * @subpackage Url_Shortify/includes
 * @author     Your Name <hello@kaizencoders.com>
 */
class Access {
	/**
	 * @since 1.3.10
	 * @var \WP_User|null
	 *
	 */
	public $user = null;

	/**
	 * @since 1.3.10
	 * @var array|string[]|null
	 *
	 */
	public $permissions = null;

	/**
	 * Access constructor.
	 *
	 * @since 1.3.10
	 *
	 * @param string $user
	 *
	 */
	public function __construct() {

	}

	/**
	 * Get current user ID.
	 *
	 * @since 1.6.1
	 * @return int
	 *
	 */
	public function get_current_user_id() {
		$user = \wp_get_current_user();

		return $user->ID;
	}

	/**
	 * Get all permissions
	 *
	 * @since 1.3.10
	 * @return array|string[]
	 *
	 */
	public function get_permissions( $user = '' ) {

		$permissions = [];

		if ( ! $user instanceof \WP_User ) {
			$user = \wp_get_current_user();
		}

		if ( ! $user->exists() ) {
			return $permissions;
		}

		// Is user administrator? User has access to all submenus
		if ( US()->is_administrator( $user ) ) {
			return [
				'create_links',
				'manage_links',
				'manage_groups',
				'manage_settings',
				'manage_custom_domains',
				'manage_utm_presets',
				'manage_tracking_pixels'
			];
		}

		$permissions = apply_filters( 'kc_us_user_permissions', $permissions, $user );

		return array_unique( $permissions );
	}

	/**
	 * Can user?
	 *
	 * @since 1.3.10
	 *
	 * @param string $permission
	 *
	 * @return bool
	 *
	 */
	public function can( $permission = '' ) {
		return in_array( $permission, $this->get_permissions() );
	}

}
