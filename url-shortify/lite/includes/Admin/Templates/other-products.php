<?php

use KaizenCoders\URL_Shortify\Tracker;

$current_plugin   = 'url-shortify';
$active_plugins   = Tracker::get_active_plugins();
$inactive_plugins = Tracker::get_inactive_plugins();
$all_plugins      = Tracker::get_plugins();

$kaizencoders_url = 'https://kaizencoders.com';

$plugins = [
	[
		'title'       => __( 'Social Linkz', 'url-shortify' ),
		'logo'        => 'https://ps.w.org/social-linkz/assets/icon-256x256.png',
		'desc'        => __( 'Lightweight and fast social media sharing plugin', 'url-shortify' ),
		'name'        => 'social-linkz/social-linkz.php',
		'install_url' => admin_url( 'plugin-install.php?s=social-likz&tab=search&type=term' ),
		'plugin_url'  => 'https://wordpress.org/plugins/social-linkz/',
		'slug'        => 'social-linkz',
		'is_premium'  => false,
	],
	[
		'title'       => __( 'URL Shortify', 'url-shortify' ),
		'logo'        => 'https://ps.w.org/url-shortify/assets/icon-256x256.png',
		'desc'        => __( 'Simple, Powerful and Easy URL Shortener Plugin For WordPress', 'url-shortify' ),
		'name'        => 'url-shortify/url-shortify.php',
		'install_url' => admin_url( 'plugin-install.php?s=url+shortify&tab=search&type=term' ),
		'plugin_url'  => 'https://wordpress.org/plugins/url-shortify/',
		'is_premium'  => false,
		'slug'        => 'url-shortify',
	],

	[
		'title'       => __( 'Update URLs', 'url-shortify' ),
		'logo'        => 'https://ps.w.org/update-urls/assets/icon-256x256.png',
		'desc'        => __( 'Quick and Easy way to search old links and replace them with new links in WordPress',
			'url-shortify' ),
		'name'        => 'update-urls/update-urls.php',
		'install_url' => admin_url( 'plugin-install.php?s=update+urls&tab=search&type=term' ),
		'plugin_url'  => 'https://wordpress.org/plugins/update-urls/',
		'is_premium'  => false,
		'slug'        => 'update-urls',
	],
	[
		'title'       => __( 'Logify', 'url-shortify' ),
		'logo'        => 'https://ps.w.org/logify/assets/icon-256x256.png',
		'desc'        => __( 'Simple and Easy To Use Activity Log Plugin For WordPress',
			'url-shortify' ),
		'name'        => 'logify/logify.php',
		'install_url' => admin_url( 'plugin-install.php?s=logify&tab=search&type=term' ),
		'plugin_url'  => 'https://wordpress.org/plugins/logify/',
		'is_premium'  => false,
		'slug'        => 'logify',
	],

	[
		'title'       => __( 'Magic Link', 'url-shortify' ),
		'logo'        => 'https://ps.w.org/magic-link/assets/icon-256x256.png',
		'desc'        => __( 'Simple, Easy and Secure one click login for WordPress.',
			'url-shortify' ),
		'name'        => 'magic-link/magic-link.php',
		'install_url' => admin_url( 'plugin-install.php?s=magic-link&tab=search&type=term' ),
		'plugin_url'  => 'https://wordpress.org/plugins/magic-link/',
		'is_premium'  => false,
		'slug'        => 'magic-link',
	],

	[
		'title'       => __( 'Zapify', 'url-shortify' ),
		'logo'        => 'https://ps.w.org/zapify/assets/icon-256x256.png?rev=3171937',
		'desc'        => __( 'Transform your WordPress experience by automating repetitive tasks effortlessly',
			'url-shortify' ),
		'name'        => 'zapify/zapify.php',
		'install_url' => admin_url( 'plugin-install.php?s=zapify&tab=search&type=term' ),
		'plugin_url'  => 'https://wordpress.org/plugins/zapify/',
		'is_premium'  => false,
		'slug'        => 'zapify',
	],

	[
		'title'       => __( 'Utilitify', 'url-shortify' ),
		'logo'        => 'https://ps.w.org/utilitify/assets/icon-256x256.png',
		'desc'        => __( 'Supercharge Your WordPress Site With Powerpack WordPress Utilities', 'url-shortify' ),
		'name'        => 'utilitify/utilitify.php',
		'install_url' => admin_url( 'plugin-install.php?s=utilitify&tab=search&type=term' ),
		'plugin_url'  => 'https://wordpress.org/plugins/utilitify/',
		'is_premium'  => false,
		'slug'        => 'utilitify',
	],

];

