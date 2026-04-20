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

	$password_page_choices = [
		'0' => __( 'Default password page', 'url-shortify' ),
	];

	$pages = get_pages(
		[
			'post_status' => 'publish',
			'sort_column' => 'post_title',
		]
	);

	if ( ! empty( $pages ) ) {
		foreach ( $pages as $page ) {
			$password_page_choices[ (string) $page->ID ] = $page->post_title;
		}
	}

	$password_page_choices = apply_filters( 'kc_us_password_page_choices', $password_page_choices );

	$default_display_options[] = [
		'id'      => 'password_page',
		'title'   => __( 'Password Page', 'url-shortify' ),
		'desc'    => __( 'Select a page containing the [url-shortify-password] shortcode for password protected links.', 'url-shortify' ),
		'type'    => 'select',
		'default' => '0',
		'choices' => $password_page_choices,
		'order'   => 1,
	];

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
		/* translators: %s: Default short URL template content */
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

	// -------------------------------------------------------------------------
	// Email Digest section fields
	// -------------------------------------------------------------------------
	$all_settings  = US()->get_settings();
	$digest_prefix = 'reports_email_digest_';

	$digest_enabled   = ! empty( $all_settings[ $digest_prefix . 'enabled' ] ) ? 1 : 0;
	$digest_frequency = isset( $all_settings[ $digest_prefix . 'frequency' ] ) ? $all_settings[ $digest_prefix . 'frequency' ] : 'weekly';
	$digest_day       = isset( $all_settings[ $digest_prefix . 'day' ] ) ? (int) $all_settings[ $digest_prefix . 'day' ] : 1;

	// Build time select choices (00:00 – 23:30 in 30-minute steps).
	$time_choices = [];
	for ( $h = 0; $h < 24; $h++ ) {
		foreach ( [ 0, 30 ] as $m ) {
			$key                  = sprintf( '%02d:%02d', $h, $m );
			$time_choices[ $key ] = $key;
		}
	}

	// Day selector HTML.
	ob_start();
	$day_wrapper_style = ( 'daily' === $digest_frequency ) ? ' style="display:none;"' : '';
	if ( 'monthly' === $digest_frequency ) {
		$day_options = [];
		for ( $d = 1; $d <= 31; $d++ ) {
			$suffix = 'th';
			if ( 1 === $d || 21 === $d ) {
				$suffix = 'st';
			} elseif ( 2 === $d || 22 === $d ) {
				$suffix = 'nd';
			} elseif ( 3 === $d || 23 === $d ) {
				$suffix = 'rd';
			}
			$day_options[ $d ] = $d . $suffix;
		}
	} else {
		$day_options = [
			1 => __( 'Monday', 'url-shortify' ),
			2 => __( 'Tuesday', 'url-shortify' ),
			3 => __( 'Wednesday', 'url-shortify' ),
			4 => __( 'Thursday', 'url-shortify' ),
			5 => __( 'Friday', 'url-shortify' ),
			6 => __( 'Saturday', 'url-shortify' ),
			7 => __( 'Sunday', 'url-shortify' ),
		];
	}

	?>
	<div id="kc-us-digest-day-wrapper"<?php echo $day_wrapper_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<select id="kc-us-digest-day" name="kc_us_settings[<?php echo esc_attr( $digest_prefix . 'day' ); ?>]">
			<?php foreach ( $day_options as $val => $label ) : ?>
			<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $digest_day, $val ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>
			<?php endforeach; ?>
		</select>
	</div>
	<?php
	$day_selector_html = ob_get_clean();

	// Status + test HTML.
	ob_start();
	$last_sent  = (int) \KaizenCoders\URL_Shortify\Option::get( 'email_report_last_sent', 0 );
	$wp_tz      = wp_timezone_string();
	$status_str = $digest_enabled
		? '<span style="color:#16a34a;font-weight:600;">&#10003; ' . esc_html__( 'Active', 'url-shortify' ) . '</span>'
		: '<span style="color:#6b7280;">' . esc_html__( 'Inactive', 'url-shortify' ) . '</span>';
	$last_sent_str = $last_sent > 0 ? esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_sent ) ) : esc_html__( 'Never', 'url-shortify' );
	?>
	<div>
		<div class="kc-us-digest-status-grid">
			<div class="kc-us-digest-status-item">
				<span class="kc-us-digest-status-label"><?php esc_html_e( 'Status', 'url-shortify' ); ?></span>
				<span class="kc-us-digest-status-value"><?php echo wp_kses( $status_str, [ 'span' => [ 'style' => [] ] ] ); ?></span>
			</div>
			<div class="kc-us-digest-status-item">
				<span class="kc-us-digest-status-label"><?php esc_html_e( 'Frequency', 'url-shortify' ); ?></span>
				<span class="kc-us-digest-status-value"><?php echo esc_html( ucfirst( $digest_frequency ) ); ?></span>
			</div>
			<div class="kc-us-digest-status-item">
				<span class="kc-us-digest-status-label"><?php esc_html_e( 'Last Sent', 'url-shortify' ); ?></span>
				<span class="kc-us-digest-status-value"><?php echo esc_html( $last_sent_str ); ?></span>
			</div>
			<div class="kc-us-digest-status-item">
				<span class="kc-us-digest-status-label"><?php esc_html_e( 'Timezone', 'url-shortify' ); ?></span>
				<span class="kc-us-digest-status-value"><?php echo esc_html( $wp_tz ); ?></span>
			</div>
		</div>

		<div class="kc-us-digest-preview-section">
			<div class="kc-us-digest-card">
				<strong><?php esc_html_e( 'Preview Email', 'url-shortify' ); ?></strong>
				<p style="margin:6px 0 10px;color:#6b7280;font-size:13px;"><?php esc_html_e( 'Open a preview of the email in a new window using sample data from the last 7 days.', 'url-shortify' ); ?></p>
				<button type="button" id="kc-us-digest-preview-btn" class="button button-secondary">
					<?php esc_html_e( 'Preview Email', 'url-shortify' ); ?>
				</button>
			</div>

			<div class="kc-us-digest-card">
				<strong><?php esc_html_e( 'Send Test Email', 'url-shortify' ); ?></strong>
				<p style="margin:6px 0 10px;color:#6b7280;font-size:13px;"><?php esc_html_e( 'Send a test email to verify formatting and delivery.', 'url-shortify' ); ?></p>
				<div class="kc-us-digest-test-row">
					<input type="email" id="kc-us-digest-test-email" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" class="regular-text">
					<button type="button" id="kc-us-digest-test-btn" class="button button-secondary">
						<?php esc_html_e( 'Send Test', 'url-shortify' ); ?>
					</button>
				</div>
				<span id="kc-us-digest-test-status" style="display:none;margin-top:8px;font-size:13px;"></span>
			</div>
		</div>
	</div>
	<?php
	$status_and_test_html = ob_get_clean();

	$cpt_array = [
		'post' => __( 'Posts', 'url-shortify' ),
		'page' => __( 'Pages', 'url-shortify' ),
	];

	$cpt_array = apply_filters( 'kc_us_get_custom_post_types', $cpt_array );

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

		[
			'tab_id'        => 'reports',
			'section_id'    => 'email_digest',
			'section_title' => __( 'Email Digest', 'url-shortify' ),
			'section_order' => 20,
			'fields'        => [
				[
					'id'      => 'enabled',
					'title'   => __( 'Enable Email Digest', 'url-shortify' ),
					'desc'    => __( 'Automatically send a summary of your link activity via email.', 'url-shortify' ),
					'type'    => 'switch',
					'default' => 0,
				],
				[
					'id'      => 'frequency',
					'title'   => __( 'Frequency', 'url-shortify' ),
					'type'    => 'radio',
					'default' => 'weekly',
					'choices' => [
						'daily'   => __( 'Daily', 'url-shortify' ),
						'weekly'  => __( 'Weekly', 'url-shortify' ),
						'monthly' => __( 'Monthly', 'url-shortify' ),
					],
				],
				[
					'id'      => 'day',
					'title'   => __( 'Day', 'url-shortify' ),
					'desc'    => __( 'Day of the week (weekly) or day of the month (monthly) to send the digest. Not used for daily frequency.', 'url-shortify' ),
					'type'    => 'custom',
					'default' => '',
					'output'  => $day_selector_html,
				],
				[
					'id'      => 'time',
					'title'   => __( 'Send Time', 'url-shortify' ),
					'desc'    => __( 'Time of day to send the digest (site timezone).', 'url-shortify' ),
					'type'    => 'select',
					'default' => '14:00',
					'choices' => $time_choices,
				],
				[
					'id'          => 'recipients',
					'title'       => __( 'Recipients', 'url-shortify' ),
					'desc'        => sprintf( __( 'Enter one email address per line. If left blank, the email digest will be sent to the admin email: <b>%s</b>', 'url-shortify' ), get_option( 'admin_email' ) ),
					'type'        => 'textarea',
					'default'     => '',
					'placeholder' => "user@example.com\nanother@example.com",
				],
				[
					'id'      => 'status',
					'title'   => __( 'Digest Status', 'url-shortify' ),
					'type'    => 'custom',
					'default' => '',
					'output'  => $status_and_test_html,
				],
			],
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
