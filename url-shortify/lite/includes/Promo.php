<?php

namespace KaizenCoders\URL_Shortify;

/**
 * Class Promo
 *
 * Handle Promotional Campaign
 *
 * @since   1.5.12.2
 * @package KaizenCoders\URL_Shortify
 *
 */
class Promo {
	/**
	 * Initialize Promotions
	 *
	 * @since 1.5.12.2
	 */
	public function init() {
		add_action( 'admin_init', [ $this, 'dismiss_promotions' ] );
		add_action( 'admin_notices', [ $this, 'handle_promotions' ] );
	}

	/**
	 * Get Valid Promotions.
	 *
	 * @return string[]
	 *
	 * @since 1.5.12.2
	 */
	public function get_valid_promotions() {
		return [
			'initial_upgrade',
			'welcome_offer',
			'anniversary_offer',
			'june_2024_promotion',
			'price_increase_notification',
			'pre_launch_offer',
			'helloween_offer',
		];
	}

	/**
	 * Dismiss Promotions.
	 *
	 * @since 1.5.12.2
	 */
	public function dismiss_promotions() {
		if ( isset( $_GET['kc_us_dismiss_admin_notice'] ) && $_GET['kc_us_dismiss_admin_notice'] == '1' && isset( $_GET['option_name'] ) ) {
			$option_name = sanitize_text_field( $_GET['option_name'] );

			$valid_options = $this->get_valid_promotions();

			if ( in_array( $option_name, $valid_options ) ) {

				update_option( 'kc_us_' . $option_name . '_dismissed', 'yes', false );

				if ( in_array( $option_name, $valid_options ) ) {
					if ( 'lifetime' === sanitize_text_field( Helper::get_data( $_GET, 'billing_cycle', '' ) ) ) {
						wp_safe_redirect( US()->get_pricing_url( 'lifetime' ) );
					} elseif ( (boolean) Helper::get_data( $_GET, 'landing', false ) ) {
						$pricing = Helper::get_data( $_GET, 'pricing', false );
						wp_safe_redirect( US()->get_landing_page_url( $pricing ) );
					} else {
						wp_safe_redirect( US()->get_pricing_url() );
					}
				} else {
					$referer = wp_get_referer();
					wp_safe_redirect( $referer );
				}
				exit();
			}
		}
	}

