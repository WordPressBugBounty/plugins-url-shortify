<?php

namespace KaizenCoders\URL_Shortify\Admin\Controllers;

use KaizenCoders\URL_Shortify\Admin\Controllers\Importers\Eps301RedirectImporter;
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
	public function add_links_to_group( $groups = [] ) {

		if ( Helper::is_forechable( $groups ) ) {

			$create_by_id       = \get_current_user_id();
			$groups_name_id_map = US()->db->groups->get_columns_map( 'name', 'id' );

			$links_slug_id_map = US()->db->links->get_columns_map( 'slug', 'id' );

			$data_to_insert = [];

			$key = 0;

			foreach ( $groups as $group => $links ) {

				$group_id = Helper::get_data( $groups_name_id_map, $group, 0 );

				if ( 0 != $group_id ) {

					if ( Helper::is_forechable( $links ) ) {
						foreach ( $links as $slug ) {
							$link_id = Helper::get_data( $links_slug_id_map, $slug, 0 );

							if ( 0 != $link_id ) {
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
	public function add_links_to_tag( $tags = [] ) {
		if ( ! Helper::is_forechable( $tags ) ) {
			return;
		}

		$created_by_id     = \get_current_user_id();
		$tags_name_id_map  = US()->db->tags->get_columns_map( 'name', 'id' );
		$links_slug_id_map = US()->db->links->get_columns_map( 'slug', 'id' );
		$data_to_insert    = [];
		$key               = 0;

		foreach ( $tags as $tag => $links ) {
			$tag_id = Helper::get_data( $tags_name_id_map, $tag, 0 );

			if ( 0 != $tag_id && Helper::is_forechable( $links ) ) {
				foreach ( $links as $slug ) {
					$link_id = Helper::get_data( $links_slug_id_map, $slug, 0 );

					if ( 0 != $link_id ) {
						$data_to_insert[ $key ]['link_id']       = $link_id;
						$data_to_insert[ $key ]['tag_id']        = $tag_id;
						$data_to_insert[ $key ]['created_by_id'] = $created_by_id;
						$key ++;
					}
				}
			}
		}

		if ( Helper::is_forechable( $data_to_insert ) ) {
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

		$columns = array_map('trim', $columns);

		$required_headings = [ 'Target URL' ];

		if ( count( array_intersect( $columns, $required_headings ) ) != count( $required_headings ) ) {
			wp_die( 'Invalid columns in CSV file. Please make sure Target URL column is available in CSV file.' );
		}

		$links = [];

		while ( ( $data = fgetcsv( $csv_file ) ) !== false ) {
			$links[] = array_combine( $columns, $data );
		}

		fclose( $csv_file );

		if ( Helper::is_forechable( $links ) ) {

			$settings = US()->get_settings();

			$default_nofollow          = Helper::get_data( $settings, 'links_default_link_options_enable_nofollow', 1 );
			$default_track_me          = Helper::get_data( $settings, 'links_default_link_options_enable_tracking', 1 );
			$default_sponsored         = Helper::get_data( $settings, 'links_default_link_options_enable_sponsored', 1 );
			$default_params_forwarding = Helper::get_data( $settings, 'links_default_link_options_enable_paramter_forwarding', 1 );
			$default_redirect_type     = Helper::get_data( $settings, 'links_default_link_options_redirection_type', 301 );

			$default_created_at = date( 'Y-m-d H:i:s' );

			$current_user_id = \get_current_user_id();

			$existing_links = US()->db->links->get_columns_map( 'id', 'slug' );

			$values = [];

			$key = 0;

			$groups_to_import = [];
			$tags_to_import   = [];

			foreach ( $links as $link ) {

				$slug = Helper::get_data( $link, 'Slug', '', true );

				$groups = $this->parse_csv_terms( Helper::get_data( $link, 'Groups', '', true ) );
				$tags   = [];
				if ( US()->is_pro() ) {
					$tags = $this->parse_csv_terms( Helper::get_data( $link, 'Tags', '', true ) );
				}

				if ( empty( $slug ) ) {
					$slug = Utils::generate_random_slug();
					$slug = Helper::get_slug_with_prefix( $slug );
				}


				if ( ! empty( $groups ) ) {
					foreach ( $groups as $group ) {
						$groups_to_import[ $group ][] = $slug;
					}
				}

				if ( US()->is_pro() && ! empty( $tags ) ) {
					foreach ( $tags as $tag ) {
						$tags_to_import[ $tag ][] = $slug;
					}
				}

				if ( in_array( $slug, $existing_links ) ) {
					continue;
				}

				$values[ $key ]['slug']              = $slug;
				$values[ $key ]['name']              = ! empty( Helper::get_data( $link, 'Title', '' ) ) ? Helper::get_data( $link, 'Title', '', true ) : Helper::get_data( $link, 'Target URL', '' );
				$values[ $key ]['description']       = Helper::get_data( $link, 'Description', '', true );
				$values[ $key ]['url']               = esc_url_raw( Helper::get_data( $link, 'Target URL', '' ) );
				$values[ $key ]['nofollow']          = Helper::get_data( $link, 'Nofollow', $default_nofollow );
				$values[ $key ]['track_me']          = Helper::get_data( $link, 'Track', $default_track_me );
				$values[ $key ]['sponsored']         = Helper::get_data( $link, 'Sponsored', $default_sponsored );
				$values[ $key ]['params_forwarding'] = Helper::get_data( $link, 'Parameter Forwarding', $default_params_forwarding );
				// $values[ $key ]['params_structure']  = Helper::get_data( $link, 'params_struct', '' );
				$values[ $key ]['redirect_type'] = Helper::get_data( $link, 'Redirect Type', $default_redirect_type );
				$values[ $key ]['status']        = 1;
				$values[ $key ]['type']          = 'direct';
				$values[ $key ]['type_id']       = null;
				$values[ $key ]['password']      = null;
				$values[ $key ]['expires_at']    = null;
				$values[ $key ]['cpt_id']        = null;
				$values[ $key ]['cpt_type']      = '';
				$values[ $key ]['rules']         = null;
				$values[ $key ]['created_at']    = Helper::get_data( $link, 'Created At', $default_created_at );
				$values[ $key ]['created_by_id'] = $current_user_id;
				$values[ $key ]['updated_at']    = Helper::get_data( $link, 'Updated At', '' );
				$values[ $key ]['updated_by_id'] = $current_user_id;

				$key ++;
			}

			// Import Links
			if ( Helper::is_forechable( $values ) ) {
				US()->db->links->bulk_insert( $values );
			}

			if ( ! empty( $groups_to_import ) ) {
				$this->import_groups( $groups_to_import );

				$this->add_links_to_group( $groups_to_import );
			}

			if ( US()->is_pro() && ! empty( $tags_to_import ) ) {
				$this->import_tags( $tags_to_import );

				$this->add_links_to_tag( $tags_to_import );
			}
		}

		return true;
	}

}