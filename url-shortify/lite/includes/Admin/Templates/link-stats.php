<?php

use KaizenCoders\URL_Shortify\Admin\Controllers\ClicksController;
use KaizenCoders\URL_Shortify\Common\Utils;
use KaizenCoders\URL_Shortify\Helper;

$page_refresh_url = Utils::get_current_page_refresh_url();


$today_url        = Utils::get_stats_filter_url( array( 'time_filter' => 'today' ) );
$last_7_days_url  = Utils::get_stats_filter_url( array( 'time_filter' => 'last_7_days' ) );
$last_30_days_url = Utils::get_stats_filter_url( array( 'time_filter' => 'last_30_days' ) );
$last_60_days_url = Utils::get_stats_filter_url( array( 'time_filter' => 'last_60_days' ) );
$all_time_url     = Utils::get_stats_filter_url( array( 'time_filter' => 'all_time' ) );

$time_filter = Helper::get_data( $_GET, 'time_filter', '' );

if ( empty( $time_filter ) ) {
	$time_filter = ( US()->is_pro() ) ? 'all_time' : 'last_7_days';
}

// Custom date filter is PRO-only; fall back gracefully for free users.
if ( 'custom' === $time_filter && ! US()->is_pro() ) {
	$time_filter = 'last_7_days';
}

$buttons = [
	'today' => [
		'label' => __( 'Today', 'url-shortify' ),
		'url'   => $today_url,
		'class' => 'today' === $time_filter ? 'active' : 'inactive',
		'filter' => 'today',
	],

	'last_7_days' => [
		'label' => __( '7 Days', 'url-shortify' ),
		'url'   => $last_7_days_url,
		'class' => 'last_7_days' === $time_filter ? 'active' : 'inactive',
		'filter' => 'last_7_days',
	],
];

if ( US()->is_pro() ) {
	$pro_buttons = [
		'last_30_days' => [
			'label' => __( '30 Days', 'url-shortify' ),
			'url'   => $last_30_days_url,
			'class' => 'last_30_days' === $time_filter ? 'active' : 'inactive',
			'filter' => 'last_30_days',
		],

		'last_60_days' => [
			'label' => __( '2 Months', 'url-shortify' ),
			'url'   => $last_60_days_url,
			'class' => 'last_60_days' === $time_filter ? 'active' : 'inactive',
			'filter' => 'last_60_days',
		],

		'all_time' => [
			'label' => __( 'All Time', 'url-shortify' ),
			'url'   => $all_time_url,
			'class' => 'all_time' === $time_filter ? 'active' : 'inactive',
			'filter' => 'all_time',
		],
	];

	$buttons = $buttons + $pro_buttons;
}


$short_link = esc_attr( Helper::get_data( $data, 'short_url', '' ) );

$link_id = Helper::get_data( $data, 'id', '' );

$export_url = Helper::get_link_action_url( $link_id, 'export' );

$clicks_data = $data['reports']['clicks'];

$click_data_for_graph = $data['click_data_for_graph'];
$chart_data = Helper::get_data( $data, 'chart_data', [] );
$has_chart_data = ! empty( $chart_data )
	&& ! empty( Helper::get_data( $chart_data, 'dates', [] ) )
	&& array_sum( array_map( 'intval', Helper::get_data( $chart_data, 'total_series', [] ) ) ) > 0;
$has_heatmap_data = ! empty( $chart_data )
	&& ! empty( Helper::get_data( $chart_data, 'heatmap_series', [] ) )
	&& ! empty( Helper::get_data( $chart_data, 'has_clicks_data', false ) );

$last_updated_on = Helper::get_data( $data, 'last_updated_on', time() );

$elapsed_time = Utils::get_elapsed_time( $last_updated_on );

$labels = $values = '';
$chart_labels = [];
$chart_values = [];

$total_clicks = 0;
if ( ! empty( $click_data_for_graph ) ) {
	$chart_labels = array_keys( $click_data_for_graph );

	$clicks = array_map( 'intval', array_values( $click_data_for_graph ) );

	$total_clicks = array_sum( $clicks );

	$chart_values = $clicks;

	$labels = wp_json_encode( $chart_labels );

	$values = wp_json_encode( $clicks );
}

