<?php

use KaizenCoders\URL_Shortify\Admin\Controllers\ClicksController;
use KaizenCoders\URL_Shortify\Admin\Controllers\LinksTableController;
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

$current_start_date = ( 'custom' === $time_filter ) ? Helper::get_data( $_GET, 'start_date', '' ) : '';
$current_end_date   = ( 'custom' === $time_filter ) ? Helper::get_data( $_GET, 'end_date', '' ) : '';

$buttons = [
	'today' => [
		'label' => __( 'Today', 'url-shortify' ),
		'url'   => $today_url,
		'class' => 'today' === $time_filter ? 'active' : 'inactive',
	],

	'last_7_days' => [
		'label' => __( 'Last 7 Days', 'url-shortify' ),
		'url'   => $last_7_days_url,
		'class' => 'last_7_days' === $time_filter ? 'active' : 'inactive',
	],
];

if ( US()->is_pro() ) {
	$pro_buttons = [
		'last_30_days' => [
			'label' => __( 'Last 30 Days', 'url-shortify' ),
			'url'   => $last_30_days_url,
			'class' => 'last_30_days' === $time_filter ? 'active' : 'inactive',
		],

		'last_60_days' => [
			'label' => __( 'Last 60 Days', 'url-shortify' ),
			'url'   => $last_60_days_url,
			'class' => 'last_60_days' === $time_filter ? 'active' : 'inactive',
		],

		'all_time' => [
			'label' => __( 'All Time', 'url-shortify' ),
			'url'   => $all_time_url,
			'class' => 'all_time' === $time_filter ? 'active' : 'inactive',
		],
	];

	$buttons = $buttons + $pro_buttons;
}


$reports     = Helper::get_data( $data, 'reports', array() );
$clicks_data = Helper::get_data( $reports, 'clicks', array() );

$group_id = Helper::get_data( $data, 'id', '' );

$export_url = Helper::get_group_action_url( $group_id, 'export' );

$export_links_url = Helper::get_group_action_url( $group_id, 'export_links' );

$click_data_for_graph = Helper::get_data( $data, 'click_data_for_graph', array() );

$labels       = $values = '';
$chart_labels = [];
$chart_values = [];
$total_clicks = 0;

$group_link_ids = [];
if ( ! empty( $data['links'] ) && is_array( $data['links'] ) ) {
	foreach ( $data['links'] as $link ) {
		$group_link_ids[] = absint( Helper::get_data( $link, 'id', 0 ) );
	}
}

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
	case 'custom':
		$days = 0;
		break;
}

if ( ! empty( $click_data_for_graph ) ) {
	$chart_labels = array_keys( $click_data_for_graph );

	$clicks = array_map( 'intval', array_values( $click_data_for_graph ) );

	$total_clicks = array_sum( $clicks );

	$chart_values = $clicks;

	$labels = wp_json_encode( $chart_labels );

	$values = wp_json_encode( $chart_values );
}

$last_updated_on = Helper::get_data( $data, 'last_updated_on', time() );

$elapsed_time = Utils::get_elapsed_time( $last_updated_on );


$columns = array(
	'ip'         => array( 'title' => __( 'IP', 'url-shortify' ) ),
	'uri'        => array( 'title' => __( 'URI', 'url-shortify' ) ),
	'link'       => array( 'title' => __( 'Link', 'url-shortify' ) ),
	'host'       => array( 'title' => __( 'Host', 'url-shortify' ) ),
	'referrer'   => array( 'title' => __( 'Referrer', 'url-shortify' ) ),
	'clicked_on' => array( 'title' => __( 'Clicked On', 'url-shortify' ) ),
	'info'       => array( 'title' => __( 'Info', 'url-shortify' ) ),
);

$click_history = new ClicksController();
$click_history->set_columns( $columns );

$links = Helper::get_data( $data, 'links', array() );

$links_columns = array(
	'name'       => array( 'title' => __( 'Title', 'url-shortify' ) ),
	'clicks'     => array( 'title' => __( 'Clicks', 'url-shortify' ) ),
	'created_at' => array( 'title' => __( 'Created On', 'url-shortify' ) ),
	'link'       => array( 'title' => __( 'Link', 'url-shortify' ) )
);

$links_table_controller = new LinksTableController();
$links_table_controller->set_columns( $links_columns );

?>

