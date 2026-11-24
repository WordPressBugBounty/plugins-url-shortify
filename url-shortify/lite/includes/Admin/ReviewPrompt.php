<?php
/**
 * A friendly "would you leave us a review?" popup on URL Shortify screens.
 *
 * Replaces the old admin notice in Feedback.php and keeps using its user meta
 * keys, so anyone who already reviewed or asked not to be asked again is
 * never asked again.
 *
 * Kept deliberately polite: it only appears to administrators who have
 * actually used the plugin for a while, at most once a day, never on the same
 * page as a promotional banner, and every way of closing it is honoured.
 *
 * @package KaizenCoders\URL_Shortify\Admin
 */

namespace KaizenCoders\URL_Shortify\Admin;

use KaizenCoders\URL_Shortify\Admin\Promotions\PromoBanner;
use KaizenCoders\URL_Shortify\Helper;

class ReviewPrompt {

	const AJAX_ACTION  = 'kc_us_review_prompt';
	const NONCE_ACTION = 'kc_us_review_prompt';

	const REVIEW_URL = 'https://wordpress.org/support/plugin/url-shortify/reviews/#new-post';

	// Linked from the popup and the Thanksgiving email.
	const KAIZENCODERS_URL = 'https://kaizencoders.com';
	const PLUGIN_URL       = 'https://wordpress.org/plugins/url-shortify';
	const CONTACT_URL      = 'https://kaizencoders.com/contact';

	// Shared with the old notice in Feedback.php.
	const META_REVIEWED    = 'kc_us_feedback_review_done';
	const META_ALREADY_DID = 'kc_us_feedback_already_did';
	const META_NEVER       = 'kc_us_feedback_do_not_ask_again';
	const META_LATER       = 'kc_us_feedback_maybe_later';

	// When the popup was last displayed to this user.
	const META_LAST_SHOWN = 'kc_us_review_prompt_last_shown';

	const MIN_INSTALL_DAYS = 14;
	const MIN_LINKS        = 3;
	const SNOOZE_DAYS      = 30;

