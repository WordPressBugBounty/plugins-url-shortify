<?php

namespace KaizenCoders\URL_Shortify\Admin;

class Tools {

	/**
	 * @var string
	 */
	private $plugin_path;

	/**
	 * @var \KaizenCoders\URL_Shortify\Settings
	 */
	private $wpsf;

	/**
	 * WPSFTest constructor.
	 */
	public function __construct() {

		$this->plugin_path = plugin_dir_path( __FILE__ );

		$this->wpsf = new \KaizenCoders\URL_Shortify\Settings(  $this->plugin_path . '/tools-settings.php', 'kc_us_tools' );

		// Add admin menu
		add_action( 'admin_menu', array( $this, 'add_settings_page' ), 20 );

		// Add an optional settings validation filter (recommended)
		add_filter( $this->wpsf->get_option_group() . '_settings_validate', array( &$this, 'validate_settings' ) );

		add_filter( 'wpsf_show_save_changes_button_' . $this->wpsf->get_option_group() , array( &$this, 'remove_button' ) );
	}

	/**
	 * Add settings page.
	 */
	public function add_settings_page() {

		$this->wpsf->add_settings_page( array(
			'parent_slug' => 'url_shortify',
			'page_title'  => __( 'Tools', 'url-shortify' ),
			'menu_title'  => __( 'Tools', 'url-shortify' ),
			'capability'  => 'edit_posts',
		) );
	}

	/**
	 * Validate settings.
	 *
	 * @param $input
	 *
	 * @return mixed
	 */
	public function validate_settings( $input ) {
		// Do your settings validation here
		// Same as $sanitize_callback from http://codex.wordpress.org/Function_Reference/register_setting
		return $input;
	}

	public function remove_button( $show ) {
		return false;
	}
}
