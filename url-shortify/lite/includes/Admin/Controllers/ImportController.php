<?php

namespace KaizenCoders\URL_Shortify\Admin\Controllers;

use KaizenCoders\URL_Shortify\Admin\Controllers\Importers\Eps301RedirectImporter;
use KaizenCoders\URL_Shortify\Admin\Controllers\Importers\LinkCentralImporter;
use KaizenCoders\URL_Shortify\Admin\Controllers\Importers\MtsShortLinksImporter;
use KaizenCoders\URL_Shortify\Admin\Controllers\Importers\PrettyLinksImporter;
use KaizenCoders\URL_Shortify\Admin\Controllers\Importers\RedirectionImporter;
use KaizenCoders\URL_Shortify\Admin\Controllers\Importers\ShortenUrlImporter;
use KaizenCoders\URL_Shortify\Admin\Controllers\Importers\Simple301RedirectImporter;
use KaizenCoders\URL_Shortify\Admin\Controllers\Importers\ThirstyAffiliatesImporter;
use KaizenCoders\URL_Shortify\Common\Utils;
use KaizenCoders\URL_Shortify\Helper;
use KaizenCoders\URL_Shortify\Option;

class ImportController extends BaseController {

	/**
	 * Map of import-source action keys to their concrete importer classes.
	 *
	 * Adding a new one-click import source means adding an entry here and
	 * creating a class that extends Importers\BaseImporter.
	 *
	 * @var array<string,string>
	 */
	private $importers = [
		'pretty_links'         => PrettyLinksImporter::class,
		'mts_links'            => MtsShortLinksImporter::class,
		'eps_301_redirects'    => Eps301RedirectImporter::class,
		'simple_301_redirects' => Simple301RedirectImporter::class,
		'thirsty_affiliates'   => ThirstyAffiliatesImporter::class,
		'shorten_url'          => ShortenUrlImporter::class,
		'redirection'          => RedirectionImporter::class,
		'link_central'         => LinkCentralImporter::class,
	];

	/**
	 * ImportController constructor.
	 *
	 * @since 1.3.4
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Dispatch a one-click or CSV import.
	 *
	 * @since 1.4.8
	 *
	 * @param string $action Import source key.
	 *
	 * @return bool
	 */
	public function import_links( $action = '' ) {
		if ( empty( $action ) ) {
			return false;
		}

		if ( 'csv' === $action ) {
			return $this->import_csv();
		}

		if ( ! isset( $this->importers[ $action ] ) ) {
			return false;
		}

		$importer_class = $this->importers[ $action ];
		$importer       = new $importer_class( $this );

		return $importer->run();
	}

	/**
	 * Import Groups
	 *
	 * @since 1.4.4
	 *
	 * @param array $groups
	 *
	 */
	public function import_groups( $groups = [] ) {
		if ( Helper::is_forechable( $groups ) ) {

			$existing_groups = US()->db->groups->get_id_name_map();

			$current_user_id = \get_current_user_id();

			$groups_to_import = [];

			$key = 0;
			foreach ( $groups as $group_name => $links ) {

				if ( ! in_array( $group_name, $existing_groups ) ) {
					$groups_to_import[ $key ]['name']          = $group_name;
					$groups_to_import[ $key ]['created_by_id'] = $current_user_id;

					$key ++;
				}
			}

			if ( Helper::is_forechable( $groups_to_import ) ) {
				US()->db->groups->bulk_insert( $groups_to_import );
			}
		}

	}

	/**
	 * Add links to group
	 *
	 * @since 1.4.4
	 *
	 * @param array $groups
	 *
	 */
	public function add_links_to_group( $groups = [], $replace_for_slugs = [] ) {

		$links_slug_id_map = US()->db->links->get_columns_map( 'slug', 'id' );

		/*
		 * Links whose source row supplied a Groups value get their existing
		 * assignments cleared first, so the file decides which groups the link
		 * is in rather than adding to whatever was there before. Rows that omit
		 * the column pass nothing here and keep their groups.
		 */
		$replace_link_ids = [];

		foreach ( (array) $replace_for_slugs as $slug ) {
			$link_id = absint( Helper::get_data( $links_slug_id_map, $slug, 0 ) );

			if ( $link_id ) {
				$replace_link_ids[ $link_id ] = true;
			}
		}

		if ( ! empty( $replace_link_ids ) ) {
			$replace_ids_str = US()->db->links_groups->prepare_for_in_query( array_keys( $replace_link_ids ) );

			if ( '' !== $replace_ids_str ) {
				US()->db->links_groups->delete_by_condition( "link_id IN ($replace_ids_str)" );
			}
		}

		if ( Helper::is_forechable( $groups ) ) {

			$create_by_id       = \get_current_user_id();
			$groups_name_id_map = US()->db->groups->get_columns_map( 'name', 'id' );

			$data_to_insert = [];

			$key = 0;

			// link_id => true, per group, so the same pair is never queued twice
			// from one file.
			$pairs = [];

			foreach ( $groups as $group => $links ) {

				$group_id = Helper::get_data( $groups_name_id_map, $group, 0 );

				if ( 0 != $group_id ) {

					if ( Helper::is_forechable( $links ) ) {
						foreach ( $links as $slug ) {
							$link_id = Helper::get_data( $links_slug_id_map, $slug, 0 );

							if ( 0 != $link_id && ! isset( $pairs[ $group_id ][ $link_id ] ) ) {
								$pairs[ $group_id ][ $link_id ] = true;

								$data_to_insert[ $key ]['link_id']       = $link_id;
								$data_to_insert[ $key ]['group_id']      = $group_id;
								$data_to_insert[ $key ]['created_by_id'] = $create_by_id;

								$key ++;
							}
						}
					}
				}
			}

			if ( Helper::is_forechable( $data_to_insert ) ) {
				/*
				 * Clear the pairs about to be written first. Importing the same
				 * file twice used to add a second row for every link and group,
				 * so the link then listed the same group several times over.
				 * This mirrors Links_Groups::map_links_and_groups(), which
				 * deletes before inserting for the same reason.
				 */
				foreach ( $pairs as $group_id => $link_ids ) {
					// Links in the replace set were cleared wholesale above.
					$remaining = array_diff_key( $link_ids, $replace_link_ids );

					if ( empty( $remaining ) ) {
						continue;
					}

					$link_ids_str = US()->db->links_groups->prepare_for_in_query( array_keys( $remaining ) );

					if ( '' !== $link_ids_str ) {
						US()->db->links_groups->delete_by_condition( "group_id = " . absint( $group_id ) . " AND link_id IN ($link_ids_str)" );
					}
				}

				US()->db->links_groups->bulk_insert( $data_to_insert );
			}
		}
	}

