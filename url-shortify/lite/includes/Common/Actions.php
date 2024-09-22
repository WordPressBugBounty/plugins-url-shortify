<?php

namespace KaizenCoders\URL_Shortify\Common;

use KaizenCoders\URL_Shortify\Admin\Controllers\LinksController;
use KaizenCoders\URL_Shortify\Admin\Stats;
use KaizenCoders\URL_Shortify\Cache;
use KaizenCoders\URL_Shortify\Cron;
use KaizenCoders\URL_Shortify\Helper;

/**
 * Class Actions
 *
 * @since 1.0.2
 * @package KaizenCoders\URL_Shortify\Common
 *
 */
class Actions {

	public $excerpt_called = false;

	public $post_ids;

	/**
	 * Init all actions & filters
	 *
	 * @since 1.0.2
	 */
	public function init() {

		// Add URL Shortify Metabox on Post.
		add_action( 'add_meta_boxes', [ $this, 'add_url_shortify_metabox' ], 10, 2 );

		// Delete clicks data on deletion of delete link
		add_action( 'kc_us_link_deleted', [ $this, 'delete_clicks' ], 10, 1 );
		add_action( 'kc_us_link_deleted', [ $this, 'delete_groups' ], 10, 1 );

		add_action( 'kc_us_group_deleted', [ $this, 'delete_links' ], 10, 1 );

		// Save Short URLS
		add_action( 'save_post', [ $this, 'save_short_link' ], 10, 3 );

		// Delete Posts Short Links
		add_action( 'delete_post', [ $this, 'delete_post_short_links' ], 10, 1 );

		add_filter( 'manage_pages_columns', [ $this, 'add_short_link_column_to_pages' ], 9999, 1 );
		add_filter( 'manage_posts_columns', [ $this, 'add_short_link_column' ], 9999, 2 );
		add_filter( 'edd_download_columns', [ $this, 'add_short_link_column_to_edd' ], 9999 );

		add_action( 'manage_posts_custom_column', [ $this, 'add_short_link' ], 10, 2 );
		add_action( 'manage_pages_custom_column', [ $this, 'add_short_link' ], 10, 2 );


		/**
		 * TODO: Handle this to improve performance.
		 */
		//add_action('admin_head', array($this, 'get_listed_post_ids'));
		//add_action('pre_get_posts', array($this, 'store_main_query_object'));


		/**
		 * We can ask 3rd party plugins developer to apply this filter to get the
		 * short url based on custom post type (including Posts & Pages)
		 *
		 * @since 1.1.5
		 */
		add_filter( 'kc_us_cpt_short_link', [ $this, 'get_cpt_short_link' ], 10, 2 );

		/**
		 * Compatible with https://wordpress.org/plugins/super-socializer/
		 *
		 * Reference: https://wordpress.org/support/topic/any-filter-to-change-sharable-link-3/
		 *
		 * We have set the priority to 9 to stop being override mycred_referral_id
		 *
		 * heateor_ss_append_mycred_referral_id
		 *
		 * @since 1.1.6
		 */
		add_filter( 'heateor_ss_target_share_url_filter', [ $this, 'get_heateor_ss_target_share_url' ], 9, 3 );

		/**
		 * Compatible with https://wordpress.org/plugins/sassy-social-share/
		 *
		 * Reference: https://wordpress.org/support/topic/any-filter-to-change-sharable-link-for-post-pages/
		 *
		 * We have set the priority to 9 to stop being override mycred_referral_id
		 *
		 * append_mycred_referral_id
		 *
		 * @since 1.1.6
		 */
		add_filter( 'heateor_sss_target_share_url_filter', [ $this, 'get_heateor_ss_target_share_url' ], 9, 3 );

		/**
		 * Betterdocs using the_permalink() to get the docs permalink.
		 * So, we are targeting 'the_permalink' filter to replace permalink
		 *
		 * @since 1.1.8
		 *
		 * @modify 1.4.11 This filter applied on every post type everywhere which we don't want.
		 * So, commenting this for now until we find better alternative.
		 */
		//add_filter( 'the_permalink', array( $this, 'get_short_permalink' ), 10, 2 );

		/**
		 * Compatible with Newspaper theme's Social Counter
		 *
		 * @since 1.4.2
		 *
		 * @modify 1.5.5 Commenting this filter as it applies everywhere. So, even if we don't want to use short link for posts, pages
		 * on WordPress site, it replaces generated short links with permalink on site.
		 *
		 * @TODO Need to find better alternative to make this compatible with with Newspaper theme's Social Counter.
		 *
		 * If you still want to use this feature, uncomment this line.
		 */
		//add_filter( 'post_link', array( $this, 'get_post_short_permalink' ), 10, 3 );


		/**
		 * Delete cache on following actions
		 *
		 * @since 1.2.13
		 */
		add_action( 'kc_us_link_created', [ $this, 'delete_cache' ], 10, 1 );
		add_action( 'kc_us_link_updated', [ $this, 'delete_cache' ], 10, 1 );
		add_action( 'kc_us_link_deleted', [ $this, 'delete_cache' ], 10, 1 );

		add_action( 'kc_us_group_created', [ $this, 'delete_cache' ], 10, 1 );
		add_action( 'kc_us_group_updated', [ $this, 'delete_cache' ], 10, 1 );
		add_action( 'kc_us_group_deleted', [ $this, 'delete_cache' ], 10, 1 );

		add_filter( 'kc_us_filter_links_actions', [ $this, 'filter_link_actions' ], 10, 2 );

		add_action( 'kc_us_link_saved', [ $this, 'regenerate_json_links' ] );
		add_action( 'kc_us_links_deleted', [ $this, 'regenerate_json_links' ] );

		/**
		 * Integration with WP To Twitter WordPress plugin. (https://wordpress.org/plugins/wp-to-twitter/)
		 *
		 * These two filters allows user to choose "URL Shortify" as url shortener in WP To Twitter plugin
		 * and use URL Shortify to short the URL.
		 *
		 * @since 1.5.15
		 */
		// add_filter( 'wpt_choose_shortener', array( $this, 'add_url_shortener_option' ), 10, 2 );
		// add_filter( 'wpt_do_shortening', array( $this, 'do_shortening_using_url_shortify' ), 10, 6 );

		/**
		 * This filter is being called from `wp_get_shortlink` WP method. It will replace the actual URL with short URL.
		 *
		 * @since 1.7.1
		 */
		add_filter( 'get_shortlink', [ $this, 'get_the_short_url' ], 999999, 2 );

		/**
		 * Anonymise clicks data
		 *
		 * @since 1.7.4
		 */
		add_filter( 'kc_us_clicks_data', [ $this, 'anonymise_clicks_data' ], 10, 2 );

		/**
		 * Add short link to posts, pages & excerpt.
		 *
		 * @since 1.7.1
		 */
		add_filter( 'the_content', [ $this, 'add_short_link_to_cpt' ], 1000 );
		add_filter( 'get_the_excerpt', [ $this, 'add_short_link_to_excerpt' ], 1000000 );
		add_filter( 'get_the_excerpt', [ $this, 'short_link_added_to_excerpt' ], 2 );
		add_action( 'wp_head', [ $this, 'insert_custom_short_url_css' ], 100 );
	}


