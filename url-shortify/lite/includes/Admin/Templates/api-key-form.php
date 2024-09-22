<?php

use KaizenCoders\URL_Shortify\Cache;
use KaizenCoders\URL_Shortify\Helper;
use KaizenCoders\URL_Shortify\Common\Utils;

$submitted = Helper::get_request_data( 'submitted' );

$nonce = Helper::get_request_data( 'us_add_api_key' );

$key       = $secret = $form_action = '';
$form_data = [];
if ( 'submitted' == $submitted && wp_verify_nonce( $nonce, 'us_add_api_key' ) ) {
	$form_data = Helper::get_post_data( 'form_data', [] );

	$key    = 'ck_' . Utils::rest_random_hash();
	$secret = 'cs_' . Utils::rest_random_hash();

	$user_id = Helper::get_data( $form_data, 'user_id', 0 );

	if ( empty( $user_id ) ) {
		$value = [
			'status'  => 'error',
			'message' => __( 'Please select valid user', 'url-shortify' ),
		];

		Cache::set_transient( 'notice', $value );

		$query_args = [
			'tab'    => 'rest-api',
			'action' => 'add-new-key',
		];

		$api_form_url = add_query_arg( $query_args, admin_url( 'admin.php?page=us_tools' ) );

		wp_safe_redirect( $api_form_url );
		exit();
	}

	$api_key_data = [
		'description'     => Helper::get_data( $form_data, 'description', '' ),
		'user_id'         => $user_id,
		'permissions'     => Helper::get_data( $form_data, 'permissions', 'read' ),
		'consumer_key'    => Helper::rest_api_hash( $key ),
		'consumer_secret' => $secret,
		'truncated_key'   => substr( $key, - 7 ),
	];

	$data = US()->db->api_keys->prepare_form_data( $api_key_data );

	$id = US()->db->api_keys->save( $data );

	if ( $id ) {
		$download_query_args = [
			'ck'     => base64_encode( $key ),
		];

		$download_url = Helper::get_action_url( $id, 'api-keys', 'download' );
        $download_url = add_query_arg( $download_query_args, $download_url );

		$delete_url   = Helper::get_action_url( $id, 'api-keys', 'delete' );

		$value = [
			'status'  => 'success',
			'message' => __( 'API Key have been saved successfully!', 'url-shortify' ),
		];

		Cache::set_transient( 'notice', $value );
	}
} else {
	$roles = [ 'Administrator', 'Editor', 'Author' ];

	$users = Helper::prepare_user_dropdown_options();

	$query_args = [
		'tab'    => 'rest-api',
		'action' => 'add-new-key',
	];

	$form_action = add_query_arg( $query_args, admin_url( 'admin.php?page=us_tools' ) );

	$form_data = [];
}

?>


