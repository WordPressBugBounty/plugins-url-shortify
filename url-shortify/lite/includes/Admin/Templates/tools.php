<?php

use KaizenCoders\URL_Shortify\Helper;

$nav_menus = Helper::get_data( $template_data, 'links', [] );

$tab = ! empty( $_GET['tab'] ) ? Helper::clean( $_GET['tab'] ) : 'import';

$action = ! empty( $_GET['action'] ) ? Helper::clean( $_GET['action'] ) : '';

$current_url = \KaizenCoders\URL_Shortify\Common\Utils::get_current_page_url();

$nonce = wp_create_nonce( 'kc_us_import' );

$valid_imports = [
	'csv',
	'pretty_links',
	'mts_links',
	'eps_301_redirects',
	'simple_301_redirects',
	'thirsty_affiliates',
	'shorten_url',
	'redirection',
	'link_central',
];

/*
 * The nonce arrives two different ways: one-click sources carry it in the
 * import link's query string, the CSV form posts its own. Reading $_GET only
 * meant neither ever validated, so no import ran at all.
 */
$received_nonce = Helper::get_request_data( '_wpnonce', '' );

$is_valid_request = wp_verify_nonce( $received_nonce, 'kc_us_import' )
                    || ( 'csv' === $action && wp_verify_nonce( $received_nonce, 'import_csv' ) );

$import_status = Helper::get_request_data( 'import_status', '' );

?>

<style>
/* Not core's .notice: WordPress hoists those to be direct children of .wrap,
   and a number of admin-tidying plugins hide "#wpbody-content > .wrap > .notice"
   outright, which silently swallows the import result. */
