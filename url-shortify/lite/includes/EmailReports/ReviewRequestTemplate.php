<?php
/**
 * Thanksgiving review request email.
 *
 * Available variables (from ReviewRequestEmail::generate_html):
 *   $data['stats']       - [ 'links' => int, 'clicks' => int ]
 *   $data['site_name']   - Site name
 *   $data['site_url']    - Site URL
 *   $data['review_url']  - WordPress.org review form
 *   $data['support_url'] - KaizenCoders contact page
 *   $data['company_url'] - KaizenCoders website
 *   $data['plugin_url']  - URL Shortify on WordPress.org
 *
 * @package KaizenCoders\URL_Shortify\EmailReports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$links       = isset( $data['stats']['links'] ) ? (int) $data['stats']['links'] : 0;
$clicks      = isset( $data['stats']['clicks'] ) ? (int) $data['stats']['clicks'] : 0;
$site_name   = ! empty( $data['site_name'] ) ? $data['site_name'] : get_bloginfo( 'name' );
$site_url    = ! empty( $data['site_url'] ) ? $data['site_url'] : home_url();
$review_url  = $data['review_url'];
$support_url  = $data['support_url'];
$link_style   = 'color:#c2410c;font-weight:600;text-decoration:none;';
$allowed      = [ 'a' => [ 'href' => [], 'style' => [] ] ];
$plugin_link  = '<a href="' . esc_url( $data['plugin_url'] ) . '" style="' . esc_attr( $link_style ) . '">' . esc_html__( 'URL Shortify', 'url-shortify' ) . '</a>';
$company_link = '<a href="' . esc_url( $data['company_url'] ) . '" style="' . esc_attr( $link_style ) . '">' . esc_html__( 'KaizenCoders', 'url-shortify' ) . '</a>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php esc_html_e( 'Happy Thanksgiving from the URL Shortify team', 'url-shortify' ); ?></title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f3f4f6;">
<tr><td align="center" style="padding:32px 16px;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;background-color:#ffffff;border-radius:10px;overflow:hidden;">

	<tr>
		<td style="background-color:#9a3412;background:linear-gradient(135deg,#9a3412 0%,#c2410c 55%,#d97706 100%);padding:34px 32px;text-align:center;">
			<div style="font-size:40px;line-height:1;">🦃🍂</div>
			<p style="margin:14px 0 0;font-size:24px;font-weight:700;color:#ffffff;line-height:1.3;">
				<?php esc_html_e( 'Happy Thanksgiving!', 'url-shortify' ); ?>
			</p>
			<p style="margin:6px 0 0;font-size:14px;color:rgba(255,255,255,0.9);">
				<?php esc_html_e( 'A thank-you note from the URL Shortify team', 'url-shortify' ); ?>
			</p>
		</td>
	</tr>

	<tr>
		<td style="padding:30px 32px 8px;font-size:15px;line-height:1.7;color:#374151;">
			<p style="margin:0 0 16px;"><?php esc_html_e( 'Hi there,', 'url-shortify' ); ?></p>

			<p style="margin:0 0 16px;">
				<?php
				/* translators: %s: site name */
				printf( esc_html__( 'Today is a day for gratitude, and we are grateful that %s trusts URL Shortify with its links.', 'url-shortify' ), '<strong>' . esc_html( $site_name ) . '</strong>' );
				?>
			</p>

			<?php if ( $links > 0 ) : ?>
			<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 18px;">
				<tr>
					<td width="50%" style="background-color:#fff7ed;border-radius:8px 0 0 8px;padding:16px;text-align:center;">
						<div style="font-size:26px;font-weight:700;color:#c2410c;"><?php echo esc_html( number_format_i18n( $links ) ); ?></div>
						<div style="font-size:12px;color:#9a3412;"><?php esc_html_e( 'short links created', 'url-shortify' ); ?></div>
					</td>
					<td width="50%" style="background-color:#fffbeb;border-radius:0 8px 8px 0;padding:16px;text-align:center;">
						<div style="font-size:26px;font-weight:700;color:#b45309;"><?php echo esc_html( number_format_i18n( $clicks ) ); ?></div>
						<div style="font-size:12px;color:#92400e;"><?php esc_html_e( 'clicks on your links', 'url-shortify' ); ?></div>
					</td>
				</tr>
			</table>
			<?php endif; ?>

			<p style="margin:0 0 16px;">
				<?php
				echo wp_kses(
					sprintf(
						/* translators: 1: "KaizenCoders" linked to its website, 2: "URL Shortify" linked to its plugin page */
						__( 'We are a small team at %1$s. Every feature, fix and support reply in %2$s comes from a handful of people who care a lot about getting it right, and hearing from the people who use it is the best part of our work.', 'url-shortify' ),
						$company_link,
						$plugin_link
					),
					$allowed
				);
				?>
			</p>

			<p style="margin:0 0 22px;">
				<?php esc_html_e( 'If URL Shortify has made your work a little easier this year, would you share that in a review on WordPress.org? It takes about two minutes, it helps other site owners find us, and a 5-star review from you would truly make our Thanksgiving.', 'url-shortify' ); ?>
			</p>

			<div style="text-align:center;margin:0 0 8px;">
				<div style="font-size:26px;letter-spacing:4px;color:#f59e0b;margin-bottom:12px;">★★★★★</div>
				<a href="<?php echo esc_url( $review_url ); ?>" style="display:inline-block;background-color:#c2410c;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;padding:13px 28px;border-radius:8px;">
					<?php esc_html_e( 'Write a review', 'url-shortify' ); ?> &rarr;
				</a>
			</div>

			<p style="margin:22px 0 0;font-size:14px;color:#6b7280;">
				<?php esc_html_e( 'And if something is not working the way you hoped, please tell us first. We would much rather fix it for you:', 'url-shortify' ); ?>
				<a href="<?php echo esc_url( $support_url ); ?>" style="color:#c2410c;"><?php esc_html_e( 'tell us and we will fix it', 'url-shortify' ); ?></a>.
			</p>

			<p style="margin:24px 0 0;">
				<?php esc_html_e( 'Wishing you and your family a warm and happy Thanksgiving,', 'url-shortify' ); ?><br>
				<strong>
				<?php
				echo wp_kses(
					/* translators: 1: "URL Shortify" linked to its plugin page, 2: "KaizenCoders" linked to its website */
					sprintf( __( 'The %1$s team at %2$s', 'url-shortify' ), $plugin_link, $company_link ),
					$allowed
				);
				?>
				</strong>
			</p>
		</td>
	</tr>

	<tr>
		<td style="padding:22px 32px 26px;font-size:12px;line-height:1.6;color:#9ca3af;text-align:center;border-top:1px solid #f3f4f6;">
			<?php
			/* translators: %s: site name */
			printf( esc_html__( 'This is a once-a-year note sent by the URL Shortify plugin on %s to its admin email address.', 'url-shortify' ), '<a href="' . esc_url( $site_url ) . '" style="color:#6b7280;">' . esc_html( $site_name ) . '</a>' );
			?>
			<br>
			<?php esc_html_e( 'It is not sent again once you have left a review or chosen "Don\'t ask again" in URL Shortify.', 'url-shortify' ); ?>
		</td>
	</tr>

</table>
</td></tr>
</table>
</body>
</html>
