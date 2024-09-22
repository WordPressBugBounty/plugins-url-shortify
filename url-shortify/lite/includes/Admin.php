<?php

/**
 * The dashboard-specific functionality of the plugin.
 *
 * @link       https://kaizencoders.com
 * @since      1.0.0
 *
 * @package    KaizenCoders\URL_Shortify
 * @subpackage Admin
 */

namespace KaizenCoders\URL_Shortify;

use KaizenCoders\URL_Shortify\Admin\Controllers\DashboardController;
use KaizenCoders\URL_Shortify\Admin\Controllers\ToolsController;
use KaizenCoders\URL_Shortify\Admin\Controllers\WidgetsController;
use KaizenCoders\URL_Shortify\Admin\Groups_Table;
use KaizenCoders\URL_Shortify\Admin\Links_Table;

/**
 * The dashboard-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    Url_Shortify
 * @subpackage Url_Shortify/admin
 * @author     KaizenCoders <hello@kaizencoders.com>
 */
class Admin {
	/**
	 * The plugin's instance.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    Plugin $plugin This plugin's instance.
	 */
	private $plugin;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param  Plugin  $plugin  This plugin's instance.
	 *
	 * @since 1.0.0
	 *
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Register the stylesheets for the Dashboard.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Url_Shortify_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Url_Shortify_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		if ( Helper::is_plugin_admin_screen() ) {

			\wp_enqueue_style(
				'url-shortify-main',
				\plugin_dir_url( dirname( __FILE__ ) ) . 'dist/styles/app.css',
				[],
				$this->plugin->get_version(),
				'all' );

			\wp_enqueue_style(
				'jquery-datatables',
				'https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css',
				[],
				$this->plugin->get_version(),
				'all' );

			\wp_enqueue_style(
				'url-shortify-admin',
				\plugin_dir_url( dirname( __FILE__ ) ) . 'dist/styles/url-shortify-admin.css',
				[],
				$this->plugin->get_version(),
				'all' );

			\wp_enqueue_style(
				'us-select2',
				'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css',
				[],
				$this->plugin->get_version(),
				'all' );

			if ( ! wp_style_is( 'jquery-ui-css', 'enqueued' ) ) {
				wp_enqueue_style( 'jquery-ui-css',
					'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
			}

		}

		\wp_enqueue_style(
			'url-shortify',
			\plugin_dir_url( dirname( __FILE__ ) ) . 'dist/styles/url-shortify.css',
			[],
			$this->plugin->get_version(),
			'all' );

		\wp_enqueue_style(
			'url-shortify-fs',
			'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css',
			[],
			$this->plugin->get_version(),
			'all' );

	}

