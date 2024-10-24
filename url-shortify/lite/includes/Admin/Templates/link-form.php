<?php

use KaizenCoders\URL_Shortify\Helper;

$form_data = Helper::get_data( $template_data, 'form_data', array() );

$redirection_types = Helper::get_data( $template_data, 'redirection_types', '' );
$groups            = Helper::get_data( $template_data, 'groups', array() );
$title             = Helper::get_data( $template_data, 'title', '' );
$link_id           = Helper::get_data( $template_data, 'link_id', 0 );

$button_text       = Helper::get_data( $template_data, 'button_text', '' );
$form_action       = Helper::get_data( $template_data, 'form_action', '' );
$blog_url          = Helper::get_data( $template_data, 'blog_url', '' );
$domains           = Helper::get_data( $template_data, 'domains', array() );

$rules             = Helper::get_data( $form_data, 'rules', array() );
$default_domain    = Helper::get_data( $form_data, 'default_domain', '' );

$group_url = admin_url( 'admin.php?page=us_groups&action=new' );

$dynamic_redirect_type = Helper::get_data($rules, 'dynamic_redirect_type', 'off');

$settings = array(

	'nofollow'          => array(
		'title' => __( 'No Follow', 'url-shortify' ),
		'desc'  => ''
	),
	'sponsored'         => array(
		'title' => __( 'Sponsored', 'url-shortify' ),
		'desc'  => ''
	),
	'params_forwarding' => array(
		'title' => __( 'Parameter Forwarding', 'url-shortify' ),
		'desc'  => ''
	),


	'track_me' => array(
		'title' => __( 'Tracking', 'url-shortify' ),
		'desc'  => ''
	)

);


?>