	/**
	 * Register hooks.
	 *
	 * @since 2.7.3
	 */
	public function init() {
		// The popup replaces the old notice; showing both would ask twice.
		add_filter( 'kc_us_enable_review_notice', '__return_false' );

		add_action( 'admin_footer', [ $this, 'maybe_render' ] );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, [ $this, 'handle_response' ] );
	}

	/**
	 * Responses the popup can send, mapped to the user meta they set.
	 *
	 * @return array
	 *
	 * @since 2.7.3
	 */
	public static function get_responses() {
		return [
			'review'      => self::META_REVIEWED,
			'already_did' => self::META_ALREADY_DID,
			'never'       => self::META_NEVER,
			'later'       => self::META_LATER,
		];
	}

	/* ---------------------------------------------------------------------
	 * Show logic
	 * ------------------------------------------------------------------ */

	/**
	 * Should the popup render?
	 *
	 * Free of WordPress lookups so it can be reasoned about from its inputs.
	 *
	 * @param array $context {
	 *     @type bool $can_manage         Current user is an administrator.
	 *     @type bool $on_plugin_screen   Current screen belongs to URL Shortify.
	 *     @type bool $promo_showing      A promotional banner renders on this page.
	 *     @type bool $answered           Reviewed, already did, or never ask again.
	 *     @type int  $later_at           When "maybe later" was chosen, 0 if never.
	 *     @type int  $last_shown         When the popup was last displayed, 0 if never.
	 *     @type int  $days_since_install Whole days since install.
	 *     @type int  $total_links        Links on this site.
	 *     @type int  $now                Unix timestamp.
	 * }
	 *
	 * @return bool
	 *
	 * @since 2.7.3
	 */
	public static function should_show( $context ) {
		$c = wp_parse_args(
			(array) $context,
			[
				'can_manage'         => false,
				'on_plugin_screen'   => false,
				'promo_showing'      => false,
				'answered'           => false,
				'later_at'           => 0,
				'last_shown'         => 0,
				'days_since_install' => 0,
				'total_links'        => 0,
				'now'                => time(),
			]
		);

		if ( ! $c['can_manage'] || ! $c['on_plugin_screen'] || $c['answered'] ) {
			return false;
		}

		// One ask per page: an offer and a review request together is too much.
		if ( $c['promo_showing'] ) {
			return false;
		}

		// Only ask people who have had time to form an opinion.
		if ( (int) $c['days_since_install'] < self::MIN_INSTALL_DAYS || (int) $c['total_links'] < self::MIN_LINKS ) {
			return false;
		}

		$now = (int) $c['now'];

		if ( (int) $c['later_at'] > 0 && ( $now - (int) $c['later_at'] ) < self::SNOOZE_DAYS * DAY_IN_SECONDS ) {
			return false;
		}

		// Ignored without an answer (navigated away): wait a day before asking again.
		if ( (int) $c['last_shown'] > 0 && ( $now - (int) $c['last_shown'] ) < DAY_IN_SECONDS ) {
			return false;
		}

		return true;
	}

	/**
	 * Has this user already answered for good?
	 *
	 * @param int $user_id
	 *
	 * @return bool
	 *
	 * @since 2.7.3
	 */
	public static function has_answered( $user_id ) {
		foreach ( [ self::META_REVIEWED, self::META_ALREADY_DID, self::META_NEVER ] as $key ) {
			if ( get_user_meta( $user_id, $key, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Gather everything should_show() needs for the current request.
	 *
	 * Cheap checks run first so the common case costs no database queries.
	 *
	 * @return array
	 *
	 * @since 2.7.3
	 */
	public static function build_context() {
		$user_id = get_current_user_id();

		$context = [
			'can_manage'       => current_user_can( 'manage_options' ),
			'on_plugin_screen' => Helper::is_plugin_admin_screen(),
			'answered'         => $user_id > 0 ? self::has_answered( $user_id ) : true,
			'now'              => time(),
		];

		if ( ! $context['can_manage'] || ! $context['on_plugin_screen'] || $context['answered'] ) {
			return $context;
		}

		$later = get_user_meta( $user_id, self::META_LATER, true );

		$context['later_at']           = $later ? (int) get_user_meta( $user_id, self::META_LATER . '_time', true ) : 0;
		$context['last_shown']         = (int) get_user_meta( $user_id, self::META_LAST_SHOWN, true );
		$context['days_since_install'] = PromoBanner::get_days_since_install();
		$context['total_links']        = (int) US()->db->links->count();
		$context['promo_showing']      = null !== PromoBanner::get_active_campaign( PromoBanner::build_context() );

		return $context;
	}

	/**
	 * Links created and clicks recorded on this site, for a personal touch.
	 *
	 * Uses the counters stored on each link, so it is one cheap query.
	 *
	 * @return array { @type int $links, @type int $clicks }
	 *
	 * @since 2.7.3
	 */
	public static function get_usage_stats() {
		global $wpdb;

		$table = $wpdb->prefix . 'kc_us_links';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( "SELECT COUNT(*) AS links, COALESCE(SUM(total_clicks), 0) AS clicks FROM {$table}", ARRAY_A );

		return [
			'links'  => isset( $row['links'] ) ? (int) $row['links'] : 0,
			'clicks' => isset( $row['clicks'] ) ? (int) $row['clicks'] : 0,
		];
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	/**
	 * Print the popup when it applies.
	 *
	 * @return void
	 *
	 * @since 2.7.3
	 */
	public function maybe_render() {
		/**
		 * Turn the review popup off entirely.
		 *
		 * @param bool $show Whether the popup may be shown.
		 *
		 * @since 2.7.3
		 */
		if ( ! apply_filters( 'kc_us_show_review_prompt', true ) ) {
			return;
		}

		// Preview: show it regardless of the rules, and remember nothing.
		if ( self::is_preview() ) {
			$this->render( self::get_usage_stats(), true );

			return;
		}

		if ( ! self::should_show( self::build_context() ) ) {
			return;
		}

		update_user_meta( get_current_user_id(), self::META_LAST_SHOWN, time() );

		$this->render( self::get_usage_stats() );
	}

	/**
	 * Is an administrator asking to preview the popup?
	 *
	 * Add `&kc_us_preview=review-popup` to any URL Shortify screen. Nothing is
	 * recorded in preview: not that it was shown, and not the answer.
	 *
	 * @return bool
	 *
	 * @since 2.7.3
	 */
	public static function is_preview() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only preview.
		$preview = isset( $_GET['kc_us_preview'] ) ? sanitize_key( wp_unslash( $_GET['kc_us_preview'] ) ) : '';

		return 'review-popup' === $preview
			&& current_user_can( 'manage_options' )
			&& Helper::is_plugin_admin_screen();
	}

	/**
	 * Link that previews the popup on the URL Shortify dashboard.
	 *
	 * @return string
	 *
	 * @since 2.7.3
	 */
	public static function get_preview_url() {
		return admin_url( 'admin.php?page=url_shortify&kc_us_preview=review-popup' );
	}

	/**
	 * Popup markup.
	 *
	 * @param array $stats   See get_usage_stats().
	 * @param bool  $preview Do not save the answer.
	 *
	 * @return void
	 *
	 * @since 2.7.3
	 */
	public function render( $stats, $preview = false ) {
		$links  = (int) Helper::get_data( $stats, 'links', 0 );
		$clicks = (int) Helper::get_data( $stats, 'clicks', 0 );

		$current_user = wp_get_current_user();
		$first_name   = ! empty( $current_user->first_name ) ? $current_user->first_name : $current_user->display_name;
		$nonce        = wp_create_nonce( self::NONCE_ACTION );

		if ( $clicks > 0 ) {
			$stat_line = sprintf(
				/* translators: 1: number of links, 2: number of clicks */
				_n(
					'You have created %1$s short link, and it has been clicked %2$s times.',
					'You have created %1$s short links, and they have been clicked %2$s times.',
					$links,
					'url-shortify'
				),
				'<strong>' . esc_html( number_format_i18n( $links ) ) . '</strong>',
				'<strong>' . esc_html( number_format_i18n( $clicks ) ) . '</strong>'
			);
		} else {
			$stat_line = sprintf(
				/* translators: %s: number of links */
				_n( 'You have created %s short link with URL Shortify.', 'You have created %s short links with URL Shortify.', $links, 'url-shortify' ),
				'<strong>' . esc_html( number_format_i18n( $links ) ) . '</strong>'
			);
		}
		?>
		<div id="kc-us-review-prompt" class="kc-us-rp" role="dialog" aria-modal="true" aria-labelledby="kc-us-rp-title"
			data-nonce="<?php echo esc_attr( $nonce ); ?>" data-preview="<?php echo $preview ? '1' : '0'; ?>">
			<div class="kc-us-rp__backdrop" data-kc-us-rp="later"></div>
			<div class="kc-us-rp__card">
				<button type="button" class="kc-us-rp__close" data-kc-us-rp="later" aria-label="<?php esc_attr_e( 'Close', 'url-shortify' ); ?>">×</button>

				<div class="kc-us-rp__heart" aria-hidden="true">💜</div>

				<h2 id="kc-us-rp-title" class="kc-us-rp__title">
					<?php
					/* translators: %s: user's first name */
					printf( esc_html__( 'Hi %s, can we ask a small favour?', 'url-shortify' ), esc_html( $first_name ) );
					?>
				</h2>

				<p class="kc-us-rp__stat"><?php echo wp_kses( $stat_line, [ 'strong' => [] ] ); ?></p>

				<p class="kc-us-rp__body">
					<?php
					echo wp_kses(
						sprintf(
							/* translators: 1: "URL Shortify" linked to its plugin page, 2: "KaizenCoders" linked to its website */
							__( '%1$s is built and supported by a small team at %2$s. We read every review, and each one helps another site owner decide to give us a try. On a long day, a kind word from someone who uses what we make honestly keeps us going.', 'url-shortify' ),
							'<a href="' . esc_url( self::PLUGIN_URL ) . '" target="_blank" rel="noopener">' . esc_html__( 'URL Shortify', 'url-shortify' ) . '</a>',
							'<a href="' . esc_url( self::KAIZENCODERS_URL ) . '" target="_blank" rel="noopener">' . esc_html__( 'KaizenCoders', 'url-shortify' ) . '</a>'
						),
						[ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ]
					);
					?>
				</p>

				<p class="kc-us-rp__body">
					<?php esc_html_e( 'If URL Shortify has earned it, would you take two minutes to leave a 5-star review on WordPress.org? It means more to us than you might think.', 'url-shortify' ); ?>
				</p>

				<div class="kc-us-rp__stars" aria-hidden="true">★★★★★</div>

				<div class="kc-us-rp__actions">
					<a class="kc-us-rp__primary" href="<?php echo esc_url( self::REVIEW_URL ); ?>" target="_blank" rel="noopener" data-kc-us-rp="review">
						<?php esc_html_e( 'Sure, happy to help!', 'url-shortify' ); ?>
					</a>
					<button type="button" class="kc-us-rp__secondary" data-kc-us-rp="later"><?php esc_html_e( 'Maybe later', 'url-shortify' ); ?></button>
					<button type="button" class="kc-us-rp__secondary" data-kc-us-rp="already_did"><?php esc_html_e( 'I already did 🙌', 'url-shortify' ); ?></button>
				</div>

				<p class="kc-us-rp__footer">
					<?php esc_html_e( 'Something not working for you?', 'url-shortify' ); ?>
					<a href="<?php echo esc_url( self::CONTACT_URL ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Tell us and we will fix it.', 'url-shortify' ); ?></a>
					&nbsp;·&nbsp;
					<button type="button" class="kc-us-rp__link" data-kc-us-rp="never"><?php esc_html_e( "Don't ask again", 'url-shortify' ); ?></button>
				</p>
			</div>
		</div>

		<style>
			.kc-us-rp { position: fixed; inset: 0; z-index: 100000; display: flex; align-items: center; justify-content: center; padding: 16px; }
			.kc-us-rp__backdrop { position: absolute; inset: 0; background: rgba(17, 24, 39, .55); animation: kc-us-rp-fade .2s ease; }
			.kc-us-rp__card {
				position: relative; box-sizing: border-box; width: 100%; max-width: 480px; max-height: calc(100vh - 32px); overflow: auto;
				background: #fff; border-radius: 14px; padding: 32px 30px 22px; text-align: center;
				box-shadow: 0 24px 60px rgba(17, 24, 39, .3); animation: kc-us-rp-pop .25s ease;
			}
			.kc-us-rp__close { position: absolute; top: 10px; right: 12px; background: none; border: 0; font-size: 24px; line-height: 1; color: #9ca3af; cursor: pointer; padding: 4px 8px; border-radius: 6px; }
			.kc-us-rp__close:hover { background: #f3f4f6; color: #374151; }
			.kc-us-rp__heart { font-size: 40px; line-height: 1; margin-bottom: 10px; }
			.kc-us-rp__title { font-size: 20px; line-height: 1.35; margin: 0 0 10px; color: #111827; }
			.kc-us-rp__stat { font-size: 14px; color: #4f46e5; background: #eef2ff; border-radius: 8px; padding: 10px 12px; margin: 0 0 14px; }
			.kc-us-rp__body { font-size: 14px; line-height: 1.6; color: #4b5563; margin: 0 0 10px; }
			.kc-us-rp__body a { color: #4f46e5; font-weight: 600; text-decoration: none; }
			.kc-us-rp__body a:hover { text-decoration: underline; }
			.kc-us-rp__stars { font-size: 28px; letter-spacing: 4px; color: #f59e0b; margin: 8px 0 16px; }
			.kc-us-rp__actions { display: flex; flex-direction: column; gap: 8px; }
			.kc-us-rp__primary {
				display: block; background: #4f46e5; color: #fff !important; font-size: 15px; font-weight: 600; text-decoration: none;
				padding: 12px 18px; border-radius: 8px; transition: background .15s ease;
			}
			.kc-us-rp__primary:hover, .kc-us-rp__primary:focus { background: #4338ca; }
			.kc-us-rp__secondary { background: #fff; border: 1px solid #e5e7eb; color: #374151; font-size: 14px; padding: 10px 18px; border-radius: 8px; cursor: pointer; }
			.kc-us-rp__secondary:hover { background: #f9fafb; }
			.kc-us-rp__footer { font-size: 12px; color: #9ca3af; margin: 16px 0 0; }
			.kc-us-rp__footer a { color: #6b7280; }
			.kc-us-rp__link { background: none; border: 0; padding: 0; color: #9ca3af; font-size: 12px; text-decoration: underline; cursor: pointer; }
			.kc-us-rp__link:hover { color: #6b7280; }
			@keyframes kc-us-rp-fade { from { opacity: 0; } to { opacity: 1; } }
			@keyframes kc-us-rp-pop { from { opacity: 0; transform: translateY(12px) scale(.98); } to { opacity: 1; transform: none; } }
			@media (prefers-reduced-motion: reduce) { .kc-us-rp__backdrop, .kc-us-rp__card { animation: none; } }
		</style>

		<script>
			( function () {
				var root = document.getElementById( 'kc-us-review-prompt' );

				if ( ! root ) {
					return;
				}

				var answered = false;

				function respond( answer ) {
					if ( answered ) {
						return;
					}
					answered = true;

					root.style.display = 'none';
					document.removeEventListener( 'keydown', onKey );

					if ( '1' === root.getAttribute( 'data-preview' ) ) {
						return;
					}

					var body = new URLSearchParams();
					body.append( 'action', <?php echo wp_json_encode( self::AJAX_ACTION ); ?> );
					body.append( 'nonce', root.getAttribute( 'data-nonce' ) || '' );
					body.append( 'response', answer );

					fetch( ajaxurl, {
						method:      'POST',
						credentials: 'same-origin',
						headers:     { 'Content-Type': 'application/x-www-form-urlencoded' },
						body:        body.toString()
					} ).catch( function () { /* best effort, the popup is already closed */ } );
				}

				function onKey( e ) {
					if ( 'Escape' === e.key ) {
						respond( 'later' );
					}
				}

				root.addEventListener( 'click', function ( e ) {
					var target = e.target.closest( '[data-kc-us-rp]' );

					if ( target ) {
						// The review link still opens in its new tab.
						respond( target.getAttribute( 'data-kc-us-rp' ) );
					}
				} );

				document.addEventListener( 'keydown', onKey );

				var primary = root.querySelector( '.kc-us-rp__primary' );
				if ( primary ) {
					primary.focus();
				}
			} )();
		</script>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Response
	 * ------------------------------------------------------------------ */

	/**
	 * Save the user's answer.
	 *
	 * @return void
	 *
	 * @since 2.7.3
	 */
	public function handle_response() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
		}

		$response  = isset( $_POST['response'] ) ? sanitize_key( wp_unslash( $_POST['response'] ) ) : '';
		$responses = self::get_responses();

		if ( ! isset( $responses[ $response ] ) ) {
			wp_send_json_error( [ 'message' => 'unknown_response' ], 400 );
		}

		$user_id = get_current_user_id();
		$key     = $responses[ $response ];

		update_user_meta( $user_id, $key, 1 );
		update_user_meta( $user_id, $key . '_time', time() );

		wp_send_json_success();
	}
}