$current_start_date = Helper::get_data( $_GET, 'start_date', '' );
$current_end_date   = Helper::get_data( $_GET, 'end_date', '' );

$days = 7;
switch ( $time_filter ) {
	case 'today':
		$days = 1;
		break;
	case 'last_7_days':
		$days = 7;
		break;
	case 'last_30_days':
		$days = 30;
		break;
	case 'last_60_days':
		$days = 60;
		break;
	case 'all_time':
		$days = 0;
		break;
}

$columns = ClicksController::get_table_columns();

$click_history = new ClicksController();
$click_history->set_columns( $columns );

?>

<div class="wrap">
    <div class="font-sans bg-grey-lighter flex flex-col min-h-screen w-full">

        <div class="w-full">
            <div class="md:block mt-3 border-b border-gray-300 pb-5">
                <div class="container mx-auto">
                    <div class="md:flex">
                        <div class="flex inline -mb-px mr-8 w-11/12">
								<span class="flex">
									<img class="h-6 w-6 mr-2" src="<?php echo $data['icon_url']; ?>" title="<?php echo esc_attr( $data['url'] ); ?>"/>
									<strong class="text-2xl">
										<a href="<?php echo $data['url']; ?>" target="_blank">
										 <?php echo stripslashes( $data['name'] ); ?>
										</a>
									</strong>
								</span>
							<?php

							echo Helper::create_copy_short_link_html( $short_link, $data['id'] );
							?>
                        </div>

                        <div class="flex float-right text-center mr-2 w-1/12">
							<?php if ( US()->is_pro() ) {
								echo Helper::get_social_share_widget( $link_id, 2 );
							} ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Click History Report -->
        <div class="mt-5">
            <div class="grid grid-cols-1">
                <section class="kc-us-chart-card kc-us-heatmap-card bg-white relative overflow-hidden rounded-3xl border rounded-xl border-gray-200 px-6 py-8 shadow-[0_20px_45px_rgba(15,23,42,0.1)]">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h2 class="text-2xl font-semibold leading-tight text-slate-900"><?php _e( 'Total vs Unique Links', 'url-shortify' ); ?></h2>
                            <p class="mt-1 max-w-2xl text-sm leading-5 text-slate-500 mb-2">
                                <span id="kc-us-total-clicks"><?php echo esc_html( sprintf( __( '%d Total Clicks', 'url-shortify' ), $total_clicks ) ); ?></span>
                            </p>
                        </div>
                        <div id="kc-us-clicks-filter-controls" class="flex flex-wrap items-center gap-2">

                            <!-- Segmented pill filter -->
                            <div class="inline-flex items-center rounded-xl border border-gray-200 bg-gray-100 p-1 gap-0.5">
                                <?php foreach ( $buttons as $key => $button ) : ?>
                                    <button type="button"
                                            class="kc-us-filter-pill rounded-lg px-3 py-1.5 text-sm font-medium transition-all duration-150 <?php echo 'active' === $button['class'] ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700 hover:bg-white/60'; ?>"
                                            data-filter="<?php echo esc_attr( $button['filter'] ); ?>">
                                        <?php echo esc_html( $button['label'] ); ?>
                                    </button>
                                <?php endforeach; ?>
                                <?php if ( US()->is_pro() ) : ?>
                                <button type="button"
                                        class="kc-us-filter-pill rounded-lg px-3 py-1.5 text-sm font-medium transition-all duration-150 <?php echo 'custom' === $time_filter ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700 hover:bg-white/60'; ?>"
                                        data-filter="custom">
                                    <span class="dashicons dashicons-calendar-alt" style="width:14px;height:14px;font-size:14px;vertical-align:middle;margin-right:3px;" aria-hidden="true"></span><?php esc_html_e( 'Custom', 'url-shortify' ); ?>
                                </button>
                                <?php endif; ?>
                            </div>

                            <!-- Custom date range picker (PRO only, visible when Custom is active) -->
                            <?php if ( US()->is_pro() ) : ?>
                            <div id="kc-us-clicks-custom-control" class="<?php echo ( 'custom' === $time_filter ) ? '' : 'hidden'; ?> inline-flex flex-wrap items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-1.5 shadow-sm">
                                <input type="text"
                                       id="kc-us-start-date"
                                       class="kc-us-date-picker w-28 rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                       placeholder="<?php esc_attr_e( 'Start date', 'url-shortify' ); ?>"
                                       value="<?php echo esc_attr( $current_start_date ); ?>" />
                                <span class="text-xs font-medium text-slate-400"><?php esc_html_e( '→', 'url-shortify' ); ?></span>
                                <input type="text"
                                       id="kc-us-end-date"
                                       class="kc-us-date-picker w-28 rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                       placeholder="<?php esc_attr_e( 'End date', 'url-shortify' ); ?>"
                                       value="<?php echo esc_attr( $current_end_date ); ?>" />
                                <button type="button"
                                        id="kc-us-clicks-custom-apply"
                                        class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
                                        title="<?php esc_attr_e( 'Apply custom date range', 'url-shortify' ); ?>">
                                    <?php esc_html_e( 'Apply', 'url-shortify' ); ?>
                                </button>
                            </div>
                            <?php endif; // is_pro — custom control ?>

                            <!-- Refresh -->
                            <a href="<?php echo esc_url( $page_refresh_url ); ?>"
                               id="kc-us-clicks-refresh"
                               class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white p-2 text-gray-400 shadow-sm hover:bg-gray-50 hover:text-gray-600 transition-colors duration-150"
                               title="<?php esc_attr_e( 'Refresh', 'url-shortify' ); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M10.2 3.28c3.53 0 6.43 2.61 6.92 6h2.08l-3.5 4l-3.5-4h2.32a4.439 4.439 0 0 0-4.32-3.45c-1.45 0-2.73.71-3.54 1.78L4.95 5.66a6.965 6.965 0 0 1 5.25-2.38zm-.4 13.44c-3.52 0-6.43-2.61-6.92-6H.8l3.5-4c1.17 1.33 2.33 2.67 3.5 4H5.48a4.439 4.439 0 0 0 4.32 3.45c1.45 0 2.73-.71 3.54-1.78l1.71 1.95a6.95 6.95 0 0 1-5.25 2.38z" fill="currentColor"/></svg>
                            </a>

                        </div>
                    </div>

                    <?php if ( $has_chart_data ) { ?>
                        <div id="spline-area-chart" class="mt-6 h-[220px] w-full"></div>
                    <?php } else { ?>
                        <div class="mt-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 py-12 text-center">
                            <p class="text-base font-medium text-slate-700">
                                <?php esc_html_e( 'No clicks data available yet.', 'url-shortify' ); ?>
                            </p>
                        </div>
                    <?php } ?>
                </section>

                <?php if ( US()->is_pro() ) : ?>
                <section class="kc-us-chart-card kc-us-heatmap-card bg-white relative overflow-hidden rounded-3xl border rounded-xl border-gray-200 px-6 py-8 shadow-[0_20px_45px_rgba(15,23,42,0.1)] mt-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900"><?php _e( 'Link Activity Intensity', 'url-shortify' ); ?></h2>
                        </div>
                    </div>

                    <?php if ( $has_heatmap_data ) { ?>
                        <div class="kc-us-heatmap-chart-wrapper mt-2 w-full">
                            <div id="activity-heatmap" class="w-full"></div>
                            <div id="heatmap-month-row" class="kc-us-heatmap-month-row" aria-hidden="true"></div>
                        </div>
                    <?php } else { ?>
                        <div class="kc-us-heatmap-empty-state mt-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 py-12 text-center">
                            <p class="text-base font-medium text-slate-700">
                                <?php esc_html_e( 'No clicks data available. Once your link is visited, analytics will appear here', 'url-shortify' ); ?>
                            </p>
                        </div>
                    <?php } ?>
                </section>
                <?php endif; // is_pro — custom control ?>
            </div>
        </div>

        <!-- Country & Referrer Info -->
        <div class="mt-6">
            <div class="grid md:grid-cols-2 md:grid-cols-2 sm:grid-cols-1 gap-4">
                <!-- Country Info -->
                <div class="overflow-hidden  rounded-lg">

                    <div class="mb-4">
                        <span class="text-xl leading-6 font-medium text-gray-900"><?php _e( 'Top Locations', 'url-shortify' ); ?></span>
                    </div>

                    <div class="bg-white border-2">
						<?php
						if ( US()->is_pro() ) {
							do_action( 'kc_us_render_country_info', $data );
						} else {
                        if( US()->can_show_premium_promotion() ) {
							?>
                            <div class="w-full h-64 p-10 bg-green-50">
                                <div class="">
                                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100">
                                        <svg class="h-12 w-12 text-green-600" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                            <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                    </div>
                                    <div class="mt-3 text-center sm:mt-5">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-headline">
											<?php echo sprintf( __( '<a href="%s">Upgrade Now</a>', 'url-shortify' ), US()->get_landing_page_url( true ) ); ?>
                                        </h3>
                                        <div class="mt-2">
                                            <p class="text-sm leading-5 text-gray-500">
												<?php _e( 'Get insights about top locations from where people are clicking on your links.', 'url-shortify' ); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
						<?php } } ?>
                    </div>
                </div>

                <!-- Referrer Info -->
                <div class="overflow-hidden rounded-lg h-px-400">
                    <div class="mb-4">
                        <span class="text-xl leading-6 font-medium text-gray-900"><?php _e( 'Referrers', 'url-shortify' ); ?></span>
                    </div>
                    <div class="bg-white border-2" id="">
						<?php
						if ( US()->is_pro() ) {
							do_action( 'kc_us_render_referrer_info', $data );
						} else {
                        if( US()->can_show_premium_promotion() ) {
							?>
                            <div class="w-full h-64 p-10 bg-green-50">
                                <div class="">
                                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100">
                                        <svg class="h-12 w-12 text-green-600" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                            <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                    </div>
                                    <div class="mt-3 text-center sm:mt-5">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-headline">
											<?php echo sprintf( __( '<a href="%s">Upgrade Now</a>', 'url-shortify' ), US()->get_landing_page_url( true ) ); ?>
                                        </h3>
                                        <div class="mt-2">
                                            <p class="text-sm leading-5 text-gray-500">
												<?php _e( 'Know who are your top referrers.', 'url-shortify' ); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
						<?php } } ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Device Info, Browser Info & Platforms Info -->
        <div class="mt-6">
            <div class="grid md:grid-cols-3 sm:grid-cols-1 gap-4">

                <!-- Device Info -->
                <div class="overflow-hidden rounded-lg h-px-400">
                    <div class="mb-4">
                        <span class="text-xl leading-6 font-medium text-gray-900"><?php _e( 'Top Devices', 'url-shortify' ); ?></span>
                    </div>
					<?php
					if ( US()->is_pro() ) {
						do_action( 'kc_us_render_device_info', $data );
					} else {
                    if( US()->can_show_premium_promotion() ) {
						?>
                        <div class="w-full h-64 p-10 bg-green-50">
                            <div class="">
                                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100">
                                    <svg class="h-12 w-12 text-green-600" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                        <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-5">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-headline">
										<?php echo sprintf( __( '<a href="%s">Upgrade Now</a>', 'url-shortify' ), US()->get_landing_page_url( true ) ); ?>
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm leading-5 text-gray-500">
											<?php _e( 'Want to know which devices were used to access your links?', 'url-shortify' ); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
					<?php } } ?>
                </div>

                <!-- Browser Info -->
                <div class="overflow-hidden rounded-lg h-px-400">
                    <div class="mb-4">
                        <span class="text-xl leading-6 font-medium text-gray-900"><?php _e( 'Top Browsers', 'url-shortify' ); ?></span>
                    </div>
					<?php
					if ( US()->is_pro() ) {
						do_action( 'kc_us_render_browser_info', $data );
					} else {
                    if( US()->can_show_premium_promotion() ) {
						?>
                        <div class="w-full h-64 p-10 bg-green-50">
                            <div class="">
                                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100">
                                    <svg class="h-12 w-12 text-green-600" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                        <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-5">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-headline">
										<?php echo sprintf( __( '<a href="%s">Upgrade Now</a>', 'url-shortify' ), US()->get_landing_page_url( true ) ); ?>
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm leading-5 text-gray-500">
											<?php _e( 'Get information about browsers.', 'url-shortify' ); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
					<?php } } ?>
                </div>

                <!-- OS Info -->
                <div class="overflow-hidden rounded-lg h-px-400">
                    <div class="mb-4">
                        <span class="text-xl leading-6 font-medium text-gray-900"><?php _e( 'Top Platforms', 'url-shortify' ); ?></span>
                    </div>
					<?php
					if ( US()->is_pro() ) {
						do_action( 'kc_us_render_os_info', $data );
					} else {
                    if( US()->can_show_premium_promotion() ) {
						?>
                        <div class="w-full h-64 p-10 bg-green-50">
                            <div class="">
                                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100">
                                    <svg class="h-12 w-12 text-green-600" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                        <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-5">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-headline">
										<?php echo sprintf( __( '<a href="%s">Upgrade Now</a>', 'url-shortify' ), US()->get_landing_page_url( true ) ); ?>
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm leading-5 text-gray-500">
											<?php _e( 'Know more about which devices people used to access your links.', 'url-shortify' ); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
					<?php } }  ?>
                </div>
            </div>
        </div>

        <!-- Split Test Results -->
		<?php
		$split_test_results = Helper::get_data( $data, 'split_test_results', [] );
		$show_split_test    = US()->is_pro();
		if ( $show_split_test && ! empty( $split_test_results ) ) :
			$is_split_test = ! empty( $split_test_results[0]['is_split_test'] );
		?>
        <div class="mt-6">
            <div class="mt-2 flex w-full border-b-2 border-gray-100 mb-4">
                <div>
                    <span class="text-xl leading-6 font-medium text-gray-900">
                        <?php _e( 'Link Rotation Results', 'url-shortify' ); ?>
                    </span>
                    <?php if ( $is_split_test ) : ?>
                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                        <?php _e( 'Split Test', 'url-shortify' ); ?>
                    </span>
                    <?php endif; ?>
                    <p class="mt-1 text-sm text-gray-500">
                        <?php
                        if ( $is_split_test ) {
                            _e( 'Clicks and goal conversions per variant since tracking began (2.2.0+).', 'url-shortify' );
                        } else {
                            _e( 'Clicks per variant since tracking began (2.2.0+).', 'url-shortify' );
                        }
                        ?>
                    </p>
                </div>
            </div>

            <div class="bg-white border-2 overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 font-semibold text-gray-600 w-10"><?php _e( '#', 'url-shortify' ); ?></th>
                            <th class="px-4 py-3 font-semibold text-gray-600"><?php _e( 'Destination URL', 'url-shortify' ); ?></th>
                            <th class="px-4 py-3 font-semibold text-gray-600 text-right w-28"><?php _e( 'Traffic %', 'url-shortify' ); ?></th>
                            <th class="px-4 py-3 font-semibold text-gray-600 text-right w-28"><?php _e( 'Total Clicks', 'url-shortify' ); ?></th>
                            <th class="px-4 py-3 font-semibold text-gray-600 text-right w-28"><?php _e( 'Unique Visitors', 'url-shortify' ); ?></th>
                            <th class="px-4 py-3 font-semibold text-gray-600 text-right w-28"><?php _e( 'First Clicks', 'url-shortify' ); ?></th>
                            <?php if ( $is_split_test ) : ?>
                            <th class="px-4 py-3 font-semibold text-gray-600 text-right w-36">
                                <?php _e( 'Goal Conv. %', 'url-shortify' ); ?>
                                <span class="block text-xs font-normal text-gray-400"><?php _e( 'visitors → goal', 'url-shortify' ); ?></span>
                            </th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    <?php
                    $max_clicks      = max( array_column( $split_test_results, 'total_clicks' ) );
                    $max_conv_rate   = $is_split_test
                        ? max( array_map( function( $v ) { return isset( $v['conversion_rate'] ) ? (float) $v['conversion_rate'] : 0; }, $split_test_results ) )
                        : 0;
                    foreach ( $split_test_results as $variant ) :
                        $variant_num     = (int) $variant['r_index'] + 1;
                        $conv_rate       = isset( $variant['conversion_rate'] ) ? (float) $variant['conversion_rate'] : null;
                        $conversions     = isset( $variant['conversions'] ) ? (int) $variant['conversions'] : null;
                        // Leader: highest conversion rate when split-test; highest clicks otherwise.
                        $is_leader       = $is_split_test
                            ? ( $max_conv_rate > 0 && $conv_rate === $max_conv_rate )
                            : ( $max_clicks > 0 && (int) $variant['total_clicks'] === $max_clicks );
                        $bar_pct         = $max_clicks > 0 ? round( ( $variant['total_clicks'] / $max_clicks ) * 100 ) : 0;
                        $variant_label   = sprintf( __( 'Variant %s', 'url-shortify' ), chr( 64 + $variant_num ) ); // A, B, C…
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold
                                <?php echo $is_leader ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600'; ?>">
                                <?php echo esc_html( chr( 64 + $variant_num ) ); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col gap-1">
                                <a href="<?php echo esc_url( $variant['url'] ); ?>" target="_blank"
                                   class="text-indigo-600 hover:underline break-all text-sm font-medium">
                                    <?php echo esc_html( $variant['url'] ); ?>
                                </a>
                                <?php if ( $is_leader ) : ?>
                                <span class="inline-flex items-center text-xs text-green-700 font-medium">
                                    &#9650; <?php _e( 'Leading', 'url-shortify' ); ?>
                                </span>
                                <?php endif; ?>
                                <!-- Click volume bar -->
                                <div class="w-full bg-gray-100 rounded-full h-1.5 mt-1">
                                    <div class="<?php echo $is_leader ? 'bg-indigo-500' : 'bg-gray-300'; ?> h-1.5 rounded-full"
                                         style="width:<?php echo esc_attr( $bar_pct ); ?>%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-700">
                            <?php echo null !== $variant['weight_pct']
                                ? esc_html( $variant['weight_pct'] ) . '%'
                                : '&mdash;'; ?>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900">
                            <?php echo number_format_i18n( $variant['total_clicks'] ); ?>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-700">
                            <?php echo number_format_i18n( $variant['unique_visitors'] ); ?>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-700">
                            <?php echo number_format_i18n( $variant['first_clicks'] ); ?>
                        </td>
                        <?php if ( $is_split_test ) : ?>
                        <td class="px-4 py-3 text-right">
                            <?php if ( null !== $conv_rate ) : ?>
                                <span class="inline-flex flex-col items-end gap-0.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold
                                        <?php echo $is_leader ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'; ?>">
                                        <?php echo esc_html( number_format( $conv_rate, 1 ) ); ?>%
                                        <?php if ( $is_leader ) : ?>
                                        <svg class="ml-1 w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                        <?php endif; ?>
                                    </span>
                                    <span class="text-xs text-gray-400"><?php echo esc_html( number_format_i18n( $conversions ) ); ?> <?php _e( 'conv.', 'url-shortify' ); ?></span>
                                </span>
                            <?php else : ?>
                                <span class="text-gray-400 text-xs"><?php _e( 'No goal set', 'url-shortify' ); ?></span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php
            // Summary callout: winning variant
            $winner_idx  = 0;
            $winner_val  = -1;
            foreach ( $split_test_results as $i => $v ) {
                $score = $is_split_test
                    ? ( isset( $v['conversion_rate'] ) ? (float) $v['conversion_rate'] : 0 )
                    : (int) $v['total_clicks'];
                if ( $score > $winner_val ) { $winner_val = $score; $winner_idx = $i; }
            }
            $winner        = $split_test_results[ $winner_idx ];
            $winner_letter = chr( 65 + (int) $winner['r_index'] );
            $total_clicks_all = array_sum( array_column( $split_test_results, 'total_clicks' ) );
            ?>
            <div class="mt-3 flex flex-wrap gap-4">
                <!-- Winner callout -->
                <div class="flex-1 min-w-0 flex items-center gap-3 bg-green-50 border border-green-200 rounded-lg px-4 py-3">
                    <div class="flex-shrink-0 flex items-center justify-center w-9 h-9 rounded-full bg-green-100 text-green-700 text-sm font-bold">
                        <?php echo esc_html( $winner_letter ); ?>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-green-800">
                            <?php printf( esc_html__( 'Variant %s is winning', 'url-shortify' ), $winner_letter ); ?>
                        </p>
                        <p class="text-xs text-green-700 truncate">
                            <?php if ( $is_split_test && isset( $winner['conversion_rate'] ) ) : ?>
                                <?php printf( esc_html__( '%s%% conversion rate · %s conversions', 'url-shortify' ),
                                    number_format( $winner['conversion_rate'], 1 ),
                                    number_format_i18n( $winner['conversions'] ) ); ?>
                            <?php else : ?>
                                <?php printf( esc_html__( '%s total clicks', 'url-shortify' ),
                                    number_format_i18n( $winner['total_clicks'] ) ); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <!-- Total summary -->
                <div class="flex items-center gap-3 bg-gray-50 border border-gray-200 rounded-lg px-4 py-3">
                    <div class="text-center">
                        <p class="text-lg font-bold text-gray-900"><?php echo number_format_i18n( $total_clicks_all ); ?></p>
                        <p class="text-xs text-gray-500"><?php _e( 'Total Clicks', 'url-shortify' ); ?></p>
                    </div>
                    <?php if ( $is_split_test ) : ?>
                    <div class="w-px h-8 bg-gray-200"></div>
                    <div class="text-center">
                        <p class="text-lg font-bold text-gray-900"><?php echo number_format_i18n( array_sum( array_column( $split_test_results, 'conversions' ) ) ); ?></p>
                        <p class="text-xs text-gray-500"><?php _e( 'Total Conv.', 'url-shortify' ); ?></p>
                    </div>
                    <div class="w-px h-8 bg-gray-200"></div>
                    <div class="text-center">
                        <?php
                        $total_unique = array_sum( array_column( $split_test_results, 'unique_visitors' ) );
                        $total_conv   = array_sum( array_column( $split_test_results, 'conversions' ) );
                        $overall_rate = $total_unique > 0 ? round( $total_conv / $total_unique * 100, 1 ) : 0;
                        ?>
                        <p class="text-lg font-bold text-gray-900"><?php echo esc_html( $overall_rate ); ?>%</p>
                        <p class="text-xs text-gray-500"><?php _e( 'Overall Conv. Rate', 'url-shortify' ); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
		<?php endif; ?>

        <!-- Clicks Info -->
        <div class="mt-10 flex w-full">
            <div class="w-11/12">
                <span class="text-xl leading-6 font-medium text-gray-900"><?php _e( 'Clicks Details', 'url-shortify' ); ?></span>
            </div>
	        <?php if ( US()->is_pro() ) { ?>
                <div class="w-1/12 py-2 pl-8">
                    <a href="<?php echo $export_url; ?>" class="text-white hover:text-white" title="<?php _e('Download CSV', 'url-shortify'); ?>">
                        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" class="w-8 h-8 text-indigo-600 hover:text-indigo-500 active:text-indigo-600"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </a>
                </div>
            <?php } ?>
        </div>
        <div class="bg-white flex-grow sm:px-4 mt-4 pt-6 pb-8">

            <div>
                <table id="clicks-data"
                       class="display"
                       data-server-side="true"
                       data-link-id="<?php echo esc_attr( $link_id ); ?>"
                       data-time-filter="<?php echo esc_attr( $time_filter ); ?>"
                       data-start-date="<?php echo esc_attr( $current_start_date ); ?>"
                       data-end-date="<?php echo esc_attr( $current_end_date ); ?>"
                       data-days="<?php echo esc_attr( $days ); ?>"
                       style="width:100%">
                    <thead>
				<?php $click_history->render_header(); ?>
                    </thead>
                    <tbody>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>

</div>

<script type="text/javascript">
	window.us_chart_data = <?php echo wp_json_encode( $chart_data ); ?>;
</script>
