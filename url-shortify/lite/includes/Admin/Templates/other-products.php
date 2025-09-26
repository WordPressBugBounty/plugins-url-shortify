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
        <h3 class="text-3xl font-bold leading-9 text-gray-700 sm:truncate mb-3 text-center"><?php
            echo sprintf( 'Other awesome plugins from <a href="%s" target="_blank">KaizenCoders</a>',
                    $kaizencoders_url ); ?></h3>
    </div>
    <div class="grid w-full grid-cols-3">
        <?php
        foreach ( $plugins as $plugin ) {
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
                                         src="<?php
                                         echo esc_url( $plugin['logo'] ); ?>" alt="">
                                </div>
                            </div>
                        </div>
                        <div class="flex w-3/4 pt-2">
                            <div class="flex flex-col">
                                <div class="flex w-full">
                                    <a href="<?php
                                    echo esc_url( $plugin['plugin_url'] ); ?>" target="_blank"><h3
                                                class="pb-2 pl-2 mt-2 text-lg font-medium text-indigo-600"><?php
                                            echo esc_html( $plugin['title'] ); ?></h3>
                                    </a>
                                </div>
                                <div class="flex w-full pl-2 leading-normal xl:pb-4 lg:pb-2 md:pb-2">
                                    <h4 class="pt-1 pr-4 text-sm text-gray-700"><?php
                                        echo esc_html( $plugin['desc'] ); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-row mb-0 border-t">
                    <div class="flex w-2/3 px-3 py-5 text-sm"><?php
                        echo esc_html__( 'Status', 'url-shortify' ); ?>:
                        <?php
                        if ( in_array( $plugin['name'], $active_plugins ) ) { ?>
                            <span class="font-bold text-green-600"><?php
                                echo esc_html__( 'Active', 'url-shortify' ); ?></span>
                            <?php
                        } elseif ( in_array( $plugin['name'], $inactive_plugins ) ) { ?>
                            <span class="font-bold text-red-600">&nbsp;<?php
                                echo esc_html__( 'Inactive', 'url-shortify' ); ?></span>
                            <?php
                        } else { ?>
                            <span class="font-bold text-orange-500">&nbsp;<?php
                                echo esc_html__( 'Not Installed', 'url-shortify' ); ?></span>
                            <?php
                        } ?>
                    </div>

                    <!-- Buttons -->
                    <div class="flex justify-center w-1/3 py-3 md:pr-4">
                        <div class="plugin-action-container relative">
                            <span class="rounded-md shadow-sm">
            <?php
            if ( ! in_array( $plugin['name'], $all_plugins ) ) : ?>
                <button type="button"
                        class="plugin-action-btn inline-flex justify-center w-full px-4 py-2 text-sm font-medium leading-5 text-white transition duration-150 ease-in-out bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none"
                        data-action="install"
                        data-plugin="<?php
                        echo esc_attr( $plugin['name'] ); ?>"
                        data-slug="<?php
                        echo esc_attr( $plugin['slug'] ); ?>">
                    <?php
                    echo esc_html__( 'Install', 'url-shortify' ); ?>
                </button>
            <?php
            elseif ( in_array( $plugin['name'], $inactive_plugins ) ) : ?>
                <button type="button"
                        class="plugin-action-btn inline-flex justify-center w-full px-4 py-2 text-sm font-medium leading-5 text-white transition duration-150 ease-in-out bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none"
                        data-action="activate"
                        data-plugin="<?php
                        echo esc_attr( $plugin['name'] ); ?>"
                        data-slug="<?php
                        echo esc_attr( $plugin['slug'] ); ?>">
                    <?php
                    echo esc_html__( 'Activate', 'url-shortify' ); ?>
                </button>
            <?php
            elseif ( in_array( $plugin['name'], $active_plugins ) ) : ?>
                <button type="button"
                        class="plugin-action-btn inline-flex justify-center w-full px-4 py-2 text-sm font-medium leading-5 text-white transition duration-150 ease-in-out bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none"
                        data-action="deactivate"
                        data-plugin="<?php
                        echo esc_attr( $plugin['name'] ); ?>"
                        data-slug="<?php
                        echo esc_attr( $plugin['slug'] ); ?>"><?php
                    echo esc_html__( 'Deactivate', 'url-shortify' ); ?>
                </button>
            <?php
            endif; ?>
        </span>
                            <div class="spinner-container absolute left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2 hidden">
                                <div class="spinner"></div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <?php
        } ?>

    </div>

    <script>
		document.addEventListener('DOMContentLoaded', function () {
			const buttons = document.querySelectorAll('.plugin-action-btn');

			buttons.forEach(button => {
				button.addEventListener('click', async function (e) {
					const button = e.currentTarget;
					const container = button.closest('.plugin-action-container');
					const spinnerContainer = container.querySelector('.spinner-container');
					const action = button.dataset.action;
					const plugin = button.dataset.plugin;
					const slug = button.dataset.slug;

					button.classList.add('button-disabled');
					spinnerContainer.classList.remove('hidden');

					try {
						const response = await fetch(ajaxurl, {
							method: 'POST',
							headers: {
								'Content-Type': 'application/x-www-form-urlencoded',
							},
							body: new URLSearchParams({
								action: 'url_shortify_manage_plugin',
								nonce: '<?php echo wp_create_nonce( "url-shortify-plugin-management" ); ?>',
								plugin_action: action,
								plugin: plugin,
								slug: slug
							})
						});

						const data = await response.json();

						if (data.success) {
							window.location.reload();
						} else {
							alert(data.data.message || 'Operation failed');
						}
					} catch (error) {
						alert('An error occurred');
					} finally {
						button.classList.remove('button-disabled');
						spinnerContainer.classList.add('hidden');
					}
				});
			});
		});
    </script>

    <style>
        .plugin-action-container {
            position: relative;
        }

        .spinner-container {
            align-items: center;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
    </style>

</div>

