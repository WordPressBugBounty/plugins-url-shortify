<?php

use KaizenCoders\URL_Shortify\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$clear_clicks_nonce = wp_create_nonce( 'kc_us_clear_clicks' );

$time_clicks_older_than_30_days_url = add_query_arg( [
	'tab'      => 'trim_clicks',
	'action'   => 'trim_clicks_older_than_30_days',
	'_wpnonce' => $clear_clicks_nonce,
], admin_url( 'admin.php?page=us_tools' ) );

$time_clicks_older_than_60_days_url = add_query_arg( [
	'tab'      => 'trim_clicks',
	'action'   => 'trim_clicks_older_than_60_days',
	'_wpnonce' => $clear_clicks_nonce,
], admin_url( 'admin.php?page=us_tools' ) );

$time_clicks_older_than_90_days_url = add_query_arg( [
	'tab'      => 'trim_clicks',
	'action'   => 'trim_clicks_older_than_90_days',
	'_wpnonce' => $clear_clicks_nonce,
], admin_url( 'admin.php?page=us_tools' ) );

$time_all_clicks_url = add_query_arg( [
	'tab'      => 'trim_clicks',
	'action'   => 'trim_all_clicks',
	'_wpnonce' => $clear_clicks_nonce,
], admin_url( 'admin.php?page=us_tools' ) );


$tooltip_for_trim_clicks_older_than_30_days = Helper::get_tooltip_html( 'This will clear all clicks in your database that are older than 30 days.' );
$tooltip_for_trim_clicks_older_than_60_days = Helper::get_tooltip_html( 'This will clear all clicks in your database that are older than 60 days.' );
$tooltip_for_trim_clicks_older_than_90_days = Helper::get_tooltip_html( 'This will clear all clicks in your database that are older than 90 days.' );
$tooltip_for_trim_all_clicks                = Helper::get_tooltip_html( 'This will clear all clicks in your database.' );

$trim_clicks = [
	'trim_clicks_older_than_30_days' => [
		'text'    => __( 'Clear clicks older than 30 days', 'url-shortify' ),
		'url'     => $time_clicks_older_than_30_days_url,
		'tooltip' => $tooltip_for_trim_clicks_older_than_30_days,
	],

	'trim_clicks_older_than_60_days' => [
		'text'    => __( 'Clear clicks older than 60 days', 'url-shortify' ),
		'url'     => $time_clicks_older_than_60_days_url,
		'tooltip' => $tooltip_for_trim_clicks_older_than_60_days,
	],

	'trim_clicks_older_than_90_days' => [
		'text'    => __( 'Clear clicks older than 90 days', 'url-shortify' ),
		'url'     => $time_clicks_older_than_90_days_url,
		'tooltip' => $tooltip_for_trim_clicks_older_than_90_days,
	],

	'trim_all_clicks' => [
		'text'    => __( 'Clear all clicks', 'url-shortify' ), // 'Clear all clicks
		'url'     => $time_all_clicks_url,
		'tooltip' => $tooltip_for_trim_all_clicks,
	],
];

?>
<?php if ( 'success' === $status ) { ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e( 'Click trimming completed successfully!', 'url-shortify' ); ?></p></div>
<?php } elseif ( 'error' === $status ) { ?>
    <div class="notice notice-error is-dismissible"><p><?php _e( 'An error occurred during the clicks trimming process.', 'url-shortify' ); ?></p></div>
<?php } ?>

<div class="flex-row pt-2 pb-2 ml-5 mr-4 text-left item-center">

	<?php foreach ( $trim_clicks as $trim_click_key => $trim_click ) : ?>
        <div class="flex flex-row border-b border-gray-100 m-5">
            <div class="flex w-full">
                <label for="">
                    <a class="kc-us-primary-button"
                       href="<?php echo $trim_click['url']; ?>"><?php echo $trim_click['text']; ?></a> <?php echo $trim_click['tooltip']; ?>
                </label>
            </div>
        </div>
	<?php endforeach; ?>

</div>