<div class="wrap">
	<div class="font-sans bg-grey-lighter flex flex-col min-h-screen w-full">

		<div class="w-full border-b border-gray-300 pb-5">
			<div class="md:block mt-3">
				<div class="container mx-auto">
					<div class="md:flex">
						<div class="flex inline -mb-px mr-8 w-9/12">
							<span class="flex">
								<strong class="text-2xl">
									 <?php echo stripslashes( $data['name'] ); ?>
								</strong>
							</span>
						</div>

					</div>
				</div>
			</div>
		</div>

		<!-- Click History Report -->
		<div class="mt-5">
			<div class="grid grid-cols-1">
				<div class="mt-2 flex w-full items-center justify-between border-b-2 border-gray-100 pb-4">
					<div>
						<span class="text-xl leading-6 font-medium text-gray-900"><?php _e( 'Clicks History', 'url-shortify' ); ?></span>
						<p class="mt-1 max-w-2xl text-sm leading-5 text-gray-500 mb-2"><?php echo sprintf( /* translators: %d: Total number of clicks */ __( '%d Total Clicks', 'url-shortify' ), $total_clicks ); ?></p>
					</div>
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <!-- Segmented pill filter -->
                        <div class="inline-flex items-center rounded-xl border border-gray-200 bg-gray-100 p-1 gap-0.5">
                            <?php foreach ( $buttons as $key => $button ) : ?>
                                <a href="<?php echo esc_url( $button['url'] ); ?>"
                                   class="rounded-lg px-3 py-1.5 text-sm font-medium transition-all duration-150 no-underline <?php echo 'active' === $button['class'] ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700 hover:bg-white/60'; ?>">
                                    <?php echo esc_html( $button['label'] ); ?>
                                </a>
                            <?php endforeach; ?>
                            <?php if ( US()->is_pro() ) : ?>
                                <button type="button"
                                        id="kc-us-group-custom-pill"
                                        class="rounded-lg px-3 py-1.5 text-sm font-medium transition-all duration-150 <?php echo 'custom' === $time_filter ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700 hover:bg-white/60'; ?>">
                                    <span class="dashicons dashicons-calendar-alt" style="width:14px;height:14px;font-size:14px;vertical-align:middle;margin-right:3px;" aria-hidden="true"></span><?php esc_html_e( 'Custom', 'url-shortify' ); ?>
                                </button>
                            <?php endif; ?>
                        </div>

                        <?php if ( US()->is_pro() ) : ?>
                        <!-- Custom date range picker (visible when Custom is active) -->
                        <div id="kc-us-group-custom-control" class="<?php echo ( 'custom' === $time_filter ) ? '' : 'hidden'; ?> inline-flex flex-wrap items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-1.5 shadow-sm">
                            <input type="text"
                                   id="kc-us-group-start-date"
                                   class="kc-us-date-picker w-28 rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                   placeholder="<?php esc_attr_e( 'Start date', 'url-shortify' ); ?>"
                                   value="<?php echo esc_attr( $current_start_date ); ?>" />
                            <span class="text-xs font-medium text-slate-400"><?php esc_html_e( '→', 'url-shortify' ); ?></span>
                            <input type="text"
                                   id="kc-us-group-end-date"
                                   class="kc-us-date-picker w-28 rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                   placeholder="<?php esc_attr_e( 'End date', 'url-shortify' ); ?>"
                                   value="<?php echo esc_attr( $current_end_date ); ?>" />
                            <button type="button"
                                    id="kc-us-group-custom-apply"
                                    class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
                                    title="<?php esc_attr_e( 'Apply custom date range', 'url-shortify' ); ?>">
                                <?php esc_html_e( 'Apply', 'url-shortify' ); ?>
                            </button>
                        </div>
                        <?php endif; ?>

                        <!-- Refresh -->
                        <a href="<?php echo esc_url( $page_refresh_url ); ?>"
                           id="kc-us-group-refresh"
                           class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white p-2 text-gray-400 shadow-sm hover:bg-gray-50 hover:text-gray-600 transition-colors duration-150"
                           title="<?php esc_attr_e( 'Refresh', 'url-shortify' ); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M10.2 3.28c3.53 0 6.43 2.61 6.92 6h2.08l-3.5 4l-3.5-4h2.32a4.439 4.439 0 0 0-4.32-3.45c-1.45 0-2.73.71-3.54 1.78L4.95 5.66a6.965 6.965 0 0 1 5.25-2.38zm-.4 13.44c-3.52 0-6.43-2.61-6.92-6H.8l3.5-4c1.17 1.33 2.33 2.67 3.5 4H5.48a4.439 4.439 0 0 0 4.32 3.45c1.45 0 2.73-.71 3.54-1.78l1.71 1.95a6.95 6.95 0 0 1-5.25 2.38z" fill="currentColor"/></svg>
                        </a>
                    </div>
				</div>
				<div class="bg-white mt-2" id="click-chart">

				</div>
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
						<?php }} ?>
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
					<?php } } ?>
				</div>
			</div>
		</div>

		<!-- Links Details -->
		<div class="mt-6 flex w-full">
			<div class="w-11/12">
				<span class="text-xl leading-6 font-medium text-gray-900"><?php _e( 'Links Info', 'url-shortify' ); ?></span>
			</div>
			<?php if ( US()->is_pro() ) { ?>
                <div class="w-1/12 py-2 pl-8">
                    <a href="<?php echo $export_links_url; ?>" class="text-white hover:text-white" title="<?php _e('Download CSV', 'url-shortify'); ?>">
                        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" class="w-8 h-8 text-indigo-600 hover:text-indigo-500 active:text-indigo-600"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </a>
                </div>
			<?php } ?>
		</div>

		<div class="bg-white flex-grow sm:px-4 mt-4 pt-6 pb-8">

			<div>
				<table id="links-data" class="display" style="width:100%">
					<thead>
					<?php $links_table_controller->render_header(); ?>
					</thead>
					<tbody>
					<?php 
					foreach ( $links as $link ) {
						$links_table_controller->render_row( $link );
					} 
					?>
					</tbody>
					<tfoot>
					<?php $links_table_controller->render_footer(); ?>
					</tfoot>
				</table>
			</div>
		</div>

		<!-- Click Info -->
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
				<table id="clicks-data" class="display" data-server-side="true" data-link-ids="<?php echo esc_attr( implode( ',', $group_link_ids ) ); ?>" data-days="<?php echo esc_attr( $days ); ?>" style="width:100%">
					<thead>
					<?php $click_history->render_header(); ?>
					</thead>
					<tbody></tbody>
					<tfoot>
					<?php $click_history->render_footer(); ?>
					</tfoot>
				</table>
			</div>
		</div>

	</div>

