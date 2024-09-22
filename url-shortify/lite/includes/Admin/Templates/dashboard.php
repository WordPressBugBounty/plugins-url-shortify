<?php

use KaizenCoders\URL_Shortify\Admin\Controllers\ClicksController;
use KaizenCoders\URL_Shortify\Common\Utils;
use KaizenCoders\URL_Shortify\Helper;

$page_refresh_url = Utils::get_current_page_refresh_url();

$export_url = Helper::get_action_url( null, 'main', 'export' );


$last_updated_on = Helper::get_data( $data, 'last_updated_on', time() );

$elapsed_time = Utils::get_elapsed_time( $last_updated_on );

$show_kpis     = Helper::get_data( $data, 'show_kpis', false );
$new_link_url  = Helper::get_data( $data, 'new_link_url', '' );
$new_group_url = Helper::get_data( $data, 'new_group_url', '' );

$show_landing_page = Helper::get_request_data('landing', false);

if ( $show_kpis && ! $show_landing_page) {

	$kpis = Helper::get_data( $data, 'kpis', array() );

	$clicks_data = $data['reports']['clicks'];

	$click_data_for_graph = $data['click_data_for_graph'];

	$labels = $values = '';
	if ( ! empty( $click_data_for_graph ) ) {
		$labels = json_encode( array_keys( $click_data_for_graph ) );

		$clicks = array_values( $click_data_for_graph );

		$total_clicks = array_sum( $clicks );

		$values = json_encode( $clicks );

	}

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

	?>

	<div class="wrap" id="">
		<header class="mx-auto">
			<div class="pb-5 border-b border-gray-300 md:flex md:items-center md:justify-between">
				<div class="flex-1 min-w-0">
					<h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:leading-9 sm:truncate">
						<?php _e( 'Dashboard', 'url-shortify' ); ?>
					</h2>
				</div>
                <!-- Theme Switcher -->
<!--                <div class="flex items-center justify-end pr-16 lg:pr-0">-->
<!--                    <label for="themeSwitcher" class="inline-flex cursor-pointer items-center" aria-label="themeSwitcher" name="themeSwitcher">-->
<!--                        <input type="checkbox" name="themeSwitcher" id="themeSwitcher" class="sr-only" />-->
<!--                        <span class="block text-black dark:hidden">-->
<!--                            <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">-->
<!--                                <path d="M13.3125 1.50001C12.675 1.31251 12.0375 1.16251 11.3625 1.05001C10.875 0.975006 10.35 1.23751 10.1625 1.68751C9.93751 2.13751 10.05 2.70001 10.425 3.00001C13.0875 5.47501 14.0625 9.11251 12.975 12.525C11.775 16.3125 8.25001 18.975 4.16251 19.0875C3.63751 19.0875 3.22501 19.425 3.07501 19.9125C2.92501 20.4 3.15001 20.925 3.56251 21.1875C4.50001 21.75 5.43751 22.2 6.37501 22.5C7.46251 22.8375 8.58751 22.9875 9.71251 22.9875C11.625 22.9875 13.5 22.5 15.1875 21.5625C17.85 20.1 19.725 17.7375 20.55 14.8875C22.1625 9.26251 18.975 3.37501 13.3125 1.50001ZM18.9375 14.4C18.2625 16.8375 16.6125 18.825 14.4 20.0625C12.075 21.3375 9.41251 21.6 6.90001 20.85C6.63751 20.775 6.33751 20.6625 6.07501 20.55C10.05 19.7625 13.35 16.9125 14.5875 13.0125C15.675 9.56251 15 5.92501 12.7875 3.07501C17.5875 4.68751 20.2875 9.67501 18.9375 14.4Z" />-->
<!--                            </svg>-->
<!--                        </span>-->
<!--                        <span class="hidden text-white dark:block">-->
<!--                          <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">-->
<!--                            <g clip-path="url(#clip0_2172_3070)">-->
<!--                              <path d="M12 6.89999C9.18752 6.89999 6.90002 9.18749 6.90002 12C6.90002 14.8125 9.18752 17.1 12 17.1C14.8125 17.1 17.1 14.8125 17.1 12C17.1 9.18749 14.8125 6.89999 12 6.89999ZM12 15.4125C10.125 15.4125 8.58752 13.875 8.58752 12C8.58752 10.125 10.125 8.58749 12 8.58749C13.875 8.58749 15.4125 10.125 15.4125 12C15.4125 13.875 13.875 15.4125 12 15.4125Z"/>-->
<!--                              <path d="M12 4.2375C12.45 4.2375 12.8625 3.8625 12.8625 3.375V1.5C12.8625 1.05 12.4875 0.637497 12 0.637497C11.55 0.637497 11.1375 1.0125 11.1375 1.5V3.4125C11.175 3.8625 11.55 4.2375 12 4.2375Z"/>-->
<!--                              <path d="M12 19.7625C11.55 19.7625 11.1375 20.1375 11.1375 20.625V22.5C11.1375 22.95 11.5125 23.3625 12 23.3625C12.45 23.3625 12.8625 22.9875 12.8625 22.5V20.5875C12.8625 20.1375 12.45 19.7625 12 19.7625Z"/>-->
<!--                              <path d="M18.1125 6.74999C18.3375 6.74999 18.5625 6.67499 18.7125 6.48749L19.9125 5.28749C20.25 4.94999 20.25 4.42499 19.9125 4.08749C19.575 3.74999 19.05 3.74999 18.7125 4.08749L17.5125 5.28749C17.175 5.62499 17.175 6.14999 17.5125 6.48749C17.6625 6.67499 17.8875 6.74999 18.1125 6.74999Z"/>-->
<!--                              <path d="M5.32501 17.5125L4.12501 18.675C3.78751 19.0125 3.78751 19.5375 4.12501 19.875C4.27501 20.025 4.50001 20.1375 4.72501 20.1375C4.95001 20.1375 5.17501 20.0625 5.32501 19.875L6.52501 18.675C6.86251 18.3375 6.86251 17.8125 6.52501 17.475C6.18751 17.175 5.62501 17.175 5.32501 17.5125Z"/>-->
<!--                              <path d="M22.5 11.175H20.5875C20.1375 11.175 19.725 11.55 19.725 12.0375C19.725 12.4875 20.1 12.9 20.5875 12.9H22.5C22.95 12.9 23.3625 12.525 23.3625 12.0375C23.3625 11.55 22.95 11.175 22.5 11.175Z"/>-->
<!--                              <path d="M4.23751 12C4.23751 11.55 3.86251 11.1375 3.37501 11.1375H1.50001C1.05001 11.1375 0.637512 11.5125 0.637512 12C0.637512 12.45 1.01251 12.8625 1.50001 12.8625H3.41251C3.86251 12.8625 4.23751 12.45 4.23751 12Z"/>-->
<!--                              <path d="M18.675 17.5125C18.3375 17.175 17.8125 17.175 17.475 17.5125C17.1375 17.85 17.1375 18.375 17.475 18.7125L18.675 19.9125C18.825 20.0625 19.05 20.175 19.275 20.175C19.5 20.175 19.725 20.1 19.875 19.9125C20.2125 19.575 20.2125 19.05 19.875 18.7125L18.675 17.5125Z"/>-->
<!--                              <path d="M5.32501 4.125C4.98751 3.7875 4.46251 3.7875 4.12501 4.125C3.78751 4.4625 3.78751 4.9875 4.12501 5.325L5.32501 6.525C5.47501 6.675 5.70001 6.7875 5.92501 6.7875C6.15001 6.7875 6.37501 6.7125 6.52501 6.525C6.86251 6.1875 6.86251 5.6625 6.52501 5.325L5.32501 4.125Z"/>-->
<!--                            </g>-->
<!--                            <defs>-->
<!--                              <clipPath id="clip0_2172_3070">-->
<!--                                <rect width="24" height="24" fill="white" />-->
<!--                              </clipPath>-->
<!--                            </defs>-->
<!--                          </svg>-->
<!--                        </span>-->
<!--                    </label>-->
<!--                    <div class="hidden sm:flex">-->
<!---->
<!--                    </div>-->
<!--                </div>-->
                <!-- Theme SWitcher End -->

				<div class="flex mt-4 md:mt-0 md:ml-4">
					<span class="rounded-md shadow-sm">
						<button type="button" class="w-full text-white bg-green-500 kc-us-primary-button hover:bg-green-400" title="<?php echo sprintf(__('Last Updated On: %s', 'url-shortify'), $elapsed_time ); ?>">
							<a href="<?php echo $page_refresh_url; ?>" class="text-white hover:text-white"><?php _e('Refresh', 'url-shortify'); ?></a>
						</button>
					</span>
					<span class="ml-3 rounded-md shadow-sm">
						<div id="kc-us-create-button" class="relative inline-block text-left">
							<div>
							  <span class="rounded-md shadow-sm">
								<button type="button" class="w-full kc-us-primary-button">
								  <?php _e( 'Create', 'url-shortify' ); ?>
								  <svg class="w-5 h-5 ml-2 -mr-1" fill="currentColor" viewBox="0 0 20 20">
									<path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
								  </svg>
								</button>
							  </span>
							</div>
							<div  id="kc-us-create-dropdown" class="absolute right-0 hidden w-56 mt-2 origin-top-right rounded-md shadow-lg">
							  <div class="bg-white rounded-md shadow-xs">
								<div class="py-1">
								  <a href="<?php echo $new_link_url; ?>" class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900"><?php _e( 'New Link', 'url-shortify' ); ?></a>
								  <a href="<?php echo $new_group_url; ?>" class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900"><?php _e( 'New Group', 'url-shortify' ); ?></a>
								</div>
							  </div>
							</div>
						</div>
					</span>
				</div>
			</div>
		</header>

		<!-- KPI -->
		<div class="mt-5">
			<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
				<?php foreach ( $kpis as $kpi ) { ?>
					<?php 
					if ( ! empty( $kpi['url'] ) ) {
						?>
						<a href="<?php echo $kpi['url']; ?>" target="_blank"> <?php } ?>
					<div class="overflow-hidden bg-white rounded-lg shadow">
						<div class="px-4 py-5 sm:p-6">
							<div class="flex items-center">
								<div class="flex-shrink-0 p-3 bg-indigo-500 rounded-md">
									<svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
										<?php echo $kpi['icon']; ?>
									</svg>
								</div>
								<div class="flex-1 w-0 ml-5">
									<dl>
										<dt class="text-sm font-medium leading-5 text-gray-500 truncate">
											<?php echo $kpi['title']; ?>
										</dt>
										<dd class="flex items-baseline">
											<div class="text-2xl font-semibold leading-8 text-gray-900">
												<?php echo $kpi['count']; ?>
											</div>
										</dd>
									</dl>
								</div>
							</div>
						</div>
					</div>
					<?php 
					if ( ! empty( $kpi['url'] ) ) {
						?>
						 </a> <?php } ?>
				<?php } ?>
			</div>
		</div>
		<!-- KPI END -->

		<!-- Click History Report -->
		<div class="mt-4">
			<div class="grid grid-cols-1 mt-5">
				<div class="flex w-full mt-2 border-b-2 border-gray-100">
					<div class="w-11/12">
						<span class="text-xl font-medium leading-6 text-gray-900"><?php _e( 'Clicks History', 'url-shortify' ); ?></span>
						<p class="max-w-2xl mt-1 mb-2 text-sm leading-5 text-gray-500"><?php echo sprintf( __( '%d Total Clicks', 'url-shortify' ), $total_clicks ); ?></p>
					</div>
				</div>
				<!-- Click Chart will draw here -->
				<div class="mt-2 bg-white" id="click-chart">

				</div>
			</div>
		</div>

		<!-- Country & Referrer Info -->
		<div class="mt-6">
			<div class="grid gap-4 md:grid-cols-2 sm:grid-cols-1">
				<!-- Country Info -->
				<div class="overflow-hidden rounded-lg">

					<div class="mb-4">
						<span class="text-xl font-medium leading-6 text-gray-900"><?php _e( 'Top Locations', 'url-shortify' ); ?></span>
					</div>

					<div class="bg-white border-2">
						<?php 
						if ( US()->is_pro() ) {
							do_action( 'kc_us_render_country_info', $data );
						} else { 
							?>
							<div class="w-full h-64 p-10 bg-green-50">
								<div class="">
									<div class="flex items-center justify-center w-16 h-16 mx-auto bg-green-100 rounded-full">
										<svg class="w-12 h-12 text-green-600" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
											<path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
										</svg>
									</div>
									<div class="mt-3 text-center sm:mt-5">
										<h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-headline">
											<?php echo sprintf( __( '<a href="%s">Upgrade Now</a>', 'url-shortify' ), US()->get_landing_page_url() ); ?>
										</h3>
										<div class="mt-2">
											<p class="text-sm leading-5 text-gray-500">
												<?php _e( 'Get insights about top locations from where people are clicking on your links.', 'url-shortify' ); ?>
											</p>
										</div>
									</div>
								</div>
							</div>
						<?php } ?>
					</div>
				</div>

				<!-- Referrer Info -->
				<div class="overflow-hidden rounded-lg h-px-400">
					<div class="mb-4">
						<span class="text-xl font-medium leading-6 text-gray-900"><?php _e( 'Referrers', 'url-shortify' ); ?></span>
					</div>
					<div class="bg-white border-2" id="">
						<?php 
						if ( US()->is_pro() ) {
							do_action( 'kc_us_render_referrer_info', $data );
						} else { 
							?>
							<div class="w-full h-64 p-10 bg-green-50">
								<div class="">
									<div class="flex items-center justify-center w-16 h-16 mx-auto bg-green-100 rounded-full">
										<svg class="w-12 h-12 text-green-600" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
											<path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
										</svg>
									</div>
									<div class="mt-3 text-center sm:mt-5">
										<h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-headline">
											<?php echo sprintf( __( '<a href="%s">Upgrade Now</a>', 'url-shortify' ), US()->get_landing_page_url() ); ?>
										</h3>
										<div class="mt-2">
											<p class="text-sm leading-5 text-gray-500">
												<?php _e( 'Know who are your top referrers.', 'url-shortify' ); ?>
											</p>
										</div>
									</div>
								</div>
							</div>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>

		<!-- Device Info, Browser Info & Platforms Info -->
		<div class="mt-6">
			<div class="grid gap-4 md:grid-cols-3 sm:grid-cols-1">

				<!-- Device Info -->
				<div class="overflow-hidden rounded-lg h-px-400">
					<div class="mb-4">
						<span class="text-xl font-medium leading-6 text-gray-900"><?php _e( 'Top Devices', 'url-shortify' ); ?></span>
					</div>
					<?php 
					if ( US()->is_pro() ) {
						do_action( 'kc_us_render_device_info', $data );
					} else { 
						?>
						<div class="w-full h-64 p-10 bg-green-50">
							<div class="">
								<div class="flex items-center justify-center w-16 h-16 mx-auto bg-green-100 rounded-full">
									<svg class="w-12 h-12 text-green-600" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
										<path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
									</svg>
								</div>
								<div class="mt-3 text-center sm:mt-5">
									<h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-headline">
										<?php echo sprintf( __( '<a href="%s">Upgrade Now</a>', 'url-shortify' ), US()->get_landing_page_url() ); ?>
									</h3>
									<div class="mt-2">
										<p class="text-sm leading-5 text-gray-500">
											<?php _e( 'Want to know which devices were used to access your links?', 'url-shortify' ); ?>
										</p>
									</div>
								</div>
							</div>
						</div>
					<?php } ?>
				</div>

				<!-- Browser Info -->
				<div class="overflow-hidden rounded-lg h-px-400">
					<div class="mb-4">
						<span class="text-xl font-medium leading-6 text-gray-900"><?php _e( 'Top Browsers', 'url-shortify' ); ?></span>
					</div>
					<?php 
					if ( US()->is_pro() ) {
						do_action( 'kc_us_render_browser_info', $data );
					} else { 
						?>
						<div class="w-full h-64 p-10 bg-green-50">
							<div class="">
								<div class="flex items-center justify-center w-16 h-16 mx-auto bg-green-100 rounded-full">
									<svg class="w-12 h-12 text-green-600" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
										<path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
									</svg>
								</div>
								<div class="mt-3 text-center sm:mt-5">
									<h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-headline">
										<?php echo sprintf( __( '<a href="%s">Upgrade Now</a>', 'url-shortify' ), US()->get_landing_page_url() ); ?>
									</h3>
									<div class="mt-2">
										<p class="text-sm leading-5 text-gray-500">
											<?php _e( 'Get information about browsers.', 'url-shortify' ); ?>
										</p>
									</div>
								</div>
							</div>
						</div>
					<?php } ?>
				</div>


				<!-- OS Info -->
				<div class="overflow-hidden rounded-lg h-px-400">
					<div class="mb-4">
						<span class="text-xl font-medium leading-6 text-gray-900"><?php _e( 'Top Platforms', 'url-shortify' ); ?></span>
					</div>
					<?php 
					if ( US()->is_pro() ) {
						do_action( 'kc_us_render_os_info', $data );
					} else { 
						?>
						<div class="w-full h-64 p-10 bg-green-50">
							<div class="">
								<div class="flex items-center justify-center w-16 h-16 mx-auto bg-green-100 rounded-full">
									<svg class="w-12 h-12 text-green-600" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
										<path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
									</svg>
								</div>
								<div class="mt-3 text-center sm:mt-5">
									<h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-headline">
										<?php echo sprintf( __( '<a href="%s">Upgrade Now</a>', 'url-shortify' ), US()->get_landing_page_url() ); ?>
									</h3>
									<div class="mt-2">
										<p class="text-sm leading-5 text-gray-500">
											<?php _e( 'Know more about which devices people used to access your links.', 'url-shortify' ); ?>
										</p>
									</div>
								</div>
							</div>
						</div>
					<?php } ?>
				</div>
			</div>
		</div>

		<!-- Click History -->
		<div class="flex w-full mt-6">
			<div class="w-11/12">
				<span class="text-xl font-medium leading-6 text-gray-900"><?php _e( 'Clicks Details', 'url-shortify' ); ?></span>
			</div>
			<?php if ( US()->is_pro() ) { ?>
                <div class="w-1/12 py-2 pl-8">
                    <a href="<?php echo $export_url; ?>" class="text-white hover:text-white">
                        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" class="w-8 h-8 text-indigo-600 hover:text-indigo-500 active:text-indigo-600"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </a>
                </div>
			<?php } ?>
		</div>
		<div class="flex-grow pt-6 pb-8 mx-auto mt-4 bg-white sm:px-4">

			<div>
				<table id="clicks-data" class="display" style="width:100%">
					<thead>
					<?php $click_history->render_header(); ?>
					</thead>
					<tbody>
					<?php 
					foreach ( $clicks_data as $click ) {
						$click_history->render_row( $click );
					} 
					?>
					</tbody>
					<tfoot>
					<?php $click_history->render_footer(); ?>
					</tfoot>
				</table>
			</div>
		</div>


	</div>


<?php } else {
        include_once 'landing.php';
} ?>


<script type="text/javascript">

	(function ($) {

		$(document).ready(function () {

			var labels = 
			<?php 
			if ( ! empty( $labels ) ) {
				echo $labels;
			} else {
				echo "''";
			} 
			?>
			;

			var values = 
			<?php 
			if ( ! empty( $values ) ) {
				echo $values;
			} else {
				echo "''";
			} 
			?>
			;

			if (labels != '' && values != '') {
				const data = {
					labels: labels,
					datasets: [
						{
							values: values
						},
					]
				};

				const chart = new frappe.Chart("#click-chart", {
					title: "",
					data: data,
					type: 'axis-mixed',
					colors: ['#5850ec'],
					lineOptions: {
						hideDots: 1,
						regionFill: 1
					},
					height: 250,

					axisOptions: {
						xIsSeries: true
					}
				});
			}

		});

	})(jQuery);

</script>
