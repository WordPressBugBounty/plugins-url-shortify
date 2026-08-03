<?php
/**
 * Promotional banners shown on the URL Shortify admin screens to free users.
 *
 * Campaigns are declared in one registry and evaluated in order: the first one
 * whose conditions match is the only one that renders, so two offers can never
 * stack on top of each other. Every campaign is dismissible per user and every
 * part of the registry is filterable, so copy, coupons and dates can change
 * without touching this file.
 *
 * @package KaizenCoders\URL_Shortify\Admin\Promotions
 */

namespace KaizenCoders\URL_Shortify\Admin\Promotions;

use KaizenCoders\URL_Shortify\Helper;
use KaizenCoders\URL_Shortify\Option;

class PromoBanner {

	const AJAX_ACTION  = 'kc_us_dismiss_promo';
	const NONCE_ACTION = 'kc_us_dismiss_promo';

	/**
	 * Name of the admin_notices callback.
	 *
	 * Admin::remove_admin_notices() strips notices on the plugin's own screens
	 * by callback name, so it has to know about this one or the banner is
	 * silently removed before it can render.
	 */
	const RENDER_CALLBACK = 'maybe_render_promo';

	/**
	 * User meta prefix. The campaign id is appended, so a new campaign shows
	 * again to everyone without any stored state having to be cleaned up.
	 */
	const META_PREFIX = '_kc_us_promo_dismissed_';

