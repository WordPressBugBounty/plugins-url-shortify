<?php

namespace KaizenCoders\URL_Shortify\Ajax;

use KaizenCoders\URL_Shortify\Helper;

/**
 * Handle Link Preview AJAX requests.
 */
class Link_Preview {

	/**
	 * Row-action key for the Preview link.
	 *
	 * Intentionally namespaced rather than 'preview' to avoid matching WP
	 * core's wp-admin/css/list-tables.css rules for `.row-actions .preview`,
	 * which add visible spacing reserved for the post-preview action.
	 */
	const ACTION_KEY = 'kc-preview';

	/**
	 * OG metadata cache TTL.
	 */
	const OG_CACHE_TTL = DAY_IN_SECONDS;

	/**
	 * Initialize the AJAX hooks.
	 */
	public function init() {
		add_action( 'wp_ajax_get_link_preview_data', [ $this, 'get_link_preview_data' ] );
		add_filter( 'kc_us_filter_links_actions', [ $this, 'add_preview_row_action' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_preview_assets' ] );
		add_action( 'admin_footer', [ $this, 'render_preview_modal_html' ] );
	}

	/**
	 * Whether the current admin screen is the Links page that hosts the modal.
	 *
	 * @return bool
	 */
	private function is_links_screen() {
		return Helper::is_plugin_admin_screen( 'url-shortify_page_us_links' );
	}

	/**
	 * Whether the current user can view link details / trigger OG previews.
	 *
	 * @return bool
	 */
	private function current_user_can_preview() {
		return current_user_can( 'read' );
	}

	/**
	 * Enqueue preview modal assets on the Links screen.
	 *
	 * @param string $hook Current admin hook suffix.
	 */
	public function enqueue_preview_assets( $hook ) {
		if ( 'url-shortify_page_us_links' !== $hook || ! $this->is_links_screen() ) {
			return;
		}

		// Localised data — replaces the previous interpolation-into-JS pattern
		// and makes every user-facing string translatable.
		wp_localize_script(
			'url-shortify-admin',
			'kcUsLinkPreview',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'kc_us_link_preview_nonce' ),
				'i18n'    => [
					'visualPreviewCard'   => __( 'Visual Preview Card', 'url-shortify' ),
					'loadingPreview'      => __( 'Loading preview', 'url-shortify' ),
					'fetchingDescription' => __( 'Fetching the visual card for this link.', 'url-shortify' ),
					'copyTargetUrl'       => __( 'Copy Target URL', 'url-shortify' ),
					'openInNewTab'        => __( 'Open in New Tab', 'url-shortify' ),
					'untitledPreview'     => __( 'Untitled preview', 'url-shortify' ),
					'noDescription'       => __( 'No description is available for this destination.', 'url-shortify' ),
					'noPreviewImage'      => __( 'No preview image available', 'url-shortify' ),
					'previewImageAlt'     => __( 'Preview image', 'url-shortify' ),
					'closePreview'        => __( 'Close preview', 'url-shortify' ),
					'unableToLoad'        => __( 'Unable to load the link preview.', 'url-shortify' ),
					'copied'              => __( 'Copied!', 'url-shortify' ),
				],
			]
		);

		$css = <<<'CSS'
#kc-us-preview-modal-container {
	position: fixed;
	inset: 0;
	z-index: 999999;
	display: none;
	align-items: center;
	justify-content: center;
	padding: 24px;
	box-sizing: border-box;
	background: rgba(15, 23, 42, 0.72);
	backdrop-filter: blur(12px);
	-webkit-backdrop-filter: blur(12px);
}

#kc-us-preview-modal-container.is-open {
	display: flex;
}

#kc-us-preview-modal-container .kc-us-preview-modal__panel {
	position: relative;
	width: min(760px, 100%);
	max-height: calc(100vh - 48px);
	overflow: auto;
	border-radius: 24px;
	border: 1px solid rgba(148, 163, 184, 0.18);
	background: linear-gradient(180deg, rgba(15, 23, 42, 0.98), rgba(17, 24, 39, 0.98));
	color: #e5e7eb;
	box-shadow: 0 30px 90px rgba(0, 0, 0, 0.55);
	transform: translateY(10px) scale(0.985);
	animation: kcUsPreviewIn 180ms ease-out forwards;
}