.kc-us-import-result { margin: 18px 24px 4px; padding: 18px 22px; border-radius: 8px; border: 1px solid #e5e7eb; border-left: 4px solid #6b7280; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
.kc-us-import-result--success { border-left-color: #16a34a; }
.kc-us-import-result--error { border-left-color: #dc2626; }
.kc-us-import-result__title { margin: 0; font-size: 15px; font-weight: 600; color: #111827; }
.kc-us-import-result__stats { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 16px; }
.kc-us-import-result__stat { flex: 1 1 150px; min-width: 140px; background: #f9fafb; border: 1px solid #eef2f7; border-radius: 6px; padding: 12px 14px; }
.kc-us-import-result__stat.is-warning { background: #fffbeb; border-color: #fde68a; }
.kc-us-import-result__value { display: block; font-size: 22px; font-weight: 700; color: #111827; line-height: 1.2; font-variant-numeric: tabular-nums; }
.kc-us-import-result__label { display: block; margin-top: 2px; font-size: 12px; font-weight: 600; color: #4b5563; }
.kc-us-import-result__note { display: block; margin-top: 4px; font-size: 11px; color: #9ca3af; line-height: 1.45; }
.kc-us-import-result__footer { margin: 14px 0 0; font-size: 13px; color: #6b7280; }

/* ── Import CSV screen ─────────────────────────────────────────────────── */
.kc-us-csv { padding: 4px 24px 28px; }
.kc-us-csv__head { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 12px; padding: 18px 0 6px; }
.kc-us-csv__head h3 { margin: 0; font-size: 16px; font-weight: 600; color: #111827; }
.kc-us-csv__back { text-decoration: none; font-size: 13px; }
.kc-us-csv__layout { display: flex; flex-wrap: wrap; gap: 28px; align-items: flex-start; }
.kc-us-csv__form { flex: 1 1 460px; min-width: 320px; }
.kc-us-csv__row { display: flex; flex-wrap: wrap; gap: 16px; padding: 18px 0; border-top: 1px solid #f1f5f9; }
.kc-us-csv__row:first-of-type { border-top: 0; }
.kc-us-csv__label { flex: 0 0 140px; padding-top: 3px; }
.kc-us-csv__label label, .kc-us-csv__label span { font-size: 13px; font-weight: 600; color: #4b5563; }
.kc-us-csv__field { flex: 1 1 280px; min-width: 240px; }
.kc-us-csv__hint { margin: 8px 0 0; font-size: 12px; color: #6b7280; line-height: 1.6; max-width: 46em; }
.kc-us-csv__check { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: #111827; }
.kc-us-csv__check input { margin: 0; }
.kc-us-csv__actions { display: flex; align-items: center; gap: 14px; padding-top: 20px; border-top: 1px solid #f1f5f9; }
.kc-us-csv__cancel { color: #6b7280; text-decoration: none; font-size: 13px; }
.kc-us-csv__cancel:hover { color: #b32d2e; }

.kc-us-csv__reference { flex: 0 1 380px; min-width: 300px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 18px 20px; }
.kc-us-csv__reference h4 { margin: 0 0 4px; font-size: 13px; font-weight: 600; color: #111827; }
.kc-us-csv__columns { width: 100%; border-collapse: collapse; margin-top: 12px; }
.kc-us-csv__columns th, .kc-us-csv__columns td { text-align: left; vertical-align: top; padding: 6px 0; border-top: 1px solid #eef2f7; font-weight: 400; }
.kc-us-csv__columns tr:first-child th, .kc-us-csv__columns tr:first-child td { border-top: 0; }
.kc-us-csv__columns th { width: 44%; padding-right: 12px; }
.kc-us-csv__columns code { font-size: 11px; background: #fff; border: 1px solid #e5e7eb; border-radius: 3px; padding: 1px 5px; white-space: nowrap; }
.kc-us-csv__columns td { font-size: 12px; color: #6b7280; line-height: 1.5; }
.kc-us-csv__sample { margin: 14px 0 0; font-size: 13px; }
</style>

<div class="wrap">
    <h2>Tools</h2>
    <h2 class="nav-tab-wrapper">
		<?php foreach ( $nav_menus as $id => $menu ) { ?>
            <a href="<?php echo $menu['link']; ?>" class="nav-tab wpsf-tab-link <?php if ( $id === $tab ) {
				echo "nav-tab-active";
			} ?>">
				<?php echo $menu['title']; ?>
            </a>
		<?php } ?>
    </h2>

    <div class="bg-white shadow-md meta-box-sortables">


        <!-- First Screen - List all import section -->
		<?php

		$submitted = Helper::get_request_data( 'submitted', '' );

		if ( 'import' === $tab && '' === $action ) {

			$import_from = [
				[
					'title'  => __( 'Import CSV', 'url-shortify' ),
					'action' => 'csv',
					'show'   => true,
				],

				[
					/* translators: %s: URL for the Pretty Links plugin on WordPress.org */
				'title'  => sprintf( __( 'Import Short Links From <a href="%s" target="_blank">Pretty Links</a> WordPress Plugin',
						'url-shortify' ), 'https://wordpress.org/plugins/prettylinks' ),
					'action' => 'pretty_links',
					'show'   => Helper::is_import_source_available( 'pretty_links' ),
				],

				[
					/* translators: %s: URL for the URL Shortener by MyThemeShop plugin on WordPress.org */
				'title'  => sprintf( __( 'Import Short Links From <a href="%s" target="_blank">URL Shortener by MyThemeShop</a> WordPress Plugin',
						'url-shortify' ), 'https://wordpress.org/plugins/mts-url-shortener/' ),
					'action' => 'mts_links',
					'show'   => Helper::is_import_source_available( 'mts_links' ),
				],

				[
					/* translators: %s: URL for the 301 Redirect plugin on WordPress.org */
				'title'  => sprintf( __( 'Import Short Links From <a href="%s" target="_blank">301 Redirect</a> WordPress Plugin',
						'url-shortify' ), 'https://wordpress.org/plugins/eps-301-redirects/' ),
					'action' => 'eps_301_redirects',
					'show'   => Helper::is_import_source_available( 'eps_301_redirects' ),
				],

				[
					/* translators: %s: URL for the Simple 301 Redirect plugin on WordPress.org */
				'title'  => sprintf( __( 'Import Short Links From <a href="%s" target="_blank">Simple 301 Redirect</a> WordPress Plugin',
						'url-shortify' ), 'https://wordpress.org/plugins/simple-301-redirects/' ),
					'action' => 'simple_301_redirects',
					'show'   => Helper::is_import_source_available( 'simple_301_redirects' ),
				],

				[
					/* translators: %s: URL for the Short URL plugin on WordPress.org */
				'title'  => sprintf( __( 'Import Short Links From <a href="%s" target="_blank">Short URL</a> WordPress Plugin',
						'url-shortify' ), 'https://wordpress.org/plugins/shorten-url/' ),
					'action' => 'shorten_url',
					'show'   => Helper::is_import_source_available( 'shorten_url' ),
				],

				[
					/* translators: %s: URL for the Thirsty Affiliates plugin on WordPress.org */
				'title'  => sprintf( __( 'Import Short Links From <a href="%s" target="_blank">Thirsty Affiliates</a> WordPress Plugin',
						'url-shortify' ), 'https://wordpress.org/plugins/simple-301-redirects/' ),
					'action' => 'thirsty_affiliates',
					'show'   => Helper::is_import_source_available( 'thirsty_affiliates' ),
				],

				[
					/* translators: %s: URL for the Redirection plugin on WordPress.org */
				'title'  => sprintf( __( 'Import Short Links From <a href="%s" target="_blank">Redirection</a> WordPress Plugin',
						'url-shortify' ), 'https://wordpress.org/plugins/redirection/' ),
					'action' => 'redirection',
					'show'   => Helper::is_import_source_available( 'redirection' ),
				],

				[
					/* translators: %s: URL for the LinkCentral plugin on WordPress.org */
				'title'  => sprintf( __( 'Import Short Links From <a href="%s" target="_blank">LinkCentral</a> WordPress Plugin',
						'url-shortify' ), 'https://wordpress.org/plugins/linkcentral/' ),
					'action' => 'link_central',
					'show'   => Helper::is_import_source_available( 'link_central' ),
				],

			];

			if ( 'success' === $import_status ) {

				// Only the CSV importer reports counts; the one-click sources
				// just finish. Without this check a plugin import reads back as
				// "Nothing to import" simply because it returned no numbers.
				$has_counts = null !== Helper::get_request_data( 'created', null );

				$counts = [
					'created' => absint( Helper::get_request_data( 'created', 0 ) ),
					'updated' => absint( Helper::get_request_data( 'updated', 0 ) ),
					'skipped' => absint( Helper::get_request_data( 'skipped', 0 ) ),
					'invalid' => absint( Helper::get_request_data( 'invalid', 0 ) ),
				];

				$stats = [
					'created' => [ __( 'Links added', 'url-shortify' ), '' ],
					'updated' => [ __( 'Links updated', 'url-shortify' ), '' ],
					'skipped' => [ __( 'Rows skipped', 'url-shortify' ), __( 'Slug already existed and the update option was off.', 'url-shortify' ) ],
					'invalid' => [ __( 'Rows not imported', 'url-shortify' ), __( 'Missing or invalid target URL.', 'url-shortify' ) ],
				];

				$total_rows = array_sum( $counts );
				?>
                <div class="kc-us-import-result kc-us-import-result--success">
                    <p class="kc-us-import-result__title">
						<?php
						if ( ! $has_counts ) {
							esc_html_e( 'Import completed.', 'url-shortify' );
						} elseif ( 0 === $total_rows ) {
							esc_html_e( 'Import completed, but the file contained no rows to import.', 'url-shortify' );
						} elseif ( 0 === $counts['invalid'] ) {
							esc_html_e( 'Import completed successfully.', 'url-shortify' );
						} else {
							esc_html_e( 'Import completed, with some rows skipped.', 'url-shortify' );
						}
						?>
                    </p>

					<?php if ( $has_counts && $total_rows > 0 ) : ?>
                        <div class="kc-us-import-result__stats">
							<?php foreach ( $stats as $stat_key => $stat ) : ?>
                                <div class="kc-us-import-result__stat <?php echo ( 'invalid' === $stat_key && $counts[ $stat_key ] > 0 ) ? 'is-warning' : ''; ?>">
                                    <span class="kc-us-import-result__value"><?php echo esc_html( number_format_i18n( $counts[ $stat_key ] ) ); ?></span>
                                    <span class="kc-us-import-result__label"><?php echo esc_html( $stat[0] ); ?></span>
									<?php if ( '' !== $stat[1] && $counts[ $stat_key ] > 0 ) : ?>
                                        <span class="kc-us-import-result__note"><?php echo esc_html( $stat[1] ); ?></span>
									<?php endif; ?>
                                </div>
							<?php endforeach; ?>
                        </div>

                        <p class="kc-us-import-result__footer">
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=us_links' ) ); ?>">
								<?php esc_html_e( 'View your links', 'url-shortify' ); ?>
                            </a>
                        </p>
					<?php endif; ?>
                </div>
			<?php } elseif ( 'error' === $import_status ) { ?>
                <div class="kc-us-import-result kc-us-import-result--error">
                    <p class="kc-us-import-result__title"><?php esc_html_e( 'The import could not be completed.', 'url-shortify' ); ?></p>
                    <p class="kc-us-import-result__footer">
						<?php esc_html_e( 'Check that the file is a CSV, that its first row contains the column headings, and that a Target URL column is present.', 'url-shortify' ); ?>
                    </p>
                </div>
			<?php } ?>

			<?php foreach ( $import_from as $item ) {

				if ( true == $item['show'] ) { ?>
                    <div class="flex-row pt-2 pb-2 ml-5 mr-4 text-left item-center">
                        <div class="flex flex-row border-b border-gray-100">
                            <div class="flex w-4/5">
                                <label for="">
                            <span class="block pt-1 mb-2 pr-4 ml-4 text-sm font-medium text-gray-600">
                                <?php echo $item['title']; ?>
                            </span>
                                </label>
                            </div>
                            <div class="flex w-1/5">
                                <a href="<?php echo esc_url( \KaizenCoders\URL_Shortify\Common\Utils::get_current_page_url( array( 'action' => $item['action'], '_wpnonce' => $nonce ) ) ); ?>"
                                   class="px-4 py-2 mx-2 my-2 text-sm font-medium leading-5 align-middle transition duration-150 ease-in-out border border-indigo-600 rounded-md cursor-pointer hover:shadow-md focus:outline-none focus:shadow-outline-indigo">
									<?php _e( 'Import', 'url-shortify' ); ?>
                                </a>
                            </div>
                        </div>
                    </div>
				<?php }
			} ?>

		<?php } elseif ( 'bookmarklet' === $tab && '' === $action ) {
			// Generate bookmarklet page from PRO.
			do_action( 'kc_us_render_bookmarklet_page' );

		} elseif ( 'migration' === $tab ) {
			// Link Central now lives in the Import list above. The tab is no
			// longer in the nav, but a bookmarked URL still resolves here and
			// the batched migration screen keeps working.
			include_once KC_US_ADMIN_TEMPLATES_DIR . '/migration.php';

		} elseif ( 'export_import' === $tab ) {
			// Rendered by PRO. The tab is only registered when PRO is active.
			do_action( 'kc_us_render_export_import_page' );

		} elseif ( 'import' === $tab && 'csv' === $action && ( '' === $submitted ) ) {

			$max_upload_size = Helper::get_max_upload_size();
			$sample_csv_url  = plugin_dir_url( __FILE__ ) . '../../Admin/Templates/sample.csv';
			// Built from scratch rather than by stripping 'action' off the current
			// URL: get_current_page_url() merges extra args, so a false value
			// would write action=0 instead of removing it.
			$import_list_url = add_query_arg( [ 'page' => 'us_tools', 'tab' => 'import' ], admin_url( 'admin.php' ) );

			/*
			 * Documented inline rather than only in the sample file: matching the
			 * heading text is the one thing that has to be right, and sending
			 * people off to download a file to find that out is the main reason
			 * an import fails on the first try.
			 */
			$csv_columns = [
				[ 'Target URL', __( 'Required. Where the short link points.', 'url-shortify' ) ],
				[ 'Slug', __( 'Optional. Generated for you when blank.', 'url-shortify' ) ],
				[ 'Title', __( 'Optional. Falls back to the target URL.', 'url-shortify' ) ],
				[ 'Description', __( 'Optional.', 'url-shortify' ) ],
				[ 'Groups', __( 'Optional. Pipe separated: Sports|Culture. Replaces the link\'s groups.', 'url-shortify' ) ],
				[ 'Tags', __( 'Optional, PRO only. Pipe separated. Replaces the link\'s tags.', 'url-shortify' ) ],
				[ 'Redirect Type', __( '301, 302 or 307. Uses your default when blank.', 'url-shortify' ) ],
				[ 'Nofollow', __( '1 or 0. Uses your default when blank.', 'url-shortify' ) ],
				[ 'Sponsored', __( '1 or 0. Uses your default when blank.', 'url-shortify' ) ],
				[ 'Parameter Forwarding', __( '1 or 0. Uses your default when blank.', 'url-shortify' ) ],
				[ 'Track', __( '1 or 0. Uses your default when blank.', 'url-shortify' ) ],
			];
			?>

            <div class="kc-us-csv">
                <div class="kc-us-csv__head">
                    <h3><?php esc_html_e( 'Import links from a CSV file', 'url-shortify' ); ?></h3>
                    <a href="<?php echo esc_url( $import_list_url ); ?>" class="kc-us-csv__back">
						<?php esc_html_e( '&larr; All import sources', 'url-shortify' ); ?>
                    </a>
                </div>

                <div class="kc-us-csv__layout">
                    <form method="post" enctype="multipart/form-data" class="kc-us-csv__form">
						<?php wp_nonce_field( 'import_csv' ); ?>
                        <input type="hidden" name="submitted" value="submitted"/>

                        <div class="kc-us-csv__row">
                            <div class="kc-us-csv__label">
                                <label for="csv_file"><?php esc_html_e( 'CSV file', 'url-shortify' ); ?></label>
                            </div>
                            <div class="kc-us-csv__field">
                                <input type="file" name="csv_file" id="csv_file" accept=".csv,text/csv" required>
                                <p class="kc-us-csv__hint">
									<?php
									/* translators: %s: Maximum allowed file size (e.g. "2 MB") */
									echo esc_html( sprintf( __( 'Maximum file size %s. The first row must contain the column headings.', 'url-shortify' ), size_format( $max_upload_size ) ) );
									?>
                                </p>
                            </div>
                        </div>

                        <div class="kc-us-csv__row">
                            <div class="kc-us-csv__label">
                                <span><?php esc_html_e( 'Existing links', 'url-shortify' ); ?></span>
                            </div>
                            <div class="kc-us-csv__field">
                                <label for="update_existing" class="kc-us-csv__check">
                                    <input type="checkbox" name="update_existing" id="update_existing" value="1">
                                    <span><?php esc_html_e( 'Update links that already exist', 'url-shortify' ); ?></span>
                                </label>
                                <p class="kc-us-csv__hint">
									<?php esc_html_e( 'When a row\'s slug matches an existing link, its target URL and any other columns in the file are applied to that link. Columns the file does not contain are left unchanged. Without this, matching rows are skipped.', 'url-shortify' ); ?>
                                </p>
                            </div>
                        </div>

                        <div class="kc-us-csv__actions">
							<?php submit_button( __( 'Import CSV', 'url-shortify' ), 'primary', 'submit', false ); ?>
                            <a href="<?php echo esc_url( $import_list_url ); ?>" class="kc-us-csv__cancel">
								<?php esc_html_e( 'Cancel', 'url-shortify' ); ?>
                            </a>
                        </div>
                    </form>

                    <aside class="kc-us-csv__reference">
                        <h4><?php esc_html_e( 'Expected columns', 'url-shortify' ); ?></h4>
                        <p class="kc-us-csv__hint">
							<?php esc_html_e( 'Headings are matched by name. Any column you leave out keeps its default. Where a row gives Groups or Tags, those replace whatever the link had; leave the column out to keep them.', 'url-shortify' ); ?>
                        </p>

                        <table class="kc-us-csv__columns">
                            <tbody>
							<?php foreach ( $csv_columns as $column ) : ?>
                                <tr>
                                    <th scope="row"><code><?php echo esc_html( $column[0] ); ?></code></th>
                                    <td><?php echo esc_html( $column[1] ); ?></td>
                                </tr>
							<?php endforeach; ?>
                            </tbody>
                        </table>

                        <p class="kc-us-csv__sample">
                            <a href="<?php echo esc_url( $sample_csv_url ); ?>" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'Download a sample CSV', 'url-shortify' ); ?>
                            </a>
                        </p>
                    </aside>
                </div>
            </div>

		<?php } elseif ( 'import' === $tab && in_array( $action, $valid_imports ) && $is_valid_request ) {

			$import    = new \KaizenCoders\URL_Shortify\Admin\Controllers\ImportController();
			$do_import = $import->import_links( $action );

			$current_url = remove_query_arg( [ 'action', '_wpnonce' ] );

			if ( false === $do_import ) {
				$current_url = add_query_arg( [ 'import_status' => 'error' ], $current_url );
			} else {
				$status_args = [ 'import_status' => 'success' ];

				// The CSV importer reports what it did; the one-click importers
				// just report that they finished.
				if ( is_array( $do_import ) ) {
					$status_args = array_merge( $status_args, array_map( 'absint', $do_import ) );
				}

				$current_url = add_query_arg( $status_args, $current_url );
			}

			wp_safe_redirect( $current_url );
			exit;
		} elseif ( 'trim_clicks' === $tab ) {

			$action = Helper::get_data( $_GET, 'action', '' );
			$status = Helper::get_data( $_GET, 'status', '' );

			$nonce_verified = wp_verify_nonce( Helper::get_data( $_GET, '_wpnonce', '' ), 'kc_us_clear_clicks' );

			$valid_actions = [
				'trim_clicks_older_than_30_days',
				'trim_clicks_older_than_60_days',
				'trim_clicks_older_than_90_days',
				'trim_all_clicks',
			];

			if ( in_array( $action, $valid_actions ) && $nonce_verified ) {
				if ( 'trim_clicks_older_than_30_days' === $action ) {
					$delete = US()->db->clicks->delete_clicks_older_than_days( 30 );
				} elseif ( 'trim_clicks_older_than_60_days' === $action ) {
					$delete = US()->db->clicks->delete_clicks_older_than_days( 60 );
				} elseif ( 'trim_clicks_older_than_90_days' === $action ) {
					$delete = US()->db->clicks->delete_clicks_older_than_days( 90 );
				} elseif ( 'trim_all_clicks' === $action ) {
					$delete = US()->db->clicks->delete_all_clicks();
				}

				$current_url = remove_query_arg( [ 'action', '_wpnonce' ] );

				if ( $delete ) {
					$current_url = add_query_arg( [ 'status' => 'success' ], $current_url );
				} else {
					$current_url = add_query_arg( [ 'status' => 'error' ], $current_url );
				}

				wp_safe_redirect( $current_url );
			}

			include_once KC_US_ADMIN_TEMPLATES_DIR . '/trim-clicks.php';
		} elseif ( 'rest-api' === $tab ) {
			if ( 'add-new-key' === $action ) {
				include_once KC_US_ADMIN_TEMPLATES_DIR . '/api-key-form.php';
			} elseif ( 'delete' === $action ) {
				$nonce = Helper::get_request_data( '_wpnonce' );

				if ( wp_verify_nonce( $nonce, 'us_action_nonce' ) ) {
					$id = Helper::get_data( $_GET, 'id', '' );
					if ( $id ) {
						$delete = US()->db->api_keys->delete( $id );
						if ( $delete ) {
							$value = [
								'status'  => 'success',
								'message' => __( 'API Key have been deleted successfully!', 'url-shortify' ),
							];

							\KaizenCoders\URL_Shortify\Cache::set_transient( 'notice', $value );
						}
					}
				}

                wp_safe_redirect(admin_url('admin.php?page=us_tools&tab=rest-api'));
                die();

			} elseif ( 'download' === $action ) {
				$nonce = Helper::get_request_data( '_wpnonce' );

				if ( wp_verify_nonce( $nonce, 'us_action_nonce' ) ) {
					$id = Helper::get_data( $_GET, 'id', '' );
					$ck = Helper::get_data( $_GET, 'ck', '' );
					Helper::handle_key_download( $id, $ck );
				}
			} else {
				include_once KC_US_ADMIN_TEMPLATES_DIR . '/api-keys.php';
			}

		} elseif ( 'awesome_products' === $tab ) {
			include_once KC_US_ADMIN_TEMPLATES_DIR . '/other-products.php';
		} ?>
    </div>

</div>