	public function store_main_query_object( $query ) {
		// Check if we're in the admin area and on the posts list page
		if ( is_admin() && $query->is_main_query() && $query->get( 'post_type' ) === 'post' ) {
			set_query_var( 'stored_query_object', $query );
		}
	}

	public function get_listed_post_ids() {
		$query = get_query_var( 'stored_query_object' );
		if ( $query ) {
			// Now the posts are available
			$post_ids = wp_list_pluck( $query->posts, 'ID' );

			$this->post_ids = $post_ids;
		}
	}


	/**
	 * Get allowed post types to generate short links
	 *
	 * @since 1.1.6
	 *
	 * @modified 1.7.2
	 * @return string[]
	 *
	 */
	public function get_allowed_post_types_to_generate_short_links() {

		if ( US()->is_pro() ) {
			return Helper::get_all_cpts();
		}

		return [
			'post',
			'page',
			'product', // https://wordpress.org/plugins/woocommerce
			'download', // https://wordpress.org/plugins/easy-digital-downloads
			'event', // https://wordpress.org/plugins/events-manager/
			'tribe_events', // https://wordpress.org/plugins/the-events-calendar/
			'docs', // https://wordpress.org/plugins/betterdocs/
			'kbe_knowledgebase', // https://wordpress.org/plugins/wp-knowledgebase/
			'mec-events', // https://wordpress.org/plugins/modern-events-calendar-lite/
			'kruchprodukte', // https://wordpress.org/support/topic/custom-posts-type-2/
		];
	}

