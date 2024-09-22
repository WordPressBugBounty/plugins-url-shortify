<?php

namespace KaizenCoders\URL_Shortify;

use KaizenCoders\URL_Shortify\Common\Utils;

class Install {
	/**
	 * DB updates and callbacks that need to be run per version.
	 *
	 * @since 1.0.0
	 * @var array
	 *
	 */
	private static $db_updates = [
		'1.0.1' => [
			'kc_us_update_101_alter_links_table',
			'kc_us_update_101_create_tables',
		],

		'1.0.4' => [
			'kc_us_update_104_alter_links_table',
		],

		'1.1.3' => [
			'kc_us_update_113_create_tables',
		],

		'1.1.4' => [
			'kc_us_update_114_create_tables',
		],

		'1.2.5' => [
			'kc_us_update_125_alter_links_table',
		],

		'1.2.13' => [
			'kc_us_update_1213_delete_cache',
		],

		'1.2.14' => [
			'kc_us_update_1214_set_default_settings',
		],

		'1.3.0' => [
			'kc_us_update_130_set_default_qr_setting',
		],

		'1.3.8' => [
			'kc_us_update_138_create_tables',
		],

		'1.4.1' => [
			'kc_us_update_141_add_installed_on_option',
		],

		'1.5.1' => [
			'kc_us_update_151_add_plugin_uniqueue_hash',
			'kc_us_update_151_create_files',
			'kc_us_update_151_generate_links_json',
		],

		'1.5.12' => [
			'kc_us_update_1512_generate_create_utm_presets_table',
		],

		'1.6.3' => [
			'kc_us_update_163_set_default_slug_settings',
		],

		'1.7.1' => [
			'kc_us_update_171_set_default_display_settings',
		],

		'1.8.0' => [
			'kc_us_update_180_alter_links_table',
		],

        '1.8.9' => [
			'kc_us_update_189_create_tracking_pixel_table',
		],

		'1.9.1' => [
			'kc_us_update_191_create_clicks_rotations_table',
		],

		'1.9.5' => [
			'kc_us_update_195_create_api_keys_table',
		],

		'1.9.6' => [
			'kc_us_update_196_update_link_display_options',
		],
	];

	/**
	 * Init Install/ Update Process
	 *
	 * @since 1.0.0
	 */
	public function init() {

		if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {

			add_action( 'admin_init', [ __CLASS__, 'check_version' ], 5 );
			add_action( 'admin_init', [ __CLASS__, 'install_actions' ] );
		}
	}

	/**
	 * Install if required
	 *
	 * @since 1.0.0
	 */
	public static function check_version() {

		$current_db_version = Option::get( 'db_version', null );

		// Get latest available DB update version
		$latest_db_version_to_update = self::get_latest_db_version_to_update();

		if ( is_null( $current_db_version ) || version_compare( $current_db_version, $latest_db_version_to_update, '<' ) ) {
			self::install();
		}

	}

	/**
	 * Update
	 *
	 * @since 1.0.0
	 */
	public static function install_actions() {
		if ( ! empty( $_GET['do_update_us'] ) ) {
			check_admin_referer( 'us_db_update', 'us_db_update_nonce' );
			$from_db_version = ! empty( $_GET['from_db_version'] ) ? $_GET['from_db_version'] : '';

			self::delete_update_transient();

			if ( ! empty( $from_db_version ) ) {
				self::update_db_version( $from_db_version );
			}

			self::update( true );

		}

		if ( ! empty( $_GET['force_update_us'] ) ) {
			check_admin_referer( 'us_force_db_update', 'us_force_db_update_nonce' );
			self::update();
			wp_safe_redirect( admin_url( 'admin.php?page=us_dashboard' ) );
			exit;
		}
	}

	/**
	 * Begin Installation
	 *
	 * @since 1.0.0
	 */
	public static function install() {

		if ( ! is_blog_installed() ) {
			return;
		}

		// Check if we are not already running this routine.
		if ( 'yes' === Cache::get_transient( 'installing' ) ) {
			return;
		}

		if ( self::is_new_install() ) {
			// If we made it till here nothing is running yet, lets set the transient now.
			Cache::set_transient( 'installing', 'yes', MINUTE_IN_SECONDS * 10 );

			Helper::maybe_define_constant( 'KC_US_INSTALLING', true );

			// Create Files
			self::create_files();

			// Create Tables
			self::create_tables();
			// Create Default Option
			self::create_options();

			self::update_db_version( KC_US_PLUGIN_VERSION );

			Option::add( 'installed_on', time() );
		}

		self::maybe_update_db_version();

		Cache::delete_transient( 'installing' );

	}