	/**
	 * Register the JavaScript for the dashboard.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Url_Shortify_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Url_Shortify_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		if ( Helper::is_plugin_admin_screen() ) {

			\wp_enqueue_script(
				'alpine-js',
				\plugin_dir_url( dirname( __FILE__ ) ) . 'dist/scripts/alpine.js',
				[],
				$this->plugin->get_version(),
				true );

			\wp_enqueue_script(
				'us-app',
				\plugin_dir_url( dirname( __FILE__ ) ) . 'dist/scripts/app.js',
				[ 'jquery' ],
				$this->plugin->get_version(),
				true );

			\wp_enqueue_script(
				'us-select2',
				'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js',
				[ 'jquery' ],
				$this->plugin->get_version(),
				true );

			\wp_enqueue_script(
				'us-frappe',
				'https://unpkg.com/frappe-charts@1.5.0/dist/frappe-charts.min.iife.js',
				[ 'jquery' ],
				$this->plugin->get_version(),
				true );

			\wp_enqueue_script(
				'url-shortify-admin',
				\plugin_dir_url( dirname( __FILE__ ) ) . 'dist/scripts/url-shortify-admin.js',
				[ 'jquery' ],
				$this->plugin->get_version(),
				true );

			\wp_enqueue_script(
				'jquery-datatables',
				'https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js',
				[ 'jquery' ],
				$this->plugin->get_version(),
				true );

			if ( ! wp_script_is( 'jquery-ui-core', 'enqueued' ) ) {
				wp_enqueue_script( 'jquery-ui-core' );
			}

			if ( ! wp_script_is( 'jquery-ui-datepicker', 'enqueued' ) ) {
				wp_enqueue_script( 'jquery-ui-datepicker' );
			}

			wp_localize_script(
				'url-shortify-admin',
				'usParams',
				[
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
				]
			);

		}

		\wp_enqueue_script(
			'us-clipboard',
			\plugin_dir_url( dirname( __FILE__ ) ) . 'dist/scripts/clipboard.min.js',
			[ 'jquery' ],
			$this->plugin->get_version(),
			true );

		\wp_enqueue_script(
			'url-shortify',
			\plugin_dir_url( dirname( __FILE__ ) ) . 'dist/scripts/url-shortify.js',
			[ 'jquery' ],
			$this->plugin->get_version(),
			true );

		wp_localize_script(
			'url-shortify',
			'usParams',
			[
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			]
		);

	}

	/**
	 * Add admin menu
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu() {

		$permissions = US()->access->get_permissions();

		if ( count( $permissions ) > 0 ) {

			add_menu_page( __( 'URL Shortify', 'url-shortify' ), __( 'URL Shortify', 'url-shortify' ), 'read',
				'url_shortify', [
					$this,
					'render_dashboard',
				], 'dashicons-admin-links', 30 );

			if ( in_array( 'manage_links', $permissions ) || in_array( 'create_links', $permissions ) ) {

				// Dashboard
				add_submenu_page( 'url_shortify', __( 'Dashboard', 'url-shortify' ), __( 'Dashboard', 'url-shortify' ),
					'read', 'url_shortify', [
						$this,
						'render_dashboard',
					] );

				// Links
				$hook = add_submenu_page( 'url_shortify', __( 'Links', 'url-shortify' ), __( 'Links', 'url-shortify' ),
					'read', 'us_links', [
						$this,
						'render_links_page',
					] );
				add_action( "load-$hook", [ '\KaizenCoders\URL_Shortify\Admin\Links_Table', 'screen_options' ] );
			}

			if ( in_array( 'manage_groups', $permissions ) ) {
				$hook = add_submenu_page( 'url_shortify', __( 'Groups', 'url-shortify' ),
					__( 'Groups', 'url-shortify' ), 'read', 'us_groups', [
						$this,
						'render_groups_page',
					] );
			}

			if ( US()->is_pro() && in_array( 'manage_custom_domains', $permissions ) ) {
				$hook = add_submenu_page( 'url_shortify', __( 'Domains', 'url-shortify' ),
					__( 'Domains', 'url-shortify' ), 'read', 'us_domains', [
						$this,
						'render_domains_page',
					] );
			}

			if ( US()->is_pro() && in_array( 'manage_utm_presets', $permissions ) ) {
				$hook = add_submenu_page( 'url_shortify', __( 'UTM Presets', 'url-shortify' ),
					__( 'UTM Presets', 'url-shortify' ), 'read', 'us_utm_presets', [
						$this,
						'render_utm_presets_page',
					] );
			}

			if ( US()->is_pro() && in_array( 'manage_tracking_pixels', $permissions ) ) {
				$hook = add_submenu_page( 'url_shortify', __( 'Tracking Pixels', 'url-shortify' ),
					__( 'Tracking Pixels', 'url-shortify' ), 'read', 'us_tracking_pixels', [
						$this,
						'render_tracking_pixels_page',
					] );
			}

			if ( in_array( 'manage_settings', $permissions ) ) {

				if ( Helper::can_show_tools_menu() ) {
					$hook = add_submenu_page( 'url_shortify', __( 'Tools', 'url-shortify' ),
						__( 'Tools', 'url-shortify' ), 'read', 'us_tools', [
							$this,
							'render_tools_page',
						] );
				}

				new \KaizenCoders\URL_Shortify\Admin\Settings();
			}

			do_action( 'kc_us_admin_menu' );
		}


	}

	/**
	 * Render Links
	 *
	 * @since 1.0.0
	 */
	public function render_links_page() {
		$page = new Links_Table();
		$page->render();
	}