?>

<div class="bg-gray-200 flex flex-wrap w-full mt-4 mb-7">
    <div class="grid w-full text-center m-5">
        <h3 class="text-3xl font-bold leading-9 text-gray-700 sm:truncate mb-3 text-center"><?php echo sprintf( 'Other awesome plugins from <a href="%s" target="_blank">KaizenCoders</a>',
				$kaizencoders_url ); ?></h3>
    </div>
    <div class="grid w-full grid-cols-3">
		<?php foreach ( $plugins as $plugin ) {
			if ( $current_plugin == $plugin['slug'] ) {
				continue;
			}
			?>
            <div class="flex flex-col m-2 mb-4 mr-3 bg-white rounded-lg shadow">
                <div class="flex h-48">
                    <div class="flex pl-1">
                        <div class="flex w-1/4 rounded px-2">
                            <div class="flex flex-col w-full h-6">
                                <div>
                                    <img class="mx-auto my-4 border-0 h-15"
                                         src="<?php echo esc_url( $plugin['logo'] ); ?>" alt="">
                                </div>
                            </div>
                        </div>
                        <div class="flex w-3/4 pt-2">
                            <div class="flex flex-col">
                                <div class="flex w-full">
                                    <a href="<?php echo esc_url( $plugin['plugin_url'] ); ?>" target="_blank"><h3
                                                class="pb-2 pl-2 mt-2 text-lg font-medium text-indigo-600"><?php echo esc_html( $plugin['title'] ); ?></h3>
                                    </a>
                                </div>
                                <div class="flex w-full pl-2 leading-normal xl:pb-4 lg:pb-2 md:pb-2">
                                    <h4 class="pt-1 pr-4 text-sm text-gray-700"><?php echo esc_html( $plugin['desc'] ); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-row mb-0 border-t">
                    <div class="flex w-2/3 px-3 py-5 text-sm"><?php echo esc_html__( 'Status', 'url-shortify' ); ?>:
						<?php if ( in_array( $plugin['name'], $active_plugins ) ) { ?>
                            <span class="font-bold text-green-600">&nbsp;<?php echo esc_html__( 'Active',
									'url-shortify' ); ?></span>
						<?php } elseif ( in_array( $plugin['name'], $inactive_plugins ) ) { ?>
                            <span class="font-bold text-red-600">&nbsp;<?php echo esc_html__( 'Inactive',
									'url-shortify' ); ?></span>
						<?php } else { ?>
                            <span class="font-bold text-orange-500">&nbsp;<?php echo esc_html__( 'Not Installed',
									'url-shortify' ); ?></span>
						<?php } ?>
                    </div>
                    <div class="flex justify-center w-1/3 py-3 md:pr-4">
		  <span class="rounded-md shadow-sm">
				<?php if ( ! in_array( $plugin['name'], $active_plugins ) ) { ?>
			  <a href="<?php echo esc_url( $plugin['install_url'] ); ?>" target="_blank">
					<?php
					}

					if ( ! in_array( $plugin['name'], $all_plugins ) ) {

					if ( isset( $plugin['is_premium'] ) && true === $plugin['is_premium'] ) { ?>
                                    <button type="button"
                                            class="inline-flex justify-center w-full px-4 py-2 text-sm font-medium leading-5 text-white transition duration-150 ease-in-out bg-green-500 border border-transparent rounded-md hover:bg-green-600 focus:outline-none focus:shadow-outline-blue">
                                        <?php echo esc_html__( 'Buy Now', 'url-shortify' ); ?>
	                                    <?php } else { ?>
                                    <button type="button"
                                            class="inline-flex justify-center w-full px-4 py-2 text-sm font-medium leading-5 text-white transition duration-150 ease-in-out bg-orange-400 border border-transparent rounded-md hover:bg-orange-500 focus:outline-none focus:shadow-outline-blue">
                                        <?php echo esc_html__( 'Install', 'url-shortify' ); ?>
                                        <?php } ?>
                        </button>
					<?php } elseif ( in_array( $plugin['name'], $inactive_plugins ) ) { ?>
                        <button type="button"
                                class="inline-flex justify-center w-full px-4 py-2 text-sm font-medium leading-5 text-white transition duration-150 ease-in-out bg-indigo-500 border border-transparent rounded-md hover:bg-indigo-600 focus:outline-none focus:shadow-outline-blue">
					<?php echo esc_html__( 'Activate', 'url-shortify' ); ?> </button>
					<?php } ?>
			  </a>
			</span>
                    </div>
                </div>
            </div>
		<?php } ?>

    </div>
</div>