	/**
	 * Delete Update Transient
	 *
	 * @since 1.0.0
	 */
	public static function delete_update_transient() {
		global $wpdb;

		Option::delete( 'update_processed_tasks' );
		Option::delete( 'update_tasks_to_process' );

		$transient_like               = $wpdb->esc_like( '_transient_kc_us_update_' ) . '%';
		$updating_like                = $wpdb->esc_like( '_transient_kc_us_updating' ) . '%';
		$last_sent_queue_like         = '%' . $wpdb->esc_like( '_last_sending_queue_batch_run' ) . '%';
		$running_migration_queue_like = '%' . $wpdb->esc_like( '_running_migration_for_' ) . '%';
		$db_migration_queue_like      = '%' . $wpdb->esc_like( 'kc_us_updater_batch_' ) . '%';

		$query = "DELETE FROM {$wpdb->prefix}options WHERE `option_name` LIKE '{$transient_like}' OR `option_name` LIKE '{$updating_like}' OR `option_name` LIKE '{$last_sent_queue_like}' OR `option_name` LIKE '{$running_migration_queue_like}' OR `option_name` LIKE '{$db_migration_queue_like}'";

		$wpdb->query( $query );

	}

	/**
	 * Is this new Installation?
	 *
	 * @since 1.0.0
	 * @return bool
	 *
	 */
	public static function is_new_install() {
		/**
		 * We are storing us_db_version if it's new installation.
		 *
		 */
		return is_null( Option::get( 'db_version', null ) );
	}

	/**
	 * Get latest db version based on available updates.
	 *
	 * @since 1.0.0
	 * @return mixed
	 *
	 */
	public static function get_latest_db_version_to_update() {

		$updates         = self::get_db_update_callbacks();
		$update_versions = array_keys( $updates );
		usort( $update_versions, 'version_compare' );

		return end( $update_versions );
	}

	/**
	 * Require DB updates?
	 *
	 * @since 1.0.0
	 * @return bool
	 *
	 */
	private static function needs_db_update() {
		$current_db_version = Helper::get_db_version();

		$latest_db_version_to_update = self::get_latest_db_version_to_update();

		return ! is_null( $current_db_version ) && version_compare( $current_db_version, $latest_db_version_to_update, '<' );
	}

	/**
	 * Check whether database update require? If require do update.
	 *
	 * @since 1.0.0
	 */
	private static function maybe_update_db_version() {
		if ( self::needs_db_update() ) {
			if ( apply_filters( 'kc_us_enable_auto_update_db', true ) ) {
				self::update();
			}
		}
	}

	/**
	 * Get all database updates
	 *
	 * @since 1.0.0
	 * @return array
	 *
	 */
	public static function get_db_update_callbacks() {
		return self::$db_updates;
	}

	/**
	 * Do database update.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $force
	 *
	 */
	private static function update( $force = false ) {

		// Check if we are not already running this routine.
		if ( ! $force && 'yes' === Cache::get_transient( 'updating' ) ) {
			return;
		}

		Cache::set_transient( 'updating', 'yes', MINUTE_IN_SECONDS * 5 );

		$current_db_version = Helper::get_db_version();

		$tasks_to_process = Option::get( 'update_tasks_to_process', [] );

		// Get all tasks processed
		$processed_tasks = Option::get( 'update_processed_tasks', [] );

		// Get al tasks to process
		$tasks = self::get_db_update_callbacks();

		if ( count( $tasks ) > 0 ) {

			foreach ( $tasks as $version => $update_callbacks ) {

				if ( version_compare( $current_db_version, $version, '<' ) ) {
					foreach ( $update_callbacks as $update_callback ) {
						if ( ! in_array( $update_callback, $tasks_to_process ) && ! in_array( $update_callback, $processed_tasks ) ) {
							$tasks_to_process[] = $update_callback;
						} else {
						}
					}

					// Update db version on every update run
					self::update_db_version( $version );
				}
			}
		}

		if ( count( $tasks_to_process ) > 0 ) {

			Option::set( 'update_tasks_to_process', $tasks_to_process );

			self::dispatch();

		} else {
			Cache::delete_transient( 'updating' );
		}

	}

