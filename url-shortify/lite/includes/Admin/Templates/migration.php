<?php
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Migration', 'url-shortify' ); ?></h1>

	<div id="us-migration-notice" class="notice notice-success is-dismissible" style="display:none;">
		<p><strong id="us-migration-message"></strong></p>
	</div>

	<div id="us-migration-error" class="notice notice-error is-dismissible" style="display:none;">
		<p><strong id="us-error-message"></strong></p>
	</div>

	<div class="card" style="max-width: 760px; margin-top: 20px;">
		<h2 class="title"><?php esc_html_e( 'Link Central Migration', 'url-shortify' ); ?></h2>
		<p>
			<?php esc_html_e( 'Copy links from the Link Central custom post type into the URL Shortify links table. Existing slugs are skipped so the original data stays untouched.', 'url-shortify' ); ?>
		</p>

		<div class="us-migration-actions" style="margin-top: 16px; display:flex; align-items:center; gap:10px;">
			<button type="button" id="us-start-lc-migration" class="button button-primary">
				<?php esc_html_e( 'Migrate Now', 'url-shortify' ); ?>
			</button>
			<span class="spinner" id="us-lc-migration-spinner" style="float:none; margin:0;"></span>
		</div>

		<div id="us-lc-migration-status" style="margin-top: 16px; display:none;">
			<p style="margin: 0 0 8px 0;">
				<strong><?php esc_html_e( 'Processing...', 'url-shortify' ); ?></strong>
				<span id="us-lc-migrated-count">0</span>
				<?php esc_html_e( 'links migrated so far.', 'url-shortify' ); ?>
			</p>
			<p class="description" style="margin: 0;">
				<?php esc_html_e( 'The migration runs in batches of 50 to reduce timeout risk.', 'url-shortify' ); ?>
			</p>
		</div>
	</div>
</div>

<script>
( function( $ ) {
	$( function() {
		var nonce = '<?php echo esc_js( wp_create_nonce( 'us_migrate_link_central' ) ); ?>';
		var totalMigrated = 0;
		var offset = 0;

		var $button = $( '#us-start-lc-migration' );
		var $spinner = $( '#us-lc-migration-spinner' );
		var $notice = $( '#us-migration-notice' );
		var $message = $( '#us-migration-message' );
		var $errorNotice = $( '#us-migration-error' );
		var $errorMessage = $( '#us-error-message' );
		var $status = $( '#us-lc-migration-status' );
		var $countDisplay = $( '#us-lc-migrated-count' );

		function finishSuccess( message ) {
			$spinner.removeClass( 'is-active' );
			$button.prop( 'disabled', false );
			$status.hide();
			$message.text( message );
			$notice.fadeIn();
		}

		function finishError( message ) {
			$spinner.removeClass( 'is-active' );
			$button.prop( 'disabled', false );
			$status.hide();
			$errorMessage.text( message );
			$errorNotice.fadeIn();
		}

		function runBatch() {
			$.post( ajaxurl, {
				action: 'url_shortify_migrate_link_central',
				nonce: nonce,
				offset: offset
			} ).done( function( response ) {
				if ( ! response || ! response.success ) {
					finishError( ( response && response.data && response.data.message ) ? response.data.message : '<?php echo esc_js( __( 'Migration failed.', 'url-shortify' ) ); ?>' );
					return;
				}

				totalMigrated += parseInt( response.data.migrated || 0, 10 );
				offset = parseInt( response.data.offset || offset, 10 );
				$countDisplay.text( totalMigrated );

				if ( response.data.completed ) {
					if ( totalMigrated === 0 ) {
						finishSuccess( '<?php echo esc_js( __( 'Migration complete. All links already existed in URL Shortify.', 'url-shortify' ) ); ?>' );
					} else {
						finishSuccess( '<?php echo esc_js( __( 'Migration complete. Successfully imported', 'url-shortify' ) ); ?> ' + totalMigrated + ' <?php echo esc_js( __( 'links.', 'url-shortify' ) ); ?>' );
					}
					return;
				}

				runBatch();
			} ).fail( function() {
				finishError( '<?php echo esc_js( __( 'Server error occurred during migration. Please check your logs.', 'url-shortify' ) ); ?>' );
			} );
		}

		$button.on( 'click', function( e ) {
			e.preventDefault();

			totalMigrated = 0;
			offset = 0;

			$button.prop( 'disabled', true );
			$spinner.addClass( 'is-active' );
			$notice.hide();
			$errorNotice.hide();
			$status.show();
			$countDisplay.text( '0' );

			runBatch();
		} );
	} );
} )( jQuery );
</script>