</div>

<script type="text/javascript">
(function ($) {
	$(document).ready(function () {
		const labels = <?php echo wp_json_encode( $chart_labels ); ?>;
		const values = <?php echo wp_json_encode( $chart_values ); ?>;
		const target = document.querySelector('#click-chart');

		if (!target || !Array.isArray(labels) || !labels.length || !Array.isArray(values) || !values.length) {
			return;
		}

		if (typeof ApexCharts === 'undefined') {
			console.error('ApexCharts is not available on this page.');
			return;
		}

		const options = {
			series: [{
				name: '<?php echo esc_js( __( 'Clicks', 'url-shortify' ) ); ?>',
				data: values,
			}],
			chart: {
				type: 'area',
				height: 320,
				toolbar: { show: false },
				zoom: { enabled: false },
				background: 'transparent',
			},
			dataLabels: { enabled: false },
			stroke: { curve: 'smooth', width: 3 },
			colors: ['#4f46e5', '#10b981'],
			fill: {
				type: 'gradient',
				gradient: {
					shadeIntensity: 0.35,
					opacityFrom: 0.85,
					opacityTo: 0.15,
					stops: [0, 40, 100],
				},
			},
			markers: { size: 0, hover: { sizeOffset: 4 } },
			xaxis: {
				type: 'category',
				categories: labels,
				labels: {
					rotate: -30,
					hideOverlappingLabels: true,
					style: { colors: '#475569' }
				},
				axisBorder: { color: 'rgba(148,163,184,0.35)' },
				axisTicks: { color: 'rgba(148,163,184,0.35)' },
				tickAmount: 8
			},
			yaxis: {
				labels: {
					formatter: function (val) { return Math.round(val); },
					style: { colors: '#475569' }
				},
				tickAmount: 6,
				min: 0
			},
			tooltip: {
				shared: true,
				y: {
					formatter: function (value) {
						return value + ' <?php echo esc_js( __( 'clicks', 'url-shortify' ) ); ?>';
					}
				}
			},
			grid: {
				borderColor: 'rgba(148,163,184,0.25)',
				strokeDashArray: 4,
				padding: { left: 10, right: 10 }
			},
		};

		target.innerHTML = '';
		const chart = new ApexCharts(target, options);
		chart.render();
	});

	// Custom date filter (PRO only).
	var $customPill    = $('#kc-us-group-custom-pill');
	var $customControl = $('#kc-us-group-custom-control');
	var $customApply   = $('#kc-us-group-custom-apply');
	var $startDate     = $('#kc-us-group-start-date');
	var $endDate       = $('#kc-us-group-end-date');

	if ($customPill.length) {
		$customPill.on('click', function () {
			$customControl.toggleClass('hidden');
		});
	}

	if ($customApply.length) {
		$customApply.on('click', function () {
			var start = $startDate.val().trim();
			var end   = $endDate.val().trim();

			if (!start || !end) {
				alert('<?php echo esc_js( __( 'Please enter both a start date and an end date.', 'url-shortify' ) ); ?>');
				return;
			}

			var url = new URL(window.location.href);
			url.searchParams.set('time_filter', 'custom');
			url.searchParams.set('start_date', start);
			url.searchParams.set('end_date', end);
			url.searchParams.set('refresh', '1');
			window.location.href = url.toString();
		});

		// Allow Enter key inside date inputs to apply.
		$startDate.add($endDate).on('keydown', function (e) {
			if (e.key === 'Enter') {
				e.preventDefault();
				$customApply.trigger('click');
			}
		});
	}
})(jQuery);

</script>