#kc-us-preview-modal-container .kc-us-preview-modal__backdrop {
	position: absolute;
	inset: 0;
	z-index: 0;
}

#kc-us-preview-modal-container .kc-us-preview-card {
	position: relative;
	z-index: 1;
	display: grid;
	grid-template-columns: 1fr;
	overflow: hidden;
}

#kc-us-preview-modal-container .kc-us-preview-card__media {
	position: relative;
	background: #0f172a;
	aspect-ratio: 16 / 9;
	overflow: hidden;
}

#kc-us-preview-modal-container .kc-us-preview-card__media img {
	display: block;
	width: 100%;
	height: 100%;
	object-fit: cover;
	object-position: center;
}

#kc-us-preview-modal-container .kc-us-preview-card__placeholder {
	display: flex;
	align-items: center;
	justify-content: center;
	height: 100%;
	padding: 24px;
	background:
		radial-gradient(circle at top left, rgba(59, 130, 246, 0.22), transparent 40%),
		linear-gradient(135deg, rgba(15, 23, 42, 1), rgba(30, 41, 59, 1));
	color: rgba(226, 232, 240, 0.78);
	font-size: 14px;
	letter-spacing: 0.02em;
	text-transform: uppercase;
}

#kc-us-preview-modal-container .kc-us-preview-card__body {
	padding: 24px 24px 22px;
	background: linear-gradient(180deg, rgba(15, 23, 42, 0.15), rgba(15, 23, 42, 0));
}

#kc-us-preview-modal-container .kc-us-preview-card__eyebrow {
	margin: 0 0 10px;
	font-size: 12px;
	font-weight: 700;
	letter-spacing: 0.14em;
	text-transform: uppercase;
	color: #93c5fd;
}

#kc-us-preview-modal-container .kc-us-preview-card__title {
	margin: 0 0 12px;
	font-size: 22px;
	line-height: 1.25;
	font-weight: 700;
	color: #f8fafc;
}

#kc-us-preview-modal-container .kc-us-preview-card__description {
	margin: 0 0 18px;
	font-size: 15px;
	line-height: 1.7;
	color: rgba(226, 232, 240, 0.85);
	word-break: break-word;
}

#kc-us-preview-modal-container .kc-us-preview-card__site {
	display: inline-flex;
	align-items: center;
	padding: 6px 12px;
	margin-bottom: 18px;
	border-radius: 999px;
	background: rgba(59, 130, 246, 0.14);
	border: 1px solid rgba(96, 165, 250, 0.18);
	color: #bfdbfe;
	font-size: 13px;
	font-weight: 600;
}

#kc-us-preview-modal-container .kc-us-preview-card__actions {
	display: flex;
	flex-wrap: wrap;
	gap: 10px;
}

#kc-us-preview-modal-container .kc-us-preview-btn,
#kc-us-preview-modal-container .kc-us-preview-link {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	min-height: 40px;
	padding: 0 14px;
	border-radius: 12px;
	font-size: 14px;
	font-weight: 600;
	text-decoration: none;
	transition: transform 140ms ease, background-color 140ms ease, border-color 140ms ease, color 140ms ease;
}

#kc-us-preview-modal-container .kc-us-preview-btn {
	border: 1px solid rgba(96, 165, 250, 0.28);
	background: rgba(37, 99, 235, 0.88);
	color: #fff;
	cursor: pointer;
}

#kc-us-preview-modal-container .kc-us-preview-btn:hover,
#kc-us-preview-modal-container .kc-us-preview-link:hover {
	transform: translateY(-1px);
}

#kc-us-preview-modal-container .kc-us-preview-link {
	border: 1px solid rgba(148, 163, 184, 0.22);
	background: rgba(15, 23, 42, 0.28);
	color: #e2e8f0;
}

#kc-us-preview-modal-container .kc-us-preview-modal__close {
	position: absolute;
	top: 14px;
	right: 14px;
	z-index: 2;
	width: 36px;
	height: 36px;
	border: 0;
	border-radius: 999px;
	background: rgba(15, 23, 42, 0.65);
	color: #fff;
	font-size: 24px;
	line-height: 1;
	cursor: pointer;
}