	/**
	 * Import Tags
	 *
	 * Create missing tags before mapping them to links.
	 *
	 * @since 1.14.0
	 *
	 * @param array $tags
	 */
	public function import_tags( $tags = [] ) {
		if ( ! Helper::is_forechable( $tags ) ) {
			return;
		}

		$existing_tags   = US()->db->tags->get_id_name_map();
		$current_user_id = \get_current_user_id();
		$tags_to_import  = [];
		$key             = 0;

		foreach ( $tags as $tag_name => $links ) {
			if ( ! in_array( $tag_name, $existing_tags, true ) ) {
				$tags_to_import[ $key ]['name']          = $tag_name;
				$tags_to_import[ $key ]['created_by_id'] = $current_user_id;
				$key ++;
			}
		}

		if ( Helper::is_forechable( $tags_to_import ) ) {
			US()->db->tags->bulk_insert( $tags_to_import );
		}
	}

	/**
	 * Add links to tag
	 *
	 * @since 1.14.0
	 *
	 * @param array $tags
	 */
	public function add_links_to_tag( $tags = [], $replace_for_slugs = [] ) {
		$links_slug_id_map = US()->db->links->get_columns_map( 'slug', 'id' );

		// Same rule as groups above: a row that supplies Tags replaces the
		// link's tags outright, a row that omits the column leaves them be.
		$replace_link_ids = [];

		foreach ( (array) $replace_for_slugs as $slug ) {
			$link_id = absint( Helper::get_data( $links_slug_id_map, $slug, 0 ) );

			if ( $link_id ) {
				$replace_link_ids[ $link_id ] = true;
			}
		}

		if ( ! empty( $replace_link_ids ) ) {
			$replace_ids_str = US()->db->links_tags->prepare_for_in_query( array_keys( $replace_link_ids ) );

			if ( '' !== $replace_ids_str ) {
				US()->db->links_tags->delete_by_condition( "link_id IN ($replace_ids_str)" );
			}
		}

		if ( ! Helper::is_forechable( $tags ) ) {
			return;
		}

		$created_by_id    = \get_current_user_id();
		$tags_name_id_map = US()->db->tags->get_columns_map( 'name', 'id' );
		$data_to_insert   = [];
		$key              = 0;

		// link_id => true, per tag, so the same pair is never queued twice from
		// one file.
		$pairs = [];

		foreach ( $tags as $tag => $links ) {
			$tag_id = Helper::get_data( $tags_name_id_map, $tag, 0 );

			if ( 0 != $tag_id && Helper::is_forechable( $links ) ) {
				foreach ( $links as $slug ) {
					$link_id = Helper::get_data( $links_slug_id_map, $slug, 0 );

					if ( 0 != $link_id && ! isset( $pairs[ $tag_id ][ $link_id ] ) ) {
						$pairs[ $tag_id ][ $link_id ] = true;

						$data_to_insert[ $key ]['link_id']       = $link_id;
						$data_to_insert[ $key ]['tag_id']        = $tag_id;
						$data_to_insert[ $key ]['created_by_id'] = $created_by_id;
						$key ++;
					}
				}
			}
		}

		if ( Helper::is_forechable( $data_to_insert ) ) {
			// Same reasoning as the group mapping above: clear these pairs
			// before writing them so a repeat import cannot stack duplicates.
			foreach ( $pairs as $tag_id => $link_ids ) {
				// Links in the replace set were cleared wholesale above.
				$remaining = array_diff_key( $link_ids, $replace_link_ids );

				if ( empty( $remaining ) ) {
					continue;
				}

				$link_ids_str = US()->db->links_tags->prepare_for_in_query( array_keys( $remaining ) );

				if ( '' !== $link_ids_str ) {
					US()->db->links_tags->delete_by_condition( "tag_id = " . absint( $tag_id ) . " AND link_id IN ($link_ids_str)" );
				}
			}

			US()->db->links_tags->bulk_insert( $data_to_insert );
		}
	}

	/**
	 * Parse a CSV field into terms.
	 *
	 * Supports pipe-separated or comma-separated values.
	 *
	 * @since 1.14.0
	 *
	 * @param string $value
	 *
	 * @return array
	 */
	private function parse_csv_terms( $value = '' ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return [];
		}

		$delimiter = false !== strpos( $value, '|' ) ? '|' : ',';
		$terms     = array_map( 'trim', explode( $delimiter, $value ) );

