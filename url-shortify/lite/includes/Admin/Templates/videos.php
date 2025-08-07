<?php

$videos = [
	[
		'url'         => 'https://www.youtube.com/embed/VIfPOcQje4E?si=XsoA29Snycco2Oy0',
		'title'       => 'Create Short Links',
		'description' => 'Create your first short link',
		'is_pro'      => false,
	],
	[
		'url'         => 'https://www.youtube.com/embed/pVPHUQQrldU?si=sseLtBo9IKEDIaoM',
		'title'       => 'Import Links',
		'description' => 'Import links from CSV',
		'is_pro'      => false,
	],
	[
		'url'         => 'https://www.youtube.com/embed/hSluD5wjFzk?si=uKjkPUYzECkZx325',
		'title'       => 'Export Links',
		'description' => 'Export Links',
		'is_pro'      => true,
	],
	[
		'url'         => 'https://www.youtube.com/embed/vf3OATdWd8o?si=H9QrX3etZpW3zu99',
		'title'       => 'Cloak Affiliate URLs',
		'description' => 'Cloak Affiliate URLs',
		'is_pro'      => true,
	],
	[
		'url'         => 'https://www.youtube.com/embed/xrSiYU7M2NE?si=3pNWm2xtIgFy_hsU',
		'title'       => 'How to generate short links of imported Posts, Pages through WP All Imports',
		'description' => 'How to generate short links of imported Posts, Pages through WP All Imports',
		'is_pro'      => true,
	],
	[
		'url'         => 'https://www.youtube.com/embed/5T2Kas5zRuM?si=LjNNpgGuwQ7aBoCv',
		'title'       => 'How to generate password protected link',
		'description' => 'How to generate password protected link',
		'is_pro'      => true,
	],
];
?>

<div class="wrap">
    <div class="bg-white py-10 sm:py-10">
        <div class="mx-auto max-w-7xl p-5">
            <h2 class="text-3xl font-bold text-center tracking-tight text-gray-900 sm:text-4xl">Let's Get Started</h2>
            <p class="mt-6 text-lg leading-8 text-gray-600 text-center">Here's the video tutorials for you to quickly
                get started with the URL Shortify.</p>

			<?php
			for ( $i = 0; $i < count( $videos ); $i += 2 ): ?>
                <div class="columns-2 gap-8 p-8">
					<?php
					for ( $j = $i; $j < min( $i + 2, count( $videos ) ); $j ++ ): ?>
                        <div class="w-full">
                            <iframe
                                    width="560"
                                    height="315"
                                    src="<?php
									echo esc_url( $videos[ $j ]['url'] ); ?>"
                                    title="<?php
									echo esc_attr( $videos[ $j ]['title'] ); ?>"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    allowfullscreen>
                            </iframe>
                            <p class="text-center text-xl p-2 text-bold">
								<?php
								echo esc_html( $videos[ $j ]['description'] ); ?>
								<?php
								echo $videos[ $j ]['is_pro'] ? ' [PRO]' : ''; ?>
                            </p>
                        </div>
					<?php
					endfor; ?>
                </div>
			<?php
			endfor; ?>
        </div>
    </div>
</div>