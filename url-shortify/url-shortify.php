<?php
/**
 * URL Shortify
 *
 * URL Shortify helps you beautify, manage, share & cloak any links on or off of your WordPress website. Create links that look how you want using your own domain name!
 *
 * @link      https://wordpress.org/plugins/url-shortify
 * @author    KaizenCoders <hello@kaizencoders.com>
 * @license   GPL-3.0+
 * @package   Url_Shortify
 * @copyright 2024 KaizenCoders
 *
 * @wordpress-plugin
 *
 * Plugin Name:       URL Shortify
 * Plugin URI:        https://kaizencoders.com/url-shortify
 * Description:       URL Shortify helps you beautify, manage, share & cloak any links on or off of your WordPress website. Create links that look how you want using your own domain name!
 * Version:           1.10.3
 * Author:            KaizenCoders
 * Author URI:        https://kaizencoders.com/
 * Tested up to:      6.6.2
 * Requires PHP:      5.6
 * Text Domain:       url-shortify
 * License:           GPL-3.0+
 * License URI:       https://www.gnu.org/licenses
 * Domain Path:       /languages
 *
 * @fs_premium_only /pro/
 * @fs_ignore /vendor/, /lite/dist/styles/app.css, /lite/scripts/app.js
 *
 */

// If this file is called directly, abort.

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( function_exists( 'kc_us_fs' ) ) {
	kc_us_fs()->set_basename( true, __FILE__ );
} else {
	/**
	 * Plugin Version
	 *
	 * @since 1.0.0
	 */
	if ( ! defined( 'KC_US_PLUGIN_VERSION' ) ) {
		define( 'KC_US_PLUGIN_VERSION', '1.10.3' );
	}

	/**
	 * Minimum PHP version required for URL Shortify
	 *
	 * @since 1.0.0
	 *
	 */
	if ( ! defined( 'KC_US_MIN_PHP_VER' ) ) {
		define( 'KC_US_MIN_PHP_VER', '5.6' );
	}

	if ( ! function_exists( 'kc_us_fs' ) ) {
		function kc_us_fs() {
			global $kc_us_fs;

			if ( ! isset( $kc_us_fs ) ) {
				require_once dirname( __FILE__ ) . '/libs/fs/start.php';

				$kc_us_fs = fs_dynamic_init( [
					'id'                  => '6054',
					'slug'                => 'url-shortify',
					'type'                => 'plugin',
					'public_key'          => 'pk_62af18f49c6c943300b43e8b1d027',
					'is_premium'          => false,
					'has_premium_version' => true,
					'has_addons'          => false,
					'has_paid_plans'      => true,
					'has_affiliation'     => 'selected',
					'menu'                => [
						'slug'        => 'url_shortify',
						'first-path'  => 'admin.php?page=url_shortify',
						'account'     => true,
						'contact'     => true,
						'support'     => true,
						'affiliation' => false,
					],

					'is_live' => true,
				] );
			}

			return $kc_us_fs;
		}

		kc_us_fs();

		// Use custom icon for onboarding.
		kc_us_fs()->add_filter( 'plugin_icon', function () {
			return dirname( __FILE__ ) . '/assets/images/url-shortify-fs.png';
		} );

		do_action( 'kc_us_fs_loaded' );
	}

	if ( ! function_exists( 'kc_us_fail_php_version_notice' ) ) {
		/**
		 * URL Shortify admin notice for minimum PHP version.
		 *
		 * Warning when the site doesn't have the minimum required PHP version.
		 *
		 * @return void
		 * @since 1.0.0
		 *
		 */
		function kc_us_fail_php_version_notice() {
			/* translators: %s: PHP version */
			$message      = sprintf( esc_html__( 'URL Shortify requires PHP version %s+, plugin is currently NOT RUNNING.',
				'url-shortify' ), KC_US_MIN_PHP_VER );
			$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
			echo wp_kses_post( $html_message );
		}
	}

	if ( ! version_compare( PHP_VERSION, KC_US_MIN_PHP_VER, '>=' ) ) {
		add_action( 'admin_notices', 'kc_us_fail_php_version_notice' );

		return;
	}

	if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
		require_once dirname( __FILE__ ) . '/vendor/autoload.php';
	}

	// Plugin Folder Path.
	if ( ! defined( 'KC_US_PLUGIN_DIR' ) ) {
		define( 'KC_US_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	}

	// PRO Plugin Folder Path.
	if ( ! defined( 'KC_US_PRO_PLUGIN_DIR' ) ) {
		define( 'KC_US_PRO_PLUGIN_DIR', KC_US_PLUGIN_DIR . '/pro' );
	}

	if ( ! defined( 'KC_US_PLUGIN_ASSETS_DIR' ) ) {
		define( 'KC_US_PLUGIN_ASSETS_DIR', KC_US_PLUGIN_DIR . 'lite/dist/assets' );
	}

	if ( ! defined( 'KC_US_PLUGIN_BASE_NAME' ) ) {
		define( 'KC_US_PLUGIN_BASE_NAME', plugin_basename( __FILE__ ) );
	}

	if ( ! defined( 'KC_US_PLUGIN_FILE' ) ) {
		define( 'KC_US_PLUGIN_FILE', __FILE__ );
	}

	if ( ! defined( 'KC_US_PLUGIN_URL' ) ) {
		define( 'KC_US_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
	}

	if ( ! defined( 'KC_US_PLUGIN_ASSETS_DIR_URL' ) ) {
		define( 'KC_US_PLUGIN_ASSETS_DIR_URL', KC_US_PLUGIN_URL . 'lite/dist/assets' );
	}

	if ( ! defined( 'KC_US_PLUGIN_STYLES_DIR_URL' ) ) {
		define( 'KC_US_PLUGIN_STYLES_DIR_URL', KC_US_PLUGIN_URL . 'lite/dist/styles' );
	}

	$upload_dir = wp_upload_dir( null, false );

	if ( ! defined( 'KC_US_LOG_DIR' ) ) {
		define( 'KC_US_LOG_DIR', $upload_dir['basedir'] . '/kaizencoders_uploads/url-shortify/logs/' );
	}

	if ( ! defined( 'KC_US_UPLOADS_DIR' ) ) {
		define( 'KC_US_UPLOADS_DIR', $upload_dir['basedir'] . '/kaizencoders_uploads/url-shortify/uploads/' );
	}

	/**
	 * The code that runs during plugin activation.
	 * This action is documented in lib/Activator.php
	 */
	\register_activation_hook( __FILE__, '\KaizenCoders\URL_Shortify\Activator::activate' );

	/**
	 * The code that runs during plugin deactivation.
	 * This action is documented in lib/Deactivator.php
	 */
	\register_deactivation_hook( __FILE__, '\KaizenCoders\URL_Shortify\Deactivator::deactivate' );


	if ( ! function_exists( 'US' ) ) {
		/**
		 *
		 * @since 1.0.0
		 */
		function US() {
			return \KaizenCoders\URL_Shortify\Plugin::instance();
		}

	}

	if ( ! function_exists( 'get_the_shorturl' ) ) {
		/**
		 * Get the short url of a current page, page etc.
		 *
		 * @param $auto_create bool
		 *
		 * @return string
		 *
		 * @since 1.10.0
		 *
		 */
		function get_the_shorturl( $auto_create = false ) {
			$short_url = '';

			$post = get_post();

			if ( $post instanceof \WP_Post ) {
				$short_url = \KaizenCoders\URL_Shortify\Helper::get_post_short_url( $post, $auto_create );
			}

			return $short_url;
		}
	}

	add_action( 'plugins_loaded', function () {
		US()->run();
	} );
}
