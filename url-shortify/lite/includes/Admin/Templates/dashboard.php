<?php

use KaizenCoders\URL_Shortify\Admin\Controllers\ClicksController;
use KaizenCoders\URL_Shortify\Common\Utils;
use KaizenCoders\URL_Shortify\Helper;

$page_refresh_url = Utils::get_current_page_refresh_url();

$export_url = Helper::get_action_url( null, 'main', 'export' );


$last_updated_on = Helper::get_data( $data, 'last_updated_on', time() );

$elapsed_time = Utils::get_elapsed_time( $last_updated_on );

$show_kpis            = Helper::get_data( $data, 'show_kpis', false );
$new_link_url         = Helper::get_data( $data, 'new_link_url', '' );
$new_group_url        = Helper::get_data( $data, 'new_group_url', '' );
$click_data_for_graph = Helper::get_data( $data, 'click_data_for_graph', [] );
$has_clicks_data      = Helper::get_data( $data, 'has_clicks_data', false );
if ( ! $has_clicks_data && ! empty( $click_data_for_graph ) ) {
    $has_clicks_data = array_sum( array_map( 'intval', array_values( $click_data_for_graph ) ) ) > 0;
}

$show_landing_page = Helper::get_request_data( 'landing', false );

if ( $show_kpis && ! $show_landing_page ) {
    $kpis = Helper::get_data( $data, 'kpis', [] );

    $reports     = Helper::get_data( $data, 'reports', [] );
    $clicks_data = Helper::get_data( $reports, 'clicks', [] );

    $labels = $values = '';
    if ( ! empty( $click_data_for_graph ) ) {
        $labels = json_encode( array_keys( $click_data_for_graph ) );

        $clicks = array_values( $click_data_for_graph );

        $total_clicks = array_sum( $clicks );

        $values = json_encode( $clicks );

    }

    $columns = [
            'ip'         => [ 'title' => __( 'IP', 'url-shortify' ) ],
            'uri'        => [ 'title' => __( 'URI', 'url-shortify' ) ],
            'link'       => [ 'title' => __( 'Link', 'url-shortify' ) ],
            'host'       => [ 'title' => __( 'Host', 'url-shortify' ) ],
            'referrer'   => [ 'title' => __( 'Referrer', 'url-shortify' ) ],
            'clicked_on' => [ 'title' => __( 'Clicked On', 'url-shortify' ) ],
            'info'       => [ 'title' => __( 'Info', 'url-shortify' ) ],
    ];

    $click_history = new ClicksController();
    $click_history->set_columns( $columns );

    ?>

    <div class="wrap" id="">
        <header class="mx-auto">
            <div class="pb-5 border-b border-gray-300 md:flex md:items-center md:justify-between">
                <div class="flex-1 min-w-0">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:leading-9 sm:truncate">
                        <?php
                        _e( 'Dashboard', 'url-shortify' ); ?>
                    </h2>
                </div>
                <!-- Theme toggle is injected by url-shortify-admin.js (fixed bottom-right) -->

                <div class="flex mt-4 md:mt-0 md:ml-4">
					<span class="rounded-md shadow-sm">
						<button type="button"
                                class="w-full text-white bg-green-500 kc-us-primary-button hover:bg-green-400"
                                title="<?php echo sprintf( /* translators: %s: Human-readable elapsed time since last update */ __( 'Last Updated On: %s', 'url-shortify' ), $elapsed_time ); ?>">
							<a href="<?php
                            echo $page_refresh_url; ?>" class="text-white hover:text-white"><?php
                                _e( 'Refresh', 'url-shortify' ); ?></a>
						</button>
					</span>
                    <span class="ml-3 rounded-md shadow-sm">
						<div id="kc-us-create-button" class="relative inline-block text-left">
							<div>
							  <span class="rounded-md shadow-sm">
								<button type="button" class="w-full kc-us-primary-button">
								  <?php
                                  _e( 'Create', 'url-shortify' ); ?>
								  <svg class="w-5 h-5 ml-2 -mr-1" fill="currentColor" viewBox="0 0 20 20">
									<path fill-rule="evenodd"
                                          d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                          clip-rule="evenodd"/>
								  </svg>
								</button>
							  </span>
							</div>
							<div id="kc-us-create-dropdown"
                                 class="absolute right-0 hidden w-56 mt-2 origin-top-right rounded-md shadow-lg">
							  <div class="bg-white rounded-md shadow-xs">
								<div class="py-1">
								  <a href="<?php
                                  echo $new_link_url; ?>"
                                     class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900"><?php
                                      _e( 'New Link', 'url-shortify' ); ?></a>
								  <a href="<?php
                                  echo $new_group_url; ?>"
                                     class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900"><?php
                                      _e( 'New Group', 'url-shortify' ); ?></a>
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
                <?php
                foreach ( $kpis as $kpi ) { ?>
                    <?php
                    if ( ! empty( $kpi['url'] ) ) {
                        ?>
                        <a href="<?php
                        echo $kpi['url']; ?>" target="_blank"> <?php
                    } ?>
                    <div class="overflow-hidden bg-white rounded-lg shadow">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 p-3 bg-indigo-500 rounded-md">
                                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24"
                                         stroke="currentColor">
                                        <?php
                                        echo $kpi['icon']; ?>
                                    </svg>
                                </div>
                                <div class="flex-1 w-0 ml-5">
                                    <dl>
                                        <dt class="text-sm font-medium leading-5 text-gray-500 truncate">
                                            <?php
                                            echo $kpi['title']; ?>
                                        </dt>
                                        <dd class="flex items-baseline">
                                            <div class="text-2xl font-semibold leading-8 text-gray-900">
                                                <?php
                                                echo $kpi['count']; ?>
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
                        </a> <?php
                    } ?>
                <?php
                } ?>
            </div>
        </div>
        <!-- KPI END -->

        <!-- Click History Report -->
        <div class="kc-us-dashboard-visuals flex flex-col gap-6 mt-6">
            <section class="kc-us-chart-card kc-us-spline-card kc-us-heatmap-card bg-white relative overflow-hidden rounded-3xl border rounded-xl border-gray-200 px-6 py-8 shadow-[0_20px_45px_rgba(15,23,42,0.1)]">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h2 class="text-2xl font-semibold leading-tight text-slate-900"><?php
                            _e( 'Total vs Unique Links', 'url-shortify' ); ?></h2>
                    </div>
                </div>
                <div class="kc-us-heatmap-chart-wrapper mt-2 w-full">
                    <div id="spline-area-chart" class="mt-6 h-[220px] w-full"></div>
                </div>
            </section>

            <section class="kc-us-chart-card kc-us-heatmap-card bg-white relative overflow-hidden rounded-3xl border rounded-xl border-gray-200 px-6 py-8 shadow-[0_20px_45px_rgba(15,23,42,0.1)]">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900"><?php
                            _e( 'Link Activity Intensity', 'url-shortify' ); ?></h2>
                    </div>
                </div>
                <?php
                if ( $has_clicks_data ) { ?>
                    <div class="kc-us-heatmap-chart-wrapper mt-2 w-full">
                        <div id="activity-heatmap" class="w-full"></div>
                        <div id="heatmap-month-row" class="kc-us-heatmap-month-row" aria-hidden="true"></div>
                    </div>
                <?php
                } else { ?>
                    <div class="kc-us-heatmap-empty-state mt-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 py-12 text-center">
                        <p class="text-base font-medium text-slate-700">
                            <?php
                            _e( 'No clicks data available. Once your link is visited, analytics will appear here', 'url-shortify' ); ?>
                        </p>
                    </div>
                <?php
                } ?>
            </section>
        </div>

        <!-- Country & Referrer Info -->
        <div class="mt-6">
            <div class="grid gap-4 md:grid-cols-2 sm:grid-cols-1">
                <!-- Country Info -->
                <div class="overflow-hidden rounded-lg">

                    <div class="mb-4">
                        <span class="text-xl font-medium leading-6 text-gray-900"><?php
                            _e( 'Top Locations', 'url-shortify' ); ?></span>
                    </div>
                    <div class="bg-white border-2">
                        <?php
                        if ( US()->is_pro() ) {
                            do_action( 'kc_us_render_country_info', $data );
                        } else {
                            if ( US()->can_show_premium_promotion() ) {
                                ?>

                                <div class="w-full h-64 p-10 bg-green-50">
                                    <div class="">
                                        <div class="flex items-center justify-center w-16 h-16 mx-auto bg-green-100 rounded-full">
                                            <svg class="w-12 h-12 text-green-600" fill="none" stroke-linecap="round"
                                                 stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24"
                                                 stroke="currentColor">
                                                <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                            </svg>
                                        </div>
                                        <div class="mt-3 text-center sm:mt-5">
                                            <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-headline">
                                                <?php echo sprintf( /* translators: %s: URL for the upgrade/pricing page */ __( '<a href="%s">Upgrade Now</a>', 'url-shortify' ), US()->get_landing_page_url( true ) ); ?>
                                            </h3>
                                            <div class="mt-2">
                                                <p class="text-sm leading-5 text-gray-500">
                                                    <?php
                                                    _e( 'Get insights about top locations from where people are clicking on your links.', 'url-shortify' ); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                            }
                        } ?>
                    </div>
                </div>

                <!-- Referrer Info -->
                <div class="overflow-hidden rounded-lg h-px-400">
                    <div class="mb-4">
                        <span class="text-xl font-medium leading-6 text-gray-900"><?php
                            _e( 'Referrers', 'url-shortify' ); ?></span>
                    </div>
                    <div class="bg-white border-2" id="">
                        <?php
                        if ( US()->is_pro() ) {
                            do_action( 'kc_us_render_referrer_info', $data );
                        } else {
                            if ( US()->can_show_premium_promotion() ) {
                                ?>
                                <div class="w-full h-64 p-10 bg-green-50">
                                    <div class="">
                                        <div class="flex items-center justify-center w-16 h-16 mx-auto bg-green-100 rounded-full">
                                            <svg class="w-12 h-12 text-green-600" fill="none" stroke-linecap="round"
                                                 stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24"
                                                 stroke="currentColor">
                                                <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                            </svg>
                                        </div>
                                        <div class="mt-3 text-center sm:mt-5">
                                            <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-headline">
                                                <?php
                                                echo sprintf( __( '<a href="%s">Upgrade Now</a>', 'url-shortify' ), US()->get_landing_page_url( true ) ); ?>
                                            </h3>
                                            <div class="mt-2">
                                                <p class="text-sm leading-5 text-gray-500">
                                                    <?php
                                                    _e( 'Know who are your top referrers.', 'url-shortify' ); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                            }
                        } ?>
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
                        <span class="text-xl font-medium leading-6 text-gray-900"><?php
                            _e( 'Top Devices', 'url-shortify' ); ?></span>
                    </div>
                    <div class="bg-white border-2 h-full px-4 py-3 flex flex-col justify-start">
                        <?php
                        $device_info = Helper::get_data( $data, 'device_info', [] );
                        if ( ! empty( $device_info ) ) {
                            ?>
                            <div id="kc-us-device-pie" class="w-full h-64"></div>
                            <?php
                        } else {
                            echo '<p class="text-sm text-gray-500 p-4">' . esc_html__( 'Not enough data to show device distribution yet.', 'url-shortify' ) . '</p>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Browser Info -->
                <div class="overflow-hidden rounded-lg h-px-400">
                    <div class="mb-4">
                        <span class="text-xl font-medium leading-6 text-gray-900"><?php
                            _e( 'Top Browsers', 'url-shortify' ); ?></span>
                    </div>
                    <div class="bg-white border-2 h-full px-4 py-3 flex flex-col justify-start">
                        <?php
                        $browser_info = Helper::get_data( $data, 'browser_info', [] );
                        if ( ! empty( $browser_info ) ) {
                            ?>
                            <div id="kc-us-browser-pie" class="w-full h-64"></div>
                            <?php
                        } else {
                            echo '<p class="text-sm text-gray-500 p-4">' . esc_html__( 'Not enough data to show browser distribution yet.', 'url-shortify' ) . '</p>';
                        }
                        ?>
                    </div>
                </div>


                <!-- OS Info -->
                <div class="overflow-hidden rounded-lg h-px-400">
                    <div class="mb-4">
                        <span class="text-xl font-medium leading-6 text-gray-900"><?php
                            _e( 'Top Platforms', 'url-shortify' ); ?></span>
                    </div>
                    <div class="bg-white border-2 h-full px-4 py-3 flex flex-col justify-start">
                        <?php
                        $os_info = Helper::get_data( $data, 'os_info', [] );
                        if ( ! empty( $os_info ) ) {
                            ?>
                            <div id="kc-us-os-pie" class="w-full h-64"></div>
                            <?php
                        } else {
                            echo '<p class="text-sm text-gray-500 p-4">' . esc_html__( 'Not enough data to show platform distribution yet.', 'url-shortify' ) . '</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Click History -->
        <div class="flex w-full mt-6">
            <div class="w-11/12">
                <span class="text-xl font-medium leading-6 text-gray-900"><?php
                    _e( 'Clicks Details', 'url-shortify' ); ?></span>
            </div>
            <?php
            if ( US()->is_pro() ) { ?>
                <div class="w-1/12 py-2 pl-8">
                    <a href="<?php
                    echo $export_url; ?>" class="text-white hover:text-white">
                        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                             stroke-width="2" viewBox="0 0 24 24"
                             class="w-8 h-8 text-indigo-600 hover:text-indigo-500 active:text-indigo-600">
                            <path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </a>
                </div>
            <?php
            } ?>
        </div>
        <div class="flex-grow pt-6 pb-8 mx-auto mt-4 bg-white sm:px-4">

            <div>
                <table id="clicks-data" class="display" data-server-side="true" style="width:100%">
                    <thead>
                    <?php
                    $click_history->render_header(); ?>
                    </thead>
                    <tbody>
                    <?php
                    foreach ( $clicks_data as $click ) {
                        $click_history->render_row( $click );
                    }
                    ?>
                    </tbody>
                    <tfoot>
                    <?php
                    $click_history->render_footer(); ?>
                    </tfoot>
                </table>
            </div>
        </div>


    </div>


<?php
} else {
    include_once 'landing.php';
} ?>

<?php
// Chart datasets for pie charts.
$device_info  = Helper::get_data( $data, 'device_info', [] );
$browser_info = Helper::get_data( $data, 'browser_info', [] );
$os_info      = Helper::get_data( $data, 'os_info', [] );

$device_labels  = array_keys( $device_info );
$device_values  = array_map( 'intval', array_values( $device_info ) );
$browser_labels = array_keys( $browser_info );
$browser_values = array_map( 'intval', array_values( $browser_info ) );
$os_labels      = array_keys( $os_info );
$os_values      = array_map( 'intval', array_values( $os_info ) );
?>

<script type="text/javascript">
	(function ($) {
		$(document).ready(function () {
			if (typeof ApexCharts === 'undefined') {
				console.error('ApexCharts is not available on this page.');
				return;
			}

			const pieConfigs = [
				{
					el: '#kc-us-device-pie',
					labels: <?php echo wp_json_encode( $device_labels ); ?>,
					series: <?php echo wp_json_encode( $device_values ); ?>,
					title: '<?php echo esc_js( __( 'Devices', 'url-shortify' ) ); ?>',
					colors: ['#6366f1', '#22c55e', '#f59e0b', '#ef4444', '#14b8a6', '#a855f7', '#06b6d4', '#f97316']
				},
				{
					el: '#kc-us-browser-pie',
					labels: <?php echo wp_json_encode( $browser_labels ); ?>,
					series: <?php echo wp_json_encode( $browser_values ); ?>,
					title: '<?php echo esc_js( __( 'Browsers', 'url-shortify' ) ); ?>',
					colors: ['#0ea5e9', '#10b981', '#eab308', '#f43f5e', '#8b5cf6', '#06b6d4', '#f97316', '#94a3b8']
				},
				{
					el: '#kc-us-os-pie',
					labels: <?php echo wp_json_encode( $os_labels ); ?>,
					series: <?php echo wp_json_encode( $os_values ); ?>,
					title: '<?php echo esc_js( __( 'Platforms', 'url-shortify' ) ); ?>',
					colors: ['#f43f5e', '#a855f7', '#3b82f6', '#f59e0b', '#22c55e', '#06b6d4', '#e11d48', '#10b981']
				},
			];

			pieConfigs.forEach(function (cfg) {
				const target = document.querySelector(cfg.el);
				if ( !target || !Array.isArray(cfg.labels) || !cfg.labels.length || !Array.isArray(cfg.series) || !cfg.series.length) {
					return;
				}

				const options = {
					series: cfg.series,
					labels: cfg.labels,
					chart: {
						type: 'pie',
						height: 240,
					},
					colors: cfg.colors,
					legend: {
						position: 'bottom',
						fontSize: '13px',
						markers: {
							width: 12,
							height: 12,
							radius: 12
						},
						itemMargin: {
							vertical: 6,
							horizontal: 12
						},
						offsetY: 8
					},
					tooltip: {
						y: {
							formatter: function (val) {
								return val + ' <?php echo esc_js( __( 'clicks', 'url-shortify' ) ); ?>';
							}
						}
					}
				};

				target.innerHTML = '';
				const chart = new ApexCharts(target, options);
				chart.render();
			});
		});
	})(jQuery);

</script>