	/**
	 * Get allowed post types to auto generate short link for.
	 *
	 * @since 1.7.2
	 *
	 * @modify 1.7.4
	 * @return mixed|void
	 *
	 */
	public function get_allowed_post_types_to_auto_generate_short_links() {
		$settings = US()->get_settings();

		// Backward compatibility.
		$cpt_types = Helper::get_data( $settings, 'links_auto_create_links_for_cpt', [] );
		if ( 0 == $cpt_types ) {
			$cpt_types = [];
		}

		return $cpt_types;
	}

	/**
	 * Add custom Metabox
	 *
	 * @since 1.1.1
	 *
	 * @param $post
	 *
	 * @param $post_type
	 */
	public function add_url_shortify_metabox( $post_type, $post ) {
		add_meta_box( 'url-shortify', '👉&nbsp' . esc_html__( 'Short Link [URL Shortify]', 'url-shortify' ), [
			$this,
			'post_sidebar',
		], $this->get_allowed_post_types_to_generate_short_links(), 'side', 'high' );
	}

	/**
	 * Delete clicks on deletion of link
	 *
	 * @since 1.0.2
	 *
	 * @param  null  $link_id
	 *
	 */
	public function delete_clicks( $link_id = null ) {
		if ( $link_id ) {
			US()->db->clicks->delete_by_link_id( $link_id );
		}
	}

	/**
	 * Detach all groups which are assiciated with deleted link
	 *
	 * @since 1.1.3
	 *
	 * @param  null  $link_id
	 *
	 */
	public function delete_groups( $link_id = null ) {
		if ( $link_id ) {
			US()->db->links_groups->delete_groups_by_link_id( $link_id );
		}
	}

	/**
	 * Delete all links which are assiciated with deleted group
	 *
	 * @since 1.1.3
	 *
	 * @param  null  $group_id
	 *
	 */
	public function delete_links( $group_id = null ) {
		if ( $group_id ) {
			US()->db->links_groups->delete_links_by_group_id( $group_id );
		}
	}

	/**
	 * Delete links based on post_id
	 *
	 * @since 1.1.0
	 *
	 * @param $post_id
	 *
	 */
	public function delete_post_short_links( $post_id ) {
		if ( $post_id ) {
			US()->db->links->delete_by_cpt_id( $post_id );
		}
	}

	/**
	 * Add short link column to posts
	 *
	 * @since 1.1.3
	 *
	 * @modify 1.1.6
	 *
	 * @param  string  $post_type
	 *
	 * @param  array  $columns
	 *
	 * @return array
	 *
	 */
	public function add_short_link_column( $columns = [], $post_type = '' ) {

		if ( ! in_array( $post_type, $this->get_allowed_post_types_to_generate_short_links() ) ) {
			return $columns;
		}

		$columns['us_link_clicks'] = __( 'Clicks', 'url-shortify' );
		$columns['us_short_link']  = __( 'Short Link', 'url-shortify' );

		return $columns;
	}

	/**
	 * Add short link column to pages
	 *
	 * @since 1.1.6
	 *
	 * @param  array  $columns
	 *
	 * @return array
	 *
	 */
	public function add_short_link_column_to_pages( $columns = [] ) {
		return $this->add_short_link_column( $columns, 'page' );
	}