	/**
	 * Dispatch database updates.
	 *
	 * @since 1.0.0
	 */
	public static function dispatch() {

		$batch = Option::get( 'update_tasks_to_process', [] );

		if ( count( $batch ) > 0 ) {

			$current_memory_limit = @ini_get( 'memory_limit' );

			// We may require lots of memory
			@ini_set( 'memory_limit', '-1' );

			// It may take long time to process database update.
			// So, increase execution time
			@set_time_limit( 360 );
			@ini_set( 'max_execution_time', 360 );

			foreach ( $batch as $key => $value ) {

				$is_value_exists = true;
				//$task_transient = $value . '_processed';
				$update_processed_tasks = Option::get( 'update_processed_tasks', [] );

				$task = false; // By default it's set to false

				// Check whether the tasks is already processed? If not, process it.
				if ( ! in_array( $value, $update_processed_tasks ) ) {
					$is_value_exists = false;
					$task            = (bool) self::task( $value );
				} else {
					unset( $batch[ $key ] );
				}

				if ( false === $task ) {

					if ( ! $is_value_exists ) {
						$update_processed_tasks[] = $value;
						Option::set( 'update_processed_tasks', $update_processed_tasks );
					}

					unset( $batch[ $key ] );
				}

			}

			Option::set( 'update_tasks_to_process', $batch );

			@ini_set( 'memory_limit', $current_memory_limit );
		}

		//Delete update transient
		Cache::delete_transient( 'updating' );
	}

	/**
	 * Run individual database update.
	 *
	 * @since 1.0.0
	 *
	 * @param $callback
	 *
	 * @return bool|callable
	 *
	 */
	public static function task( $callback ) {

		include_once dirname( __FILE__ ) . '/Upgrade/update-functions.php';

		$result = false;

		if ( is_callable( $callback ) ) {

			$result = (bool) call_user_func( $callback );

			if ( $result ) {
				//$logger->info( sprintf( '%s callback needs to run again', $callback ), self::$logger_context );
			} else {
				//$logger->info( sprintf( '--- Finished Task - %s ', $callback ), self::$logger_context );
			}
		} else {
			//$logger->notice( sprintf( '--- Could not find %s callback', $callback ), self::$logger_context );
		}

		return $result ? $callback : false;
	}

	/**
	 * Update DB Version & DB Update history
	 *
	 * @since 1.0.0
	 *
	 * @param null $version
	 *
	 */
	public static function update_db_version( $version = null ) {

		$latest_db_version_to_update = self::get_latest_db_version_to_update();

		Option::set( 'db_version', is_null( $version ) ? $latest_db_version_to_update : $version );

		if ( ! is_null( $version ) ) {

			$db_update_history_option = 'db_update_history';

			$db_update_history_data = Option::get( $db_update_history_option, [] );

			$db_update_history_data[ $version ] = Helper::get_current_date_time();

			Option::set( $db_update_history_option, $db_update_history_data );
		}
	}

	/**
	 * Create default options while installing
	 *
	 * @since 1.0.0
	 */
	private static function create_options() {

		$options = self::get_options();

		if ( Helper::is_forechable( $options ) ) {
			foreach ( $options as $option => $values ) {
				Option::add( $option, $values['default'], Helper::get_data( $values, 'autoload', false ) );
			}
		}
	}

	/**
	 * Get default options
	 *
	 * @since 1.0.0
	 * @return array
	 *
	 */
	public static function get_options() {

		$html = "<div class='shorten_url'>
   The short URL of the present article is: %short_url%
</div>";

		$css = ".shorten_url { 
	   padding: 10px 10px 10px 10px ; 
	   border: 1px solid #AAAAAA ; 
	   background-color: #EEEEEE ;
}";

		$default_options = [
			'links_default_link_options_redirection_type'           => 307,
			'links_default_link_options_enable_nofollow'            => 1,
			'links_default_link_options_enable_sponsored'           => 0,
			'links_default_link_options_enable_paramter_forwarding' => 0,
			'links_default_link_options_enable_tracking'            => 1,
			'links_default_link_options_enable_qr'                  => 1,
			'links_slug_settings_slug_character_count'              => 4,
			'general_settings_case_sensitive_slug'                  => 0,
			'links_slug_settings_excluded_characters'               => '',
			'links_default_link_options_link_prefix'                => '',
			'links_auto_create_links_for_cpt'                       => [
				'page',
				'product',
				'post',
				'download',
				'event',
				'tribe_events',
				'docs',
				'kbe_knowledgebase',
				'mec-events',
				'kruchprodukte',
			],
			'display_options_html'                                  => $html,
			'display_options_css'                                   => $css,
		];

