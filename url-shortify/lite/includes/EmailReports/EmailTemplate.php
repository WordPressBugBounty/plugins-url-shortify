<?php
/**
 * Email Digest HTML Template
 *
 * Available variables (from EmailSender::generate_email_html):
 *   $data['site_name']        - Site name
 *   $data['site_url']         - Site URL
 *   $data['frequency']        - 'daily', 'weekly', or 'monthly'
 *   $data['is_preview']       - bool
 *   $data['date_range_label'] - Human-readable date range string
 *   $data['new_links']        - int
 *   $data['total_clicks']     - int
 *   $data['click_trend']      - int|null  Percentage change vs previous period
 *   $data['top_locations']    - array of objects with ->country, ->count
 *   $data['top_devices']      - array of objects with ->device, ->count
 *   $data['top_links']        - array of objects with ->id, ->url, ->slug, ->name, ->clicks
 *   $data['recent_links']     - array of objects with ->id, ->name, ->slug, ->url, ->created_at
 *   $data['news_items']       - array of news/tip/announcement items
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
$click_trend      = isset( $data['click_trend'] ) ? $data['click_trend'] : null;
$top_locations    = ! empty( $data['top_locations'] ) && is_array( $data['top_locations'] ) ? $data['top_locations'] : [];
$top_devices      = ! empty( $data['top_devices'] ) && is_array( $data['top_devices'] ) ? $data['top_devices'] : [];
$top_links        = ! empty( $data['top_links'] ) && is_array( $data['top_links'] ) ? $data['top_links'] : [];
$recent_links     = ! empty( $data['recent_links'] ) && is_array( $data['recent_links'] ) ? $data['recent_links'] : [];
$news_items       = ! empty( $data['news_items'] ) && is_array( $data['news_items'] ) ? $data['news_items'] : [];
$analytics_url    = admin_url( 'admin.php?page=url_shortify' );
$upgrade_url      = US()->get_pricing_url();
$plugin_version   = defined( 'KC_US_PLUGIN_VERSION' ) ? KC_US_PLUGIN_VERSION : '';
$docs_url         = 'https://docs.kaizencoders.com/url-shortify/';
$website_url      = 'https://kaizencoders.com';
$is_pro           = US()->is_pro();

// Total clicks for percentage calculations.
$locations_total = 0;
foreach ( $top_locations as $loc ) {
	$locations_total += isset( $loc->count ) ? (int) $loc->count : 0;
}

// Click trend display helpers.
$trend_label = '';
$trend_color = '#6b7280';
$trend_bg    = '#f3f4f6';
if ( null !== $click_trend ) {
	if ( $click_trend > 0 ) {
		/* translators: %d: positive percentage number */
		$trend_label = sprintf( __( '+%d%%', 'url-shortify' ), $click_trend );
		$trend_color = '#065f46';
		$trend_bg    = '#d1fae5';
	} elseif ( $click_trend < 0 ) {
		/* translators: %d: negative percentage number */
		$trend_label = sprintf( __( '%d%%', 'url-shortify' ), $click_trend );
		$trend_color = '#991b1b';
		$trend_bg    = '#fee2e2';
	} else {
		$trend_label = __( '0%', 'url-shortify' );
		$trend_color = '#374151';
		$trend_bg    = '#f3f4f6';
	}
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
	.frequency-badge { display: inline-block; background-color: #dbeafe; color: #1e40af; font-size: 10px; font-weight: 600; padding: 2px 7px; border-radius: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
	/* Stats */
	.section-title { font-size: 13px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 14px; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb; }
	/* Links table */
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
	/* News & Tips */
	.news-badge { display: inline-block; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; padding: 2px 8px; border-radius: 10px; margin-bottom: 6px; }
	/* CTA */
	.cta-button { display: inline-block; font-size: 15px; font-weight: 600; text-decoration: none; padding: 14px 32px; border-radius: 6px; }
	/* Footer */
	.footer { background-color: #f9fafb; border-top: 1px solid #e5e7eb; padding: 24px 40px; text-align: center; }
	.footer p { font-size: 12px; color: #9ca3af; margin: 0 0 6px; }
	.footer a { color: #6b7280; text-decoration: underline; }
</style>
</head>
<body>
<!-- Hidden preheader -->
<div style="display:none;font-size:1px;color:#f3f4f6;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
	<?php
	printf(
		/* translators: 1: Frequency label (e.g. Weekly), 2: Site name, 3: Date range */
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
				printf(
					/* translators: 1: Frequency label (e.g. Weekly/Monthly), 2: Site name */
					esc_html__( "Here's your %1\$s link performance summary for %2\$s.", 'url-shortify' ),
					'<strong>' . esc_html( $frequency_label ) . '</strong>',
					'<strong>' . esc_html( $site_name ) . '</strong>'
				);
				?>
			</p>

			<!-- Period card -->
			<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">
				<tr>
					<td style="background-color:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:14px 18px;">
						<p style="font-size:12px;color:#6b7280;margin:0 0 3px;">
							<?php esc_html_e( 'Reporting Period', 'url-shortify' ); ?>
							&nbsp;<span style="display:inline-block;background-color:#dbeafe;color:#1e40af;font-size:10px;font-weight:600;padding:2px 7px;border-radius:10px;text-transform:uppercase;"><?php echo esc_html( $frequency_label ); ?></span>
						</p>
						<p style="font-size:15px;font-weight:600;color:#1e40af;margin:0;"><?php echo esc_html( $date_range_label ); ?></p>
					</td>
				</tr>
			</table>

			<!-- ====================================================== -->
			<!-- SUMMARY STATS                                           -->
			<!-- ====================================================== -->
			<p class="section-title"><?php esc_html_e( 'Summary', 'url-shortify' ); ?></p>
			<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:28px;">
				<tr>
					<td width="48%" style="background-color:#eff6ff;border-radius:6px;padding:20px;text-align:center;">
						<div style="font-size:40px;font-weight:700;color:#2563eb;line-height:1;"><?php echo esc_html( number_format_i18n( $new_links ) ); ?></div>
						<div style="font-size:13px;color:#6b7280;margin-top:6px;"><?php esc_html_e( 'New Links Created', 'url-shortify' ); ?></div>
					</td>
					<td width="4%">&nbsp;</td>
					<td width="48%" style="background-color:#f0fdf4;border-radius:6px;padding:20px;text-align:center;">
						<div style="font-size:40px;font-weight:700;color:#16a34a;line-height:1;"><?php echo esc_html( number_format_i18n( $total_clicks ) ); ?></div>
						<div style="font-size:13px;color:#6b7280;margin-top:6px;"><?php esc_html_e( 'Total Clicks', 'url-shortify' ); ?></div>
						<?php if ( null !== $click_trend ) : ?>
						<div style="margin-top:8px;">
							<span style="display:inline-block;font-size:11px;font-weight:700;padding:2px 8px;border-radius:10px;background-color:<?php echo esc_attr( $trend_bg ); ?>;color:<?php echo esc_attr( $trend_color ); ?>;">
								<?php
								if ( $click_trend > 0 ) {
									echo '&#9650; ';
								} elseif ( $click_trend < 0 ) {
									echo '&#9660; ';
								}
								echo esc_html( $trend_label );
								?>
							</span>
							<span style="display:block;font-size:10px;color:#9ca3af;margin-top:3px;">
								<?php
								/* translators: %s: frequency label e.g. "week" */
								printf( esc_html__( 'vs previous %s', 'url-shortify' ), esc_html( strtolower( $frequency_label === 'Daily' ? __( 'day', 'url-shortify' ) : ( $frequency_label === 'Monthly' ? __( 'month', 'url-shortify' ) : __( 'week', 'url-shortify' ) ) ) ) );
								?>
							</span>
						</div>
						<?php endif; ?>
					</td>
				</tr>
			</table>

            <!-- ====================================================== -->
            <!-- FREE: RECENTLY ADDED LINKS                              -->
            <!-- ====================================================== -->
            <?php if ( ! empty( $recent_links ) ) : ?>
                <div style="margin-top:28px;margin-bottom:28px;">
                    <p class="section-title"><?php esc_html_e( 'Recently Added Links', 'url-shortify' ); ?></p>
                    <table class="data-table" cellpadding="0" cellspacing="0" border="0">
                        <thead>
                        <tr>
                            <th><?php esc_html_e( 'Short Link', 'url-shortify' ); ?></th>
                            <th><?php esc_html_e( 'Destination', 'url-shortify' ); ?></th>
                            <th class="right" width="80"><?php esc_html_e( 'Created', 'url-shortify' ); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ( $recent_links as $rlink ) :
                            $rlink_id    = isset( $rlink->id ) ? (int) $rlink->id : 0;
                            $rlink_slug  = isset( $rlink->slug ) ? $rlink->slug : '';
                            $rlink_name  = ! empty( $rlink->name ) ? $rlink->name : '';
                            $rlink_url   = isset( $rlink->url ) ? $rlink->url : '';
                            $rlink_date  = isset( $rlink->created_at ) ? $rlink->created_at : '';
                            $rlink_short = ! empty( $rlink_slug ) ? \KaizenCoders\URL_Shortify\Helper::get_short_link( $rlink_slug ) : '';
                            $rlink_edit  = $rlink_id ? \KaizenCoders\URL_Shortify\Helper::get_link_action_url( $rlink_id, 'edit' ) : '';
                            $rlink_dest  = strlen( $rlink_url ) > 40 ? substr( $rlink_url, 0, 37 ) . '...' : $rlink_url;
                            $rlink_label = ! empty( $rlink_name ) ? $rlink_name : ( ! empty( $rlink_short ) ? $rlink_short : '/' . $rlink_slug );
                            $rlink_ago   = ! empty( $rlink_date ) ? human_time_diff( strtotime( $rlink_date ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'url-shortify' ) : '';
                            ?>
                            <tr>
                                <td>
                                    <?php if ( ! empty( $rlink_edit ) ) : ?>
                                        <a href="<?php echo esc_url( $rlink_edit ); ?>" style="font-size:13px;font-weight:600;color:#2563eb;text-decoration:none;"><?php echo esc_html( $rlink_label ); ?></a>
                                    <?php else : ?>
                                        <span style="font-size:13px;font-weight:600;color:#111827;"><?php echo esc_html( $rlink_label ); ?></span>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $rlink_short ) && $rlink_label !== $rlink_short ) : ?>
                                        <span style="font-size:11px;color:#6b7280;display:block;margin-top:2px;"><?php echo esc_html( $rlink_short ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-size:12px;color:#6b7280;" title="<?php echo esc_attr( $rlink_url ); ?>"><?php echo esc_html( $rlink_dest ); ?></span>
                                </td>
                                <td class="right">
                                    <span style="font-size:11px;color:#9ca3af;white-space:nowrap;"><?php echo esc_html( $rlink_ago ); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

			<!-- ====================================================== -->
			<!-- TOP PERFORMING LINKS (PRO: full · Free: teaser)         -->
			<!-- ====================================================== -->
			<?php
			// For free users show the table only when there are at least 4 top links,
			// so we can lock #1–3 and reveal #4 as a genuine teaser.
			$free_teaser = ! $is_pro && count( $top_links ) >= 4;
			if ( ( $is_pro && ! empty( $top_links ) ) || $free_teaser ) :
				// Free users: display rows 1–4 only (3 locked + 1 visible).
				$display_links = $is_pro ? $top_links : array_slice( $top_links, 0, 4 );
			?>
			<div style="margin-top:28px;margin-bottom:28px;">
				<p class="section-title"><?php esc_html_e( 'Top Performing Links', 'url-shortify' ); ?></p>
				<table class="data-table" cellpadding="0" cellspacing="0" border="0">
					<thead>
						<tr>
							<th width="36">#</th>
							<th><?php esc_html_e( 'Link', 'url-shortify' ); ?></th>
							<th><?php esc_html_e( 'Destination URL', 'url-shortify' ); ?></th>
							<th class="right" width="80"><?php esc_html_e( 'Clicks', 'url-shortify' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $display_links as $i => $link ) :
							$rank        = $i + 1;
							$is_locked   = ! $is_pro && $rank <= 3;
							$link_id     = isset( $link->id ) ? (int) $link->id : 0;
							$destination = isset( $link->url ) ? $link->url : '';
							$slug        = isset( $link->slug ) ? $link->slug : '';
							$link_name   = ! empty( $link->name ) ? $link->name : '';
							$clicks      = isset( $link->clicks ) ? (int) $link->clicks : 0;
							$stats_url   = ( ! $is_locked && $link_id ) ? \KaizenCoders\URL_Shortify\Helper::create_link_stats_url( $link_id ) : '';
							$display_dest = strlen( $destination ) > 50 ? substr( $destination, 0, 47 ) . '...' : $destination;
						?>
						<?php if ( $is_locked ) : ?>
						<tr>
							<td style="vertical-align:middle;padding-top:12px;padding-bottom:12px;">
								<span class="rank-badge top"><?php echo esc_html( $rank ); ?></span>
							</td>
							<td class="url-cell" colspan="2" style="vertical-align:middle;">
								<span style="display:inline-block;background-color:#f3f4f6;color:#9ca3af;font-size:12px;font-weight:600;letter-spacing:3px;padding:4px 10px;border-radius:4px;">&#9608;&#9608;&#9608;&#9608;&#9608;&#9608;&#9608;&#9608;&#9608;&#9608;&#9608;&#9608;</span>
							</td>
							<td class="right" style="vertical-align:middle;padding-top:12px;">
								<span style="display:inline-block;background-color:#f3f4f6;color:#d1d5db;font-size:12px;font-weight:700;letter-spacing:2px;padding:2px 6px;border-radius:3px;">•••</span>
							</td>
						</tr>
						<?php else : ?>
						<tr>
							<td style="vertical-align:top;padding-top:12px;">
								<span class="rank-badge <?php echo $rank <= 3 ? 'top' : ''; ?>"><?php echo esc_html( $rank ); ?></span>
							</td>
							<td class="url-cell">
								<?php if ( ! empty( $stats_url ) ) : ?>
								<a href="<?php echo esc_url( $stats_url ); ?>" style="font-size:13px;font-weight:600;color:#2563eb;text-decoration:none;display:block;margin-bottom:2px;"><?php echo esc_html( $link_name ); ?></a>
								<?php else : ?>
								<span style="font-size:13px;font-weight:600;color:#111827;display:block;margin-bottom:2px;"><?php echo esc_html( $link_name ); ?></span>
								<?php endif; ?>
								<span class="url-destination" title="<?php echo esc_attr( $slug ); ?>">/<?php echo esc_html( $slug ); ?></span>
							</td>
							<td class="url-cell">
								<?php if ( ! empty( $destination ) ) : ?>
								<a href="<?php echo esc_url( $destination ); ?>" style="font-size:13px;color:#2563eb;text-decoration:none;display:block;margin-bottom:2px;"><?php echo esc_html( $display_dest ); ?></a>
								<?php endif; ?>
							</td>
							<td class="right" style="vertical-align:top;padding-top:12px;">
								<span class="clicks-count"><?php echo esc_html( number_format_i18n( $clicks ) ); ?></span>
							</td>
						</tr>
						<?php endif; ?>
						<?php endforeach; ?>
					</tbody>
				</table>

			</div>
			<?php endif; // end top links ?>

			<?php if ( $is_pro ) : ?>
			<!-- PRO: TOP LOCATIONS -->
			<?php if ( ! empty( $top_locations ) ) : ?>
			<div style="margin-top:28px;margin-bottom:28px;">
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

			<!-- PRO: TOP DEVICES -->
			<?php if ( ! empty( $top_devices ) ) : ?>
			<div style="margin-top:28px;margin-bottom:28px;">
				<p class="section-title"><?php esc_html_e( 'Top Devices', 'url-shortify' ); ?></p>
				<table width="100%" cellpadding="0" cellspacing="0" border="0">
					<?php foreach ( $top_devices as $device_item ) :
						$device_name  = ! empty( $device_item->device ) ? $device_item->device : __( 'Unknown', 'url-shortify' );
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

			<?php endif; // end $is_pro ?>

			<!-- No data message -->
			<?php if ( 0 === $new_links && 0 === $total_clicks ) : ?>
			<p style="color:#9ca3af;font-style:italic;font-size:14px;text-align:center;padding:20px 0;">
				<?php esc_html_e( 'No activity recorded during this period.', 'url-shortify' ); ?>
			</p>
			<?php endif; ?>

			<!-- ====================================================== -->
			<!-- NEWS, TIPS & ANNOUNCEMENTS                              -->
			<!-- ====================================================== -->
			<?php if ( ! empty( $news_items ) ) : ?>
			<div style="margin-top:32px;margin-bottom:8px;">
				<p class="section-title"><?php esc_html_e( 'News, Tips & Announcements', 'url-shortify' ); ?></p>
				<?php foreach ( $news_items as $item ) :
					$badge       = ! empty( $item['badge'] ) ? strtolower( $item['badge'] ) : 'tip';
					$badge_label = ! empty( $item['badge'] ) ? $item['badge'] : 'Tip';
					$title       = ! empty( $item['title'] ) ? $item['title'] : '';
					$description = ! empty( $item['description'] ) ? $item['description'] : '';
					$item_url    = ! empty( $item['url'] ) ? $item['url'] : '';
					$url_label   = ! empty( $item['url_label'] ) ? $item['url_label'] : __( 'Learn more →', 'url-shortify' );
					if ( empty( $title ) ) { continue; }
					$badge_colors = [
						'tip'          => 'background-color:#ecfdf5;color:#065f46;',
						'news'         => 'background-color:#eff6ff;color:#1e40af;',
						'announcement' => 'background-color:#fef3c7;color:#92400e;',
					];
					$badge_style = isset( $badge_colors[ $badge ] ) ? $badge_colors[ $badge ] : $badge_colors['tip'];
				?>
				<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:10px;">
					<tr>
						<td style="background-color:#fafafa;border:1px solid #e5e7eb;border-radius:6px;padding:14px 16px;">
							<span style="display:inline-block;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;padding:2px 8px;border-radius:10px;margin-bottom:7px;<?php echo esc_attr( $badge_style ); ?>"><?php echo esc_html( $badge_label ); ?></span><br>
							<span style="font-size:14px;font-weight:600;color:#111827;"><?php echo esc_html( $title ); ?></span>
							<?php if ( ! empty( $description ) ) : ?>
							<br><span style="font-size:13px;color:#6b7280;line-height:1.5;display:block;margin-top:4px;"><?php echo esc_html( $description ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $item_url ) ) : ?>
							<br><a href="<?php echo esc_url( $item_url ); ?>" style="font-size:12px;color:#2563eb;text-decoration:none;display:inline-block;margin-top:6px;"><?php echo esc_html( $url_label ); ?></a>
							<?php endif; ?>
						</td>
					</tr>
				</table>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<!-- ====================================================== -->
			<!-- CTA BUTTON                                              -->
			<!-- ====================================================== -->
            <?php if ( ! $is_pro ) : ?>
                <!-- ====================================================== -->
                <!-- UPGRADE BANNER — free users only                        -->
                <!-- ====================================================== -->
                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:2px;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#b45309 0%,#d97706 100%);border-radius:8px;padding:24px 28px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td>
									<span style="display:inline-block;background:rgba(255,255,255,0.25);color:#fff;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;padding:3px 10px;border-radius:10px;">
										<?php esc_html_e( 'Free Plan', 'url-shortify' ); ?>
									</span>
                                        <p style="font-size:19px;font-weight:700;color:#ffffff;margin:10px 0 6px;line-height:1.3;">
                                            <?php esc_html_e( 'Unlock deeper link analytics', 'url-shortify' ); ?>
                                        </p>
                                        <p style="font-size:13px;color:rgba(255,255,255,0.9);margin:0 0 18px;line-height:1.6;">
                                            <?php esc_html_e( 'See your top performing links, visitor locations, device breakdown, referrers, and more — all in your inbox.', 'url-shortify' ); ?>
                                        </p>
                                        <a href="<?php echo esc_url( $upgrade_url ); ?>" style="display:inline-block;background:#ffffff;color:#b45309;font-size:13px;font-weight:700;text-decoration:none;padding:11px 22px;border-radius:6px;">
                                            <?php esc_html_e( 'Upgrade to PRO &rarr;', 'url-shortify' ); ?>
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <div style="text-align:center;padding:2px 0 8px;">
                    <a href="<?php echo esc_url( $analytics_url ); ?>" style="display:inline-block;font-size:12px;color:#6b7280;text-decoration:none;margin-top:10px;">
                        <?php esc_html_e( 'View basic analytics &rarr;', 'url-shortify' ); ?>
                    </a>
                </div>
            <?php endif; ?>


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
			<p style="margin-top:10px;border-top:1px solid #e5e7eb;padding-top:10px;">
				<a href="<?php echo esc_url( $website_url ); ?>" style="color:#6b7280;text-decoration:none;font-size:12px;">
					<?php esc_html_e( 'KaizenCoders', 'url-shortify' ); ?>
				</a>
				&nbsp;&bull;&nbsp;
				<a href="<?php echo esc_url( $docs_url ); ?>" style="color:#6b7280;text-decoration:none;font-size:12px;">
					<?php esc_html_e( 'Documentation', 'url-shortify' ); ?>
				</a>
				&nbsp;&bull;&nbsp;
				<a href="<?php echo esc_url( $site_url ); ?>" style="color:#6b7280;text-decoration:none;font-size:12px;">
					<?php echo esc_html( $site_name ); ?>
				</a>
			</p>
		</td>
	</tr>

</table>
</td></tr>
</table>
</div>
</body>
</html>