#kc-us-preview-modal-container .kc-us-preview-modal__loading {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	min-width: 320px;
	min-height: 220px;
	padding: 28px;
	text-align: center;
	color: #e2e8f0;
}

#kc-us-preview-modal-container .kc-us-preview-modal__spinner {
	width: 32px;
	height: 32px;
	margin-bottom: 14px;
	border: 3px solid rgba(148, 163, 184, 0.22);
	border-top-color: #60a5fa;
	border-radius: 50%;
	animation: kcUsPreviewSpin 900ms linear infinite;
}

@keyframes kcUsPreviewIn {
	from {
		opacity: 0;
		transform: translateY(18px) scale(0.97);
	}
	to {
		opacity: 1;
		transform: translateY(0) scale(1);
	}
}

@keyframes kcUsPreviewSpin {
	to {
		transform: rotate(360deg);
	}
}

@media (max-width: 782px) {
	#kc-us-preview-modal-container {
		padding: 16px;
	}

	#kc-us-preview-modal-container .kc-us-preview-card__body {
		padding: 20px 18px 18px;
	}

	#kc-us-preview-modal-container .kc-us-preview-card__title {
		font-size: 19px;
	}
}
CSS;

		$script = <<<JS
jQuery( function( $ ) {
	var settings = window.kcUsLinkPreview || {};
	var ajaxUrl = settings.ajaxUrl;
	var nonce = settings.nonce;
	var i18n = settings.i18n || {};
	var closeKeyNamespace = '.kcUsPreviewModal';

	function escapeHtml( value ) {
		return String( value || '' )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#39;' );
	}

	function getContainer() {
		var \$container = $( '#kc-us-preview-modal-container' );

		if ( ! \$container.length ) {
			\$container = $( '<div id="kc-us-preview-modal-container" aria-hidden="true" style="display:none;"></div>' ).appendTo( document.body );
		}

		return \$container;
	}

	function hideModal() {
		var \$container = getContainer();

		\$container.removeClass( 'is-open' ).attr( 'aria-hidden', 'true' ).hide().empty();
		$( 'body' ).removeClass( 'kc-us-preview-open' );
		$( document ).off( 'keydown' + closeKeyNamespace );
	}

	function bindCloseEvents() {
		$( document ).off( 'keydown' + closeKeyNamespace ).on( 'keydown' + closeKeyNamespace, function( event ) {
			if ( 27 === event.keyCode ) {
				hideModal();
			}
		} );
	}

	function copyToClipboard( text, \$button ) {
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text ).then( function() {
				var originalText = \$button.text();
				\$button.text( i18n.copied || 'Copied!' );
				window.setTimeout( function() {
					\$button.text( originalText );
				}, 1400 );
			} );
			return;
		}

		var \$fallback = $( '<textarea readonly></textarea>' ).val( text ).css( {
			position: 'fixed',
			left: '-9999px',
			top: '0'
		} ).appendTo( document.body );

		\$fallback[0].select();
		try {
			document.execCommand( 'copy' );
		} catch ( error ) {}
		\$fallback.remove();
	}

	function renderLoadingState() {
		var \$container = getContainer();

		\$container
			.html(
				'<div class="kc-us-preview-modal__backdrop" data-kc-us-preview-close="true"></div>' +
				'<div class="kc-us-preview-modal__panel" role="dialog" aria-modal="true" aria-labelledby="kc-us-preview-card-title">' +
					'<button type="button" class="kc-us-preview-modal__close" aria-label="' + escapeHtml( i18n.closePreview || 'Close preview' ) + '" data-kc-us-preview-close="true">&times;</button>' +
					'<div class="kc-us-preview-modal__loading">' +
						'<span class="kc-us-preview-modal__spinner" aria-hidden="true"></span>' +
						'<strong>' + escapeHtml( i18n.loadingPreview || 'Loading preview' ) + '</strong>' +
						'<span>' + escapeHtml( i18n.fetchingDescription || 'Fetching the visual card for this link.' ) + '</span>' +
					'</div>' +
				'</div>'
			)
			.css( 'display', 'flex' )
			.addClass( 'is-open' )
			.attr( 'aria-hidden', 'false' );

		$( 'body' ).addClass( 'kc-us-preview-open' );
		bindCloseEvents();
	}

	function renderCard( data ) {
		var targetUrl = data.target_url || '';
		var imageAlt = escapeHtml( data.og_title || i18n.previewImageAlt || 'Preview image' );
		var imageHtml = data.og_image
			? '<div class="kc-us-preview-card__media"><img src="' + escapeHtml( data.og_image ) + '" alt="' + imageAlt + '"></div>'
			: '<div class="kc-us-preview-card__media"><div class="kc-us-preview-card__placeholder">' + escapeHtml( i18n.noPreviewImage || 'No preview image available' ) + '</div></div>';

		var title = escapeHtml( data.og_title || i18n.untitledPreview || 'Untitled preview' );
		var description = escapeHtml( data.og_description || i18n.noDescription || 'No description is available for this destination.' );
		var siteName = escapeHtml( data.og_site_name || '' );
		var openUrl = escapeHtml( targetUrl );

		var html =
			'<div class="kc-us-preview-modal__backdrop" data-kc-us-preview-close="true"></div>' +
			'<div class="kc-us-preview-modal__panel" role="dialog" aria-modal="true" aria-labelledby="kc-us-preview-card-title">' +
				'<button type="button" class="kc-us-preview-modal__close" aria-label="' + escapeHtml( i18n.closePreview || 'Close preview' ) + '" data-kc-us-preview-close="true">&times;</button>' +
				'<div class="kc-us-preview-card">' +
					imageHtml +
					'<div class="kc-us-preview-card__body">' +
						'<p class="kc-us-preview-card__eyebrow">' + escapeHtml( i18n.visualPreviewCard || 'Visual Preview Card' ) + '</p>' +
						'<h3 id="kc-us-preview-card-title" class="kc-us-preview-card__title">' + title + '</h3>' +
						'<p class="kc-us-preview-card__description">' + description + '</p>' +
						( siteName ? '<div class="kc-us-preview-card__site">' + siteName + '</div>' : '' ) +
						'<div class="kc-us-preview-card__actions">' +
							'<button type="button" class="kc-us-preview-btn kc-us-preview-copy" data-target-url="' + openUrl + '">' + escapeHtml( i18n.copyTargetUrl || 'Copy Target URL' ) + '</button>' +
							( targetUrl ? '<a class="kc-us-preview-link" href="' + openUrl + '" target="_blank" rel="noopener noreferrer">' + escapeHtml( i18n.openInNewTab || 'Open in New Tab' ) + '</a>' : '' ) +
						'</div>' +
					'</div>' +
				'</div>' +
			'</div>';

		getContainer().html( html ).css( 'display', 'flex' ).addClass( 'is-open' ).attr( 'aria-hidden', 'false' );
		$( 'body' ).addClass( 'kc-us-preview-open' );
		bindCloseEvents();
	}

	$( document ).on( 'click', '.kc-us-preview-trigger', function( event ) {
		event.preventDefault();

		var linkId = $( this ).data( 'link-id' );

		if ( ! linkId ) {
			return;
		}

		renderLoadingState();

		$.ajax( {
			url: ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'get_link_preview_data',
				security: nonce,
				link_id: linkId
			}
		} ).done( function( response ) {
			if ( response && response.success && response.data ) {
				renderCard( response.data );
				return;
			}

			hideModal();
			window.alert( ( response && response.data && response.data.message ) ? response.data.message : ( i18n.unableToLoad || 'Unable to load the link preview.' ) );
		} ).fail( function() {
			hideModal();
			window.alert( i18n.unableToLoad || 'Unable to load the link preview.' );
		} );
	} );

	$( document ).on( 'click', '[data-kc-us-preview-close="true"]', function() {
		hideModal();
	} );

	$( document ).on( 'click', '.kc-us-preview-copy', function() {
		var targetUrl = $( this ).data( 'target-url' );

		if ( targetUrl ) {
			copyToClipboard( targetUrl, $( this ) );
		}
	} );
} );
JS;

		wp_add_inline_style( 'url-shortify-admin', $css );
		wp_add_inline_script( 'url-shortify-admin', $script, 'after' );
	}

	/**
	 * AJAX handler to fetch and cache Open Graph preview data for a link.
	 */
	public function get_link_preview_data() {
		check_ajax_referer( 'kc_us_link_preview_nonce', 'security' );

		if ( ! $this->current_user_can_preview() ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'url-shortify' ) ] );
		}

		$link_id = absint( Helper::get_request_data( 'link_id', 0, true ) );

		if ( empty( $link_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid link ID.', 'url-shortify' ) ] );
		}

		$link = US()->db->links->get_by_id( $link_id );

		if ( empty( $link ) ) {
			wp_send_json_error( [ 'message' => __( 'Link not found.', 'url-shortify' ) ] );
		}

		$last_fetched = Helper::get_data( $link, 'og_last_fetched', '' );

		// Check if valid cached data exists (less than OG_CACHE_TTL seconds old).
		$is_cached = false;
		if ( ! empty( $last_fetched ) ) {
			$age = time() - strtotime( $last_fetched );
			if ( $age >= 0 && $age < self::OG_CACHE_TTL ) {
				$is_cached = true;
			}
		}

		// Fetch new OG data if the cache is expired or missing.
		if ( ! $is_cached ) {
			$og_data                    = Helper::kc_us_get_og_metadata( $link['url'] );
			$og_data['og_last_fetched'] = current_time( 'mysql' );

			US()->db->links->update( $link_id, $og_data );

			$link = array_merge( $link, $og_data );
		}

		$response = [
			'short_url'      => Helper::get_short_link( $link['slug'], $link ),
			'target_url'     => $link['url'],
			'total_clicks'   => intval( Helper::get_data( $link, 'total_clicks', 0 ) ),
			'unique_clicks'  => intval( Helper::get_data( $link, 'unique_clicks', 0 ) ),
			'og_title'       => Helper::get_data( $link, 'og_title', '' ),
			'og_description' => Helper::get_data( $link, 'og_description', '' ),
			'og_image'       => Helper::get_data( $link, 'og_image', '' ),
			'og_site_name'   => Helper::get_data( $link, 'og_site_name', '' ),
			'from_cache'     => $is_cached,
		];

		wp_send_json_success( $response );
	}

	/**
	 * Add the Preview row action to the links table.
	 *
	 * Inserts after the stats / statistics action if present, otherwise
	 * appends. The HTML template is hoisted into a static so the __()
	 * lookup happens once per request rather than once per row.
	 *
	 * @param array        $actions
	 * @param array|object $link
	 *
	 * @return array
	 */
	public function add_preview_row_action( $actions, $link ) {
		static $template = null;

		if ( null === $template ) {
			$template = '<a href="javascript:void(0);" class="kc-us-preview-trigger" data-link-id="%d">'
				. esc_html__( 'Preview', 'url-shortify' )
				. '</a>';
		}

		$link_id = absint( is_object( $link ) ? $link->id : Helper::get_data( $link, 'id', 0 ) );

		$preview_html = sprintf( $template, $link_id );

		$keys      = array_keys( $actions );
		$stats_pos = array_search( 'stats', $keys, true );
		if ( false === $stats_pos ) {
			$stats_pos = array_search( 'statistics', $keys, true );
		}

		if ( false === $stats_pos ) {
			$actions[ self::ACTION_KEY ] = $preview_html;

			return $actions;
		}

		return array_merge(
			array_slice( $actions, 0, $stats_pos + 1, true ),
			[ self::ACTION_KEY => $preview_html ],
			array_slice( $actions, $stats_pos + 1, null, true )
		);
	}

	/**
	 * Print an empty hidden div for the modal at the bottom of the Links page.
	 *
	 * Gated to the Links admin screen so we don't pollute the DOM of every
	 * other wp-admin page.
	 */
	public function render_preview_modal_html() {
		if ( ! $this->is_links_screen() ) {
			return;
		}

		echo '<div id="kc-us-preview-modal-container" style="display: none;" aria-hidden="true"></div>';
	}
}
