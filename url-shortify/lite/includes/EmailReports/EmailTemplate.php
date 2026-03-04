<?php
/**
 * Email Digest HTML Template
 *
 * Available variables (from EmailSender::generate_email_html):
 *   $data['site_name']        - Site name
 *   $data['site_url']         - Site URL
 *   $data['frequency']        - 'weekly' or 'monthly'
 *   $data['is_preview']       - bool
 *   $data['date_range_label'] - Human-readable date range string
 *   $data['new_links']        - int
 *   $data['total_clicks']     - int
 *   $data['top_locations']    - array of objects with ->country, ->count
 *   $data['top_devices']      - array of objects with ->device, ->count
 *   $data['top_links']        - array of objects with ->url, ->clicks
 *
 * @package KaizenCoders\URL_Shortify\EmailReports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$site_name        = ! empty( $data['site_name'] ) ? $data['site_name'] : get_bloginfo( 'name' );
$site_url         = ! empty( $data['site_url'] ) ? $data['site_url'] : home_url();
$frequency        = ! empty( $data['frequency'] ) ? $data['frequency'] : 'weekly';
$frequency_label  = ucfirst( $frequency );
$is_preview       = ! empty( $data['is_preview'] );
$date_range_label = ! empty( $data['date_range_label'] ) ? $data['date_range_label'] : '';
$new_links        = isset( $data['new_links'] ) ? (int) $data['new_links'] : 0;
$total_clicks     = isset( $data['total_clicks'] ) ? (int) $data['total_clicks'] : 0;
$top_locations    = ! empty( $data['top_locations'] ) && is_array( $data['top_locations'] ) ? $data['top_locations'] : [];
$top_devices      = ! empty( $data['top_devices'] ) && is_array( $data['top_devices'] ) ? $data['top_devices'] : [];
$top_links        = ! empty( $data['top_links'] ) && is_array( $data['top_links'] ) ? $data['top_links'] : [];
$analytics_url    = admin_url( 'admin.php?page=url_shortify' );
$plugin_version   = defined( 'KC_US_PLUGIN_VERSION' ) ? KC_US_PLUGIN_VERSION : '';

// Total clicks for percentage calculations.
$locations_total = 0;
foreach ( $top_locations as $loc ) {
	$locations_total += isset( $loc->count ) ? (int) $loc->count : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title><?php echo esc_html( $frequency_label ); ?> Report — <?php echo esc_html( $site_name ); ?></title>
<style type="text/css">
	/* Reset */
	body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
	table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
	img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
	/* General */
	body { background-color: #f3f4f6; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
	.wrapper { background-color: #f3f4f6; padding: 32px 16px; }
	.container { background-color: #ffffff; border-radius: 8px; max-width: 600px; margin: 0 auto; overflow: hidden; }
	/* Header */
	.header { background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%); padding: 32px 40px; text-align: center; }
	.header-logo { font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.75); letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 8px; }
	.header-title { font-size: 26px; font-weight: 700; color: #ffffff; margin: 0; }
	.header-subtitle { font-size: 14px; color: rgba(255,255,255,0.8); margin-top: 6px; }
	/* Preview badge */
	.preview-badge { background-color: #f59e0b; color: #7c2d12; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; padding: 4px 10px; border-radius: 3px; display: inline-block; margin-bottom: 12px; }
	/* Body */
	.body-content { padding: 32px 40px; }
	.greeting { font-size: 16px; color: #374151; margin: 0 0 24px; line-height: 1.5; }
	/* Period card */
	.period-card { background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 14px 18px; margin-bottom: 28px; display: flex; align-items: center; }
	.period-icon { font-size: 20px; margin-right: 12px; }
	.period-label { font-size: 13px; color: #6b7280; margin: 0; }
	.period-value { font-size: 15px; font-weight: 600; color: #1e40af; margin: 2px 0 0; }
	.frequency-badge { display: inline-block; background-color: #dbeafe; color: #1e40af; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
	/* Stats */
	.section-title { font-size: 13px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 14px; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb; }
	.stats-table { width: 100%; border-collapse: collapse; margin-bottom: 28px; }
	.stat-cell { width: 50%; padding: 20px; text-align: center; background-color: #f9fafb; border-radius: 6px; }
	.stat-cell.left { margin-right: 8px; }
	.stat-number { font-size: 36px; font-weight: 700; color: #111827; line-height: 1; }
	.stat-number.links { color: #2563eb; }
	.stat-number.clicks { color: #16a34a; }
	.stat-label { font-size: 13px; color: #6b7280; margin-top: 4px; }
	/* Links table */
	.links-section { margin-bottom: 28px; }
	.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
	.data-table th { background-color: #f9fafb; color: #6b7280; font-weight: 600; text-align: left; padding: 10px 12px; border-bottom: 2px solid #e5e7eb; }
	.data-table th.right { text-align: right; }
	.data-table td { padding: 10px 12px; border-bottom: 1px solid #f3f4f6; color: #374151; vertical-align: top; }
	.data-table td.right { text-align: right; }
	.data-table tr:last-child td { border-bottom: none; }
	.data-table tr:nth-child(even) td { background-color: #fafafa; }
	.rank-badge { display: inline-block; background-color: #e5e7eb; color: #374151; font-size: 11px; font-weight: 700; width: 22px; height: 22px; line-height: 22px; text-align: center; border-radius: 50%; }
	.rank-badge.top { background-color: #fef3c7; color: #92400e; }
	.url-cell { max-width: 260px; }
	.url-destination { color: #6b7280; font-size: 11px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 260px; }
	.clicks-count { font-weight: 600; color: #111827; }
	/* Locations */
	.locations-section { margin-bottom: 28px; }
	.location-item { display: flex; align-items: center; padding: 8px 0; border-bottom: 1px solid #f3f4f6; }
	.location-item:last-child { border-bottom: none; }
	.location-name { flex: 1; font-size: 14px; color: #374151; }
	.location-bar-wrap { width: 120px; background-color: #e5e7eb; border-radius: 3px; height: 6px; margin: 0 12px; overflow: hidden; }
	.location-bar { background-color: #2563eb; height: 6px; border-radius: 3px; }
	.location-count { font-size: 13px; font-weight: 600; color: #111827; min-width: 40px; text-align: right; }
	/* Devices */
	.devices-section { margin-bottom: 28px; }
	.device-item { display: flex; align-items: center; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f3f4f6; }
	.device-item:last-child { border-bottom: none; }
	.device-name { font-size: 14px; color: #374151; }
	.device-count { font-size: 13px; font-weight: 600; color: #111827; }
	/* CTA */
	.cta-section { text-align: center; padding: 8px 0 28px; }
	.cta-button { display: inline-block; background-color: #2563eb; color: #ffffff; font-size: 15px; font-weight: 600; text-decoration: none; padding: 14px 32px; border-radius: 6px; }
	/* Footer */
	.footer { background-color: #f9fafb; border-top: 1px solid #e5e7eb; padding: 24px 40px; text-align: center; }
	.footer p { font-size: 12px; color: #9ca3af; margin: 0 0 4px; }
	.footer a { color: #6b7280; text-decoration: underline; }
	/* No data */
	.no-data { color: #9ca3af; font-style: italic; font-size: 13px; padding: 12px 0; }
</style>
</head>
<body>
<!-- Hidden preheader -->
<div style="display:none;font-size:1px;color:#f3f4f6;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
	<?php
	/* translators: 1: Frequency label, 2: Site name, 3: Date range */
	printf(
		esc_html__( 'Your %1$s link performance summary for %2$s — %3$s', 'url-shortify' ),
		esc_html( $frequency_label ),
		esc_html( $site_name ),
		esc_html( $date_range_label )
	);
	?>
</div>

<div class="wrapper">
<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td align="center">
<table class="container" width="600" cellpadding="0" cellspacing="0" border="0">

	<!-- Header -->
	<tr>
		<td class="header">
			<?php if ( $is_preview ) : ?>
			<div class="preview-badge"><?php esc_html_e( 'Preview', 'url-shortify' ); ?></div><br>
			<?php endif; ?>
			<div class="header-logo">URL Shortify</div>
			<div class="header-title">
				<?php echo esc_html( $frequency_label ); ?> <?php esc_html_e( 'Performance Report', 'url-shortify' ); ?>
			</div>
			<div class="header-subtitle"><?php echo esc_html( $site_name ); ?></div>
		</td>
	</tr>

	<!-- Body -->
	<tr>
		<td class="body-content">

			<!-- Greeting -->
			<p class="greeting">
				<?php
				/* translators: 1: Frequency label (Weekly/Monthly), 2: Site name */
				printf(
					esc_html__( "Here's your %1\$s link performance summary for %2\$s.", 'url-shortify' ),
					'<strong>' . esc_html( $frequency_label ) . '</strong>',
					'<strong>' . esc_html( $site_name ) . '</strong>'
				);
				?>
			</p>

			<!-- Period card -->
			<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:28px;">
				<tr>
					<td style="background-color:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:14px 18px;">
						<table width="100%" cellpadding="0" cellspacing="0" border="0">
							<tr>
								<td>
									<p style="font-size:12px;color:#6b7280;margin:0 0 3px;">
										<?php esc_html_e( 'Reporting Period', 'url-shortify' ); ?>
										&nbsp;<span style="display:inline-block;background-color:#dbeafe;color:#1e40af;font-size:10px;font-weight:600;padding:2px 7px;border-radius:10px;text-transform:uppercase;"><?php echo esc_html( $frequency_label ); ?></span>
									</p>
									<p style="font-size:15px;font-weight:600;color:#1e40af;margin:0;"><?php echo esc_html( $date_range_label ); ?></p>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>

			<!-- Stats row -->
			<p class="section-title"><?php esc_html_e( 'Summary', 'url-shortify' ); ?></p>
			<table class="stats-table" cellpadding="0" cellspacing="0" border="0">
				<tr>
					<td width="48%" style="background-color:#eff6ff;border-radius:6px;padding:20px;text-align:center;">
						<div style="font-size:40px;font-weight:700;color:#2563eb;line-height:1;"><?php echo esc_html( number_format_i18n( $new_links ) ); ?></div>
						<div style="font-size:13px;color:#6b7280;margin-top:6px;"><?php esc_html_e( 'New Links Created', 'url-shortify' ); ?></div>
					</td>
					<td width="4%">&nbsp;</td>
					<td width="48%" style="background-color:#f0fdf4;border-radius:6px;padding:20px;text-align:center;">
						<div style="font-size:40px;font-weight:700;color:#16a34a;line-height:1;"><?php echo esc_html( number_format_i18n( $total_clicks ) ); ?></div>
						<div style="font-size:13px;color:#6b7280;margin-top:6px;"><?php esc_html_e( 'Total Clicks', 'url-shortify' ); ?></div>
					</td>
				</tr>
			</table>

			<!-- Top Performing Links -->
			<?php if ( ! empty( $top_links ) ) : ?>
			<div class="links-section" style="margin-top:28px;">
				<p class="section-title"><?php esc_html_e( 'Top Performing Links', 'url-shortify' ); ?></p>
				<table class="data-table" cellpadding="0" cellspacing="0" border="0">
					<thead>
						<tr>
							<th width="36">#</th>
							<th><?php esc_html_e( 'Destination URL', 'url-shortify' ); ?></th>
							<th class="right" width="70"><?php esc_html_e( 'Clicks', 'url-shortify' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $top_links as $i => $link ) :
							$rank = $i + 1;
							$destination = isset( $link->url ) ? $link->url : '';
							$clicks = isset( $link->clicks ) ? (int) $link->clicks : 0;
							$display_url = strlen( $destination ) > 55 ? substr( $destination, 0, 52 ) . '...' : $destination;
						?>
						<tr>
							<td>
								<span class="rank-badge <?php echo $rank <= 3 ? 'top' : ''; ?>"><?php echo esc_html( $rank ); ?></span>
							</td>
							<td class="url-cell">
								<span class="url-destination" title="<?php echo esc_attr( $destination ); ?>"><?php echo esc_html( $display_url ); ?></span>
							</td>
							<td class="right">
								<span class="clicks-count"><?php echo esc_html( number_format_i18n( $clicks ) ); ?></span>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php endif; ?>

			<!-- Top Locations -->
			<?php if ( ! empty( $top_locations ) ) : ?>
			<div class="locations-section" style="margin-top:28px;">
				<p class="section-title"><?php esc_html_e( 'Top Locations', 'url-shortify' ); ?></p>
				<table width="100%" cellpadding="0" cellspacing="0" border="0">
					<?php foreach ( $top_locations as $location ) :
						$country = ! empty( $location->country ) ? $location->country : __( 'Unknown', 'url-shortify' );
						$count   = isset( $location->count ) ? (int) $location->count : 0;
						$pct     = $locations_total > 0 ? round( ( $count / $locations_total ) * 100 ) : 0;
					?>
					<tr>
						<td style="padding:8px 0;border-bottom:1px solid #f3f4f6;">
							<table width="100%" cellpadding="0" cellspacing="0" border="0">
								<tr>
									<td style="font-size:14px;color:#374151;"><?php echo esc_html( $country ); ?></td>
									<td width="120" style="padding:0 12px;">
										<div style="background-color:#e5e7eb;border-radius:3px;height:6px;overflow:hidden;">
											<div style="background-color:#2563eb;height:6px;width:<?php echo esc_attr( $pct ); ?>%;border-radius:3px;"></div>
										</div>
									</td>
									<td width="60" style="text-align:right;font-size:13px;font-weight:600;color:#111827;">
										<?php echo esc_html( number_format_i18n( $count ) ); ?>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<?php endforeach; ?>
				</table>
			</div>
			<?php endif; ?>

			<!-- Top Devices -->
			<?php if ( ! empty( $top_devices ) ) : ?>
			<div class="devices-section" style="margin-top:28px;">
				<p class="section-title"><?php esc_html_e( 'Top Devices', 'url-shortify' ); ?></p>
				<table width="100%" cellpadding="0" cellspacing="0" border="0">
					<?php foreach ( $top_devices as $device_item ) :
						$device_name = ! empty( $device_item->device ) ? $device_item->device : __( 'Unknown', 'url-shortify' );
						$device_count = isset( $device_item->count ) ? (int) $device_item->count : 0;
					?>
					<tr>
						<td style="padding:8px 0;border-bottom:1px solid #f3f4f6;">
							<table width="100%" cellpadding="0" cellspacing="0" border="0">
								<tr>
									<td style="font-size:14px;color:#374151;"><?php echo esc_html( ucfirst( $device_name ) ); ?></td>
									<td style="text-align:right;font-size:13px;font-weight:600;color:#111827;"><?php echo esc_html( number_format_i18n( $device_count ) ); ?></td>
								</tr>
							</table>
						</td>
					</tr>
					<?php endforeach; ?>
				</table>
			</div>
			<?php endif; ?>

			<!-- No data message -->
			<?php if ( 0 === $new_links && 0 === $total_clicks && empty( $top_links ) ) : ?>
			<p style="color:#9ca3af;font-style:italic;font-size:14px;text-align:center;padding:20px 0;">
				<?php esc_html_e( 'No activity recorded during this period.', 'url-shortify' ); ?>
			</p>
			<?php endif; ?>

			<!-- CTA -->
			<div class="cta-section" style="margin-top:32px;">
				<a href="<?php echo esc_url( $analytics_url ); ?>" class="cta-button" style="display:inline-block;background-color:#2563eb;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;padding:14px 32px;border-radius:6px;">
					<?php esc_html_e( 'View Full Analytics', 'url-shortify' ); ?>
				</a>
			</div>

		</td>
	</tr>

	<!-- Footer -->
	<tr>
		<td class="footer">
			<p><?php esc_html_e( 'You are receiving this email because you have email digest enabled in URL Shortify settings.', 'url-shortify' ); ?></p>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=kc-us-settings&tab=reports' ) ); ?>">
					<?php esc_html_e( 'Manage Email Preferences', 'url-shortify' ); ?>
				</a>
				<?php if ( ! empty( $plugin_version ) ) : ?>
				&nbsp;&bull;&nbsp; URL Shortify v<?php echo esc_html( $plugin_version ); ?>
				<?php endif; ?>
			</p>
		</td>
	</tr>

</table>
</td></tr>
</table>
</div>
</body>
</html>