	/**
	 * Handle promotions activity.
	 *
	 * @since 1.5.12.2
	 */
	public function handle_promotions() {
		$helloween_offer = [
			'title'                         => "<b class='text-red-600 text-xl'>" . __( 'Holiday Prep Sale',
					'url-shortify' ) . "</b>",
			'start_date'                    => '2024-10-24',
			'end_date'                      => '2024-11-02',
			'total_links'                   => 1,
			'start_after_installation_days' => 0,
			'pricing_url'                   => US()->get_pricing_url( 'yearly' ),
			'promotion'                     => 'helloween_offer',
			'message'                       => __( '<p class="text-xl">Buy an annual/lifetime URL Shortify PRO plan at <b class="text-2xl">flat 30%</b> discount until <b class="text-red-600 text-xl">November 2, 2024</b></p>',
				'url-shortify' ),
			'coupon_message'                => __( 'Use Coupon Code - <b>HOLIDAY</b>', 'url-shortify' ),
			'show_upgrade'                  => true,
			'show_plan'                     => 'free',
			'dismiss_url'                   => add_query_arg( 'pricing', 'true', US()->get_landing_page_url() ),
		];

		$price_increase_notification = [
			'title'                         => "<b class='text-red-600 text-xl'>" . __( 'Important Announcement',
					'url-shortify' ) . "</b>",
			'start_date'                    => '2024-08-20',
			'end_date'                      => '2024-09-03',
			'total_links'                   => 1,
			'start_after_installation_days' => 0,
			'pricing_url'                   => US()->get_pricing_url( 'yearly' ),
			'promotion'                     => 'price_increase_notification',
			'message'                       => __( '<p class="text-xl">Buy an annual/lifetime URL Shortify PRO plan at the old price until <b class="text-red-600 text-xl">September 2, 2024</b></p>',
				'url-shortify' ),
			'coupon_message'                => sprintf( __( 'Starting September 2, our updated pricing will apply to all our subscription plans... <b><a href="%s" target="_blank">Learn More</a></b>',
				'url-shortify' ),
				'https://docs.kaizencoders.com/announcements/important-buy-an-annual-lifetime-url-shortify-pro-plan-at-the-old-price-until-september-2-2024?utm_source=plugin&utm_medium=notification&utm_campaign=price_increase_august_2024' ),
			'show_upgrade'                  => false,
			'show_plan'                     => 'free',
			'dismiss_url'                   => add_query_arg( 'pricing', 'true', US()->get_landing_page_url() ),
		];

		$month_end_sale_data = [
			'start_date'                    => '2022-07-26',
			'end_date'                      => '2022-08-02',
			'total_links'                   => 1,
			'start_after_installation_days' => 2,
			'promotion'                     => 'jully_2022_month_end_promotion',
			'message'                       => __( 'Your plugin plan is limited to 1 week of historical data. Upgrade your plan to see all historical data.',
				'url-shortify' ),
			'coupon_message'                => sprintf( __( 'Use coupon code <b class="text-green-800 px-1 py-1 text-xl">%s</b> to get flat <b class="text-2xl">$30</b> off on any plan',
				'url-shortify' ), 'WELCOME30' ),
		];

		$initial_upgrade_banner_data = [
			'message'                       => __( 'Your plugin plan is limited to 1 week of historical data. Upgrade your plan to see all historical data.',
				'url-shortify' ),
			'total_links'                   => 3,
			'start_after_installation_days' => 9,
			'end_before_installation_days'  => 15,
		];

		$welcome_offer_data = [
			'total_links'                   => 3,
			'start_after_installation_days' => 18,
			'end_before_installation_days'  => 27,
			'promotion'                     => 'welcome_offer',
			'message'                       => __( 'Your plugin plan is limited to 1 week of historical data. Upgrade your plan to see all historical data.',
				'url-shortify' ),
			'coupon_message'                => sprintf( __( 'Use coupon code <b class="text-green-800 px-1 py-1 text-xl">%s</b> to get flat <b class="text-2xl">$30</b> off on any plan',
				'url-shortify' ), 'WELCOME30' ),
		];

		$regular_upgrade_banner_data = [
			'message'     => __( 'Unlock the Full Power of Historical Data with an Upgraded Plugin Plan! Upgrade Today to Access All Historical Data.',
				'url-shortify' ),
			'total_links' => 2,
		];

		$pre_launch_offer = [
			'title'                         => "<b class='text-red-600 text-xl'>" . __( '🚀 Pre-launch Offer',
					'update-urls' ) . "</b>",
			'total_links'                   => 1,
			'start_date'                    => '2024-09-20',
			'end_date'                      => '2024-10-03',
			'start_after_installation_days' => 0,
			'pricing_url'                   => 'https://kaizencoders.com/social-linkz/',
			'promotion'                     => 'pre_launch_offer',
			'message'                       => __( '<p class="text-xl">Get <b>Update URLs</b> & <b>Social Linkz</b> (newly launched) PRO for Lifetime at Just $49 until <b class="text-red-600 text-xl">September 30, 2024</b></p>',
				'update-urls' ),
			'coupon_message'                => '',
			'show_plan'                     => 'free',
		];


		// Promotion.
		if ( Helper::can_show_promotion( $pre_launch_offer ) ) {
			$this->show_promotion( 'helloween_offer', $helloween_offer );
		} elseif ( Helper::can_show_promotion( $pre_launch_offer ) ) {
			$this->show_promotion( 'pre_launch_offer', $pre_launch_offer );
		} elseif ( Helper::can_show_promotion( $price_increase_notification ) ) {
			$this->show_promotion( 'price_increase_notification', $price_increase_notification );
		} elseif ( Helper::can_show_promotion( $month_end_sale_data ) ) {
			$this->show_promotion( 'month_end_sale', $month_end_sale_data );
		} elseif ( Helper::can_show_promotion( $initial_upgrade_banner_data ) ) {
			$this->show_promotion( 'initial_upgrade', $initial_upgrade_banner_data );
		} elseif ( Helper::can_show_promotion( $welcome_offer_data ) ) {
			$this->show_promotion( 'welcome_offer', $welcome_offer_data );
		} elseif ( Helper::can_show_promotion( $regular_upgrade_banner_data ) ) {
			$this->show_promotion( 'regular_upgrade_banner', $regular_upgrade_banner_data );
		}
	}

	/**
	 * Show Promotion.
	 *
	 * @param $data      array
	 * @param $promotion string
	 *
	 * @since 1.5.12.2
	 *
	 */
	public function show_promotion( $promotion, $data ) {

		$current_screen_id = Helper::get_current_screen_id();

		if ( in_array( $promotion, [
				'initial_upgrade',
				'regular_upgrade_banner',
			] ) && Helper::is_plugin_admin_screen( $current_screen_id ) ) {
			$action = Helper::get_data( $_GET, 'action' );
			if ( 'statistics' === $action ) {
				?>
                <div class="wrap">
					<?php Helper::get_upgrade_banner( null, null, $data ); ?>
                </div>
				<?php
			}
		} else {
			$query_strings = [
				'kc_us_dismiss_admin_notice' => 1,
				'option_name'                => $promotion,
			];
			?>

            <div class="wrap">
				<?php Helper::get_upgrade_banner( $query_strings, true, $data ); ?>
            </div>
			<?php
		}
	}

	/**
	 * Is Promo displayed and dismissed by user?
	 *
	 * @param $promo
	 *
	 * @return bool
	 *
	 * @since 1.5.12.2
	 *
	 */
	public function is_promotion_dismissed( $promotion ) {
		if ( empty( $promotion ) ) {
			return false;
		}

		$promotion_dismissed_option = 'kc_us_' . trim( $promotion ) . '_dismissed';

		return 'yes' === get_option( $promotion_dismissed_option );
	}
}
