<?php

namespace KaizenCoders\URL_Shortify\Admin\Controllers;

use KaizenCoders\URL_Shortify\Common\Utils;
use KaizenCoders\URL_Shortify\Helper;

class WidgetsController extends BaseController {
	/**
	 * WidgetsController constructor.
	 *
	 * @since 1.2.5
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Render Shortlink Widget
	 *
	 * @since 1.2.5
	 */
	public function render_dashboard_generate_shortlink_widget() {
		$blog_url         = Helper::get_blog_url();
		$slug             = Utils::get_valid_slug();
		$loading_icon_url = KC_US_PLUGIN_ASSETS_DIR_URL . '/images/loader.gif';

		$default_settings = US()->get_settings();
		$default_domain   = Helper::get_data( $default_settings, 'links_default_link_options_default_custom_domain', 'home' );

		$action = wp_create_nonce( KC_US_AJAX_SECURITY );

		require_once KC_US_ADMIN_TEMPLATES_DIR . '/widgets/dashboard-widget.php';
	}
}
