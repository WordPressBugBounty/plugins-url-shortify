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
use KaizenCoders\URL_Shortify\Common\Utils;

add_filter( 'wpsf_register_settings_kc_us', 'wpsf_tabbed_settings' );
add_filter( 'kc_us_settings_validate', 'kc_us_settings_validate' );

/**
 * Settings
 *
 * @since 1.0.0
 *
 * @param $wpsf_settings
 *
 * @return mixed
 *
 */
function wpsf_tabbed_settings( $wpsf_settings ) {

	// Tabs
	$tabs = [

		[
			'id'    => 'general',
			'title' => __( 'General', 'url-shortify' ),
		],

		[
			'id'    => 'links',
			'title' => __( 'Links', 'url-shortify' ),
			'order' => 2,
		],

		[
			'id'    => 'display',
			'title' => __( 'Display', 'url-shortify' ),
			'order' => 2,
		],

		[
			'id'    => 'reports',
			'title' => __( 'Reports', 'url-shortify' ),
			'order' => 3,
		],

	];

	$wpsf_settings['tabs'] = apply_filters( 'kc_us_filter_settings_tab', $tabs );

	$redirection_types = Helper::get_redirection_types();

	// $link_prefixes = Helper::get_link_prefixes();

	$default_link_options = [
		[
			'id'      => 'redirection_type',
			'title'   => __( 'Redirection', 'url-shortify' ),
			'desc'    => '',
			'type'    => 'select',
			'default' => '307',
			'choices' => $redirection_types,
		],

		[
			'id'      => 'link_prefix',
			'title'   => __( 'Link Prefix', 'url-shortify' ),
			/* translators: %s Home URL */
			'desc'    => sprintf( __( "The prefix that comes before your short link's slug. <br>eg. %s/<strong>go</strong>/short-slug.<br><br><b>Note: </b>Link prefix will be added to all the new short links generated now onwards. It won't add prefix to existing links.",
				'url-shortify' ), home_url() ),
			'type'    => 'text',
			'default' => '',
		],

		[
			'id'      => 'enable_nofollow',
			'title'   => __( 'Nofollow', 'url-shortify' ),
			'desc'    => '',
			'type'    => 'switch',
			'default' => 1,
		],

		[
			'id'      => 'enable_sponsored',
			'title'   => __( 'Sponsored', 'url-shortify' ),
			'desc'    => '',
			'type'    => 'switch',
			'default' => 0,
		],

		[
			'id'      => 'enable_paramter_forwarding',
			'title'   => __( 'Parameter Forwarding', 'url-shortify' ),
			'desc'    => '',
			'type'    => 'switch',
			'default' => 0,
		],

		[
			'id'      => 'enable_tracking',
			'title'   => __( 'Tracking', 'url-shortify' ),
			'desc'    => '',
			'type'    => 'switch',
			'default' => 1,
		],

	];

	$default_link_options = apply_filters( 'kc_us_filter_default_link_options', $default_link_options );

	$general_options = [];

	$general_options = apply_filters( 'kc_us_filter_default_general_options', $general_options );

	$general_options[] = [
		'id'      => 'enable_anonymise_clicks_data',
		'title'   => __( 'Anonymise Clicks Data', 'url-shortify' ),
		'desc'    => __( 'Anonymise all clicks data will anonymise all personal clicks data like IP address, User-Agents, Referrers, Country, OS, Device and more. You are still able to track clicks count',
			'url-shortify' ),
		'type'    => 'radio',
		'choices' => [
			'no'  => __( 'Do Not Anonymise', 'url-shortify' ),
			'ip'  => __( 'Anonymise Only IP Address', 'url-shortify' ),
			'all' => __( 'Anonymise All Clicks Data', 'url-shortify' ),
		],

		'default' => 'no',
		'order'   => 5,
	];

	$general_options[] = [
		'id'      => 'delete_plugin_data',
		'title'   => __( 'Remove Data on Uninstall', 'url-shortify' ),
		'desc'    => __( 'Check this box if you would like to remove all data when plugin is deleted.',
			'url-shortify' ),
		'type'    => 'checkbox',
		'default' => 0,
		'order'   => 20,
	];

	$short_link_position_array = Helper::get_link_display_position_options();

	$default_display_options[] = [
		'id'      => 'where_to_display',
		'title'   => __( 'Where to display short URL?', 'url-shortify' ),
		'type'    => 'checkboxes',
		'default' => [],
		'choices' => $short_link_position_array,
	];

	$html = "*<div class='shorten_url'>
   The short URL of the present article is: %short_url%
</div>";
	$html = Utils::format_html_to_text( $html );

	$default_display_options[] = [
		'id'      => 'html',
		'title'   => __( 'Displayed HTML', 'url-shortify' ),
		/* translators: %1$s Home URL */
		'desc'    => sprintf(
		/* translators: 1: HTML content, 2: %short_url%, 3: %short_url_without_link% */
			__( '<p>%1$s</p> <br /><p>Note that %2$s will be automatically replaced by the shorten URL.</p><br /><p>In addition, %3$s will be replaced by the shorten URL without any html link.</p>',
				'url-shortify' ), $html, "<code>%short_url%</code>", "<code>%short_url_without_link%</code>"
		),
		'type'    => 'textarea',
		'default' => sprintf( __( "<div class='shorten_url'>%s</div>", 'url-shortify' ),
			"The short URL of the present article is: %short_url%" ),
	];

	$css = ".shorten_url { 
	   padding: 10px 10px 10px 10px ; 
	   border: 1px solid #AAAAAA ; 
	   background-color: #EEEEEE ;
}";

	$default_display_options[] = [
		'id'      => 'css',
		'title'   => __( 'CSS', 'url-shortify' ),
		'desc'    => __( 'This CSS will be applied to the above mentioned html. Make sure you use the same class',
			'url-shortify' ),
		'type'    => 'textarea',
		'default' => $css,
	];

	$default_display_options[] = [
		'id'      => 'default_domain',
		'title'   => __( 'Default domain', 'url-shortify' ),
		'desc'    => __( 'When creating a short link and choosing the option <code>All my domains</code> which domain will be used to display the short link?',
			'url-shortify' ),
		'type'    => 'select',
		'default' => '307',
		'order'   => 4,
		'choices' => Helper::get_domains_for_select(),
	];

	$default_display_options[] = [
		'id'      => 'auto_create_short_link',
		'title'   => __( 'Auto create short link?', 'url-shortify' ),
		'desc'    => __( 'If checked, it will automatically create short link and display HTML. If unchecked, it won\'t show above html if short link is not created before.',
			'url-shortify' ),
		'type'    => 'checkbox',
		'default' => 0,
		'order'   => 10,
	];

	$default_display_options = apply_filters( 'kc_us_filter_display_options', $default_display_options );

	$default_reporting_options[] = [
		'id'      => 'how_to_get_ip',
		'title'   => __( 'How does URL Shortify get IPs?', 'url-shortify' ),
		'type'    => 'radio',
		'choices' => [
			''                      => __( 'Let URL Shortify use the most secure method to get visitor IP addresses. Prevents spoofing and works with most sites. <b>(Recommended)</b>',
				'url-shortify' ),
			'REMOTE_ADDR'           => __( 'Use PHP\'s built in REMOTE_ADDR and don\'t use anything else. Very secure if this is compatible with your site.',
				'url-shortify' ),
			'HTTP_X_FORWARDED_FOR'  => __( 'Use the X-Forwarded-For HTTP header. Only use if you have a front-end proxy or spoofing may result.',
				'url-shortify' ),
			'HTTP_X_REAL_IP'        => __( 'Use the X-Real-IP HTTP header. Only use if you have a front-end proxy or spoofing may result.',
				'url-shortify' ),
			'HTTP_CF_CONNECTING_IP' => __( 'Use the Cloudflare "CF-Connecting-IP" HTTP header to get a visitor IP. Only use if you\'re using Cloudflare.',
				'url-shortify' ),
		],

		'default' => '',
		'order'   => 3,
	];

	$reporting_options = apply_filters( 'kc_us_filter_default_reports_options', $default_reporting_options );

	$order = array_column( $reporting_options, 'order' );

	array_multisort( $order, SORT_ASC, $reporting_options );

	$cpt_array = [
		'post' => __( 'Posts', 'url-shortify' ),
		'page' => __( 'Pages', 'url-shortify' ),
	];

	$cpt_array = apply_filters( 'kc_us_filter_auto_create_links_for_options', $cpt_array );

	$sections = [
		[
			'tab_id'        => 'general',
			'section_id'    => 'settings',
			'section_title' => __( 'Settings', 'url-shortify' ),
			'section_order' => 10,
			'fields'        => $general_options,
		],

		[
			'tab_id'        => 'links',
			'section_id'    => 'default_link_options',
			'section_title' => __( 'Default Link Options', 'url-shortify' ),
			'section_order' => 8,
			'fields'        => $default_link_options,
		],

		// CPT Section
		[
			'tab_id'        => 'links',
			'section_id'    => 'auto_create_links_for',
			'section_title' => __( 'Auto Create Links For', 'url-shortify' ),
			'section_order' => 15,
			'fields'        => [
				[
					'id'      => 'cpt',
					'title'   => __( 'Select Custom Post Type(s)', 'url-shortify' ),
					'desc'    => '',
					'type'    => 'checkboxes',
					'default' => [
						'post',
						'page',
					],

					'choices' => $cpt_array,
				],
			],
		],

		[
			'tab_id'        => 'display',
			'section_id'    => 'options',
			'section_title' => __( 'Display Options', 'url-shortify' ),
			'section_order' => 8,
			'fields'        => $default_display_options,
		],

		[
			'tab_id'        => 'reports',
			'section_id'    => 'reporting_options',
			'section_title' => __( 'Reporting Options', 'url-shortify' ),
			'section_order' => 10,
			'fields'        => $reporting_options,
		],

	];

	$sections = apply_filters( 'kc_us_filter_settings_sections', $sections );

	$wpsf_settings['sections'] = $sections;

	return $wpsf_settings;
}

/**
 * Validate/ Sanitize settings.
 *
 * @since 1.7.0
 *
 * @param $settings
 *
 * @return void
 *
 */
function kc_us_settings_validate( $settings ) {

	if ( ! Helper::is_forechable( $settings ) ) {
		return $settings;
	}

	$prefix = Helper::get_data( $settings, 'links_default_link_options_link_prefix', '' );
	if ( ! empty( $prefix ) ) {
		$settings['links_default_link_options_link_prefix'] = sanitize_text_field( $prefix );
	}

	$excluded_characters = Helper::get_data( $settings, 'links_slug_settings_excluded_characters', '' );
	if ( ! empty( $excluded_characters ) ) {
		$settings['links_slug_settings_excluded_characters'] = sanitize_text_field( $excluded_characters );
	}

	$excluded_ip_addresses = Helper::get_data( $settings, 'reports_reporting_options_excluded_ip_addresss', '' );
	if ( ! empty( $excluded_ip_addresses ) ) {
		$settings['reports_reporting_options_excluded_ip_addresss'] = sanitize_text_field( $excluded_ip_addresses );
	}

	return $settings;
}