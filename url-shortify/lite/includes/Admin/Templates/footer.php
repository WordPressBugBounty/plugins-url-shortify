<?php
/**
 * The URL Shortify footer.
 *
 * One row, three zones, separated from the page by a hairline and nothing else.
 * A footer is the end of the page, not another panel on it — so no card, no
 * tint, no border box. What identifies this as URL Shortify's is the mark and
 * the link colour; everything else gets out of the way.
 *
 * Left is identity: what this is and which version. The centre is the
 * signature. The right is where anybody would go next, and the review ask ends
 * it — the last thing the eye lands on, and the one item here that asks for
 * something rather than pointing somewhere.
 *
 * Written in visual order — left, centre, right — so the focus ring travels the
 * way the row reads. Placing the nav before the signature in the markup and
 * moving it with `grid-column` would tab right, then back to the middle.
 *
 * The mark is the menu's dashicon rather than the plugin icon image: it is the
 * same glyph somebody just clicked to get here, and it needs no binary asset.
 *
 * @link       https://kaizencoders.com
 * @since      2.5.2
 *
 * @package    URL_Shortify
 * @subpackage URL_Shortify/includes/Admin/Templates
 *
 * @var array<string, mixed> $kc_us_footer What Admin\Footer gathered.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

?>
<div class="kc-us-footer">
	<div class="kc-us-footer__identity">
		<span class="kc-us-footer__mark dashicons dashicons-admin-links" aria-hidden="true"></span>

		<span class="kc-us-footer__name"><?php echo esc_html( $kc_us_footer['name'] ); ?></span>

		<span class="kc-us-footer__version"><?php echo esc_html( $kc_us_footer['version'] ); ?></span>
	</div>

	<p class="kc-us-footer__maker">
		<?php
		printf(
			/* translators: 1: a heart, 2: link opening tag, 3: link closing tag */
			esc_html__( 'Made with %1$s by %2$sKaizenCoders%3$s', 'url-shortify' ),
			'<span class="kc-us-footer__heart" aria-hidden="true">❤️</span>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- a literal.
			'<a href="' . esc_url( $kc_us_footer['maker'] ) . '" target="_blank" rel="noreferrer noopener">',
			'</a>'
		);
		?>
	</p>

	<nav class="kc-us-footer__links" aria-label="<?php esc_attr_e( 'URL Shortify links', 'url-shortify' ); ?>">
		<?php foreach ( $kc_us_footer['links'] as $kc_us_link ) : ?>
			<a
				<?php if ( ! empty( $kc_us_link['class'] ) ) : ?>
					class="<?php echo esc_attr( $kc_us_link['class'] ); ?>"
				<?php endif; ?>
				href="<?php echo esc_url( $kc_us_link['url'] ); ?>"
				target="_blank"
				rel="noreferrer noopener"
			>
				<?php echo esc_html( $kc_us_link['label'] ); ?>
			</a>
		<?php endforeach; ?>

		<a
			class="kc-us-footer__rating"
			href="<?php echo esc_url( $kc_us_footer['review'] ); ?>"
			target="_blank"
			rel="noreferrer noopener"
		>
			<span class="kc-us-footer__stars" aria-hidden="true">★★★★★</span>

			<?php
			/*
			 * Named, because in the footer of a page full of somebody else's
			 * plugins "Leave a review" does not say what is being reviewed.
			 *
			 * "URL Shortify" and not the build's own name: the listing being
			 * reviewed is the one on wordpress.org, which is URL Shortify
			 * whichever build is installed, and "Rate URL Shortify PRO" would
			 * send somebody to a page named for something else.
			 */
			esc_html_e( 'Rate URL Shortify', 'url-shortify' );
			?>

			<span class="screen-reader-text">
				<?php esc_html_e( 'on WordPress.org (opens in a new tab)', 'url-shortify' ); ?>
			</span>
		</a>
	</nav>
</div>
