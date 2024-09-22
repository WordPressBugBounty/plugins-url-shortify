<?php

namespace KaizenCoders\URL_Shortify;


class Feedback {
	/**
	 * Plugin Abbr
	 *
	 * @since 1.2.15
	 * @var string
	 *
	 */
	public $plugin_abbr = 'kc_us';

	/**
	 * Plugin Text Domain
	 *
	 * @since 1.2.15
	 * @var string
	 *
	 */
	public $plugin = 'url-shortify';

	/**
	 * Name of the plugin
	 *
	 * @since 1.2.15
	 * @var string
	 *
	 */
	public $name = 'URL Shortify';

	public function init() {

		add_action( 'admin_notices', [ &$this, 'show_review_notice' ] );

		add_filter( $this->plugin_abbr . '_can_ask_user_for_review', [ $this, 'can_ask_user_for_review' ], 10, 2 );

		add_filter( $this->plugin_abbr . '_review_message_data', [ $this, 'review_message_data' ], 10 );
	}

	/**
	 * Show Review Notice
	 *
	 * @since 1.2.15
	 */
	public function show_review_notice() {

		if ( ! defined( 'DOING_AJAX' ) && is_admin() ) {

			$enable_review_notice = apply_filters( $this->plugin_abbr . '_enable_review_notice', true );

			$can_ask_user_for_review = true;

			if ( $enable_review_notice ) {

				$current_user_id = \get_current_user_id();

				$review_done_option      = $this->plugin_abbr . '_feedback_review_done';
				$no_bug_option           = $this->plugin_abbr . '_feedback_do_not_ask_again';
				$already_did_option      = $this->plugin_abbr . '_feedback_already_did';
				$maybe_later_option      = $this->plugin_abbr . '_feedback_maybe_later';
				$review_done_time_option = $review_done_option . '_time';
				$no_bug_time_option      = $no_bug_option . '_time';
				$already_did_time_option = $already_did_option . '_time';
				$maybe_later_time_option = $maybe_later_option . '_time';

				$no_bug_days_before = 1;
				$no_bug_value       = get_user_meta( $current_user_id, $no_bug_option, true );
				$no_bug_time_value  = get_user_meta( $current_user_id, $no_bug_time_option, true );

				$review_done_value      = get_user_meta( $current_user_id, $review_done_option, true );
				$review_done_time_value = get_user_meta( $current_user_id, $review_done_time_option, true );

				if ( ! empty( $no_bug_time_value ) && 0 !== $no_bug_time_value ) {
					$no_bug_time_diff   = time() - $no_bug_time_value;
					$no_bug_days_before = floor( $no_bug_time_diff / 86400 ); // 86400 seconds == 1 day
				}

				$already_did_value      = get_user_meta( $current_user_id, $already_did_option, true );
				$already_did_time_value = get_user_meta( $current_user_id, $already_did_time_option, true );

				$maybe_later_days_before = 1;
				$maybe_later_value       = get_user_meta( $current_user_id, $maybe_later_option, true );
				$maybe_later_time_value  = get_user_meta( $current_user_id, $maybe_later_time_option, true );

				if ( $maybe_later_value && ! empty( $maybe_later_time_value ) && 0 !== $maybe_later_time_value ) {
					$maybe_later_time_diff   = time() - $maybe_later_time_value;
					$maybe_later_days_before = floor( $maybe_later_time_diff / 86400 ); // 86400 seconds == 1 day
				}


				if ( $review_done_value || $no_bug_value || $already_did_value || ( $maybe_later_value && $maybe_later_days_before < 15 ) ) {
					$can_ask_user_for_review = false;
				}

				$review_data = [
					'review_done_value'      => $review_done_value,
					'review_done_time_value' => $review_done_time_value,
					'no_bug_value'           => $no_bug_value,
					'no_bug_time_value'      => $no_bug_time_value,
					'maybe_later_value'      => $maybe_later_value,
					'maybe_later_time_value' => $maybe_later_time_value,
					'already_did_value'      => $already_did_value,
					'already_did_time_value' => $already_did_time_value,
				];

				$can_ask_user_for_review = apply_filters( $this->plugin_abbr . '_can_ask_user_for_review', $can_ask_user_for_review, $review_data );

				if ( $can_ask_user_for_review ) {

					$current_page_url = "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

					$got_feedback = false;
					/************** Update Review Status ********************/
					$nonce          = ! empty( $_GET['kc_us_feedback_nonce'] ) ? esc_attr( wp_unslash( $_GET['kc_us_feedback_nonce'] ) ) : '';
					$nonce_verified = wp_verify_nonce( $nonce, 'review' );

					$action = '';
					if ( $nonce_verified ) {
						$action = ! empty( $_GET['kc_us_feedback_action'] ) ? esc_attr( wp_unslash( $_GET['kc_us_feedback_action'] ) ) : '';

						if ( ! empty( $action ) && $this->is_valid_action( $action ) ) {
							update_user_meta( $current_user_id, $action, 1 );
							update_user_meta( $current_user_id, $action . "_time", time() );

							// Got the review request?
							// Redirect them to review page
							if ( $action === $review_done_option ) {

								$url = ! empty( $_GET['review_url'] ) ? esc_url( $_GET['review_url'] ) : '';

								if ( ! empty( $url ) ) {
									?>

                                    <meta http-equiv="refresh" content="0; url=<?php echo $url; ?>"/>

									<?php
								}
							}
						}

						$got_feedback = true;
					}
					/************** Update Review Status (End) ********************/

					if ( ! $got_feedback ) {

						$review_url = "https://wordpress.org/support/plugin/{$this->plugin}/reviews/";
						$icon_url   = plugin_dir_url( __FILE__ ) . 'assets/images/icon-64.png';
						$message    = __( sprintf( "<span><p>We hope you're enjoying <b>%s</b> plugin! Could you please do us a BIG favor and give us a 5-star rating on WordPress to help us spread the word and boost our motivation?</p>", $this->name ), $this->plugin, 'url-shortify' );

						$message_data = [
							'review_url' => $review_url,
							'icon_url'   => $icon_url,
							'message'    => $message,
						];

						$message_data = apply_filters( $this->plugin_abbr . '_review_message_data', $message_data );

						$message    = ! empty( $message_data['message'] ) ? $message_data['message'] : '';
						$review_url = ! empty( $message_data['review_url'] ) ? $message_data['review_url'] : '';
						$icon_url   = ! empty( $message_data['icon_url'] ) ? $message_data['icon_url'] : '';

						$nonce = wp_create_nonce( 'review' );

						$review_url      = add_query_arg( 'review_url', $review_url, add_query_arg( 'kc_us_feedback_nonce', $nonce, add_query_arg( 'kc_us_feedback_action', $review_done_option, $current_page_url ) ) );
						$maybe_later_url = add_query_arg( 'kc_us_feedback_nonce', $nonce, add_query_arg( 'kc_us_feedback_action', $maybe_later_option, $current_page_url ) );
						$already_did_url = add_query_arg( 'kc_us_feedback_nonce', $nonce, add_query_arg( 'kc_us_feedback_action', $already_did_option, $current_page_url ) );
						$no_bug_url      = add_query_arg( 'kc_us_feedback_nonce', $nonce, add_query_arg( 'kc_us_feedback_action', $no_bug_option, $current_page_url ) );

						?>

                        <style type="text/css">

                            .kc-us-feedback-notice-links li {
                                display: inline-block;
                                margin-right: 15px;
                            }

                            .kc-us-feedback-notice-links li a.kc-us-primary-button {
                                color: white;
                            }

                            .kc-us-feedback-notice-links li a {
                                display: inline-block;
                                color: #5850EC;
                                text-decoration: none;
                                padding-left: 26px;
                                position: relative;
                            }

                            .kc-us-feedback-notice {
                                display: flex;
                                align-items: center;
                            }

                            .kc-us-feedback-plugin-icon {
                                float: left;
                                margin-right: 0.5em;
                            }

                        </style>

						<?php

						echo '<div class="notice notice-success kc-us-feedback-notice">';
						echo '<span class="kc-us-feedback-plugin-icon"> <img src="' . $icon_url . '" alt="Logo"/></span>';
						echo $message;
						echo "<ul class='kc-us-feedback-notice-links'>";
						echo sprintf( '<li><a href="%s" class="button button-primary px-4 py-2 ml-6 mr-2 text-white align-middle cursor-pointer kc-us-primary-button" target="_blank" data-rated="' . esc_attr__( "Thank You :) ",
								$this->plugin, 'url-shortify' ) . '"><span class="dashicons dashicons-external"></span>&nbsp;&nbsp;Ok, you deserve it</a></li> <li><a href="%s"><span class="dashicons dashicons-calendar-alt"></span>&nbsp;&nbsp;Maybe later</a></li><li><a href="%s"><span class="dashicons dashicons-smiley"></span>&nbsp;&nbsp;I already did!</a></li><li><a href="%s"><span class="dashicons dashicons-no"></span>&nbsp;&nbsp;Don\'t ask me again</a></li>',
							esc_url( $review_url ), esc_url( $maybe_later_url ), esc_url( $already_did_url ), esc_url( $no_bug_url ) );
						echo "</ul></span>";
						echo '</div>';
					}
				}
			}
		}
	}