		return [
			'settings'           => [ 'default' => $default_options, 'autoload' => true ],
			'plugin_secret'      => [ 'default' => Utils::generate_hash(), 'autoload' => true ],
			'bookmarklet_secret' => [ 'default' => Utils::generate_hash(), 'autoload' => true ],
		];

	}

	/**
	 * @since 1.0.0
	 *
	 * @param null $version
	 *
	 */
	public static function create_tables( $version = null ) {

		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		if ( is_null( $version ) ) {
			$schema_fn = 'get_schema';
		} else {
			$v         = str_replace( '.', '', $version );
			$schema_fn = 'get_' . $v . '_schema';
		}

		$wpdb->hide_errors();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( self::$schema_fn( $collate ) );
	}

	/**
	 * @since 1.0.0
	 *
	 * @param string $collate
	 *
	 * @return string
	 *
	 */
	private static function get_schema( $collate = '' ) {

		$tables = self::get_100_schema( $collate );
		$tables .= self::get_101_schema( $collate );
		$tables .= self::get_113_schema( $collate );
		$tables .= self::get_138_schema( $collate );
		$tables .= self::get_1512_schema( $collate );
		$tables .= self::get_189_schema( $collate );
		$tables .= self::get_191_schema( $collate );
		$tables .= self::get_195_schema( $collate );

		return $tables;
	}

	/**
	 * @since 1.0.0
	 *
	 * @param string $collate
	 *
	 * @return string
	 *
	 */
	public static function get_100_schema( $collate = '' ) {

		global $wpdb;

		$tables = "
            CREATE TABLE `{$wpdb->prefix}kc_us_links` (
				`id` int(10) NOT NULL AUTO_INCREMENT,
				`slug` varchar(255) DEFAULT NULL,
				`name` varchar(255) DEFAULT NULL,
				`url` text DEFAULT NULL,
				`description` text DEFAULT NULL,
				`nofollow` tinyint(1) DEFAULT 0,
				`track_me` tinyint(1) DEFAULT 1,
				`sponsored` tinyint(1) DEFAULT 0,
				`params_forwarding` tinyint(1) DEFAULT 0,
				`params_structure` varchar(255) DEFAULT NULL,
				`redirect_type` varchar(255) DEFAULT '307',
				`status` tinyint(1) DEFAULT 1,
				`type` varchar(30) DEFAULT 'direct',
				`type_id` int(11) DEFAULT NULL,
				`password` varchar(255) DEFAULT NULL,
				`expires_at` datetime DEFAULT NULL,
				`total_clicks` int(11) DEFAULT null,	
				`unique_clicks` int(11) DEFAULT null,	
				`cpt_id` int(11) DEFAULT 0,
				`cpt_type` varchar(20) DEFAULT NULL,
				`rules` text DEFAULT NULL,
				`created_at` datetime DEFAULT NULL,
				`created_by_id` int(11) DEFAULT NULL,
				`updated_at` datetime DEFAULT NULL,
				`updated_by_id` int(11) DEFAULT NULL,
                PRIMARY KEY  (id),
                KEY cpt_id (cpt_id),
                KEY type_id (type_id),
				KEY status (status),
				KEY nofollow (nofollow),
				KEY track_me (track_me),
				KEY sponsored (sponsored),
				KEY params_forwarding (params_forwarding),
				KEY redirect_type (redirect_type(191)),
				KEY slug (slug(191)),
				KEY expires_at (expires_at),
				KEY created_at (created_at),
				KEY updated_at (updated_at)
            ) $collate;
        ";

		return $tables;
	}

	/**
	 * Get Clicks table schema
	 *
	 * @since 1.0.1
	 *
	 * @param string $collate
	 *
	 * @return string
	 *
	 */
	public static function get_101_schema( $collate = '' ) {
		global $wpdb;

		$table = "
			CREATE TABLE `{$wpdb->prefix}kc_us_clicks` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`link_id` int(11) DEFAULT NULL,
				`uri` varchar(255) DEFAULT NULL,
				`host` varchar(255) DEFAULT NULL,
				`referer` varchar(255) DEFAULT NULL,
				`is_first_click` tinyint(1) DEFAULT 0,
				`is_robot` tinyint(1) DEFAULT 0,
				`user_agent` text DEFAULT NULL,
				`os` varchar(255) DEFAULT NULL,
				`device` varchar(255) DEFAULT NULL,
				`browser_type` varchar(255) DEFAULT NULL,
				`browser_version` varchar(255) DEFAULT NULL,
				`visitor_id` varchar(25) default NULL,
				`country` varchar(50) DEFAULT NULL,
				`ip` varchar(255) DEFAULT NULL,
				`created_at` DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY link_id (link_id),
				KEY ip (ip(191)),
				KEY browser_type (browser_type(191)),
				KEY browser_version (browser_version(191)),
				KEY os (os(191)),
				KEY device (device(191)),
				KEY country (country(50)),
				KEY referer (referer(191)),
				KEY host (host(191)),
				KEY uri (uri(191)),
				KEY is_robot (is_robot),
				KEY is_first_click (is_first_click),
				KEY visitor_id (visitor_id) 
			) $collate;
        ";

		return $table;

	}

	/**
	 * @since 1.1.3
	 *
	 * @param string $collate
	 *
	 * @return string
	 *
	 */
	public static function get_113_schema( $collate = '' ) {

		global $wpdb;

		return "
            CREATE TABLE `{$wpdb->prefix}kc_us_groups` (
				`id` int(10) NOT NULL AUTO_INCREMENT,
				`name` varchar(255) DEFAULT NULL,
				`description` text DEFAULT NULL,
				`created_by_id` int(11) DEFAULT NULL,
				`created_at` datetime DEFAULT NULL,
				`updated_by_id` int(11) DEFAULT NULL,
				`updated_at` datetime DEFAULT NULL,
                PRIMARY KEY  (id),
				KEY created_at (created_at),
				KEY updated_at (updated_at)
            ) $collate;

			 CREATE TABLE `{$wpdb->prefix}kc_us_links_groups` (
				`id` int(10) NOT NULL AUTO_INCREMENT,
				`link_id` int(10) NOT NULL,
				`group_id` int(10) NOT NULL,
				`created_by_id` int(11) DEFAULT NULL,
				`created_at` datetime DEFAULT NULL,
                PRIMARY KEY  (id),
                KEY link_id (link_id),	
                KEY group_id (group_id),	
                KEY created_by_id (created_by_id),	
				KEY created_at (created_at)
            ) $collate;
        ";

	}

	/**
	 * @since 1.3.8
	 *
	 * @param string $collate
	 *
	 * @return string
	 *
	 */
	public static function get_138_schema( $collate = '' ) {

		global $wpdb;

		return "
            CREATE TABLE `{$wpdb->prefix}kc_us_domains` (
				`id` int(10) NOT NULL AUTO_INCREMENT,
				`host` varchar(255) DEFAULT NULL,
				`status` tinyint(1) DEFAULT 1,
				`created_by_id` int(11) DEFAULT NULL,
				`created_at` datetime DEFAULT NULL,
				`updated_by_id` int(11) DEFAULT NULL,
				`updated_at` datetime DEFAULT NULL,
                PRIMARY KEY  (id),
				KEY created_at (created_at),
				KEY updated_at (updated_at)
            ) $collate;
        ";
	}

	/**
	 * Table Schema.
	 *
	 * @since 1.5.12
	 *
	 * @param string $collate
	 *
	 * @return string
	 *
	 */
	public static function get_1512_schema( $collate = '' ) {
		global $wpdb;

		return "
            CREATE TABLE `{$wpdb->prefix}kc_us_utm_presets` (
				`id` int(10) NOT NULL AUTO_INCREMENT,
				`name` varchar(255) DEFAULT NULL,
				`description` text DEFAULT NULL,
				`utm_id` varchar(255) DEFAULT NULL,
				`utm_source` varchar(255) DEFAULT NULL,
				`utm_medium` varchar(255) DEFAULT NULL,
				`utm_campaign` varchar(255) DEFAULT NULL,
				`utm_term` varchar(255) DEFAULT NULL,
				`utm_content` varchar(255) DEFAULT NULL,
				`created_by_id` int(11) DEFAULT NULL,
				`created_at` datetime DEFAULT NULL,
				`updated_by_id` int(11) DEFAULT NULL,
				`updated_at` datetime DEFAULT NULL,
                PRIMARY KEY  (id),
				KEY created_at (created_at),
				KEY updated_at (updated_at)
            ) $collate;
        ";
	}

    /**
     * Create Tracking Pixel Table.
     *
     * @since 1.8.9
     *
     * @param string $collate
     *
     * @return string
     *
     */
    public static function get_189_schema( $collate = '' ) {
        global $wpdb;

        return "
            CREATE TABLE `{$wpdb->prefix}kc_us_tracking_pixels` (
				`id` int(10) NOT NULL AUTO_INCREMENT,
				`name` varchar(80) DEFAULT NULL,
				`type` varchar(40) DEFAULT NULL,
				`pixel_id` varchar(191) DEFAULT NULL,
				`head_code` text DEFAULT NULL,
				`body_code` text DEFAULT NULL,
				`created_by_id` int(11) DEFAULT NULL,
				`created_at` datetime DEFAULT NULL,
				`updated_by_id` int(11) DEFAULT NULL,
				`updated_at` datetime DEFAULT NULL,
                PRIMARY KEY  (id),
				KEY created_at (created_at),
				KEY updated_at (updated_at)
            ) $collate;
        ";
    }

	/**
	 * Create Clicks Rotations Table.
	 *
	 * @since 1.8.9
	 *
	 * @param string $collate
	 *
	 * @return string
	 *
	 */
	public static function get_191_schema( $collate = '' ) {
		global $wpdb;

		return "
            CREATE TABLE `{$wpdb->prefix}kc_us_clicks_rotations` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`click_id` int(11) NOT NULL,
				`link_id` int(11) NOT NULL,
				`url` text NOT NULL,
                PRIMARY KEY  (id),
                KEY click_id (click_id),
        		KEY link_id (link_id)
            ) $collate;
        ";
	}

	/**
	 * Create Api Keys Table.
	 *
	 * @since 1.9.5
	 *
	 * @param string $collate
	 *
	 * @return string
	 *
	 */
	public static function get_195_schema( $collate = '' ) {
		global $wpdb;

		return "
            CREATE TABLE `{$wpdb->prefix}kc_us_api_keys` (
				  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				  `user_id` bigint(20) unsigned NOT NULL,
				  `description` varchar(200) DEFAULT NULL,
				  `permissions` varchar(10) NOT NULL,
				  `consumer_key` char(64) NOT NULL,
				  `consumer_secret` char(43) NOT NULL,
				  `truncated_key` char(7) NOT NULL,
				  `last_access` datetime DEFAULT NULL,
				  `created_at` datetime DEFAULT NULL,
				  `created_by_id` int(11) DEFAULT NULL,
				  `updated_at` datetime DEFAULT NULL,
				  `updated_by_id` int(11) DEFAULT NULL,
				  PRIMARY KEY (`id`),
				  KEY `consumer_key` (`consumer_key`),
				  KEY `consumer_secret` (`consumer_secret`),
				  KEY created_at (created_at),
				  KEY updated_at (updated_at)
				) $collate;
        ";
	}

	/**
	 * Get files to create
	 *
	 * @since 1.5.1
	 * @return array[]
	 *
	 */
	public static function get_files() {
		// If we need to create more files/ dirs in the future
		// use following code
		// return array_merge(self::get_151_files(), self::get_152_files(), self::get_153_files());

		return self::get_151_files();
	}

	/**
	 * Create logs & uploads dir
	 *
	 * @since 1.5.1
	 * @return array[]
	 *
	 */
	public static function get_151_files() {
		return [
			[
				'base'    => KC_US_LOG_DIR,
				'file'    => '.htaccess',
				'content' => 'deny from all',
			],
			[
				'base'    => KC_US_LOG_DIR,
				'file'    => 'index.html',
				'content' => '',
			],

			[
				'base'    => KC_US_UPLOADS_DIR,
				'file'    => '.htaccess',
				'content' => 'deny from all',
			],

			[
				'base'    => KC_US_UPLOADS_DIR,
				'file'    => 'index.html',
				'content' => '',
			],
		];
	}

	/**
	 * Create files
	 *
	 * @since 1.5.1
	 *
	 * @param array $files
	 *
	 */
	public static function create_files( $files = [] ) {

		// Want to bypass creation of files?
		if ( apply_filters( 'kc_us_install_skip_create_files', false ) ) {
			return;
		}

		if ( empty( $files ) ) {
			$files = self::get_files();
		}

		foreach ( $files as $file ) {
			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				$file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' );
				if ( $file_handle ) {
					fwrite( $file_handle, $file['content'] );
					fclose( $file_handle );
				}
			}
		}
	}

}