	/**
	 * Add short link column to pages
	 *
	 * @since 1.1.6
	 *
	 * @param  array  $columns
	 *
	 * @return array
	 *
	 */
	public function add_short_link_column_to_edd( $columns = [] ) {
		return $this->add_short_link_column( $columns, 'download' );
	}

	/**
	 * Add short link on post list
	 *
	 * @since 1.1.0
	 *
	 * @param $post_id
	 *
	 * @param $column_id
	 */
	public function add_short_link( $column_id, $post_id ) {

		switch ( $column_id ) {
			case 'us_short_link':
				$link_data = US()->db->links->get_by_cpt_id( $post_id );
				if ( ! empty( $link_data ) ) {
					$slug = Helper::get_data( $link_data, 'slug', '' );
					$link = Helper::get_short_link( $slug, $link_data );

					$id   = Helper::get_data( $link_data, 'id', 0 );
					$html = Helper::create_copy_short_link_html( $link, $post_id );
				} else {
					$loading_icon_url = KC_US_PLUGIN_ASSETS_DIR_URL . '/images/loader.gif';

					$action = wp_create_nonce( KC_US_AJAX_SECURITY );

					$html = '<span class="kc-flex kc_us_create_short_link" data-post_id="' . $post_id . '" data-us-security="' . $action . '"><svg class="kc-us-link-create-icon" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor"><title>' . __( 'Create',
							'url-shortify' ) . '</title><path d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><p class="kc_us_loading" style="display: none;"><img height="24px" width="24px" src="' . $loading_icon_url . '" ></p></span>';
				}
				echo $html;
				break;
			case 'us_link_clicks':
				$link_data = US()->db->links->get_by_cpt_id( $post_id );
				if ( ! empty( $link_data ) ) {
					$id        = Helper::get_data( $link_data, 'id', 0 );
					$stats_url = Helper::get_link_action_url( $id, 'statistics' );
					echo Helper::prepare_clicks_column( $id, $stats_url );
				}
		}
	}

	/**
	 * Add meta box in post sidebar
	 *
	 * @since 1.1.0
	 *
	 * @param $post  | \WP_Post
	 *
	 */
	public function post_sidebar( $post ) {

		if ( $post->post_status != 'publish' ) {
			$this->show_content( $post );
		} else {
			$link = US()->db->links->get_by_cpt_id( $post->ID );

			if ( ! empty( $link ) ) {
				$slug = Helper::get_data( $link, 'slug', '' );

				if ( ! empty( $slug ) ) {
					$short_link = Helper::get_short_link( $slug );
					?>
                    <p>
						<?php esc_html_e( 'Short Link:', 'url-shortify' ); ?><br/>
                        <strong><?php echo esc_url( $short_link ); ?></strong><br/></p>
					<?php
				}
			} else {
				$this->show_content( $post );
			}
		}
	}

