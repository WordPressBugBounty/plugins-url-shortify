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
use KaizenCoders\URL_Shortify\Admin\Controllers\ResourcesController;
use KaizenCoders\URL_Shortify\Admin\Controllers\ToolsController;
use KaizenCoders\URL_Shortify\Admin\Controllers\WidgetsController;
use KaizenCoders\URL_Shortify\Admin\Groups_Table;
use KaizenCoders\URL_Shortify\Admin\Tags_Table;
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
	 * Bootstrap the active theme class as early as possible.
	 *
	 * This runs in the <head> so the admin shell can paint in the correct
	 * theme before the main admin bundle finishes loading.
	 *
	 * @since 2.1.1
	 */
	public function print_theme_bootstrap_script() {
		if ( ! Helper::is_plugin_admin_screen() ) {
			return;
		}
		?>
		<script>
			(function () {
				var mode = 'system';
				try {
					mode = localStorage.getItem('kc_us_theme') || 'system';
				} catch (e) {}

				var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
				var isDark = mode === 'dark' || (mode === 'system' && prefersDark);

				if (isDark) {
					document.documentElement.classList.add('kc-us-dark', 'kc-us-dark-active');
				}
			}());
		</script>
		<?php
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
		$version = $this->plugin->get_version();
		if(defined('KC_US_DEV_MODE') && KC_US_DEV_MODE) {
			$version = time();
		}

		if ( Helper::is_plugin_admin_screen() ) {
			\wp_enqueue_script(
				'alpine-js',
				\plugin_dir_url( dirname( __FILE__ ) ) . 'dist/scripts/alpine.js',
				[],
				$version,
				true );

			\wp_enqueue_script(
				'us-app',
				\plugin_dir_url( dirname( __FILE__ ) ) . 'dist/scripts/app.js',
				[ 'jquery' ],
				$version,
				true );

			\wp_enqueue_script(
				'us-select2',
				'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js',
				[ 'jquery' ],
				$version,
				true );

			\wp_enqueue_script(
				'apexcharts',
				\plugin_dir_url( dirname( __FILE__ ) ) . 'dist/vendor/apexcharts.min.js',
				[],
				'3.46.0',
				true );

			\wp_enqueue_script(
				'jquery-datatables',
				'https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js',
				[ 'jquery' ],
				$version,
				true );

			\wp_enqueue_script(
				'url-shortify-admin',
				\plugin_dir_url( dirname( __FILE__ ) ) . 'dist/scripts/url-shortify-admin.js',
				[ 'jquery', 'jquery-datatables', 'apexcharts' ],
				$version,
				true );

			if ( ! wp_script_is( 'jquery-ui-core', 'enqueued' ) ) {
				wp_enqueue_script( 'jquery-ui-core' );
			}

			if ( ! wp_script_is( 'jquery-ui-datepicker', 'enqueued' ) ) {
				wp_enqueue_script( 'jquery-ui-datepicker' );
			}

			$us_params = [
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'security' => wp_create_nonce( KC_US_AJAX_SECURITY ),
				'is_pro'   => US()->is_pro(),
			];

			wp_localize_script(
				'url-shortify-admin',
				'usParams',
				$us_params
			);
			// Email digest JS/CSS — settings page only.
			$screen = get_current_screen();
			if ( $screen && 'url-shortify_page_kc-us-settings' === $screen->id ) {
				wp_enqueue_script(
					'url-shortify-email-digest',
					plugin_dir_url( dirname( __FILE__ ) ) . 'dist/scripts/url-shortify-email-digest.js',
					[ 'jquery' ],
					$version,
					true
				);

				wp_enqueue_style(
					'url-shortify-email-digest',
					plugin_dir_url( dirname( __FILE__ ) ) . 'dist/styles/url-shortify-email-digest.css',
					[],
					$version
				);

				wp_localize_script(
					'url-shortify-email-digest',
					'kcUsEmailDigest',
					[
						'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
						'ajaxNonce'  => wp_create_nonce( 'kc_us_email_digest_test' ),
						'previewUrl' => admin_url( 'admin-post.php?action=kc_us_email_preview&_wpnonce=' . wp_create_nonce( 'kc_us_email_preview' ) ),
					]
				);
			}
		} else {
			wp_localize_script(
				'url-shortify',
				'usParams',
				[
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'security' => wp_create_nonce( KC_US_AJAX_SECURITY ),
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

			/**
			 * Add additional admin menus.
			 *
			 * @since 1.11.5
			 */
			do_action('kc_us_add_admin_menus', $permissions );

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


			if ( Helper::can_show_tools_menu() ) {
				$hook = add_submenu_page( 'url_shortify', __( 'Resources', 'url-shortify' ),
					__( 'Resources', 'url-shortify' ), 'read', 'us_resources', [
						$this,
						'render_resources_page',
					] );
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

	/**
	 * Render tools page
	 *
	 * @since 1.1.5
	 */
	public function render_tools_page() {
		$tools = new ToolsController();
		$tools->render();
	}

	/**
	 * Render resources page.
	 *
	 * @return void
	 *
	 * @since 1.10.7
	 */
	public function render_resources_page() {
		$tools = new ResourcesController();
		$tools->render();
	}

	/**
	 * Render apexcharts for dashboard
	 * 
	 * @since 2.1.1
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( ! Helper::is_plugin_admin_screen() ) {
			return;
		}

		// Only the main dashboard requires the aggregated chart dataset.
		if ( 'url_shortify' !== Helper::get_request_data( 'page' ) ) {
			return;
		}

		$spline_data  = US()->db->clicks->get_spline_chart_data();
		$heatmap_data = US()->db->clicks->get_heatmap_intensity_data();

		$heatmap_map = [];
		foreach ( $heatmap_data as $row ) {
			$date = Helper::get_data( $row, 'date' );
			if ( $date ) {
				$heatmap_map[ $date ] = intval( Helper::get_data( $row, 'count' ) );
			}
		}

		$end_date = ( new \DateTimeImmutable( 'today' ) )->setTime( 0, 0 );
		$start_date = $end_date->sub( new \DateInterval( 'P364D' ) )->modify( 'last monday' );

		$week_starts = [];
		$day_labels = [ __( 'Mon', 'url-shortify' ), __( 'Tue', 'url-shortify' ), __( 'Wed', 'url-shortify' ), __( 'Thu', 'url-shortify' ), __( 'Fri', 'url-shortify' ), __( 'Sat', 'url-shortify' ), __( 'Sun', 'url-shortify' ) ];
		$heatmap_series = array_map( function ( $label ) {
			return [
				'name' => $label,
				'data' => [],
			];
		}, $day_labels );

		$current_week = $start_date;
		for ( $week = 0; $week < 52; $week ++ ) {
			$week_start_label = $current_week->format( 'Y-m-d' );
			$week_starts[] = $week_start_label;
			for ( $day = 0; $day < 7; $day ++ ) {
				$day_date = $current_week->add( new \DateInterval( "P{$day}D" ) );
				$date_key = $day_date->format( 'Y-m-d' );
					$heatmap_series[ $day ]['data'][] = [
						'x'    => $week_start_label,
						'y'    => $heatmap_map[ $date_key ] ?? 0,
						'meta' => $date_key,
					];
			}
			$current_week = $current_week->add( new \DateInterval( 'P1W' ) );
		}

		$heatmap_month_labels = array_fill( 0, count( $week_starts ), '' );
		$current_month = '';
		foreach ( $week_starts as $index => $week_start ) {
			$month = ( new \DateTimeImmutable( $week_start ) )->format( 'M' );
			if ( $month !== $current_month ) {
				$heatmap_month_labels[ $index ] = $month;
				$current_month = $month;
			}
		}

		// Generate dynamic heatmap color ranges based on actual data
		$heatmap_color_ranges = $this->generate_dynamic_heatmap_color_ranges( $heatmap_map );

		// Fill missing dates in spline chart data with 0 values
		$spline_data_filled = $this->fill_missing_dates_in_spline_data( $spline_data );

		$chart_vars = [
			'dates'               => array_column( $spline_data_filled, 'date' ),
			'total_series'        => array_map( 'intval', array_column( $spline_data_filled, 'total_clicks' ) ),
			'unique_series'       => array_map( 'intval', array_column( $spline_data_filled, 'unique_clicks' ) ),
			'heatmap_series'      => $heatmap_series,
			'has_clicks_data'     => ! empty( $heatmap_map ),
			'heatmap_week_starts' => $week_starts,
			'heatmap_day_labels'  => $day_labels,
			'heatmap_month_labels'=> $heatmap_month_labels,
			'heatmap_color_ranges'=> $heatmap_color_ranges,
		];

		wp_localize_script( 'url-shortify-admin', 'us_chart_data', $chart_vars );
	}

	/**
	 * Generate dynamic heatmap color ranges based on quantile distribution.
	 *
	 * Uses quantile-based (frequency-based) binning instead of range-based.
	 * This ensures each color group represents roughly the same number of days,
	 * creating a balanced heatmap regardless of data distribution.
	 *
	 * Similar to GitHub's contribution graph approach.
	 *
	 * @since 1.14.0
	 *
	 * @param array $heatmap_map Array of date => click_count pairs
	 *
	 * @return array Array of color range objects for ApexCharts heatmap
	 */
	private function generate_dynamic_heatmap_color_ranges( $heatmap_map = [] ) {
		// Define color palette from light to dark green
		$colors = [
			'#f0fdf4', // Very light green (quantile 1)
			'#dcffd5', // Light green (quantile 2)
			'#bbf7d0', // Light-medium green (quantile 3)
			'#86efac', // Medium green (quantile 4)
			'#4ade80', // Medium-dark green (quantile 5)
			'#22c55e', // Dark green (quantile 6)
			'#16a34a', // Very dark green (quantile 7)
		];

		// Get all click values from heatmap data
		$values = array_values( $heatmap_map );

		// Handle empty data
		if ( empty( $values ) ) {
			return [
				[
					'from'  => 0,
					'to'    => 0,
					'color' => $colors[0],
					'name'  => '0 clicks',
				],
			];
		}

		// Sort values in ascending order for quantile calculation
		sort( $values );

		// Determine number of quantiles (6-7 groups)
		$num_quantiles = min( 7, max( 2, count( $colors ) ) );

		// Calculate quantile boundaries
		$quantile_boundaries = [];
		$quantile_boundaries[0] = $values[0]; // Min value

		for ( $i = 1; $i < $num_quantiles; $i++ ) {
			$position = ( $i / $num_quantiles ) * ( count( $values ) - 1 );
			$lower_index = (int) floor( $position );
			$upper_index = (int) ceil( $position );
			$fraction = $position - $lower_index;

			// Linear interpolation between values
			if ( $lower_index === $upper_index ) {
				$quantile_value = $values[ $lower_index ];
			} else {
				$quantile_value = $values[ $lower_index ] + ( $values[ $upper_index ] - $values[ $lower_index ] ) * $fraction;
			}

			$quantile_boundaries[ $i ] = (int) ceil( $quantile_value );
		}

		$quantile_boundaries[ $num_quantiles ] = $values[ count( $values ) - 1 ]; // Max value

		// Remove duplicates and keep unique boundaries
		$quantile_boundaries = array_unique( $quantile_boundaries );
		$quantile_boundaries = array_values( $quantile_boundaries ); // Re-index

		// Generate ranges from quantile boundaries
		$ranges = [];
		$num_ranges = count( $quantile_boundaries ) - 1;

		for ( $i = 0; $i < $num_ranges && $i < count( $colors ); $i++ ) {
			$range_from = $quantile_boundaries[ $i ];
			$range_to = $quantile_boundaries[ $i + 1 ];

			$ranges[] = [
				'from'  => (int) $range_from,
				'to'    => (int) $range_to,
				'color' => $colors[ $i ],
				'name'  => $this->format_range_label( $range_from, $range_to ),
			];
		}

		return ! empty( $ranges ) ? $ranges : [
			[
				'from'  => 0,
				'to'    => max( $values ),
				'color' => $colors[0],
				'name'  => '0-' . max( $values ) . ' clicks',
			],
		];
	}

	/**
	 * Format a friendly label for a click range.
	 *
	 * @since 1.14.0
	 *
	 * @param int $from Start of range
	 * @param int $to   End of range
	 *
	 * @return string Formatted range label
	 */
	private function format_range_label( $from, $to ) {
		if ( $from === $to ) {
			return $from . ' clicks';
		}

		if ( $from === 0 && $to === 0 ) {
			return '0 clicks';
		}

		// Format numbers with thousand separator for readability
		return number_format_i18n( $from ) . '-' . number_format_i18n( $to ) . ' clicks';
	}

	/**
	 * Fill missing dates in spline chart data with 0 values.
	 *
	 * This ensures dates with no clicks are still shown in the chart,
	 * creating a cleaner visual for new users with sparse data.
	 *
	 * @since 1.14.0
	 *
	 * @param array $spline_data Array of date => clicks data
	 *
	 * @return array Complete date range with 0 values for missing dates
	 */
	private function fill_missing_dates_in_spline_data( $spline_data = [] ) {
		// Create associative map of existing data
		$data_map = [];
		foreach ( $spline_data as $row ) {
			$date = Helper::get_data( $row, 'date' );
			if ( $date ) {
				$data_map[ $date ] = [
					'total_clicks'  => intval( Helper::get_data( $row, 'total_clicks', 0 ) ),
					'unique_clicks' => intval( Helper::get_data( $row, 'unique_clicks', 0 ) ),
				];
			}
		}

		// Default range to last 1 year if no data
		$start_date = ( new \DateTimeImmutable( 'today' ) )->sub( new \DateInterval( 'P1Y' ) )->setTime( 0, 0 );
		$end_date   = ( new \DateTimeImmutable( 'today' ) )->setTime( 0, 0 );

		if ( ! empty( $data_map ) ) {
			$dates = array_keys( $data_map );
			sort( $dates );
			$first_date = new \DateTimeImmutable( $dates[0] );
			if ( $first_date < $start_date ) {
				$start_date = $first_date;
			}
		}

		$filled_data = [];
		$current_date = $start_date;

		while ( $current_date <= $end_date ) {
			$date_key = $current_date->format( 'Y-m-d' );

			$filled_data[] = [
				'date'           => $date_key,
				'total_clicks'   => $data_map[ $date_key ]['total_clicks'] ?? 0,
				'unique_clicks'  => $data_map[ $date_key ]['unique_clicks'] ?? 0,
			];

			$current_date = $current_date->add( new \DateInterval( 'P1D' ) );
		}

		return $filled_data;
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