<div class="wrap">
    <div class="m-5 py-2">
        <h1 class="wp-heading-inline">
			<?php echo __( 'API Keys', 'url-shortify' ); ?> > <?php echo __( 'New API Key', 'url-shortify' ); ?>
        </h1>
    </div>
    <div class="p-2">
        <div class="">
            <form class="flex-row m-4 text-left item-center" method="post"
                  action="<?php echo $form_action; ?>">

                <!-- Description -->
                <div class="flex flex-row border-b border-gray-100">
                    <div class="flex w-1/5">
                        <div class="pt-6 ml-4">
                            <label for="tag-link"><span
                                        class="block pt-1 pb-2 pr-4 ml-4 text-sm font-medium text-gray-600"><?php echo __( 'Description',
										'url-shortify' ); ?></span></label>
                        </div>
                    </div>
                    <div class="flex w-4/5">
                        <div class="w-full h-10 mt-4 mb-4 ml-16 mr-4">
                            <div class="relative h-10">
                                <input id=""
                                       class="block w-2/3 pl-3 pr-12 border-gray-400 shadow-sm form-input  focus:bg-gray-100 sm:text-sm sm:leading-5"
                                       placeholder="" name="form_data[description]"
                                       value="<?php echo esc_attr( Helper::get_data( $form_data, 'description',
									       '' ) ); ?>"
                                       size="30" maxlength="100"/>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User -->
                <div id="kc-us-users" class="flex flex-row border-b border-gray-100">
                    <div class="flex w-1/5">
                        <div class="pt-6 ml-4">
                            <label for="user"><span
                                        class="block pt-1 pb-2 pr-4 ml-4 text-sm font-medium text-gray-600"><?php echo __( 'User',
										'url-shortify' ); ?></span></label>
                        </div>
                    </div>
                    <div class="flex">
                        <div class="h-10 mt-4 mb-4 ml-16 mr-4">
                            <div class="relative h-10">
                                <select class="relative border border-gray-400 shadow-sm form-select"
                                        name="form_data[user_id]" id="kc-us-redirection-types-options">
									<?php echo Helper::prepare_user_dropdown_options( Helper::get_data( $form_data,
										'user_id', 0 ) ); ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Permissions -->
                <div class="flex flex-row border-b border-gray-100">
                    <div class="flex w-1/5">
                        <div class="pt-6 ml-4">
                            <label for="domains">
                                <span class="block pt-1 pb-2 pr-4 ml-4 text-sm font-medium text-gray-600"><?php echo __( 'Permissions',
		                                'url-shortify' ); ?></span>

                            </label>
                        </div>
                    </div>

                    <div class="flex">
                        <div class="h-10 mt-4 mb-4 ml-16 mr-4">
                            <div class="relative h-10">
                                <select class="relative border border-gray-400 shadow-sm form-select"
                                        name="form_data[permissions]" id="kc-us-redirection-types-options">
									<?php echo Helper::prepare_api_permissions_dropdown_options( Helper::get_data( $form_data,
										'permissions', 'read' ) ); ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>


				<?php if ( ! empty( $secret ) && ! empty( $key ) ) { ?>

                    <!-- Alert -->
                    <div class="ml-4 p-4 mb-4 rounded-xl text-sm bg-amber-50" role="alert">
                        <h3 class="text-amber-500 font-normal">
                            <span class="font-semibold mr-1"><?php _e('Warning:', 'url-shortify'); ?></span>
							<?php _e('Make sure to copy or download the consumer key and consumer secret', 'url-shortify'); ?>
                        </h3>
                        <p class="mt-1 text-gray-600">
							<?php _e('Make sure to copy or download the consumer key and consumer secret. After leaving this page they will not be displayed again.', 'url-shortify'); ?>
                        </p>
                    </div>


                    <!-- Consumer Key -->
                    <div class="flex flex-row border-b border-gray-100">
                        <div class="flex w-1/5">
                            <div class="pt-6 ml-4">
                                <label for="tag-link"><span
                                            class="block pt-1 pb-2 pr-4 ml-4 text-sm font-medium text-gray-600"><?php echo __( 'Consumer Key',
											'url-shortify' ); ?></span></label>
                            </div>
                        </div>
                        <div class="flex w-4/5">
                            <div class="w-full h-10 mt-4 mb-4 ml-16 mr-4">
                                <div class="relative h-10">
                                    <input id=""
                                           class="block w-2/3 pl-3 pr-12 border-gray-400 shadow-sm form-input  focus:bg-gray-100 sm:text-sm sm:leading-5"
                                           placeholder="" name="form_data[key]"
                                           value="<?php echo $key; ?>"
                                           size="30" maxlength="100" disabled/>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Consumer Secret -->
                    <div class="flex flex-row border-b border-gray-100">
                        <div class="flex w-1/5">
                            <div class="pt-6 ml-4">
                                <label for="tag-link"><span
                                            class="block pt-1 pb-2 pr-4 ml-4 text-sm font-medium text-gray-600"><?php echo __( 'Consumer Secret',
											'url-shortify' ); ?></span></label>
                            </div>
                        </div>
                        <div class="flex w-4/5">
                            <div class="w-full h-10 mt-4 mb-4 ml-16 mr-4">
                                <div class="relative h-10">
                                    <input id=""
                                           class="block w-2/3 pl-3 pr-12 border-gray-400 shadow-sm form-input  focus:bg-gray-100 sm:text-sm sm:leading-5"
                                           placeholder="" name="form_data[secret]"
                                           value="<?php echo $secret; ?>"
                                           size="30" maxlength="100" disabled/>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Download & Revoke Key -->
                    <div class="flex flex-row border-b border-gray-100 py-4 mt-2">
                        <button onclick="document.location='<?php echo $download_url; ?>'" type="button"
                                class="ml-4 rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            Download Keys
                        </button>
                        <button onclick="document.location='<?php echo $delete_url; ?>'" type="button"
                                class="ml-4 rounded-md px-3 py-2 text-center text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 bg-red-600">
                            Revoke
                        </button>
                    </div>

				<?php } else { ?>
                    <!-- Save -->
                    <input type="hidden" name="submitted" value="submitted"/>
					<?php wp_nonce_field( 'us_add_api_key', 'us_add_api_key' ); ?>
                    <p class="submit"><input type="submit" name="submit" id=""
                                             class="px-4 py-2 ml-6 mr-2 align-middle cursor-pointer kc-us-primary-button"
                                             value="<?php echo "Save"; ?>"/>
                        <a href="admin.php?page=us_tools&tab=rest-api"
                           class="px-4 py-2 mx-2 my-2 text-sm font-medium leading-5 align-middle transition duration-150 ease-in-out border border-indigo-600 rounded-md cursor-pointer hover:shadow-md focus:outline-none focus:shadow-outline-indigo">
							<?php
							_e( 'Cancel',
								'url-shortify' );
							?>
                        </a></p>
				<?php } ?>
            </form>
        </div>
    </div>
</div>