	/**
	 * Show URL Content
	 *
	 * @param  \WP_Post  $post
	 *
	 * @sicne 1.1.0
	 *
	 * @modified 1.5.6
	 */
	public function show_content( $post ) {

		$post_id          = $post->ID;
		$link_data        = US()->db->links->get_by_cpt_id( $post_id );
		$default_settings = US()->get_settings();
		$default_domain   = Helper::get_data( $default_settings, 'links_default_link_options_default_custom_domain',
			'home' );

		if ( ! empty( $link_data ) ) {
			$slug = Helper::get_data( $link_data, 'slug', '' );
		}

		$can_generate = false;
		if ( in_array( $post->post_type, $this->get_allowed_post_types_to_auto_generate_short_links() ) ) {
			$can_generate = true;
		}

		if ( empty( $slug ) ) {
			$slug = Utils::get_valid_slug();
		}

		?>

        <div>
            <p>
                <input type="checkbox" name="kc_us_link_data[generate]"
                       id="kc_us_link_generate_checkbox" <?php if ( $can_generate ) {
					echo "checked=checked";
				} ?>/><?php _e( 'Generate Short URL', 'url-shortify' ); ?>
            </p>
        </div>

        <div id="kc_us_link_generate_link" style="">
            <select class="relative border border-gray-400 shadow-sm form-select" name="kc_us_link_data[rules][domain]"
                    id="kc-us-domain">
				<?php echo Helper::prepare_domains_dropdown_options( $default_domain ); ?>
            </select>
            <input type="text" style="width: 100px;" name="kc_us_link_data[slug]" id="" value="<?php echo $slug; ?>"/>
			<?php wp_nonce_field( 'create_short_link', 'us_nonce', true, true ); ?>
            <p><?php _e( 'A Short URL will be created on Publish', 'url-shortify' ); ?></p>
        </div>

        <script>
            (function ($) {
                'use strict';

                $(document).ready(function () {

                    var checked = $('#kc_us_link_generate_checkbox').attr('checked');
                    renderForm(checked);
                    $('#kc_us_link_generate_checkbox').on('click', function () {
                        var checked = $(this).attr('checked');
                        renderForm(checked);
                    });

                    function renderForm(checked = '') {
                        //$('#kc_us_link_generate_link').hide();
                        if ('checked' === checked) {
                            $('#kc_us_link_generate_link').show();
                        } else {
                            //$('#kc_us_link_generate_link').hide();
                        }
                    }
                });
            })(jQuery);
        </script>
		<?php
	}