	/**
	 * Is valid action?
	 *
	 * @since 1.2.15
	 *
	 * @param string $action
	 *
	 * @return bool
	 *
	 */
	public function is_valid_action( $action = '' ) {
		if ( empty( $action ) ) {
			return false;
		}

		$available_actions = [
			'_feedback_review_done',
			'_feedback_already_did',
			'_feedback_maybe_later',
			'_feedback_do_not_ask_again',
		];

		foreach ( $available_actions as $available_action ) {
			if ( strpos( $action, $available_action ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Can we ask user for review?
	 *
	 * @since 1.2.15
	 *
	 * @param $review_data
	 *
	 * @param $enable
	 *
	 * @return false
	 *
	 */
	function can_ask_user_for_review( $enable, $review_data ) {

		if ( $enable ) {

			if ( ! Helper::is_plugin_admin_screen() ) {
				return false;
			}

			$total_links = US()->db->links->count();

			if ( $total_links < 2 ) {
				return false;
			}

		}

		return $enable;
	}

	/**
	 * Show review message
	 *
	 * @since 1.2.5
	 *
	 * @param $review_data
	 *
	 * @return mixed
	 *
	 */
	function review_message_data( $review_data ) {

		$icon_url = KC_US_PLUGIN_ASSETS_DIR_URL . '/images/icon-64.png';

		$review_data['icon_url'] = $icon_url;

		return $review_data;
	}
}