<div class="max-w-full mt-1 font-sans wrap">
    <header class="wp-heading-inline">
        <div class="justify-center md:flex md:items-center md:justify-between">
            <div class="flex-1 min-w-0">
                <h1 class="text-xl leading-7 text-gray-900 sm:text-3xl sm:leading-9 sm:truncate">
					<span class="text-xl font-bold leading-7 text-gray-900 sm:text-xl sm:leading-9 sm:truncate">
						<a href="admin.php?page=us_links">
							<?php _e( 'Links ', 'url-shortify' ); ?>
						</a>
					</span>
                    <svg fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                         stroke="currentColor" viewBox="0 0 24 24" class="inline-block w-4 h-4 align-middle">
                        <path d="M9 5l7 7-7 7"></path>
                    </svg>
					<?php echo $title; ?>
                </h1>
            </div>
            <div class="flex float-right text-center mr-2 w-1/12">
				<?php if ( US()->is_pro() ) {
					echo Helper::get_social_share_widget( $link_id, 2 );
				} ?>
            </div>
        </div>
    </header>
    <div class="">
        <hr class="wp-header-end">
    </div>
    <div id="poststuff">
        <div id="post-body" class="metabox-holder column-1">
            <div id="post-body-content">
                <div class="bg-white rounded-lg shadow-md meta-box-sortables ui-sortable">
                    <form class="flex-row pt-8 mt-2 ml-5 mr-4 text-left item-center" method="post"
                          action="<?php echo $form_action; ?>">

                        <!-- Title -->
                        <div class="flex flex-row border-b border-gray-100">
                            <div class="flex w-1/5">
                                <div class="pt-6 ml-4">
                                    <label for="tag-link"><span
                                                class="block pt-1 pb-2 pr-4 ml-4 text-sm font-medium text-gray-600"><?php echo __( 'Title', 'url-shortify' ); ?></span></label>
                                </div>
                            </div>
                            <div class="flex w-4/5">
                                <div class="w-full h-10 mt-4 mb-4 ml-16 mr-4">
                                    <div class="relative h-10">
                                        <input id=""
                                               class="block w-2/3 pl-3 pr-12 border-gray-400 shadow-sm form-input  focus:bg-gray-100 sm:text-sm sm:leading-5"
                                               placeholder="" name="form_data[name]"
                                               value="<?php echo esc_attr( Helper::get_data( $form_data, 'name', '' ) ); ?>"
                                               size="30" maxlength="100"/>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Target URL -->
                        <div class="flex flex-row border-b border-gray-100">
                            <div class="flex w-1/5">
                                <div class="pt-6 ml-4">
                                    <label for="tag-link"><span
                                                class="block pt-1 pb-2 pr-4 ml-4 text-sm font-medium text-gray-600"><?php echo __( 'Target URL', 'url-shortify' ); ?></span></label>
                                </div>
                            </div>
                            <div class="flex w-4/5">
                                <div class="w-full h-10 mt-4 mb-10 ml-16 mr-4">
                                    <div class="relative h-10">
										<?php if ( empty( Helper::get_data( $form_data, 'cpt_id', '' ) ) ) { ?>
                                            <textarea id="about" rows="2" cols="53"
                                                      class="block w-2/3 transition duration-150 ease-in-out border-gray-400 form-textarea sm:text-sm sm:leading-5"
                                                      name="form_data[url]"><?php echo Helper::get_data( $form_data, 'url', '' ); ?></textarea>
										<?php } else { ?>
                                            <div class="inline-flex items-center px-3 py-3 text-gray-500 bg-gray-200 border border-gray-400 rounded-md sm:text-sm">
											<?php echo Helper::get_data( $form_data, 'url', '' ); ?>
											  <input type="hidden" name="form_data[url]"
                                                     value="<?php echo esc_url( Helper::get_data( $form_data, 'url', '' ) ); ?>"/>
										    </div>

                                            <p class="field-desciption mb-10 text-xs italic font-normal leading-snug text-gray-500 helper mt-2"><?php echo sprintf(__('You can not modify this target URL because this is attached to <a href="%s" target="_blank">this</a> post, page or custom post type.', 'url-shortify'),esc_url( Helper::get_data( $form_data, 'url', '' )), esc_url( Helper::get_data( $form_data, 'url', '' ) )); ?></p>

										<?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Short URL -->
                        <div class="flex flex-row border-b border-gray-100">
                            <div class="flex w-1/5">
                                <div class="pt-6 ml-4">
                                    <label for="tag-link"><span
                                                class="block pt-1 pb-2 pr-4 ml-4 text-sm font-medium text-gray-600"><?php echo __( 'Short URL', 'url-shortify' ); ?></span></label>
                                </div>
                            </div>

                            <div class="flex w-4/5">
                                <div class="w-full h-10 mt-4 mb-4 ml-16 mr-4">
                                    <div class="relative flex w-2/3 h-10">
										<span class="inline-flex items-center px-3 text-gray-500 bg-gray-200 border border-r-0 border-gray-400 rounded-l-md sm:text-sm">
											<?php echo esc_html( $blog_url ); ?>
										</span>
                                        <input id=""
                                               class="block w-1/3 pl-3 border-gray-400 rounded-l-none shadow-sm form-input focus:bg-gray-100 sm:text-sm sm:leading-5"
                                               placeholder="" name="form_data[slug]" id=""
                                               value="<?php echo esc_attr( Helper::get_data( $form_data, 'slug', '' ) ); ?>"
                                               size="30" maxlength="100"/>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Redirection Type -->
						<?php if ( Helper::is_forechable( $redirection_types ) ) { ?>
                            <div id="kc-us-redirection-types" class="flex flex-row border-b border-gray-100">
                                <div class="flex w-1/5">
                                    <div class="pt-6 ml-4">
                                        <label for="redirecton-types"><span
                                                    class="block pt-1 pb-2 pr-4 ml-4 text-sm font-medium text-gray-600"><?php echo __( 'Redirection', 'url-shortify' ); ?></span></label>
                                    </div>
                                </div>
                                <div class="flex">
                                    <div class="h-10 mt-4 mb-4 ml-16 mr-4">
                                        <div class="relative h-10">
                                            <select class="relative border border-gray-400 shadow-sm form-select"
                                                    name="form_data[redirect_type]" id="kc-us-redirection-types-options">
												<?php foreach ( $redirection_types as $value => $option ) { ?>
                                                    <option value="<?php echo $value; ?>"
														<?php
														if ( Helper::get_data( $form_data, 'redirect_type', '' ) == $value ) {
															echo 'selected=selected';
														}
														?>
                                                    > <?php echo esc_html( $option ); ?> </option>
												<?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
						<?php } ?>

                        <!-- Tracking Pixel -->
	                    <?php
	                    if ( US()->is_pro() ) {
		                    do_action( 'kc_us_add_tracking_pixels', $form_data );
	                    } ?>

                        <!-- Domains -->
						<?php if ( Helper::is_forechable( $domains ) ) { ?>
                            <div class="flex flex-row border-b border-gray-100">
                                <div class="flex w-1/5">
                                    <div class="pt-6 ml-4">
                                        <label for="domains">
                                            <span class="block pt-1 pb-2 pr-4 ml-4 text-sm font-medium text-gray-600"><?php echo __( 'Domain', 'url-shortify' ); ?></span>

                                        </label>
                                    </div>
                                </div>

                                <div class="flex">
                                    <div class="h-10 mt-4 mb-4 ml-16 mr-4">
                                        <div class="relative h-10">
                                            <select class="relative border border-gray-400 shadow-sm form-select"
                                                    name="form_data[rules][domain]" id="">
												<?php foreach ( $domains as $value => $option ) { ?>
                                                    <option value="<?php echo $value; ?>"
														<?php
														if ( Helper::get_data( $rules, 'domain', $default_domain ) == $value ) {
															echo 'selected=selected';
														}
														?>
                                                    > <?php echo esc_html( $option ); ?> </option>
												<?php } ?>
                                            </select>
											<?php if ( US()->is_pro() ) { ?>
                                                <p class="field-desciption mb-2 text-xs italic font-normal leading-snug text-gray-500 helper mt-2"><?php _e( 'Select on which domain should this short link be accessible.', 'url-shortify' ); ?></p>
											<?php } else { ?>
                                                <h3 class="text-sm leading-5 font-medium text-green-800"><?php echo sprintf( __( 'Want to use shortlinks with custom domains? <a href="%s">Upgrade Now</a>', 'url-shortify' ), US()->get_landing_page_url() ); ?></h3>
											<?php } ?>

                                        </div>
                                    </div>
                                </div>
                            </div>
						<?php } ?>

                        <!-- Groups -->
                        <div class="flex flex-row border-b border-gray-100">
                            <div class="flex w-1/5">
                                <div class="pt-6 ml-4">
                                    <label for="groups"><span
                                                class="block pt-1 pb-2 pr-4 ml-4 text-sm font-medium text-gray-600"><?php echo __( 'Groups', 'url-shortify' ); ?></span></label>
                                </div>
                            </div>
                            <div class="flex">
                                <div class="h-10 mt-6 mb-4 ml-16 mr-4">
                                    <div class="relative h-10">
										<?php if ( Helper::is_forechable( $groups ) ) {  asort($groups); ?>
                                            <select class="kc-us-groups relative border border-gray-400 shadow-sm form-multiselect block w-full mt-1"
                                                    name="form_data[group_ids][]" id="kc-us-groups" multiple="multiple">
												<?php foreach ( $groups as $group_id => $name ) { ?>
                                                    <option value="<?php echo $group_id; ?>"
														<?php
														if ( in_array( $group_id, Helper::get_data( $form_data, 'group_ids', array() ) ) ) {
															echo 'selected=selected';
														}
														?>
                                                    > <?php echo esc_html( $name ); ?> </option>
												<?php } ?>
                                            </select>
										<?php } else { ?>
                                            <div class="flex rounded bg-blue-50">
                                                <div class="flex-shrink-0">
                                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor"
                                                         viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                              d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                              clip-rule="evenodd"/>
                                                    </svg>
                                                </div>
                                                <div class="ml-3">
                                                    <h3 class="text-sm leading-5 font-medium text-green-800">
														<?php echo sprintf( __( 'No Group found. <a href="%s">Create a new Group</a> and add link to group.', 'url-shortify' ), $group_url ); ?>
                                                    </h3>
                                                </div>
                                            </div>
										<?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Settings (no follow, sponsored, parameter forwarding, tracking) -->
						<?php foreach ( $settings as $key => $setting ) { ?>
                            <div class="flex border-b border-gray-100">
                                <div class="w-2/5 mr-16">
                                    <div class="flex flex-row w-full">
                                        <div class="flex w-2/4">
                                            <div class="pt-4 mb-2 ml-4 mr-4 mr-8">
                                                <label for="tag-link"><span
                                                            class="block pb-2 pr-4 ml-4 text-sm font-medium text-gray-600"><?php echo $setting['title']; ?></span></label>
                                            </div>
                                        </div>
                                        <div class="flex">
                                            <div class="pt-3 mb-4 ml-16 mr-4">
                                                <label for="<?php echo $key; ?>"
                                                       class="inline-flex items-center cursor-pointer ">
												<span class="relative">
													<input id="<?php echo $key; ?>" type="checkbox"
                                                           class="absolute w-0 h-0 mt-6 opacity-0 kc-us-check-toggle"
                                                           name="form_data[<?php echo $key; ?>]" value="1"
																		  <?php
																		  if ( Helper::get_data( $form_data, $key, 0 ) == 1 ) {
																			  echo 'checked="checked"';
																		  }

																		  ?>
													 />

													<span class="kc-us-mail-toggle-line"></span>
													<span class="kc-us-mail-toggle-dot"></span>
												</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
						<?php } ?>

                        <!-- UTM Tracking -->
						<?php
						if ( US()->is_pro() ) {
							do_action( 'kc_us_add_utm_parameters', $form_data );
						} ?>

                        <!-- Dynamic Redirection -->
	                    <?php
	                    if ( US()->is_pro() ) {
		                    do_action( 'kc_us_add_dynamic_redirection', $form_data );
	                    } else { ?>
                            <div class="flex flex-row border-b border-gray-100">
                                <div class="flex w-1/5">
                                    <div class="pt-6 ml-4">
                                        <label for="domains">
                                            <span class="block pt-1 pb-2 pr-4 ml-4 text-sm font-medium text-gray-600"><?php echo __( 'Dynamic Redirection', 'url-shortify' ); ?></span>
                                        </label>
                                    </div>
                                </div>
                                <div class="flex">
                                    <div class="h-10 mt-6 mb-4 ml-16 mr-4">
                                        <div class="relative h-10">
                                            <div class="flex rounded bg-blue-50">
                                                <div class="flex-shrink-0">
                                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor"
                                                         viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                              d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                              clip-rule="evenodd"/>
                                                    </svg>
                                                </div>
                                                <div class="ml-3">
                                                    <h3 class="text-sm leading-5 font-medium text-green-800">
                                                        <h3 class="text-sm leading-5 font-medium text-green-800"><?php echo sprintf( __( 'Want to redirect to different URLs\'s based on geo location, browser, os, device. Or do you want to rotate links and split test? <a href="%s">Upgrade Now</a>', 'url-shortify' ), US()->get_landing_page_url() ); ?></h3>
                                                    </h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>


                        <!-- Expiration Date -->
						<?php
						if ( US()->is_pro() ) {
							do_action( 'kc_us_add_expiry_option', $form_data );
						} else {
							?>
                            <div class="flex flex-row border-b border-gray-100">
                                <div class="flex w-1/5">
                                    <div class="pt-6 ml-4">
                                        <label for="expiration"><span
                                                    class="block pt-1 pb-2 pr-4 ml-4 text-sm font-medium text-gray-600"><?php echo __( 'Expiration Date', 'url-shortify' ); ?></span></label>
                                    </div>
                                </div>
                                <div class="flex">
                                    <div class="h-10 mt-6 mb-4 ml-16 mr-4">
                                        <div class="relative h-10">
                                            <div class="flex rounded bg-blue-50">
                                                <div class="flex-shrink-0">
                                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor"
                                                         viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                              d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                              clip-rule="evenodd"/>
                                                    </svg>
                                                </div>
                                                <div class="ml-3">
                                                    <h3 class="text-sm leading-5 font-medium text-green-800">
														<?php echo sprintf( __( 'Set expiry date of the link with PRO version. <a href="%s">Upgrade Now</a>', 'url-shortify' ), US()->get_landing_page_url() ); ?>
                                                    </h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

						<?php } ?>


                        <!-- Password  -->
						<?php
						if ( US()->is_pro() ) {
							do_action( 'kc_us_add_password_option', $form_data );
						} else {
							?>
                            <div class="flex flex-row border-b border-gray-100">
                                <div class="flex w-1/5">
                                    <div class="pt-6 ml-4">
                                        <label for="password"><span
                                                    class="block pt-1 pb-2 pr-4 ml-4 text-sm font-medium text-gray-600"><?php echo __( 'Password', 'url-shortify' ); ?></span></label>
                                    </div>
                                </div>
                                <div class="flex">
                                    <div class="h-10 mt-6 mb-4 ml-16 mr-4">
                                        <div class="relative h-10">
                                            <div class="flex rounded bg-blue-50">
                                                <div class="flex-shrink-0">
                                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor"
                                                         viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                              d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                              clip-rule="evenodd"/>
                                                    </svg>
                                                </div>
                                                <div class="ml-3">
                                                    <h3 class="text-sm leading-5 font-medium text-green-800">
														<?php echo sprintf( __( 'Protect your short link with password using PRO version. <a href="%s">Upgrade Now</a>', 'url-shortify' ), US()->get_pricing_url() ); ?>
                                                    </h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
						<?php } ?>

                        <!-- Notes -->
                        <div class="flex flex-row border-b border-gray-100">
                            <div class="flex w-1/5">
                                <div class="pt-6 ml-4">
                                    <label for="tag-link"><span
                                                class="block pt-1 pb-2 pr-4 ml-4 text-sm font-medium text-gray-600"><?php echo __( 'Notes', 'url-shortify' ); ?></span></label>
                                </div>
                            </div>
                            <div class="flex w-4/5 pb-10">
                                <div class="w-full h-10 mt-4 mb-4 ml-16 mr-4">
                                    <div class="relative w-full h-10">
                                        <textarea id="about" rows="3"
                                                  class="block w-2/3 transition duration-150 ease-in-out border-gray-400 form-textarea sm:text-sm sm:leading-5"
                                                  name="form_data[description]"><?php echo Helper::get_data( $form_data, 'description', '' ); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Save -->
                        <input type="hidden" name="submitted" value="submitted"/>
                        <input type="hidden" name="form_data[cpt_id]"
                               value="<?php echo Helper::get_data( $form_data, 'cpt_id', '' ); ?>"/>
                        <p class="submit"><input type="submit" name="submit" id=""
                                                 class="px-4 py-2 ml-6 mr-2 align-middle cursor-pointer kc-us-primary-button"
                                                 value="<?php echo $button_text; ?>"/><a href="admin.php?page=us_links"
                                                                                         class="px-4 py-2 mx-2 my-2 text-sm font-medium leading-5 align-middle transition duration-150 ease-in-out border border-indigo-600 rounded-md cursor-pointer hover:shadow-md focus:outline-none focus:shadow-outline-indigo">
								<?php
								_e( 'Cancel',
									'url-shortify' );
								?>
                            </a></p>
                    </form>
                </div>
            </div>
        </div>
    </div>