	/**
	 * Save Short Link
	 *
	 * @since 1.1.0
	 *
	 * @param $post
	 * @param $update
	 *
	 * @param $post_id
	 *
	 * @return bool|int
	 *
	 */
	public function save_short_link( $post_id, $post, $update ) {

		if ( ! in_array( $post->post_type, $this->get_allowed_post_types_to_generate_short_links() ) ) {
			return $post_id;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( defined( 'DOING_AJAX' ) ) {
			$type = 'auto';
		}

		if ( ! $post_id || ! isset( $post->ID ) || ! $post->ID ) {
			return $post_id;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		// Make sure a nonce is set so we don't wipe these options out when the post is being bulk edited
		if ( ! wp_verify_nonce( ( isset( $_POST['us_nonce'] ) ) ? $_POST['us_nonce'] : '', 'create_short_link' ) ) {
			return $post_id;
		}

		$post_data = Helper::get_post_data( 'kc_us_link_data', [] );

		$slug            = Helper::get_data( $post_data, 'slug', '' );
		$should_generate = Helper::get_data( $post_data, 'generate', 0 );

		if ( empty( $slug ) || empty( $should_generate ) ) {
			return $post_id;
		}

		if ( $post && in_array( $post->post_status, [ 'publish', 'future' ] ) ) {

			$link_data = US()->db->links->get_by_cpt_id( $post->ID );

			$link_id = null;

			if ( empty( $link_data ) ) {
				$link_data = [
					'cpt_id'      => $post_id,
					'url'         => get_permalink( $post_id ),
					'name'        => addslashes( $post->post_title ),
					'description' => addslashes( $post->post_excerpt ),
				];
			} else {
				$link_id = Helper::get_data( $link_data, 'id', null );
			}

			$rules = [];
			if ( ! empty( Helper::get_data( $link_data, 'rules', '' ) ) ) {
				$rules = maybe_unserialize( $link_data['rules'] );
			}

			$link_data['rules'] = [];
			if ( is_array( $rules ) ) {
				$link_data['rules'] = $rules;
			}

			$link_data['slug']            = $slug;
			$link_data['rules']['domain'] = Helper::get_data( $post_data, 'rules|domain', 'home' );;;

			$data = US()->db->links->prepare_form_data( $link_data, $link_id );

			return US()->db->links->save( $data, $link_id );
		}

		return true;
	}

	/**
	 * Get shortlink by cpt id
	 *
	 * @since 1.1.5
	 *
	 * @param  int  $post_id
	 *
	 * @param  string  $post_url
	 *
	 * @return bool|string
	 *
	 */
	public function get_cpt_short_link( $post_url = '', $post_id = 0 ) {

		$link_conttoller = new LinksController();

		$short_link = $link_conttoller->get_short_link_by_cpt_id( $post_id );

		if ( $short_link ) {
			return $short_link;
		}

		return $post_url;
	}

	/**
	 * Get short link from permalink
	 *
	 * @since 1.1.8
	 *
	 * @param  string  $permalink
	 *
	 * @return bool|string
	 *
	 */
	public function get_shortlink_from_permalink( $permalink = '' ) {
		if ( empty( $permalink ) ) {
			return $permalink;
		}

		$post_id = url_to_postid( $permalink );

		if ( empty( $post_id ) ) {
			return $permalink;
		}

		return $this->get_cpt_short_link( $permalink, $post_id );
	}

	/**
	 * Get sharable link for super-socializer
	 *
	 * https://wordpress.org/plugins/super-socializer/
	 *
	 * @since 1.1.6
	 *
	 * @modify 1.1.8
	 *
	 * @param $sharing_type
	 * @param $standard_widget
	 *
	 * @param $post_url
	 *
	 * @return bool|string
	 *
	 */
	public function get_heateor_ss_target_share_url( $post_url, $sharing_type, $standard_widget ) {
		return $this->get_shortlink_from_permalink( $post_url );
	}

	/**
	 * Get short permalink if it's exists
	 *
	 * @since 1.1.8
	 *
	 * @param $post
	 *
	 * @param $post_url
	 *
	 * @return bool|string
	 *
	 */
	public function get_short_permalink( $post_url, $post = '' ) {
		return $this->get_shortlink_from_permalink( $post_url );
	}

	/**
	 * Get the
	 *
	 * @param $post_url
	 * @param  string  $post
	 * @param  string  $leavename
	 *
	 * @return bool|string
	 */
	public function get_post_short_permalink( $post_url, $post = '', $leavename = '' ) {
		return $this->get_shortlink_from_permalink( $post_url );
	}

	/**
	 * Delete cache
	 *
	 * @since 1.2.13
	 *
	 * @param  null  $link_id
	 *
	 */
	public function delete_cache( $link_id = null ) {
		Cache::delete_transient( 'dashboard_stats' );
	}

	/**
	 * Filter link actions
	 *
	 * @since 1.3.3
	 *
	 * @param $link
	 *
	 * @param $actions
	 *
	 * @return mixed
	 *
	 */
	public function filter_link_actions( $actions, $link ) {
		$link_id = Helper::get_data( $link, 'id', 0 );

		if ( empty( $link_id ) ) {
			return $actions;
		}

		if ( isset( $actions['stats'] ) ) {
			$track_me = Helper::get_data( $link, 'track_me', 0 );
			if ( 0 == $track_me ) {
				unset( $actions['stats'] );
			} else {
				$total_clicks = Stats::get_total_clicks_by_link_ids( $link_id );
				if ( $total_clicks == 0 ) {
					unset( $actions['stats'] );
				}
			}
		}

		return $actions;
	}

	/**
	 * Schedule cron for regenerate links json
	 *
	 * @since 1.5.1
	 */
	public function regenerate_json_links() {
		Cron::schedule_cron_for_regenerate_json_links();
	}


	/**
	 * Add "URL Shortify" as URL shortener option.
	 *
	 * @since 1.5.15
	 *
	 * @param $shortener
	 *
	 * @param $output
	 *
	 * @return string
	 *
	 */
	public function add_url_shortener_option( $output, $shortener ) {
		// use only lowercase alphanumerics, dashes, and underscores.
		// Value will be sanitized using sanitize_key.
		$output .= '<option value="url-shortify"' . selected( $shortener, 'url-shortify',
				false ) . '>URL Shortify</option>';

		return $output;
	}

	/**
	 * Do URL Shortening using URL Shortify if it's enable.
	 *
	 * @param $shrink
	 * @param $shortener
	 * @param $url
	 * @param $post_title
	 * @param $post_ID
	 * @param $test_mode
	 *
	 * @return mixed
	 */
	function do_shortening_using_url_shortify( $shrink, $shortener, $url, $post_title, $post_ID, $test_mode ) {
		// ensure that $shrink is always defined as a valid URL
		$shrink = $url;
		if ( 'url-shortify' === $shortener ) {
			// URL is not encoded when passed to this filter
			$url = urlencode( $url );

			$shrink = Helper::generate_short_link( $url );
		}

		return $shrink;
	}

	/**
	 * Add short link to posts, pages and cpt content.
	 *
	 * @since 1.7.1
	 *
	 * @param $content
	 *
	 * @return $content string.
	 *
	 */
	function add_short_link_to_cpt( $content ) {
		global $post;
		// If it is the loop and an the_except is called, we leave
		if ( ! is_single() ) {
			// If page
			if ( is_page() ) {
				return $this->_add_short_link_to_content( $content, 'page', false );
			} else {
				// Is excerpt?
				if ( ( method_exists( $this, '_modify_content' ) ) && ( ! $this->excerpt_called ) ) {
					return $this->_add_short_link_to_content( $content, get_post_type( $post->ID ), true );
				}

				return $content;
			}
		} else {

			if ( ! $this->excerpt_called ) {
				return $this->_add_short_link_to_content( $content, get_post_type( $post->ID ), false );
			}

			return $content;
		}
	}

	/**
	 * Modify content.
	 *
	 * @since 1.7.1
	 *
	 * @param $type
	 * @param $excerpt
	 *
	 * @param $content
	 *
	 * @return array|string|string[]|null
	 *
	 */
	function _add_short_link_to_content( $content, $type, $excerpt ) {
		global $post;

		$settings = US()->get_settings();

		$where_to_display = Helper::get_data( $settings, 'display_options_where_to_display', [] );

		$auto_replace_short_url_tag = Helper::get_data( $settings, 'display_options_auto_replace_short_url_tag', [] );
		if ( empty( $auto_replace_short_url_tag ) ) {
			$auto_replace_short_url_tag = [];
		}

		// Nowhere to show? bail.
		if ( empty( $where_to_display ) && empty( $auto_replace_short_url_tag ) ) {
			return $content;
		}

		$auto_create_short_link = Helper::get_data( $settings, 'display_options_auto_create_short_link', 0 );

        $short_url = $this->get_post_short_url( $post, $auto_create_short_link );

		if ( ! $short_url ) {
			return $content;
		}

        // Replace tags with actual short URL if we found, short URL.
		if ( in_array( $type, $auto_replace_short_url_tag ) ) {
			if ( ! empty( $short_url ) ) {
				$content = $this->replace_short_url_tags( $content, $short_url );
			}
		}

        // Not to add HTML anywhere? Bail.
		if ( empty( $where_to_display ) ) {
			return $content;
		}

		$html = Helper::get_data( $settings, 'display_options_html', '' );

		// No html content found to show? bail.
		if ( empty( $html ) ) {
			return $content;
		}

        // Generate Link html once.
		$display_html = $this->replace_short_url_tags( $html, $short_url );

		if ( $excerpt ) {
			// Excerpt.
			if ( in_array( 'bottom_excerpt', $where_to_display ) ) {
				$content = $content . $display_html;
			}

			if ( in_array( 'top_excerpt', $where_to_display ) ) {
				$content = $display_html . $content;
			}
		} else {
			if ( in_array( "top_{$type}", $where_to_display ) ) {
				$content = $display_html . $content;
			}

			if ( in_array( "bottom_{$type}", $where_to_display ) ) {
				$content = $content . $display_html;
			}
		}

		return $content;
	}

	/**
	 * Set excerpt_called variable to true as it's already set.
	 *
	 * @since 1.7.1
	 *
	 * @param $content
	 *
	 * @return mixed
	 *
	 */
	function short_link_added_to_excerpt( $content ) {
		$this->excerpt_called = true;

		return $content;
	}

	/**
	 * Add short link to excerpt.
	 *
	 * @since 1.7.1
	 *
	 * @param $content
	 *
	 * @return array|string|string[]|null
	 *
	 */
	function add_short_link_to_excerpt( $content ) {
		global $post;

		$this->excerpt_called = false;

		if ( $post instanceof \WP_Post ) {
			return $this->_add_short_link_to_content( $content, get_post_type( $post->ID ), true );
		}

		return $content;
	}

	/**
     * Generate link html for display.
     *
	 * @param $html
	 * @param $post
	 * @param $auto_create_short_link bool
	 *
	 * @return array|string|string[]
     *
     * @modfiy 1.9.5
	 */
	function generate_link_html( $html, $post, $auto_create_short_link = false ) {
		$short_url = $this->get_post_short_url( $post, $auto_create_short_link );

		// Don't have Short URL? bail.
		if ( empty( $short_url ) ) {
			return '';
		}

		return $this->replace_short_url_tags( $html, $short_url );
	}

	/**
     * Get Post Short URL.
     *
	 * @param $post
	 * @param $auto_create_short_link
	 *
	 * @return string
     *
     * @since 1.9.7
	 */
	public function get_post_short_url( $post, $auto_create_short_link = false ) {
		$short_url = '';

		if ( ! $post instanceof \WP_Post ) {
			return $short_url;
		}

		$post_id = $post->ID;
		$short   = US()->db->links->get_by_cpt_id( $post_id );

		if ( ! empty( $short ) ) {
			$short_url = Helper::get_short_link( $short['slug'], $short );
		} elseif ( $auto_create_short_link ) {
			$data = [
				'post_id' => $post_id,
			];

			$short_url = Helper::generate_short_link( $data );
		}

		return $short_url;
	}

	/**
     * Replace short url tag with actual URL.
     *
	 * @param $content
	 * @param $short_url
	 *
	 * @return array|mixed|string|string[]
     *
     * @since 1.9.7
	 */
	public function replace_short_url_tags( $content, $short_url ) {
		return str_replace( '%short_url_without_link%', "$short_url",
			str_replace( '%short_url%', "<a href='$short_url'>$short_url</a>", $content ) );
	}
	/**
	 * Get the short URL.
	 *
	 * @since 1.7.1
	 *
	 * @param $post_id
	 *
	 * @param $url
	 *
	 * @return mixed|string
	 *
	 */
	function get_the_short_url( $url, $post_id ) {
		global $post;

		if ( ! $post_id && $post ) {
			$post_id = $post->ID;
		}

		$short_link = US()->db->links->get_by_cpt_id( $post_id );

		if ( ! empty( $short_link ) ) {
			$url = Helper::get_short_link( $short_link['slug'] );
		}

		return $url;
	}

	/**
	 * Add custom stylesheet.
	 *
	 * @since 1.7.1
	 * @return void
	 *
	 */
	function insert_custom_short_url_css() {
		$settings = US()->get_settings();

		$css = Helper::get_data( $settings, 'display_options_css', '' );

		echo "<style>{$css}</style>";
	}

	/**
	 * Anonymise Clicks data
	 *
	 * @since 1.7.4
	 *
	 * @param  int  $link_id
	 *
	 * @param  array  $data
	 *
	 * @return array|mixed
	 *
	 */
	public function anonymise_clicks_data( $data = [], $link_id = 0 ) {

		$settings = US()->get_settings();

		$anonymise_settings = Helper::get_data( $settings, 'general_settings_enable_anonymise_clicks_data', 'no' );

		if ( 'no' === $anonymise_settings ) {
			return $data;
		}

		if ( 'ip' === $anonymise_settings ) {
			$data['ip'] = '';
		} else {
			$data_to_anonymise = [
				'referer',
				'user_agent',
				'os',
				'device',
				'browser_type',
				'browser_version',
				'country',
				'ip',
			];

			foreach ( $data as $column => $value ) {
				if ( in_array( $column, $data_to_anonymise ) ) {
					$data[ $column ] = '';
				}
			}

		}

		return $data;
	}
}
