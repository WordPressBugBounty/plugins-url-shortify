<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://kaizencoders.com
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/Frontend
 */

namespace KaizenCoders\URL_Shortify;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    Url_Shortify
 * @subpackage Url_Shortify/Frontend
 * @author     KaizenCoders <hello@kaizencoders.com>
 */
class Frontend {
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
	 * @since 1.0.0
	 *
	 * @param Plugin $plugin This plugin's instance.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Loader as all of the hooks are defined in that particular
		 * class.
		 *
		 * The Loader will then create the relationship between the defined
		 * hooks and the functions defined in this class.
		 */

		/*
		 * Registered, not enqueued. These assets are only needed by the public
		 * link shortener form, which most sites never place, so loading them on
		 * every page put a stylesheet, a script and jQuery in front of visitors
		 * for nothing. Whatever renders the form enqueues them by handle.
		 */
		\wp_register_style(
			$this->plugin->get_plugin_name(),
			\plugin_dir_url( dirname( __FILE__ ) ) . 'dist/styles/url-shortify.css',
			[],
			$this->plugin->get_version(),
			'all' );

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Loader as all of the hooks are defined in that particular
		 * class.
		 *
		 * The Loader will then create the relationship between the defined
		 * hooks and the functions defined in this class.
		 */

		/*
		 * Registered, not enqueued - see enqueue_styles(). jQuery stays a real
		 * dependency because the script uses it; registering rather than
		 * enqueueing means jQuery is only pulled in when the form is present.
		 *
		 * Loaded in the footer: the enqueue now happens while the shortcode
		 * renders, which is after wp_head has run.
		 */
		\wp_register_script(
			$this->plugin->get_plugin_name(),
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

}