	/**
	 * Register hooks.
	 *
	 * @since 2.5.0
	 */
	public function init() {
		// admin_notices fires INSIDE #wpbody-content > .wrap, so the banner
		// inherits the left/right margins the admin content uses to clear the
		// sidebar. Rendering on a hook that runs before the admin template opens
		// the .wrap container makes the browser paint it at viewport (0, 0),
		// overlapping the menu.
		add_action( 'admin_notices', [ $this, self::RENDER_CALLBACK ] );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, [ $this, 'handle_dismiss' ] );
	}

	/* ---------------------------------------------------------------------
	 * Campaign registry
	 * ------------------------------------------------------------------ */

	/**
	 * Values every campaign inherits unless it overrides them.
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public static function get_campaign_defaults() {
		return [
			'headline'     => '',
			'subhead'      => '',
			'coupon'       => '',
			'cta_text'     => __( 'Upgrade Now', 'url-shortify' ),
			'cta_url'      => '',
			'compare_text' => __( 'Compare plans', 'url-shortify' ),
			'compare_url'  => '',
			'image'        => '',

			// Calendar window. Empty means unbounded in that direction, which is
			// how an evergreen lifecycle campaign is expressed.
			'start'        => '',
			'end'          => '',

			// Do not promote until the user has actually built something.
			'min_links'    => 0,

			// Days since the plugin was installed, inclusive.
			'min_age_days' => 0,
			'max_age_days' => 0,
		];
	}

	/**
	 * Campaigns, highest priority first.
	 *
	 * A dated campaign should sit above the evergreen lifecycle ones so a
	 * seasonal offer takes precedence while it is running.
	 *
	 * @return array Campaign id => config.
	 *
	 * @since 2.5.0
	 */
	public static function get_campaigns() {
		$campaigns = [

			// Evergreen fallback once the free history limit starts to bite.
			'unlock_history' => [
				'headline'     => __( '📊 You are only seeing 7 days of click history', 'url-shortify' ),
				'subhead'      => __( 'Upgrade to PRO to keep and analyse your full click history. Use coupon to get flat 10% discount on any plan.', 'url-shortify' ),
                'coupon'       => 'HISTORY10',
				'cta_text'     => __( 'Upgrade Now', 'url-shortify' ),
				'min_links'    => 2,
				'min_age_days' => 7,
			],

			// Evergreen onboarding nudge, roughly three weeks in.
			'welcome_offer' => [
				'headline'     => __( '👋 Enjoying URL Shortify?', 'url-shortify' ),
				'subhead'      => __( 'Unlock full click history, link cloaking, QR codes and more.', 'url-shortify' ),
				'coupon'       => 'WELCOME30',
				'cta_text'     => __( 'Upgrade Now', 'url-shortify' ),
				'min_links'    => 3,
				'min_age_days' => 18,
				'max_age_days' => 27,
			],

		];

		/**
		 * Filter the promotional campaigns.
		 *
		 * Campaigns are evaluated in array order and the first match wins, so
		 * reordering this array changes precedence. Returning an empty array
		 * turns promotions off entirely.
		 *
		 * @param array $campaigns Campaign id => config.
		 *
		 * @since 2.5.0
		 */
		return (array) apply_filters( 'kc_us_promo_campaigns', $campaigns );
	}

	/**
	 * Fill in the defaults and the derived links for one campaign.
	 *
	 * @param string $id     Campaign id.
	 * @param array  $config Raw campaign config.
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public static function prepare_campaign( $id, $config ) {
		$campaign = array_merge( self::get_campaign_defaults(), (array) $config );

		$campaign['id'] = $id;

		if ( '' === $campaign['cta_url'] ) {
			$campaign['cta_url'] = self::upgrade_url( $id );
		}

		if ( '' === $campaign['compare_url'] ) {
			$campaign['compare_url'] = US()->get_website_url(
				'#pricing',
				[
					'medium'   => 'banner',
					'campaign' => self::utm_campaign( $id ),
					'content'  => 'compare-plans',
				]
			);
		}

		/**
		 * Filter a single resolved campaign right before it is used.
		 *
		 * @param array  $campaign Campaign config, defaults already applied.
		 * @param string $id       Campaign id.
		 *
		 * @since 2.5.0
		 */
		return (array) apply_filters( 'kc_us_promo_campaign', $campaign, $id );
	}

	/**
	 * The campaign that should render right now, if any.
	 *
	 * @param array $context Evaluation context, see build_context().
	 *
	 * @return array|null
	 *
	 * @since 2.5.0
	 */
	public static function get_active_campaign( $context ) {
		foreach ( self::get_campaigns() as $id => $config ) {
			$campaign = self::prepare_campaign( $id, $config );

			if ( self::should_show( $campaign, $context ) ) {
				return $campaign;
			}
		}

		return null;
	}

	/* ---------------------------------------------------------------------
	 * Show logic
	 * ------------------------------------------------------------------ */

	/**
	 * Should this campaign render?
	 *
	 * Kept free of WordPress lookups so the decision can be reasoned about (and
	 * tested) from its inputs alone.
	 *
	 * @param array $campaign Prepared campaign.
	 * @param array $context {
	 *     @type bool  $can_promote       Free user and promotions not disabled.
	 *     @type bool  $on_plugin_screen  Current screen belongs to URL Shortify.
	 *     @type array $dismissed         Campaign ids this user has closed.
	 *     @type int   $now               Unix timestamp.
	 *     @type int   $total_links       Links on this site.
	 *     @type int   $days_since_install Whole days since the plugin was installed.
	 * }
	 *
	 * @return bool
	 *
	 * @since 2.5.0
	 */
	public static function should_show( $campaign, $context ) {
		$campaign = (array) $campaign;
		$context  = (array) $context;

		if ( empty( $context['can_promote'] ) || empty( $context['on_plugin_screen'] ) ) {
			return false;
		}

		$id        = isset( $campaign['id'] ) ? (string) $campaign['id'] : '';
		$dismissed = isset( $context['dismissed'] ) ? (array) $context['dismissed'] : [];

		if ( '' !== $id && in_array( $id, $dismissed, true ) ) {
			return false;
		}

		if ( ! self::is_in_window(
			isset( $context['now'] ) ? (int) $context['now'] : time(),
			isset( $campaign['start'] ) ? (string) $campaign['start'] : '',
			isset( $campaign['end'] ) ? (string) $campaign['end'] : ''
		) ) {
			return false;
		}

        /**
         * Consider adding plugin specific conditions to make sure user has used (experienced) the plugin first.
         *
         * Do not promote premium version blindly.
         */
		// Nothing to promote to somebody who has not created any links yet.
		$min_links = isset( $campaign['min_links'] ) ? (int) $campaign['min_links'] : 0;

		if ( $min_links > 0 && (int) Helper::get_data( $context, 'total_links', 0 ) < $min_links ) {
			return false;
		}

		return self::is_in_install_window(
			(int) Helper::get_data( $context, 'days_since_install', 0 ),
			isset( $campaign['min_age_days'] ) ? (int) $campaign['min_age_days'] : 0,
			isset( $campaign['max_age_days'] ) ? (int) $campaign['max_age_days'] : 0
		);
	}

	/**
	 * True when $now falls inside [$start, $end] inclusive. An empty boundary
	 * means "no bound in that direction".
	 *
	 * @param int    $now   Unix timestamp.
	 * @param string $start ISO date (YYYY-MM-DD) or empty.
	 * @param string $end   ISO date (YYYY-MM-DD) or empty.
	 *
	 * @return bool
	 *
	 * @since 2.5.0
	 */
	public static function is_in_window( $now, $start, $end ) {
		$now = (int) $now;

		if ( '' !== $start ) {
			$start_ts = strtotime( $start . ' 00:00:00 UTC' );

			if ( false !== $start_ts && $now < $start_ts ) {
				return false;
			}
		}

		if ( '' !== $end ) {
			$end_ts = strtotime( $end . ' 23:59:59 UTC' );

			if ( false !== $end_ts && $now > $end_ts ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * True when the install age falls inside [$min, $max] inclusive. A zero
	 * bound means "no bound in that direction".
	 *
	 * @param int $days Whole days since installation.
	 * @param int $min  Lower bound, or 0 for none.
	 * @param int $max  Upper bound, or 0 for none.
	 *
	 * @return bool
	 *
	 * @since 2.5.0
	 */
	public static function is_in_install_window( $days, $min, $max ) {
		$days = (int) $days;

		if ( $min > 0 && $days < (int) $min ) {
			return false;
		}

		if ( $max > 0 && $days > (int) $max ) {
			return false;
		}

		return true;
	}

	/**
	 * Gather everything the show logic needs.
	 *
	 * The link count is only queried when a campaign actually asks for it, so
	 * the common case adds no database work to an admin page load.
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public static function build_context() {
		$campaigns = self::get_campaigns();

		$needs_links = false;

		foreach ( $campaigns as $config ) {
			if ( ! empty( $config['min_links'] ) ) {
				$needs_links = true;
				break;
			}
		}

		return [
			// Covers both "already PRO" and the Disable Promotion add-on.
			'can_promote'        => US()->can_show_premium_promotion(),
			'on_plugin_screen'   => Helper::is_plugin_admin_screen(),
			'dismissed'          => self::get_dismissed_campaigns(),
			'now'                => time(),
			'total_links'        => $needs_links ? (int) US()->db->links->count() : 0,
			'days_since_install' => self::get_days_since_install(),
		];
	}

	/**
	 * Whole days since the plugin was installed.
	 *
	 * @return int
	 *
	 * @since 2.5.0
	 */
	public static function get_days_since_install() {
		$installed_on = (int) Option::get( 'installed_on', 0 );

		if ( $installed_on <= 0 ) {
			// Unknown install date, e.g. an upgrade from before it was recorded.
			// Stamp it now so lifecycle campaigns get a stable reference point
			// instead of treating the site as years old.
			$installed_on = time();

			Option::set( 'installed_on', $installed_on );
		}

		return (int) floor( ( time() - $installed_on ) / DAY_IN_SECONDS );
	}

	/* ---------------------------------------------------------------------
	 * Links
	 * ------------------------------------------------------------------ */

	/**
	 * Upgrade destination for a campaign.
	 *
	 * Always the pricing section on kaizencoders.com, tagged with the campaign
	 * so the click can be attributed.
	 *
	 * @param string $campaign_id Campaign id, used for attribution.
	 *
	 * @return string
	 *
	 * @since 2.5.0
	 */
	private static function upgrade_url( $campaign_id ) {
		return US()->get_website_url(
			'#pricing',
			[
				'medium'   => 'banner',
				'campaign' => self::utm_campaign( $campaign_id ),
				'content'  => 'upgrade-cta',
			]
		);
	}

	/**
	 * Campaign id in the dashed form used by the analytics.
	 *
	 * @param string $campaign_id Campaign id.
	 *
	 * @return string
	 *
	 * @since 2.5.0
	 */
	private static function utm_campaign( $campaign_id ) {
		return str_replace( '_', '-', (string) $campaign_id );
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	/**
	 * Print the highest priority campaign that applies.
	 *
	 * @return void
	 *
	 * @since 2.5.0
	 */
	public function maybe_render_promo() {
		$campaign = self::get_active_campaign( self::build_context() );

		if ( empty( $campaign ) ) {
			return;
		}

		$nonce = wp_create_nonce( self::NONCE_ACTION );
		?>
		<div id="kc-us-promo-banner" class="kc-us-promo-banner<?php echo empty( $campaign['image'] ) ? '' : ' kc-us-promo-banner--image'; ?>"
			role="region" data-campaign="<?php echo esc_attr( $campaign['id'] ); ?>"
			aria-label="<?php esc_attr_e( 'URL Shortify promotion', 'url-shortify' ); ?>">

			<?php if ( ! empty( $campaign['image'] ) ) : ?>

				<a href="<?php echo esc_url( $campaign['cta_url'] ); ?>" target="_blank" rel="noopener">
					<img src="<?php echo esc_url( $campaign['image'] ); ?>"
						alt="<?php echo esc_attr( $campaign['headline'] ); ?>">
				</a>

			<?php else : ?>

				<div class="kc-us-promo-inner">
					<div class="kc-us-promo-copy">
						<div class="kc-us-promo-headline"><?php echo esc_html( $campaign['headline'] ); ?></div>
						<?php if ( ! empty( $campaign['subhead'] ) ) : ?>
							<div class="kc-us-promo-subhead"><?php echo esc_html( $campaign['subhead'] ); ?></div>
						<?php endif; ?>
					</div>

					<?php if ( ! empty( $campaign['coupon'] ) ) : ?>
						<div class="kc-us-promo-coupon">
							<span class="kc-us-promo-coupon-label"><?php esc_html_e( 'Coupon', 'url-shortify' ); ?></span>
							<span class="kc-us-promo-coupon-code"><?php echo esc_html( $campaign['coupon'] ); ?></span>
							<button type="button" class="kc-us-promo-copy-btn" id="kc-us-promo-copy"
								data-coupon="<?php echo esc_attr( $campaign['coupon'] ); ?>"
								data-copied-label="<?php esc_attr_e( 'Copied!', 'url-shortify' ); ?>"
								aria-label="<?php esc_attr_e( 'Copy coupon code', 'url-shortify' ); ?>">
								<?php esc_html_e( 'Copy', 'url-shortify' ); ?>
							</button>
						</div>
					<?php endif; ?>

					<div class="kc-us-promo-actions">
						<a class="kc-us-promo-cta" href="<?php echo esc_url( $campaign['cta_url'] ); ?>" target="_blank" rel="noopener">
							<?php echo esc_html( $campaign['cta_text'] ); ?> <span aria-hidden="true">→</span>
						</a>
						<?php if ( ! empty( $campaign['compare_text'] ) ) : ?>
							<a class="kc-us-promo-secondary" href="<?php echo esc_url( $campaign['compare_url'] ); ?>" target="_blank" rel="noopener">
								<?php echo esc_html( $campaign['compare_text'] ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>

			<?php endif; ?>

			<button type="button" class="kc-us-promo-dismiss" id="kc-us-promo-dismiss"
				aria-label="<?php esc_attr_e( 'Dismiss this promotion', 'url-shortify' ); ?>"
				data-nonce="<?php echo esc_attr( $nonce ); ?>">
				×
			</button>
		</div>

		<style>
			.kc-us-promo-banner {
				box-sizing: border-box;
				background: linear-gradient(90deg, #4f46e5 0%, #7c3aed 100%);
				color: #fff;
				border-radius: 8px;
				box-shadow: 0 4px 12px rgba(79, 70, 229, .18);
				/* Sits inside .wrap, which already supplies the left gutter. */
				margin: 16px 20px 20px 0;
				padding: 18px 20px;
				max-width: 100%;
				position: relative;
				overflow: hidden;
			}
			.kc-us-promo-banner--image { padding: 0; background: none; box-shadow: none; }
			.kc-us-promo-banner--image img { display: block; width: 100%; height: auto; border-radius: 8px; }
			.kc-us-promo-inner {
				display: flex;
				align-items: center;
				gap: 24px;
				flex-wrap: wrap;
			}
			.kc-us-promo-copy { flex: 1 1 260px; min-width: 240px; }
			.kc-us-promo-headline { font-size: 18px; font-weight: 700; line-height: 1.3; }
			.kc-us-promo-subhead { font-size: 13px; opacity: .9; margin-top: 4px; }
			.kc-us-promo-coupon {
				display: inline-flex;
				align-items: center;
				gap: 8px;
				background: rgba(255, 255, 255, .14);
				border: 1px dashed rgba(255, 255, 255, .5);
				border-radius: 6px;
				padding: 8px 12px;
			}
			.kc-us-promo-coupon-label {
				font-size: 11px;
				letter-spacing: .04em;
				text-transform: uppercase;
				opacity: .8;
			}
			.kc-us-promo-coupon-code {
				font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
				font-size: 14px;
				font-weight: 700;
				letter-spacing: .04em;
			}
			.kc-us-promo-copy-btn {
				background: rgba(255, 255, 255, .22) !important;
				border: 0 !important;
				color: #fff !important;
				font-size: 12px !important;
				padding: 4px 10px !important;
				border-radius: 4px !important;
				cursor: pointer !important;
				box-shadow: none !important;
			}
			.kc-us-promo-copy-btn:hover { background: rgba(255, 255, 255, .32) !important; }
			.kc-us-promo-actions { display: flex; align-items: center; gap: 16px; }
			.kc-us-promo-cta {
				background: #ffffff;
				color: #4f46e5;
				font-weight: 700;
				text-decoration: none;
				padding: 10px 18px;
				border-radius: 6px;
				transition: transform .12s ease, box-shadow .12s ease;
			}
			.kc-us-promo-cta:hover {
				transform: translateY(-1px);
				box-shadow: 0 4px 10px rgba(0, 0, 0, .15);
				color: #4f46e5;
			}
			.kc-us-promo-secondary {
				color: rgba(255, 255, 255, .92);
				text-decoration: underline;
				font-size: 13px;
			}
			.kc-us-promo-secondary:hover { color: #fff; }
			.kc-us-promo-dismiss {
				position: absolute;
				top: 8px;
				right: 10px;
				background: transparent;
				border: 0;
				color: rgba(255, 255, 255, .85);
				font-size: 22px;
				line-height: 1;
				cursor: pointer;
				padding: 4px 8px;
				border-radius: 4px;
				z-index: 1;
			}
			.kc-us-promo-dismiss:hover { background: rgba(255, 255, 255, .15); color: #fff; }
			.kc-us-promo-banner--image .kc-us-promo-dismiss {
				background: rgba(0, 0, 0, .35);
				color: #fff;
			}
			@media (max-width: 782px) {
				.kc-us-promo-inner { flex-direction: column; align-items: flex-start; gap: 16px; }
				/* Once the row becomes a column the flex-basis applies to the
				   height, so the copy block would stretch to 260px tall and leave
				   a gap above the coupon. */
				.kc-us-promo-copy { flex: 0 0 auto; min-width: 0; }
				.kc-us-promo-actions { width: 100%; justify-content: space-between; }
			}
		</style>

		<script>
			( function () {
				var banner = document.getElementById( 'kc-us-promo-banner' );

				if ( ! banner ) {
					return;
				}

				var dismiss = document.getElementById( 'kc-us-promo-dismiss' );
				var copy    = document.getElementById( 'kc-us-promo-copy' );

				if ( dismiss ) {
					dismiss.addEventListener( 'click', function () {
						banner.style.transition = 'opacity .18s ease, transform .18s ease';
						banner.style.opacity    = '0';
						banner.style.transform  = 'translateY(-8px)';
						setTimeout( function () { banner.style.display = 'none'; }, 200 );

						var body = new URLSearchParams();
						body.append( 'action', <?php echo wp_json_encode( self::AJAX_ACTION ); ?> );
						body.append( 'nonce', dismiss.getAttribute( 'data-nonce' ) || '' );
						body.append( 'campaign', banner.getAttribute( 'data-campaign' ) || '' );

						fetch( ajaxurl, {
							method:      'POST',
							credentials: 'same-origin',
							headers:     { 'Content-Type': 'application/x-www-form-urlencoded' },
							body:        body.toString()
						} ).catch( function () { /* best effort, the banner is already hidden */ } );
					} );
				}

				if ( copy ) {
					// Self contained on purpose: the banner can render on admin
					// screens that do not load the plugin's clipboard bundle.
					copy.addEventListener( 'click', function () {
						var code  = copy.getAttribute( 'data-coupon' ) || '';
						var label = copy.textContent;

						var done = function () {
							copy.textContent = copy.getAttribute( 'data-copied-label' ) || 'Copied!';
							setTimeout( function () { copy.textContent = label; }, 1500 );
						};

						if ( navigator.clipboard && navigator.clipboard.writeText ) {
							navigator.clipboard.writeText( code ).then( done ).catch( fallback );
						} else {
							fallback();
						}

						function fallback() {
							var field = document.createElement( 'textarea' );
							field.value = code;
							field.setAttribute( 'readonly', '' );
							field.style.position = 'absolute';
							field.style.left = '-9999px';
							document.body.appendChild( field );
							field.select();

							try {
								document.execCommand( 'copy' );
								done();
							} catch ( e ) {
								/* Leave the code visible so it can be copied by hand. */
							}

							document.body.removeChild( field );
						}
					} );
				}
			} )();
		</script>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Dismiss
	 * ------------------------------------------------------------------ */

	/**
	 * Persist the dismissal for the current user.
	 *
	 * @return void
	 *
	 * @since 2.5.0
	 */
	public function handle_dismiss() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => 'not_logged_in' ], 403 );
		}

		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$campaign = isset( $_POST['campaign'] ) ? sanitize_key( wp_unslash( $_POST['campaign'] ) ) : '';

		// Only ever write a key for a campaign that actually exists, so a crafted
		// request cannot fill the usermeta table with junk.
		if ( '' === $campaign || ! array_key_exists( $campaign, self::get_campaigns() ) ) {
			wp_send_json_error( [ 'message' => 'unknown_campaign' ], 400 );
		}

		$user_id = get_current_user_id();

		if ( $user_id > 0 ) {
			update_user_meta( $user_id, self::get_dismissed_meta_key( $campaign ), time() );
		}

		wp_send_json_success();
	}

	/**
	 * User meta key holding the dismissal for a campaign.
	 *
	 * @param string $campaign_id Campaign id.
	 *
	 * @return string
	 *
	 * @since 2.5.0
	 */
	public static function get_dismissed_meta_key( $campaign_id ) {
		return self::META_PREFIX . $campaign_id;
	}

	/**
	 * Campaign ids the current user has closed.
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public static function get_dismissed_campaigns() {
		$user_id = get_current_user_id();

		if ( $user_id <= 0 ) {
			return [];
		}

		$dismissed = [];

		foreach ( array_keys( self::get_campaigns() ) as $id ) {
			$value = get_user_meta( $user_id, self::get_dismissed_meta_key( $id ), true );

			if ( '' !== (string) $value && '0' !== (string) $value ) {
				$dismissed[] = $id;
			}
		}

		return $dismissed;
	}
}