	/**
	 * Render Dashboard
	 *
	 * @since 1.0.0
	 */
	public function render_dashboard() {
		$dashboard = new DashboardController();
		$dashboard->render();
	}

	/**
	 * Render Groups
	 *
	 * @since 1.1.3
	 */
	public function render_groups_page() {
		$page = new Groups_Table();
		$page->render();
	}

	public function render_domains_page() {
		do_action( 'kc_us_render_domains_page' );
	}

	public function render_utm_presets_page() {
		do_action( 'kc_us_render_utm_presets_page' );
	}

	public function render_tracking_pixels_page() {
		do_action( 'kc_us_render_tracking_pixels_page' );
	}

	/**
	 * Render tools page
	 *
	 * @since 1.1.5
	 */
	public function render_tools_page() {
		$tools = new ToolsController();
		$tools->render();
	}


	/******************************************************************* Utilities ********************************/

	/**
	 * Hooked to 'set-screen-options' filter
	 * Save screen options
	 *
	 * @since 1.0.0
	 *
	 * @param $option
	 * @param $value
	 *
	 * @param $status
	 *
	 * @return mixed
	 *
	 */
	public function save_screen_options( $status, $option, $value ) {

		$options = [
			'us_links_per_page',
		];

		if ( in_array( $option, $options ) ) {
			return $value;
		}

		return $status;
	}

	/**
	 * Remove all admin notices
	 *
	 * @since 1.0.0
	 */
	public function remove_admin_notices() {
		global $wp_filter;

		if ( ! Helper::is_plugin_admin_screen() ) {
			return;
		}

		$get_page = Helper::get_request_data( 'page' );

		if ( ! empty( $get_page ) && 'url_shortify' == $get_page ) {
			remove_all_actions( 'admin_notices' );
		} else {

			$allow_display_notices = [
				'show_review_notice',
				'kc_us_fail_php_version_notice',
				'kc_us_show_admin_notice',
				'show_custom_notices',
				'handle_promotions',
				'_admin_notices_hook',
			];

			$filters = [
				'admin_notices',
				'user_admin_notices',
				'all_admin_notices',
			];

			foreach ( $filters as $filter ) {

				if ( ! empty( $wp_filter[ $filter ]->callbacks ) && is_array( $wp_filter[ $filter ]->callbacks ) ) {

					foreach ( $wp_filter[ $filter ]->callbacks as $priority => $callbacks ) {

						foreach ( $callbacks as $name => $details ) {

							if ( is_object( $details['function'] ) && $details['function'] instanceof \Closure ) {
								unset( $wp_filter[ $filter ]->callbacks[ $priority ][ $name ] );
								continue;
							}

							if ( ! empty( $details['function'][0] ) && is_object( $details['function'][0] ) && count( $details['function'] ) == 2 ) {
								$notice_callback_name = $details['function'][1];
								if ( ! in_array( $notice_callback_name, $allow_display_notices ) ) {
									unset( $wp_filter[ $filter ]->callbacks[ $priority ][ $name ] );
								}
							}

							if ( ! empty( $details['function'] ) && is_string( $details['function'] ) ) {
								if ( ! in_array( $details['function'], $allow_display_notices ) ) {
									unset( $wp_filter[ $filter ]->callbacks[ $priority ][ $name ] );
								}
							}
						}
					}
				}

			}
		}

	}


	/**
	 * Update admin footer text
	 *
	 * @since 1.0.0
	 *
	 * @param $footer_text
	 *
	 * @return string
	 *
	 */
	public function update_admin_footer_text( $footer_text ) {

		// Update Footer admin only on URL Shortify pages
		if ( Helper::is_plugin_admin_screen() ) {

			$wordpress_url = 'https://www.wordpress.org';
			$website_url   = 'https://www.kaizencoders.com';

			$url_shortify_plugin_name = ( US()->is_pro() ) ? 'URL Shortify PRO' : 'URL Shortify';

			/* translators: 1: WordPress link, 2: Plugin name, 3: Plugin version, 4: KaizenCoders link */
			$footer_text = sprintf( __( '<span id="footer-thankyou">Thank you for creating with <a href="%1$s" target="_blank">WordPress</a> | %2$s <b>%3$s</b>. Made with ❤️ by the team <a href="%4$s" target="_blank">KaizenCoders</a></span>',
				'url-shortify' ), $wordpress_url, $url_shortify_plugin_name, KC_US_PLUGIN_VERSION, $website_url );
		}

		return $footer_text;
	}

