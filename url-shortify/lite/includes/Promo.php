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
                'bfcm_2025_offer',
                'magic_link_launch_offer',
                'bfcm_2024_offer',
                'initial_upgrade',
                'welcome_offer',
                'anniversary_offer',
                'june_2024_promotion',
                'price_increase_notification',
                'pre_launch_offer',
                'helloween_offer',
                'survey_2025',
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
                    if ( isset( $_GET['redirect_to'] ) && ! empty( $_GET['redirect_to'] ) ) {
                        $redirect_to = esc_url_raw( $_GET['redirect_to'] );
                        if( $this->is_valid_redirect_to( $redirect_to ) ) {
                            wp_redirect( $redirect_to );
                        } else {
                            wp_safe_redirect( US()->get_pricing_url() );
                        }
                    } elseif ( 'lifetime' === sanitize_text_field( Helper::get_data( $_GET, 'billing_cycle', '' ) ) ) {
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
     * Get Valid Redirect To URLs.
     *
     * @return string[]
     *
     * @since 1.12.2
     */
    public function get_valid_redirect_to() {
        return [
                US()->get_pricing_url(),
                US()->get_landing_page_url(),
                'https://kaizencoders.com/url-shortify',
                'https://kaizencoders.com',
                'https://kaizencoders.com/magic-link',
                'https://kaizencoders.com/logify',
                'https://kaizencoders.com/update-urls',
        ];
    }

    /**
     * Is Valid Redirect To URL.
     *
     * @param string $url
     *
     * @return bool
     *
     * @since 1.12.2
     */
    public function is_valid_redirect_to( $url ) {
        $valid_urls = $this->get_valid_redirect_to();
        return in_array( $url, $valid_urls, true );
    }

    /**
     * Handle promotions activity.
     *
     * @since 1.5.12.2
     */
    public function handle_promotions() {
        $dismiss_url  = $this->get_dismiss_url( 'survey_2025' );
        $external_url = $this->get_external_url( 'survey_2025', [], 'https://forms.gle/s4mmv1BCJc6yFQGWA' );

        $bfcm_2025_offer = [
                'title'                         => "<b class='text-red-600 text-xl'>" . __( 'BFCM Sale is live',
                                'url-shortify' ) . "</b>",
                'start_date'                    => '2025-11-19',
                'end_date'                      => '2025-12-08',
                'total_links'                   => 1,
                'start_after_installation_days' => 0,
                'pricing_url'                   => US()->get_pricing_url( 'yearly' ),
                'promotion'                     => 'bfcm_2025_offer',
                'show_plan'                     => 'free',
                'dismiss_url'                   => add_query_arg( 'pricing', 'true', US()->get_landing_page_url() ),
                'banner'                        => true,
        ];

        $survey_2025 = [
                'title'                         => "<b class='text-red-600 text-xl'>Help shape what comes next in URL Shortify 🚀</b>",
                'start_date'                    => '2025-10-08',
                'end_date'                      => '2025-10-20',
                'total_links'                   => 1,
                'start_after_installation_days' => 0,
                'pricing_url'                   => US()->get_pricing_url( 'yearly' ),
                'promotion'                     => 'survey_2025',
                'message'                       => '<p class="text-2xl"><strong>🚀 Help Shape the Future of URL Shortify!</strong></p><p class="text-xl mt-4">We’re working on new features for URL Shortify and would love your input.<br> Take our quick <strong>1-minute survey</strong> and tell us what you’d like to see next.<br><br> 💡 As a thank-you, you’ll get a <strong>20% OFF coupon</strong> for URL Shortify PRO!<br><br> <a href="' . $external_url . '" target="_blank" class="button-primary bg-indigo-600 text-white focus:bg-indigo-800"> 👉 Take the Survey Now </a> <a href="' . $dismiss_url . '" target="_blank" class="text-red-500 text-sm hover:text-red-600"> Close </a> </p>',
                'coupon_message'                => '',
                'show_upgrade'                  => false,
                'show_dismiss_button'           => false,
                'show_plan'                     => 'free',
            //'dismiss_url'                   => add_query_arg( 'pricing', 'true', US()->get_landing_page_url() ),
                'banner'                        => false,
            //'redirect_to'                   => 'https://forms.gle/s4mmv1BCJc6yFQGWA',
        ];

        $magic_link_launch_offer = [
                'title'                         => "<b class='text-red-600 text-xl'>" . __( 'WordPress Plugin Launch Offer - Magic Link PRO', 'url-shortify' ) . "</b>",
                'start_date'                    => '2025-09-10',
                'end_date'                      => '2025-09-30',
                'total_links'                   => 1,
                'start_after_installation_days' => 0,
                'pricing_url'                   => US()->get_pricing_url( 'yearly' ),
                'promotion'                     => 'magic_link_launch_offer',
                'message'                       => __( '<p class="text-xl">Magic Link PRO launch offer <b class="text-2xl">flat 50%</b> discount until <b class="text-red-600 text-xl">September 30, 2025</b></p>', 'url-shortify' ),
                'coupon_message'                => __( 'Use Coupon Code - <b>LAUNCH50</b>', 'url-shortify' ),
                'show_upgrade'                  => true,
                'show_plan'                     => 'free',
                'dismiss_url'                   => add_query_arg( 'pricing', 'true', 'https://kaizencoders.com/magic-link' ),
                'banner'                        => false,
                'redirect_to'                   => 'https://kaizencoders.com/magic-link',
        ];

        $bfcm_2024_offer = [
                'title'                         => "<b class='text-red-600 text-xl'>" . __( 'BFCM Sale is live',
                                'url-shortify' ) . "</b>",
                'start_date'                    => '2024-11-20',
                'end_date'                      => '2024-12-08',
                'total_links'                   => 1,
                'start_after_installation_days' => 0,
                'pricing_url'                   => US()->get_pricing_url( 'yearly' ),
                'promotion'                     => 'bfcm_2024_offer',
                'message'                       => __( '<p class="text-xl">Buy an annual/lifetime URL Shortify PRO plan at <b class="text-2xl">flat 50%</b> discount until <b class="text-red-600 text-xl">December 6, 2024</b></p>',
                        'url-shortify' ),
                'coupon_message'                => __( 'Use Coupon Code - <b>BFCM2024</b>', 'url-shortify' ),
                'show_upgrade'                  => true,
                'show_plan'                     => 'free',
                'dismiss_url'                   => add_query_arg( 'pricing', 'true', US()->get_landing_page_url() ),
                'banner'                        => true,
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

        // Promotion.
        if ( Helper::can_show_promotion( $bfcm_2025_offer ) ) {
            $this->show_promotion( 'bfcm_2025_offer', $bfcm_2025_offer );
        } elseif ( Helper::can_show_promotion( $survey_2025 ) ) {
            $this->show_promotion( 'survey_2025', $survey_2025 );
        } elseif ( Helper::can_show_promotion( $magic_link_launch_offer ) ) {
            $this->show_promotion( 'magic_link_launch_offer', $magic_link_launch_offer );
        } elseif ( Helper::can_show_promotion( $bfcm_2024_offer ) ) {
            $this->show_promotion( 'bfcm_2024_offer', $bfcm_2024_offer );
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
                    <?php
                    Helper::get_upgrade_banner( null, null, $data ); ?>
                </div>
                <?php
            }
        } else {
            $query_strings = [
                    'kc_us_dismiss_admin_notice' => 1,
                    'option_name'                => $promotion,
            ];

            if ( isset( $data['redirect_to'] ) && ! empty( $data['redirect_to'] ) ) {
                $query_strings['redirect_to'] = esc_url( $data['redirect_to'] );
            }

            ?>

            <div class="wrap">
                <?php
                Helper::get_upgrade_banner( $query_strings, true, $data ); ?>
            </div>
            <?php
        }
    }

    /**
     * Get Dismiss URL.
     *
     * @param $promotion
     * @param $query_strings
     * @param $redirect_to
     *
     * @return string
     */
    public function get_dismiss_url( $promotion, $query_strings = [], $redirect_to = '' ) {
        if ( empty( $promotion ) ) {
            return '';
        }

        $query_strings['kc_us_dismiss_admin_notice'] = 1;
        $query_strings['option_name']                = $promotion;

        // Get the current url to redirect after dismissing the notice.
        $current_url = home_url( $_SERVER['REQUEST_URI'] );

        if ( empty( $current_url ) ) {
            $current_url = admin_url();
        }

        $query_strings['redirect_to'] = esc_url( $current_url );
        if ( ! empty( $redirect_to ) ) {
            $query_strings['redirect_to'] = esc_url( $redirect_to );
        }

        return add_query_arg( $query_strings, $current_url );
    }

    /**
     * Get External URL.
     *
     * @param $promotion
     * @param $query_string
     * @param $redirect_to
     *
     * @return string
     */
    public function get_external_url( $promotion, $query_string = [], $redirect_to = '' ) {
        if ( empty( $promotion ) ) {
            return '';
        }

        // This is to track the dismiss action from external URL.
        $query_strings['kc_us_dismiss_admin_notice'] = 1;
        $query_strings['option_name']                = $promotion;

        if ( ! empty( $redirect_to ) ) {
            $query_strings['redirect_to'] = esc_url( $redirect_to );
        }

        return add_query_arg( $query_strings, US()->get_pricing_url() );
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
