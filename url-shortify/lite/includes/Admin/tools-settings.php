<?php
/**
 * WordPress Settings Framework
 *
 * @author  Gilbert Pellegrom, James Kemp
 * @link    https://github.com/gilbitron/WordPress-Settings-Framework
 * @license MIT
 */

/**
 * Define your settings
 *
 * The first parameter of this filter should be wpsf_register_settings_[options_group],
 * in this case "my_example_settings".
 *
 * Your "options_group" is the second param you use when running new WordPressSettingsFramework()
 * from your init function. It's important as it differentiates your options from others.
 *
 * To use the tabbed example, simply change the second param in the filter below to 'wpsf_tabbed_settings'
 * and check out the tabbed settings function on line 156.
 */

use KaizenCoders\URL_Shortify\Helper;

add_filter( 'wpsf_register_settings_kc_us_tools', 'kc_us_tools_tabbed_settings' );
//add_filter( 'wpsf_register_settings_kc_us', 'wpsf_tabless_settings' );


/**
 * Tabbed example
 */

function kc_us_tools_tabbed_settings( $wpsf_settings ) {
	// Tabs
	$tabs = array(

		array(
			'id'    => 'import',
			'title' => __( 'Import', 'url-shortify' ),
		),


	);

	$wpsf_settings['tabs'] = $tabs;

	$step = 1;

	$field = array(
			'id'      => 'file',
			'title'   => 'File',
			'desc'    => __('Import CSV File', 'url-shortify'),
			'type'    => 'custom',
			'output' => kc_us_import()
		);

	$sections = array(

		array(
			'tab_id'        => 'import',
			'section_id'    => 'import_short_links',
			'section_title' => __('Import Short Links', 'url-shortify'),
			'section_order' => 10,
			'fields'        => array(
					$field
			)
		),

	);

	$wpsf_settings['sections'] = $sections;

	return $wpsf_settings;
}

function kc_us_import() {
	include_once KC_US_ADMIN_TEMPLATES_DIR . '/import.php';
}