		return array_values( array_filter( $terms, 'strlen' ) );
	}

	/**
	 * Import link from CSV file.
	 *
	 * @since 1.6.0
	 * @return bool|void
	 *
	 */
	public function import_csv() {
		$nonce = Helper::get_request_data( '_wpnonce' );

		if ( ! wp_verify_nonce( $nonce, 'import_csv' ) ) {
			wp_die( esc_html__( 'You do not have permission to import CSV.', 'url-shortify' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to import CSV.', 'url-shortify' ) );
		}

		// Check if a file was uploaded
		if ( ! isset( $_FILES['csv_file'] ) || empty( $_FILES['csv_file']['tmp_name'] ) ) {
			wp_die( esc_html__( 'Please select a CSV file to import.', 'url-shortify' ) );
		}

		// Get the file path and name
		$csv_file_path = $_FILES['csv_file']['tmp_name'];
		$csv_file_name = $_FILES['csv_file']['name'];

		// Validate the file extension
		$file_extension = strtolower( pathinfo( $csv_file_name, PATHINFO_EXTENSION ) );
		if ( $file_extension !== 'csv' ) {
			wp_die( esc_html__( 'Invalid file format. Please upload a CSV file.', 'url-shortify' ) );
		}

		// Validate MIME type
		$file_type = wp_check_filetype( $csv_file_name, array( 'csv' => 'text/csv' ) );
		if ( empty( $file_type['ext'] ) ) {
			wp_die( esc_html__( 'Invalid file type.', 'url-shortify' ) );
		}

		// Import the CSV file
		if ( ! file_exists( $csv_file_path ) ) {
			return false;
		}

		$csv_file = fopen( $csv_file_path, 'r' );

		if ( ! $csv_file ) {
			return false;
		}

		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		// Read the first row of the CSV file as the column names.
		$columns = fgetcsv( $csv_file );

		if ( ! is_array( $columns ) ) {
			fclose( $csv_file );

			wp_die( esc_html__( 'The CSV file appears to be empty.', 'url-shortify' ) );
		}

		$columns = array_map( 'trim', $columns );

		// Strip a UTF-8 BOM off the first heading, or "Slug" never matches and
		// every row looks like it has no slug.
		if ( isset( $columns[0] ) ) {
			$columns[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $columns[0] );
		}

		$required_headings = [ 'Target URL' ];

		if ( count( array_intersect( $columns, $required_headings ) ) != count( $required_headings ) ) {
			fclose( $csv_file );

			wp_die( esc_html__( 'Invalid columns in CSV file. Please make sure Target URL column is available in CSV file.', 'url-shortify' ) );
		}

		$column_count = count( $columns );

		$links   = [];
		$results = [
			'created' => 0,
			'updated' => 0,
			'skipped' => 0,
			'invalid' => 0,
		];

		while ( ( $data = fgetcsv( $csv_file ) ) !== false ) {
			// A completely blank line reads as a single null cell; ignore it
			// rather than counting it as a broken row.
			if ( [ null ] === $data || [ '' ] === $data ) {
				continue;
			}

			// array_combine() throws when the counts differ, so pad or trim the
			// row to the header width instead of letting one ragged line abort
			// the whole import. This is a repair, not a rejection - the row is
			// only counted as unreadable if it then fails validation below.
			if ( count( $data ) !== $column_count ) {
				if ( count( $data ) < $column_count ) {
					$data = array_pad( $data, $column_count, '' );
				} else {
					$data = array_slice( $data, 0, $column_count );
				}
			}

			$links[] = array_combine( $columns, $data );
		}

		fclose( $csv_file );

		if ( ! Helper::is_forechable( $links ) ) {
			return $results;
		}

		$update_existing = 1 === (int) Helper::get_post_data( 'update_existing', 0 );

		$settings = US()->get_settings();

		$default_nofollow          = Helper::get_data( $settings, 'links_default_link_options_enable_nofollow', 1 );
		$default_track_me          = Helper::get_data( $settings, 'links_default_link_options_enable_tracking', 1 );
		$default_sponsored         = Helper::get_data( $settings, 'links_default_link_options_enable_sponsored', 1 );
		$default_params_forwarding = Helper::get_data( $settings, 'links_default_link_options_enable_paramter_forwarding', 1 );
		$default_redirect_type     = Helper::get_data( $settings, 'links_default_link_options_redirection_type', 301 );

		$default_created_at = date( 'Y-m-d H:i:s' );

		$current_user_id = \get_current_user_id();

		// Slug => id, so an existing link can be found and updated in place.
		$existing_links = US()->db->links->get_columns_map( 'slug', 'id' );

		$values = [];

		$key = 0;

		$groups_to_import = [];
		$tags_to_import   = [];

		// Slugs whose row supplied a Groups / Tags value. Those links have their
		// assignments replaced by what the file says; a row that leaves the
		// column out (or blank) keeps whatever it already had.
		$slugs_replacing_groups = [];
		$slugs_replacing_tags   = [];

		foreach ( $links as $link ) {

			$target_url = $this->get_csv_value( $link, 'Target URL' );

			// Without a destination there is nothing to redirect to, so the row
			// is reported rather than silently written as an empty link.
			if ( null === $target_url || ! Utils::validate_url( $target_url, true ) ) {
				$results['invalid'] ++;
				continue;
			}

			$slug = $this->get_csv_value( $link, 'Slug' );
			$slug = ( null !== $slug ) ? Helper::clean( $slug ) : '';

			$groups_cell = $this->get_csv_value( $link, 'Groups' );
			$groups      = ( null !== $groups_cell ) ? $this->parse_csv_terms( Helper::clean( $groups_cell ) ) : [];

			$tags_cell = null;
			$tags      = [];

			if ( US()->is_pro() ) {
				$tags_cell = $this->get_csv_value( $link, 'Tags' );
				$tags      = ( null !== $tags_cell ) ? $this->parse_csv_terms( Helper::clean( $tags_cell ) ) : [];
			}

			if ( empty( $slug ) ) {
				$slug = Utils::generate_random_slug();
				$slug = Helper::get_slug_with_prefix( $slug );
			}

			// Group and tag assignment is keyed by slug, so it applies to
			// updated links as well as newly created ones.
			if ( null !== $groups_cell ) {
				$slugs_replacing_groups[ $slug ] = true;

				foreach ( $groups as $group ) {
					$groups_to_import[ $group ][] = $slug;
				}
			}

			if ( US()->is_pro() && null !== $tags_cell ) {
				$slugs_replacing_tags[ $slug ] = true;

				foreach ( $tags as $tag ) {
					$tags_to_import[ $tag ][] = $slug;
				}
			}

			$existing_id = isset( $existing_links[ $slug ] ) ? absint( $existing_links[ $slug ] ) : 0;

			if ( $existing_id ) {
				if ( ! $update_existing ) {
					$results['skipped'] ++;
					continue;
				}

				$update = $this->build_csv_update( $link, $target_url );

				$update['updated_at']    = $this->get_csv_date( $link, 'Updated At', date( 'Y-m-d H:i:s' ) );
				$update['updated_by_id'] = $current_user_id;

				US()->db->links->update( $existing_id, $update );

				$results['updated'] ++;
				continue;
			}

			$values[ $key ]['slug']              = $slug;
			$values[ $key ]['name']              = $this->get_csv_value( $link, 'Title', $target_url );
			$values[ $key ]['description']       = $this->get_csv_value( $link, 'Description', '' );
			$values[ $key ]['url']               = esc_url_raw( $target_url );
			$values[ $key ]['nofollow']          = $this->get_csv_value( $link, 'Nofollow', $default_nofollow );
			$values[ $key ]['track_me']          = $this->get_csv_value( $link, 'Track', $default_track_me );
			$values[ $key ]['sponsored']         = $this->get_csv_value( $link, 'Sponsored', $default_sponsored );
			$values[ $key ]['params_forwarding'] = $this->get_csv_value( $link, 'Parameter Forwarding', $default_params_forwarding );
			$values[ $key ]['redirect_type']     = $this->get_csv_value( $link, 'Redirect Type', $default_redirect_type );
			$values[ $key ]['status']            = 1;
			$values[ $key ]['type']              = 'direct';
			$values[ $key ]['type_id']           = null;
			$values[ $key ]['password']          = null;
			$values[ $key ]['expires_at']        = null;
			$values[ $key ]['cpt_id']            = null;
			$values[ $key ]['cpt_type']          = '';
			$values[ $key ]['rules']             = null;
			$values[ $key ]['created_at']        = $this->get_csv_date( $link, 'Created At', $default_created_at );
			$values[ $key ]['created_by_id']     = $current_user_id;
			$values[ $key ]['updated_at']        = $this->get_csv_date( $link, 'Updated At', '' );
			$values[ $key ]['updated_by_id']     = $current_user_id;

			// Keep the map current so two rows carrying the same slug do not
			// both insert.
			$existing_links[ $slug ] = 0;

			$key ++;
		}

		// Import Links
		if ( Helper::is_forechable( $values ) ) {
			US()->db->links->bulk_insert( $values );

			$results['created'] = count( $values );
		}

		if ( ! empty( $groups_to_import ) || ! empty( $slugs_replacing_groups ) ) {
			$this->import_groups( $groups_to_import );

			$this->add_links_to_group( $groups_to_import, array_keys( $slugs_replacing_groups ) );
		}

		if ( US()->is_pro() && ( ! empty( $tags_to_import ) || ! empty( $slugs_replacing_tags ) ) ) {
			$this->import_tags( $tags_to_import );

			$this->add_links_to_tag( $tags_to_import, array_keys( $slugs_replacing_tags ) );
		}

		return $results;
	}

	/**
	 * Read one cell from a CSV row.
	 *
	 * A column that is absent and a column that is present but blank both mean
	 * "not supplied", and both fall back to the default. Without this a blank
	 * Redirect Type cell was stored as an empty string, which the links list
	 * then rendered as the literal word "Array" - Helper::get_data() returns
	 * the whole lookup table when it is asked for an empty key.
	 *
	 * @param array  $row
	 * @param string $column
	 * @param mixed  $default
	 *
	 * @return mixed
	 *
	 * @since 2.6.1
	 */
	private function get_csv_value( $row, $column, $default = null ) {
		if ( ! is_array( $row ) || ! array_key_exists( $column, $row ) ) {
			return $default;
		}

		$value = trim( (string) $row[ $column ] );

		return ( '' === $value ) ? $default : $value;
	}

	/**
	 * Read a date cell and normalise it for a DATETIME column.
	 *
	 * The exporter writes dates in the site's display format, so a file that
	 * came straight back out of URL Shortify can carry something like
	 * "March 14, 2026 9:30 am". Writing that into created_at unchanged leaves a
	 * zero date behind, so anything parseable is converted and anything else
	 * falls back rather than corrupting the row.
	 *
	 * @param array  $row
	 * @param string $column
	 * @param mixed  $default
	 *
	 * @return mixed
	 *
	 * @since 2.6.1
	 */
	private function get_csv_date( $row, $column, $default = null ) {
		$value = $this->get_csv_value( $row, $column );

		if ( null === $value ) {
			return $default;
		}

		$timestamp = strtotime( $value );

		return ( false === $timestamp ) ? $default : date( 'Y-m-d H:i:s', $timestamp );
	}

	/**
	 * Build the update payload for a link that already exists.
	 *
	 * Only columns the row actually supplies are included, so importing a file
	 * that carries nothing but Slug and Target URL repoints the link and leaves
	 * its title, description and options exactly as they were.
	 *
	 * @param array  $row
	 * @param string $target_url
	 *
	 * @return array
	 *
	 * @since 2.6.1
	 */
	private function build_csv_update( $row, $target_url ) {
		$update = [ 'url' => esc_url_raw( $target_url ) ];

		$map = [
			'Title'                => 'name',
			'Description'          => 'description',
			'Nofollow'             => 'nofollow',
			'Sponsored'            => 'sponsored',
			'Parameter Forwarding' => 'params_forwarding',
			'Track'                => 'track_me',
			'Redirect Type'        => 'redirect_type',
			'Created At'           => 'created_at',
		];

		foreach ( $map as $column => $field ) {
			$value = ( 'created_at' === $field )
				? $this->get_csv_date( $row, $column )
				: $this->get_csv_value( $row, $column );

			if ( null !== $value ) {
				$update[ $field ] = $value;
			}
		}

		return $update;
	}

	/**
	 * Import links from prettylink WordPress plugin
	 *
	 * @since 1.3.4
	 * @return bool
	 *
	 */
	public function import_pretty_links() {
		global $wpdb;

		$current_user_id = get_current_user_id();
		$links_table     = "{$wpdb->prefix}prli_links";

		$links_table_exists = US()->is_table_exists( $links_table );

		if ( $links_table_exists > 0 ) {

			$query = "SELECT * FROM {$links_table}";

			$links = $wpdb->get_results( $query, ARRAY_A );

			if ( Helper::is_forechable( $links ) ) {

				if ( function_exists( 'set_time_limit' ) ) {
				set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}

				$existing_links = US()->db->links->get_columns_map( 'id', 'slug' );

				$values = $groups = [];

				$key = 0;

				foreach ( $links as $link ) {

					$slug = Helper::get_data( $link, 'slug', '' );

					if ( in_array( $slug, $existing_links ) ) {
						continue;
					}

					$cpt_id = Helper::get_data( $link, 'link_cpt_id', '' );

					$values[ $key ]['slug']              = $slug;
					$values[ $key ]['name']              = ! empty( Helper::get_data( $link, 'name', '' ) ) ? Helper::get_data( $link, 'name', '' ) : Helper::get_data( $link, 'url', '' );
					$values[ $key ]['description']       = Helper::get_data( $link, 'description', '' );
					$values[ $key ]['url']               = Helper::get_data( $link, 'url', '' );
					$values[ $key ]['nofollow']          = Helper::get_data( $link, 'nofollow', '' );
					$values[ $key ]['track_me']          = Helper::get_data( $link, 'track_me', '' );
					$values[ $key ]['sponsored']         = Helper::get_data( $link, 'sponsored', '' );
					$values[ $key ]['params_forwarding'] = Helper::get_data( $link, 'param_forwarding', '' );
					$values[ $key ]['params_structure']  = Helper::get_data( $link, 'params_struct', '' );
					$values[ $key ]['redirect_type']     = Helper::get_data( $link, 'redirect_type', '' );
					$values[ $key ]['status']            = ( 'enabled' === Helper::get_data( $link, 'link_status', 'enabled' ) ) ? 1 : 0;
					$values[ $key ]['type']              = 'direct';
					$values[ $key ]['type_id']           = null;
					$values[ $key ]['password']          = null;
					$values[ $key ]['expires_at']        = null;
					$values[ $key ]['cpt_id']            = $cpt_id;
					$values[ $key ]['cpt_type']          = Helper::get_data( $link, 'link_cpt_type', '' );
					$values[ $key ]['rules']             = null;
					$values[ $key ]['created_at']        = Helper::get_data( $link, 'created_at', '' );
					$values[ $key ]['created_by_id']     = $current_user_id;
					$values[ $key ]['updated_at']        = Helper::get_data( $link, 'updated_at', '' );
					$values[ $key ]['updated_by_id']     = $current_user_id;

					// Collect all categories
					if ( ! empty( $cpt_id ) ) {
						$terms = get_the_terms( $cpt_id, 'pretty-link-category' );

						if ( Helper::is_forechable( $terms ) ) {
							foreach ( $terms as $term ) {
								$groups[ $term->name ][] = $slug;
							}
						}
					}

					$key ++;
				}

				// Import Links
				if ( Helper::is_forechable( $values ) ) {
					US()->db->links->bulk_insert( $values );
				}

				if ( Helper::is_forechable( $groups ) ) {

					// Import Groups
					$this->import_groups( $groups );

					// Map Link <-> Group
					$this->add_links_to_group( $groups );
				}

			}
		}

		return true;
	}

	/**
	 * Import links from My theme shop short links WordPress plugin
	 *
	 * @since 1.3.4
	 * @return bool
	 *
	 */
	public function import_mts_short_links() {
		global $wpdb;

		$links_table = "{$wpdb->prefix}short_links";

		$links_table_exists = US()->is_table_exists( $links_table );

		if ( $links_table_exists > 0 ) {

			$query = "SELECT * FROM {$links_table}";

			$links = $wpdb->get_results( $query, ARRAY_A );

			if ( Helper::is_forechable( $links ) ) {

				if ( function_exists( 'set_time_limit' ) ) {
				set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}

				$existing_links = US()->db->links->get_columns_map( 'id', 'slug' );

				$values = $groups = [];

				$key = 0;

				foreach ( $links as $link ) {

					$slug = Helper::get_data( $link, 'link_name', '' );

					if ( in_array( $slug, $existing_links ) ) {
						continue;
					}

					$link_id = Helper::get_data( $link, 'link_id', 0 );

					$values[ $key ]['slug']              = $slug;
					$values[ $key ]['name']              = ! empty( Helper::get_data( $link, 'link_title', '' ) ) ? Helper::get_data( $link, 'link_title', '' ) : Helper::get_data( $link, 'link_url', '' );
					$values[ $key ]['description']       = Helper::get_data( $link, 'link_description', '' );
					$values[ $key ]['url']               = Helper::get_data( $link, 'link_url', '' );
					$values[ $key ]['nofollow']          = ( "nofollow" === Helper::get_data( $link, 'link_attr_rel', 'nofollow' ) ) ? 1 : 0;
					$values[ $key ]['track_me']          = 1;
					$values[ $key ]['sponsored']         = 0;
					$values[ $key ]['params_forwarding'] = Helper::get_data( $link, 'link_forward_parameters', 0 );
					$values[ $key ]['params_structure']  = null;
					$values[ $key ]['redirect_type']     = Helper::get_data( $link, 'link_redirection_method', 307 );
					$values[ $key ]['status']            = ( 'publish' === Helper::get_data( $link, 'link_status', 'publish' ) ) ? 1 : 0;
					$values[ $key ]['type']              = 'direct';
					$values[ $key ]['type_id']           = null;
					$values[ $key ]['password']          = null;
					$values[ $key ]['expires_at']        = null;
					$values[ $key ]['cpt_id']            = null;
					$values[ $key ]['cpt_type']          = null;
					$values[ $key ]['rules']             = null;
					$values[ $key ]['created_at']        = Helper::get_data( $link, 'link_created', '' );
					$values[ $key ]['created_by_id']     = Helper::get_data( $link, 'link_owner', '' );
					$values[ $key ]['updated_at']        = Helper::get_data( $link, 'link_updated', '' );
					$values[ $key ]['updated_by_id']     = Helper::get_data( $link, 'link_owner', '' );

					// Collect all categories
					if ( ! empty( $link_id ) ) {
						$categories = wp_get_object_terms( $link_id, 'short_link_category', [ 'fields' => 'ids' ] );
						$categories = array_unique( $categories );

						if ( Helper::is_forechable( $categories ) ) {

							foreach ( $categories as $category ) {
								$term = get_term( $category, 'short_link_category' );

								$groups[ $term->name ][] = $slug;
							}
						}
					}

					$key ++;
				}

				// Import Links
				if ( Helper::is_forechable( $values ) ) {
					US()->db->links->bulk_insert( $values );
				}

				if ( Helper::is_forechable( $groups ) ) {

					// Import Groups
					$this->import_groups( $groups );

					// Map Link <-> Group
					$this->add_links_to_group( $groups );
				}
			}
		}

		return true;
	}

	/**
	 * Import links from 301 Redirect - Easy Redirect Manager WordPress plugin
	 *
	 * https://wordpress.org/plugins/eps-301-redirects/
	 *
	 * @since 1.3.4
	 * @return bool
	 *
	 */
	public function import_eps_301_redirect() {
		global $wpdb;

		$links_table = "{$wpdb->prefix}redirects";

		$links_table_exists = US()->is_table_exists( $links_table );

		if ( $links_table_exists > 0 ) {

			$query = "SELECT * FROM {$links_table}";

			$links = $wpdb->get_results( $query, ARRAY_A );

			if ( Helper::is_forechable( $links ) ) {

				if ( function_exists( 'set_time_limit' ) ) {
				set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}

				$existing_links = US()->db->links->get_columns_map( 'id', 'slug' );

				$values = $groups = [];

				$key = 0;

				$settings = US()->get_settings();

				$default_nofollow          = Helper::get_data( $settings, 'links_default_link_options_enable_nofollow', 1 );
				$default_track_me          = Helper::get_data( $settings, 'links_default_link_options_enable_tracking', 1 );
				$default_sponsored         = Helper::get_data( $settings, 'links_default_link_options_enable_sponsored', 1 );
				$default_params_forwarding = Helper::get_data( $settings, 'links_default_link_options_enable_paramter_forwarding', 1 );
				$default_redirect_type     = Helper::get_data( $settings, 'links_default_link_options_redirection_type', 301 );

				$default_created_at    = date( 'Y-m-d H:i:s' );
				$default_created_by_id = \get_current_user_id();

				foreach ( $links as $link ) {

					$slug = Helper::get_data( $link, 'url_from', '' );

					if ( '*' === $slug || in_array( $slug, $existing_links ) ) {
						continue;
					}

					$status = Helper::get_data( $link, 'status', 'off' );

					// We know only these statuses of 301 Redirects
					if ( ! in_array( $status, [ '301', '302', '307' ] ) ) {
						continue;
					}

					$values[ $key ]['slug']              = $slug;
					$values[ $key ]['name']              = 'Easy 301 Redirect - ' . $slug;
					$values[ $key ]['description']       = 'Imported From Easy 301 Redirect';
					$values[ $key ]['url']               = Helper::get_data( $link, 'url_to', '' );
					$values[ $key ]['nofollow']          = $default_nofollow;
					$values[ $key ]['track_me']          = $default_track_me;
					$values[ $key ]['sponsored']         = $default_sponsored;
					$values[ $key ]['params_forwarding'] = $default_params_forwarding;
					$values[ $key ]['redirect_type']     = Helper::get_data( $link, 'status', $default_redirect_type );
					$values[ $key ]['status']            = 1;
					$values[ $key ]['type']              = 'direct';
					$values[ $key ]['created_at']        = $default_created_at;
					$values[ $key ]['created_by_id']     = $default_created_by_id;

					$groups['Easy 301 Redirect'][] = $slug;

					$key ++;
				}

				// Import Links
				if ( Helper::is_forechable( $values ) ) {
					US()->db->links->bulk_insert( $values );
				}

				if ( Helper::is_forechable( $groups ) ) {

					// Import Groups
					$this->import_groups( $groups );

					// Map Link <-> Group
					$this->add_links_to_group( $groups );
				}

			}
		}

		return true;
	}

	/**
	 * Import links from Simple 301 Redirect WordPress plugin
	 *
	 * https://wordpress.org/plugins/simple-301-redirects/
	 *
	 * @since 1.3.4
	 * @return bool
	 *
	 */
	public function import_from_simple_301_redirect() {
		global $wpdb;

		$plugin_installed = Helper::is_simple_301_redirect_plugin_installed();

		if ( $plugin_installed ) {

			$links = maybe_unserialize( get_option( '301_redirects' ) );

			if ( Helper::is_forechable( $links ) ) {

				if ( function_exists( 'set_time_limit' ) ) {
				set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}

				$existing_links = US()->db->links->get_columns_map( 'id', 'slug' );

				$values = $groups = [];

				$key = 0;

				$settings = US()->get_settings();

				$default_nofollow          = Helper::get_data( $settings, 'links_default_link_options_enable_nofollow', 1 );
				$default_track_me          = Helper::get_data( $settings, 'links_default_link_options_enable_tracking', 1 );
				$default_sponsored         = Helper::get_data( $settings, 'links_default_link_options_enable_sponsored', 1 );
				$default_params_forwarding = Helper::get_data( $settings, 'links_default_link_options_enable_paramter_forwarding', 1 );

				$default_created_at    = date( 'Y-m-d H:i:s' );
				$default_created_by_id = \get_current_user_id();

				foreach ( $links as $slug => $target_url ) {
					$slug = ltrim( $slug, '/' );

					if ( '*' === $slug || in_array( $slug, $existing_links ) ) {
						continue;
					}

					$values[ $key ]['slug']              = $slug;
					$values[ $key ]['name']              = 'Simple 301 Redirect - ' . $slug;
					$values[ $key ]['description']       = 'Imported From Simple 301 Redirect';
					$values[ $key ]['url']               = $target_url;
					$values[ $key ]['nofollow']          = $default_nofollow;
					$values[ $key ]['track_me']          = $default_track_me;
					$values[ $key ]['sponsored']         = $default_sponsored;
					$values[ $key ]['params_forwarding'] = $default_params_forwarding;
					$values[ $key ]['redirect_type']     = 301;
					$values[ $key ]['status']            = 1;
					$values[ $key ]['type']              = 'direct';
					$values[ $key ]['created_at']        = $default_created_at;
					$values[ $key ]['created_by_id']     = $default_created_by_id;

					$groups['Simple 301 Redirect'][] = $slug;

					$key ++;
				}

				// Import Links
				if ( Helper::is_forechable( $values ) ) {
					US()->db->links->bulk_insert( $values );
				}

				if ( Helper::is_forechable( $groups ) ) {
					// Import Groups
					$this->import_groups( $groups );

					// Map Link <-> Group
					$this->add_links_to_group( $groups );
				}

			}
		}

		return true;
	}

	/**
	 * Import links from Thirsty Affiliate WordPress plugin
	 *
	 * @since 1.4.8
	 * @return bool
	 *
	 */
	public function import_thirsty_affiliate_links() {
		global $wpdb;

		$links = get_posts( [
			'posts_per_page' => - 1,
			'post_type'      => 'thirstylink',
			'post_status'    => 'publish',
		] );

		if ( Helper::is_forechable( $links ) ) {

			$current_user_id = get_current_user_id();

			$link_prefix = get_option( 'ta_link_prefix_custom', true );

			if ( function_exists( 'set_time_limit' ) ) {
				set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}

			$existing_links = US()->db->links->get_columns_map( 'id', 'slug' );

			$values = $groups = [];

			$key = 0;

			foreach ( $links as $link ) {

				$slug = $link->post_name;

				if ( in_array( $slug, $existing_links ) ) {
					continue;
				}

				$nofollow = get_post_meta( $link->ID, '_ta_no_follow', true );
				$nofollow = ( $nofollow == 'global' ? get_option( 'ta_no_follow', true ) : $nofollow );

				$redirect_type = get_post_meta( $link->ID, '_ta_redirect_type', true );
				$redirect_type = ( $redirect_type == 'global' ? get_option( 'ta_link_redirect_type', true ) : $redirect_type );

				$param_forwarding = get_post_meta( $link->ID, '_ta_pass_query_str', true );
				$param_forwarding = ( $param_forwarding == 'global' ? get_option( 'ta_pass_query_str', true ) : $param_forwarding );

				// expire
				$expire_date = get_post_meta( $link->ID, '_ta_link_expire_date', true );

				$slug = $link->post_name;
				if ( ! empty( $link_prefix ) ) {
					$slug = trim( $link_prefix, '/' ) . '/' . $slug;
				}

				$values[ $key ]['slug']              = $slug;
				$values[ $key ]['name']              = $link->post_title;
				$values[ $key ]['description']       = '';
				$values[ $key ]['url']               = get_post_meta( $link->ID, '_ta_destination_url', true );
				$values[ $key ]['nofollow']          = ( $nofollow == 'yes' ? 1 : 0 );
				$values[ $key ]['track_me']          = 1;
				$values[ $key ]['sponsored']         = 0;
				$values[ $key ]['params_forwarding'] = ( $param_forwarding == 'yes' ? 1 : 0 );
				$values[ $key ]['params_structure']  = null;
				$values[ $key ]['redirect_type']     = $redirect_type;
				$values[ $key ]['status']            = 1;
				$values[ $key ]['type']              = 'direct';
				$values[ $key ]['type_id']           = null;
				$values[ $key ]['password']          = null;
				$values[ $key ]['expires_at']        = $expire_date;
				$values[ $key ]['cpt_id']            = null;
				$values[ $key ]['cpt_type']          = null;
				$values[ $key ]['rules']             = null;
				$values[ $key ]['created_at']        = Helper::get_current_date_time();
				$values[ $key ]['created_by_id']     = $current_user_id;
				$values[ $key ]['updated_at']        = '';
				$values[ $key ]['updated_by_id']     = '';

				// Collect all categories
				if ( ! empty( $link->ID ) ) {
					$terms = get_the_terms( $link->ID, 'thirstylink-category' );

					if ( Helper::is_forechable( $terms ) ) {
						foreach ( $terms as $term ) {
							$groups[ $term->name ][] = $slug;
						}
					}
				}

				$key ++;
			}

			// Import Links
			if ( Helper::is_forechable( $values ) ) {
				US()->db->links->bulk_insert( $values );
			}

			if ( Helper::is_forechable( $groups ) ) {
				// Import Groups
				$this->import_groups( $groups );

				// Map Link <-> Group
				$this->add_links_to_group( $groups );
			}

			//Import Settings.
			$settings = Option::get( 'settings' );

			$settings['links_default_link_options_link_prefix'] = $link_prefix;

			Option::set( 'settings', $settings );
		}

		return true;
	}

	/**
	 * Import links from Shorten URL plugin
	 *
	 * https://wordpress.org/plugins/shorten-url/
	 *
	 * @since 1.5.6
	 */
	public function import_from_shorten_url() {
		global $wpdb;

		$links_table = "{$wpdb->prefix}pluginSL_shorturl";

		if ( Helper::is_shorten_url_table_exists() ) {

			$query = "SELECT * FROM {$links_table}";

			$links = $wpdb->get_results( $query, ARRAY_A );

			if ( Helper::is_forechable( $links ) ) {

				$current_user_id = get_current_user_id();

				if ( function_exists( 'set_time_limit' ) ) {
				set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}

				$existing_links = US()->db->links->get_columns_map( 'id', 'slug' );

				$values = $groups = [];

				$key = 0;

				foreach ( $links as $link ) {

					$slug = Helper::get_data( $link, 'short_url', '' );

					if ( in_array( $slug, $existing_links ) ) {
						continue;
					}

					$link_title = $url = Helper::get_data( $link, 'url_externe', '' );
					$post_id    = Helper::get_data( $link, 'id_post', 0 );
					if ( $post_id ) {
						$post = get_post( $post_id );
						if ( $post instanceof \WP_Post ) {
							$url        = get_permalink( $post );
							$link_title = get_the_title( $post );
						}
					}

					if ( ! empty( $url ) ) {

						$values[ $key ]['slug']              = $slug;
						$values[ $key ]['name']              = $link_title;
						$values[ $key ]['description']       = Helper::get_data( $link, 'comment', '' );
						$values[ $key ]['url']               = $url;
						$values[ $key ]['nofollow']          = ( "nofollow" === Helper::get_data( $link, 'link_attr_rel', 'nofollow' ) ) ? 1 : 0;
						$values[ $key ]['track_me']          = 1;
						$values[ $key ]['sponsored']         = 0;
						$values[ $key ]['params_forwarding'] = 0;
						$values[ $key ]['params_structure']  = null;
						$values[ $key ]['redirect_type']     = Helper::get_data( $link, 'link_redirection_method', 307 );
						$values[ $key ]['status']            = 1;
						$values[ $key ]['type']              = 'direct';
						$values[ $key ]['type_id']           = null;
						$values[ $key ]['password']          = null;
						$values[ $key ]['expires_at']        = null;
						$values[ $key ]['cpt_id']            = $post_id;
						$values[ $key ]['cpt_type']          = null;
						$values[ $key ]['rules']             = null;
						$values[ $key ]['created_at']        = Helper::get_current_date_time();
						$values[ $key ]['created_by_id']     = $current_user_id;
						$values[ $key ]['updated_at']        = '';
						$values[ $key ]['updated_by_id']     = '';

						$key ++;
					}
				}

				// Import Links
				if ( Helper::is_forechable( $values ) ) {
					US()->db->links->bulk_insert( $values );
				}

				if ( Helper::is_forechable( $groups ) ) {
					// Import Groups
					$this->import_groups( $groups );

					// Map Link <-> Group
					$this->add_links_to_group( $groups );
				}
			}
		}

		return true;
	}

	/**
	 * Import short URLs from redirection plugin.
	 *
	 * @since 1.8.6
	 *
	 * @return true
	 */
	public function import_from_redirection() {
		global $wpdb;

		$links_table = "{$wpdb->prefix}redirection_items";

		if ( Helper::is_shorten_url_table_exists() ) {

			$query = "SELECT * FROM {$links_table} WHERE `action_type` = 'url'";

			$links = $wpdb->get_results( $query, ARRAY_A );

			if ( Helper::is_forechable( $links ) ) {

				$settings = US()->get_settings();

				$default_nofollow          = Helper::get_data( $settings, 'links_default_link_options_enable_nofollow', 1 );
				$default_track_me          = Helper::get_data( $settings, 'links_default_link_options_enable_tracking', 1 );
				$default_sponsored         = Helper::get_data( $settings, 'links_default_link_options_enable_sponsored', 1 );
				$default_params_forwarding = Helper::get_data( $settings, 'links_default_link_options_enable_paramter_forwarding', 1 );

				$current_user_id = get_current_user_id();

				if ( function_exists( 'set_time_limit' ) ) {
				set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}

				$existing_links = US()->db->links->get_columns_map( 'id', 'slug' );

				$values = $groups = [];

				$key = 0;

				foreach ( $links as $link ) {
					$slug = trim( Helper::get_data( $link, 'url', '' ), '/' );

					if ( empty( $slug ) || in_array( $slug, $existing_links ) ) {
						continue;
					}

					$link_title = Helper::get_data( $link, 'title', '' );
					$url        = Helper::get_data( $link, 'action_data', '' );
					$post_id    = Helper::get_data( $link, 'id_post', 0 );
					if ( $post_id ) {
						$post = get_post( $post_id );
						if ( $post instanceof \WP_Post ) {
							$url        = get_permalink( $post );
							$link_title = get_the_title( $post );
						}
					}

					if ( ! empty( $url ) ) {

						$values[ $key ]['slug']              = $slug;
						$values[ $key ]['name']              = $link_title;
						$values[ $key ]['description']       = Helper::get_data( $link, 'comment', '' );
						$values[ $key ]['url']               = $url;
						$values[ $key ]['nofollow']          = $default_nofollow;
						$values[ $key ]['track_me']          = $default_track_me;
						$values[ $key ]['sponsored']         = $default_sponsored;
						$values[ $key ]['params_forwarding'] = $default_params_forwarding;
						$values[ $key ]['params_structure']  = null;
						$values[ $key ]['redirect_type']     = Helper::get_data( $link, 'action_code', 307 );
						$values[ $key ]['status']            = 1;
						$values[ $key ]['type']              = 'direct';
						$values[ $key ]['type_id']           = null;
						$values[ $key ]['password']          = null;
						$values[ $key ]['expires_at']        = null;
						$values[ $key ]['cpt_id']            = $post_id;
						$values[ $key ]['cpt_type']          = null;
						$values[ $key ]['rules']             = null;
						$values[ $key ]['created_at']        = Helper::get_current_date_time();
						$values[ $key ]['created_by_id']     = $current_user_id;
						$values[ $key ]['updated_at']        = '';
						$values[ $key ]['updated_by_id']     = '';

						$key ++;
					}
				}

				// Import Links
				if ( Helper::is_forechable( $values ) ) {
					US()->db->links->bulk_insert( $values );
				}

				if ( Helper::is_forechable( $groups ) ) {
					// Import Groups
					$this->import_groups( $groups );

					// Map Link <-> Group
					$this->add_links_to_group( $groups );
				}
			}
		}

		return true;
	}

	/**
	 * Import one batch of links from the Link Central plugin.
	 *
	 * Thin dispatcher to LinkCentralImporter — kept here for backwards
	 * compatibility with the AJAX handler that calls this method directly.
	 *
	 * @since 2.3.0
	 *
	 * @return array
	 */
	public function import_link_central() {
		$offset = absint( Helper::get_request_data( 'offset', 0 ) );

		$importer = new LinkCentralImporter( $this );

		return $importer->run_batch( $offset );
	}

}