	/**
	 * Redirect after activation
	 *
	 * @since 1.0.0
	 *
	 */
	public function redirect_to_dashboard() {

		// Check if it is multisite and the current user is in the network administrative interface. e.g. `/wp-admin/network/`
		if ( is_multisite() && is_network_admin() ) {
			return;
		}

		if ( get_option( 'url_shortify_do_activation_redirect', false ) ) {
			delete_option( 'url_shortify_do_activation_redirect' );
			wp_redirect( 'admin.php?page=url_shortify' );
		}
	}

	public function kc_us_show_admin_notice() {

		$notice = Cache::get_transient( 'notice' );

		if ( ! empty( $notice ) ) {

			$status = Helper::get_data( $notice, 'status', '' );

			if ( ! empty( $status ) ) {
				$message       = Helper::get_data( $notice, 'message', '' );
				$is_dismisible = Helper::get_data( $notice, 'is_dismisible', true );

				switch ( $status ) {
					case 'success':
						US()->notices->success( $message, $is_dismisible );
						break;
					case 'error':
						US()->notices->error( $message, $is_dismisible );
						break;
					case 'warning':
						US()->notices->warning( $message, $is_dismisible );
						break;
					case 'info':
					default;
						US()->notices->info( $message, $is_dismisible );
						break;

				}

				Cache::delete_transient( 'notice' );
			}
		}
	}

	/**
	 * Fix for wp_redirect
	 *
	 * @since 1.2.0
	 */
	public function app_output_buffer() {
		ob_start();
	}

	/**
	 * Render Dashboard Widget
	 *
	 * @since 1.2.5
	 */
	public function add_dashboard_widgets() {

		if ( US()->access->can( 'manage_links' ) ) {

			$widgets_controller = new WidgetsController();
			$widgets            = [
				[
					'id'       => 'url_shortify_dashboard_widget',
					'title'    => __( 'URL Shortify Quick Add', 'url-shortify' ),
					'callback' => [ $widgets_controller, 'render_dashboard_generate_shortlink_widget' ],
				],
			];

			$widgets = apply_filters( 'kc_us_filter_dashboard_widgets', $widgets );

			if ( Helper::is_forechable( $widgets ) ) {
				foreach ( $widgets as $widget ) {
					$widget_id = Helper::get_data( $widget, 'id', '' );
					$title     = Helper::get_data( $widget, 'title', '' );
					$callback  = Helper::get_data( $widget, 'callback', '' );

					wp_add_dashboard_widget( $widget_id, esc_html( $title ), $callback );
				}
			}

		}

	}

	/**
	 * Update plugin notice
	 *
	 * @since 1.4.2
	 *
	 * @param $response
	 *
	 * @param $data
	 */
	public function in_plugin_update_message( $data, $response ) {

		if ( isset( $data['upgrade_notice'] ) ) {
			printf(
				'<div class="update-message">%s</div>',
				wpautop( $data['upgrade_notice'] )
			);
		}
	}

	/**
	 * Add Plugin Promotion Footer.
	 *
	 * @return void
	 *
	 * @since 1.9.6
	 */
	public function promote_url_shortify() {
		if ( Helper::is_plugin_admin_screen() ) {
			$links = [
				[
					'url'    => US()->is_pro() ? 'https://kaizencoders.com/contact/' : 'https://wordpress.org/support/plugin/url-shortify/',
					'text'   => __( 'Support', 'url-shortify' ),
					'target' => '_blank',
				],
				[
					'url'    => 'https://docs.kaizencoders.com/',
					'text'   => __( 'Docs', 'pretty-link' ),
					'target' => '_blank',
				],
			];

			$title = __( 'Made with ♥ by the team KaizenCoders', 'url-shortify' );

			require_once( KC_US_ADMIN_TEMPLATES_DIR . '/footer-promotion.php' );
		}
	}


}
