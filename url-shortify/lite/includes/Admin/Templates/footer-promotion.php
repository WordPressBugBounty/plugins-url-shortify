<?php ?>

<footer class="">
    <div class="mx-auto max-w-7xl overflow-hidden px-6 py-20 sm:py-24 lg:px-8">
        <nav class="columns-2 sm:flex sm:justify-center sm:space-x-12" aria-label="Footer">
		    <?php foreach ( $links as $link ) { ?>
                <div class="pb-6">
                    <a href="<?php echo esc_url( $link['url'] ); ?>" target="<?php echo esc_attr( $link['target'] ); ?>"
                       class="text-sm leading-6 hover:text-gray-900"><?php echo esc_html( $link['text'] ); ?></a>
                </div>
		    <?php } ?>
        </nav>
        <div class="mt-5 flex justify-center space-x-10">
            <a href="https://x.com/kaizencoders" class="text-gray-400 hover:text-gray-500" target="_blank">
                <span class="sr-only">X</span>
                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M13.6823 10.6218L20.2391 3H18.6854L12.9921 9.61788L8.44486 3H3.2002L10.0765 13.0074L3.2002 21H4.75404L10.7663 14.0113L15.5685 21H20.8131L13.6819 10.6218H13.6823ZM11.5541 13.0956L10.8574 12.0991L5.31391 4.16971H7.70053L12.1742 10.5689L12.8709 11.5655L18.6861 19.8835H16.2995L11.5541 13.096V13.0956Z" />
                </svg>
            </a>
            <a href="https://www.youtube.com/channel/UC6W7BvG9JiEvIGhfBa6IkcQ" target="_blank"
               class="text-gray-400 hover:text-gray-500">
                <span class="sr-only">YouTube</span>
                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill-rule="evenodd"
                          d="M19.812 5.418c.861.23 1.538.907 1.768 1.768C21.998 8.746 22 12 22 12s0 3.255-.418 4.814a2.504 2.504 0 0 1-1.768 1.768c-1.56.419-7.814.419-7.814.419s-6.255 0-7.814-.419a2.505 2.505 0 0 1-1.768-1.768C2 15.255 2 12 2 12s0-3.255.417-4.814a2.507 2.507 0 0 1 1.768-1.768C5.744 5 11.998 5 11.998 5s6.255 0 7.814.418ZM15.194 12 10 15V9l5.194 3Z"
                          clip-rule="evenodd"/>
                </svg>
            </a>
        </div>
        <div class="mt-5 text-center text-xs leading-5 text-gray-500">
            <p>
                <?php echo sprintf( __( "Made with ❤️ by the team <a href='%s' target='_blank'>KaizenCoders</a>",
                    'url-shortify' ), 'https://kaizencoders.com' ); ?>
            </p>
        </div>
    </div>
</footer>
