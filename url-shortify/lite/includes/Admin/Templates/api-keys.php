<?php

use KaizenCoders\URL_Shortify\Helper;

$api_keys = US()->db->api_keys->get_all();

$query_args = [
	'tab' => 'rest-api',
	'action' => 'add-new-key',
];

$add_new_link = add_query_arg( $query_args, admin_url( 'admin.php?page=us_tools' ) );

$permissions = Helper::get_api_permissions();

?>

<div class="wrap">
    <div class="px-8 py-2">
	<h1 class="wp-heading-inline">
        <?php echo __('API Keys', 'url-shortify'); ?>
	</h1>
    <span class="font-bold leading-7 text-gray-900 sm:leading-9 sm:truncate">
        <button onclick="document.location='<?php echo esc_url( $add_new_link ); ?>'" type="button"
                class="ml-2 rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"><?php _e( 'Add API Key',
		        'url-shortify' ); ?></button>
    </span>
    </div>
    <div class="px-8 pb-2">
        <div class="m-10 ring-1 ring-gray-300 sm:mx-0 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
                <?php if ( Helper::is_forechable( $api_keys ) ) { ?>
                <thead>
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6"><?php _e('ID', 'url-shortify'); ?></th>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6"><?php _e('Description', ''); ?></th>
                    <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-gray-900 lg:table-cell"><?php _e('Consumer Key', 'url-shortify'); ?></th>
                    <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-gray-900 lg:table-cell"><?php _e('User', 'url-shortify'); ?></th>
                    <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-gray-900 lg:table-cell"><?php _e('Permissions', 'url-shortify'); ?></th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"><?php _e('Last Access', 'url-shortify'); ?></th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"><span class="sr-only"><?php _e('Actions', 'url-shortify'); ?></span> </th>
                </tr>
                </thead>
                <tbody>
                        <?php foreach ( $api_keys as $id => $api_key ) { ?>
                            <tr class="even:bg-gray-50">
                                <td class="relative py-4 pl-4 pr-3 text-sm sm:pl-6"><?php echo $id+1; ?></td>
                                <td class="relative py-4 pl-4 pr-3 text-sm sm:pl-6"><?php echo $api_key['description']; ?></td>
                                <td class="hidden px-3 py-3.5 text-sm text-gray-500 lg:table-cell"><?php echo "<code>..." . $api_key['truncated_key'] . "</code>"; ?></td>
                                <td class="hidden px-3 py-3.5 text-sm text-gray-500 lg:table-cell">
	                                <?php
	                                $user = get_user_by( 'id', $api_key['user_id'] );
	                                if ( ! $user ) {
		                                $value = '';
	                                } elseif ( current_user_can( 'edit_user', $user->ID ) ) {
		                                $value = '<a href="' . esc_url( get_edit_user_link( $user->ID ) ) . '">' . esc_html( $user->display_name ) . '</a>';
	                                } else {
		                                $value = esc_html( $user->display_name );
	                                }

                                    echo $value;
	                                ?>
                                </td>
                                <td class="hidden px-3 py-3.5 text-sm text-gray-500 lg:table-cell"><?php echo $permissions[ $api_key['permissions'] ]; ?></td>
                                <td class="px-3 py-3.5 text-sm text-gray-500"><?php echo $api_key['last_access']; ?></td>
                                <td class="px-3 py-3.5 text-sm text-gray-500">
	                                <?php $delete_url = Helper::get_action_url( $api_key['id'], 'api-keys',
		                                'delete' ); ?>
                                    <a href="<?php echo $delete_url; ?>" class="text-red-500 hover:text-red-400"><?php _e('Revoke', 'url-shortify'); ?></a>
                                </td>
                            </tr>
                        <?php } ?>
                </tbody>
                <?php } else { ?>
                    <div class="block ml-auto mr-auto" style="width:50%;">
                        <img src="<?php echo KC_US_PLUGIN_ASSETS_DIR_URL . '/images/empty.svg' ?>"/>
                    </div>
                <?php } ?>
                <!-- More plans... -->
            </table>
        </div>
    </div>
</div>
