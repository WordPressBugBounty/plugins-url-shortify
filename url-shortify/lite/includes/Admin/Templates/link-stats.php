<?php

use KaizenCoders\URL_Shortify\Admin\Controllers\ClicksController;
use KaizenCoders\URL_Shortify\Admin\Controllers\StatsController;
use KaizenCoders\URL_Shortify\Admin\StatsRenderer;
use KaizenCoders\URL_Shortify\Common\Utils;
use KaizenCoders\URL_Shortify\Helper;

$page_refresh_url = Utils::get_current_page_refresh_url();

// Resolved through the same gate the controller uses, so the pills, the table's
// data attributes and the figures cannot describe a period this plan may not ask
// for. Clamping only the custom range here let a free site request all time
// through the query string.
$time_filter = StatsController::sanitize_time_filter( Helper::get_data( $_GET, 'time_filter', '' ) );

$periods = [
	'today'        => __( 'Today', 'url-shortify' ),
	'last_7_days'  => __( '7 days', 'url-shortify' ),
	'last_30_days' => __( '30 days', 'url-shortify' ),
	'last_60_days' => __( '2 months', 'url-shortify' ),
	'all_time'     => __( 'All time', 'url-shortify' ),
];

// The longer windows are a PRO capability, so free sites see two.
if ( ! US()->is_pro() ) {
	$periods = array_intersect_key( $periods, array_flip( [ 'today', 'last_7_days' ] ) );
}

$buttons = [];

foreach ( $periods as $filter => $label ) {
	$buttons[ $filter ] = [
		'label'  => $label,
		'url'    => Utils::get_stats_filter_url( [ 'time_filter' => $filter ] ),
		'class'  => $filter === $time_filter ? 'active' : 'inactive',
		'filter' => $filter,
	];
}

$short_link = esc_attr( Helper::get_data( $data, 'short_url', '' ) );
$link_id    = Helper::get_data( $data, 'id', '' );
$export_url = Helper::get_link_action_url( $link_id, 'export' );

$clicks_data = $data['reports']['clicks'];

$click_data_for_graph = $data['click_data_for_graph'];
$chart_data           = Helper::get_data( $data, 'chart_data', [] );

$has_chart_data = ! empty( $chart_data )
	&& ! empty( Helper::get_data( $chart_data, 'dates', [] ) )
	&& array_sum( array_map( 'intval', Helper::get_data( $chart_data, 'total_series', [] ) ) ) > 0;

$has_heatmap_data = ! empty( $chart_data )
	&& ! empty( Helper::get_data( $chart_data, 'heatmap_series', [] ) )
	&& ! empty( Helper::get_data( $chart_data, 'has_clicks_data', false ) );

$last_updated_on = Helper::get_data( $data, 'last_updated_on', time() );
$elapsed_time    = Utils::get_elapsed_time( $last_updated_on );

$total_clicks = 0;

if ( ! empty( $click_data_for_graph ) ) {
	$total_clicks = array_sum( array_map( 'intval', array_values( $click_data_for_graph ) ) );
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

$columns = ClicksController::get_table_columns( true );

$click_history = new ClicksController();
$click_history->set_columns( $columns );

/*
 * The overview is PRO. Free sites still get the redesigned layout and the
 * breakdowns they have always had; the panels built on the new metrics show
 * what they contain behind a veil rather than disappearing.
 */
$is_pro   = US()->is_pro();
$overview = Helper::get_data( $data, 'overview', [] );

/*
 * Visitor breakdowns come from PRO. Without it the filters behind them have no
 * handler at all, so these panels have nothing to draw - they show what they
 * would contain instead. A site that has switched promotions off gets the
 * panel omitted rather than an advert.
 */
$show_promo = US()->can_show_premium_promotion();

/**
 * Open a card that is either live or locked behind the upgrade veil.
 *
 * @param bool $unlocked
 *
 * @return void
 */
$kc_us_open_card = function ( $unlocked ) {
	printf( '<div class="kc-us-st-card%s">', $unlocked ? '' : ' kc-us-st-locked' );
};

/**
 * Close it, adding the veil when the panel is locked.
 *
 * @param bool   $unlocked
 * @param string $title
 * @param string $note
 *
 * @return void
 */
$kc_us_close_card = function ( $unlocked, $title, $note ) {
	if ( ! $unlocked ) {
		StatsRenderer::locked_veil( $title, $note );
	}

	echo '</div>';
};

$period   = Helper::get_data( $overview, 'period', [] );
$kpis     = Helper::get_data( $overview, 'kpis', [] );
$insights = Helper::get_data( $overview, 'insights', [] );
$channels = Helper::get_data( $overview, 'channels', [] );
$peak     = Helper::get_data( $overview, 'peak', [] );
$summary  = Helper::get_data( $overview, 'current', [] );

$period_label = Helper::get_data( $buttons, $time_filter, [] );
$period_label = Helper::get_data( $period_label, 'label', __( 'this period', 'url-shortify' ) );

$last_click = Helper::get_data( $summary, 'last_click', '' );

// The same icons the click log and the standings use, so a browser looks the
// same wherever it is named on the screen.
$device_rows   = StatsRenderer::rows_from_map( Helper::get_data( $data, 'device_info', [] ), 6, [ Utils::class, 'get_device_icon_url' ] );
$browser_rows  = StatsRenderer::rows_from_map( Helper::get_data( $data, 'browser_info', [] ), 6, [ Utils::class, 'get_browser_icon_url' ] );
$platform_rows = StatsRenderer::rows_from_map( Helper::get_data( $data, 'os_info', [] ), 6, [ Utils::class, 'get_platform_icon_url' ] );

// Referrer URLs are long and mostly chrome; the host is the part that identifies
// the source, and collapsing to it merges the many paths of one site.
$referrer_rows = [];
$referrer_map  = [];

foreach ( (array) Helper::get_data( $data, 'referrers_info', [] ) as $referrer => $count ) {
	$host = wp_parse_url( (string) $referrer, PHP_URL_HOST );

	if ( empty( $host ) ) {
		$host = (string) $referrer;
	}

	$host = preg_replace( '/^www\./i', '', $host );

	/*
	 * Clicks with no referrer are bucketed under a label rather than a host.
	 * They belong in the channels card, where they are counted as direct; in a
	 * list of "sites sending people here" they are both wrong and, being the
	 * largest bucket on most links, the loudest thing on it.
	 */
	if ( '' === $host || false === strpos( $host, '.' ) ) {
		continue;
	}

	$referrer_map[ $host ] = Helper::get_data( $referrer_map, $host, 0 ) + (int) $count;
}

$referrer_rows = StatsRenderer::rows_from_map( $referrer_map, 8 );

$country_rows = [];

foreach ( (array) Helper::get_data( $data, 'country_info', [] ) as $country ) {
	$country_rows[] = [
		'label' => Helper::get_data( $country, 'name', '' ),
		'value' => (int) Helper::get_data( $country, 'total', 0 ),
		'share' => Helper::get_data( $country, 'percentage', null ),
		'icon'  => Helper::get_data( $country, 'flag_url', '' ),
	];
}

?>

<div class="wrap">
    <div class="kc-us-st font-sans">

        <div class="kc-us-st-head">
            <div class="kc-us-st-head__main">
                <p class="kc-us-st-eyebrow"><?php esc_html_e( 'Link statistics', 'url-shortify' ); ?></p>

                <h1 class="kc-us-st-title">
                    <img class="h-6 w-6 rounded"
                         src="<?php echo esc_url( $data['icon_url'] ); ?>"
                         alt=""
                         title="<?php echo esc_attr( $data['url'] ); ?>" />
                    <a href="<?php echo esc_url( $data['url'] ); ?>" target="_blank" rel="noopener noreferrer">
                        <?php echo esc_html( stripslashes( $data['name'] ) ); ?>
                    </a>
                </h1>

                <div class="kc-us-st-sub">
                    <?php
                    echo Helper::create_copy_short_link_html( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        $short_link,
                        $data['id'],
                        '<code>' . esc_html( preg_replace( '#^https?://#', '', $short_link ) ) . '</code>'
                    );
                    ?>

                    <?php if ( ! empty( $last_click ) ) : ?>
                        <span>
                            <?php
                            printf(
                                /* translators: %s: human readable time difference, e.g. "2 hours". */
                                esc_html__( 'Last click %s ago', 'url-shortify' ),
                                esc_html( human_time_diff( strtotime( $last_click ), time() ) )
                            );
                            ?>
                        </span>
                    <?php endif; ?>

                    <span><?php
                        /* get_elapsed_time() returns "2 Hours ago" or "Just Now", so it supplies its own tense. */
                        echo esc_html( sprintf( __( 'Updated %s', 'url-shortify' ), $elapsed_time ) );
                    ?></span>
                </div>
            </div>
        </div>

        <?php if ( $is_pro && ! empty( $kpis ) ) : ?>
            <?php StatsRenderer::kpis( $kpis ); ?>

            <?php if ( empty( $period['bounded'] ) ) : ?>
                <p class="-mt-2 mb-5 text-sm text-gray-500">
                    <?php esc_html_e( 'Showing all time. Pick a date range above to compare against the period before it.', 'url-shortify' ); ?>
                </p>
            <?php endif; ?>

            <?php StatsRenderer::insights( $insights ); ?>
        <?php elseif ( ! $is_pro ) : ?>
            <div class="kc-us-st-card kc-us-st-locked">
                <?php StatsRenderer::locked_veil(
                    __( 'Headline figures and trends are a PRO feature', 'url-shortify' ),
                    __( 'See clicks, unique clicks, visitors and repeat rate, each against the previous period, so you can tell whether a link is growing or fading.', 'url-shortify' )
                ); ?>
                <div class="kc-us-st-locked__ghost p-5">
                    <div class="kc-us-st-kpis">
                        <?php foreach ( [ __( 'Clicks', 'url-shortify' ), __( 'Unique clicks', 'url-shortify' ), __( 'Visitors', 'url-shortify' ), __( 'Repeat rate', 'url-shortify' ) ] as $ghost_label ) : ?>
                            <div class="kc-us-st-kpi">
                                <span class="kc-us-st-kpi__label"><?php echo esc_html( $ghost_label ); ?></span>
                                <span class="kc-us-st-kpi__value">&mdash;</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="kc-us-st-card">
            <div class="kc-us-st-card__head">
                <div>
                    <h2 class="kc-us-st-card__title"><?php esc_html_e( 'Clicks over time', 'url-shortify' ); ?></h2>
                    <span class="kc-us-st-card__note">
                        <span id="kc-us-total-clicks"><?php
                            printf(
                                /* translators: %s: formatted number of clicks. */
                                esc_html__( '%s clicks in this period', 'url-shortify' ),
                                esc_html( number_format_i18n( $total_clicks ) )
                            );
                        ?></span>
                    </span>

                    <?php
                    /*
                     * One link on its own has nothing to compare against, so this hands
                     * the link to Smart Reports with it already selected rather than
                     * repeating the comparison chart here.
                     */
                    ?>
                    <?php if ( $is_pro && ! empty( $link_id ) && US()->access->can( 'manage_reports' ) ) : ?>
                        <span class="kc-us-st-card__note">
                            <a class="text-indigo-600 hover:text-indigo-700"
                               href="<?php echo esc_url( add_query_arg( [
                                   'page'   => 'us_smart_reports',
                                   'view'   => 'new',
                                   'entity' => 'link',
                                   'range'  => 'last_30_days',
                                   'metric' => 'total',
                                   'ids'    => [ absint( $link_id ) ],
                               ], admin_url( 'admin.php' ) ) ); ?>">
                                <?php esc_html_e( 'Compare with other links', 'url-shortify' ); ?>
                            </a>
                        </span>
                    <?php endif; ?>
                </div>

                <div id="kc-us-clicks-filter-controls" class="flex flex-wrap items-center gap-2">
                    <div class="kc-us-st-periods">
                        <?php foreach ( $buttons as $key => $button ) : ?>
                            <button type="button"
                                    class="kc-us-filter-pill <?php echo 'active' === $button['class'] ? 'is-active' : ''; ?>"
                                    data-filter="<?php echo esc_attr( $button['filter'] ); ?>">
                                <?php echo esc_html( $button['label'] ); ?>
                            </button>
                        <?php endforeach; ?>

                        <?php if ( $is_pro ) : ?>
                            <button type="button"
                                    class="kc-us-filter-pill <?php echo 'custom' === $time_filter ? 'is-active' : ''; ?>"
                                    data-filter="custom">
                                <span class="dashicons dashicons-calendar-alt" style="width:14px;height:14px;font-size:14px;vertical-align:middle;margin-right:3px;" aria-hidden="true"></span><?php esc_html_e( 'Custom', 'url-shortify' ); ?>
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if ( $is_pro ) : ?>
                        <div id="kc-us-clicks-custom-control" class="<?php echo ( 'custom' === $time_filter ) ? '' : 'hidden'; ?> inline-flex flex-wrap items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5">
                            <input type="text"
                                   id="kc-us-start-date"
                                   class="kc-us-date-picker w-28 rounded-md border border-gray-200 bg-white px-2 py-1 text-sm text-gray-700"
                                   placeholder="<?php esc_attr_e( 'Start date', 'url-shortify' ); ?>"
                                   value="<?php echo esc_attr( $current_start_date ); ?>" />
                            <span class="text-xs font-medium text-gray-400">&rarr;</span>
                            <input type="text"
                                   id="kc-us-end-date"
                                   class="kc-us-date-picker w-28 rounded-md border border-gray-200 bg-white px-2 py-1 text-sm text-gray-700"
                                   placeholder="<?php esc_attr_e( 'End date', 'url-shortify' ); ?>"
                                   value="<?php echo esc_attr( $current_end_date ); ?>" />
                            <button type="button"
                                    id="kc-us-clicks-custom-apply"
                                    class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1 text-sm font-medium text-white hover:bg-indigo-700"
                                    title="<?php esc_attr_e( 'Apply custom date range', 'url-shortify' ); ?>">
                                <?php esc_html_e( 'Apply', 'url-shortify' ); ?>
                            </button>
                        </div>
                    <?php endif; ?>

                    <a href="<?php echo esc_url( $page_refresh_url ); ?>"
                       id="kc-us-clicks-refresh"
                       class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white p-2 text-gray-400 hover:bg-gray-50 hover:text-gray-600"
                       title="<?php esc_attr_e( 'Refresh', 'url-shortify' ); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M10.2 3.28c3.53 0 6.43 2.61 6.92 6h2.08l-3.5 4l-3.5-4h2.32a4.439 4.439 0 0 0-4.32-3.45c-1.45 0-2.73.71-3.54 1.78L4.95 5.66a6.965 6.965 0 0 1 5.25-2.38zm-.4 13.44c-3.52 0-6.43-2.61-6.92-6H.8l3.5-4c1.17 1.33 2.33 2.67 3.5 4H5.48a4.439 4.439 0 0 0 4.32 3.45c1.45 0 2.73-.71 3.54-1.78l1.71 1.95a6.95 6.95 0 0 1-5.25 2.38z" fill="currentColor"/></svg>
                    </a>
                </div>
            </div>

            <div class="kc-us-st-card__body">
                <?php if ( $has_chart_data ) : ?>
                    <div id="spline-area-chart" class="h-[260px] w-full"></div>
                <?php else : ?>
                    <?php StatsRenderer::empty_state(
                        __( 'Once this link is clicked, the trend will appear here.', 'url-shortify' ),
                        __( 'No clicks in this period', 'url-shortify' )
                    ); ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="kc-us-st-grid kc-us-st-grid--2">
            <div class="kc-us-st-card <?php echo $is_pro ? '' : 'kc-us-st-locked'; ?>">
                <?php StatsRenderer::card_head(
                    __( 'Where the clicks come from', 'url-shortify' ),
                    __( 'Grouped by the kind of source, not the individual site.', 'url-shortify' )
                ); ?>
                <div class="kc-us-st-card__body <?php echo $is_pro ? '' : 'kc-us-st-locked__ghost'; ?>">
                    <?php
                    if ( $is_pro ) {
                        StatsRenderer::bars( $channels, [ 'empty' => __( 'No clicks in this period.', 'url-shortify' ) ] );
                    } else {
                        StatsRenderer::bars( [
                            [ 'label' => __( 'Search', 'url-shortify' ), 'value' => 72, 'share' => null ],
                            [ 'label' => __( 'Social', 'url-shortify' ), 'value' => 48, 'share' => null ],
                            [ 'label' => __( 'Direct & apps', 'url-shortify' ), 'value' => 30, 'share' => null ],
                        ] );
                    }
                    ?>
                </div>
                <?php if ( ! $is_pro ) {
                    StatsRenderer::locked_veil(
                        __( 'Traffic channels are a PRO feature', 'url-shortify' ),
                        __( 'Know whether a link is carried by search, social, email or direct sharing, so you can put effort where it already works.', 'url-shortify' )
                    );
                } ?>
            </div>

            <div class="kc-us-st-card <?php echo $is_pro ? '' : 'kc-us-st-locked'; ?>">
                <?php StatsRenderer::card_head(
                    __( 'When your audience clicks', 'url-shortify' ),
                    ! empty( $peak['best_label'] )
                        ? sprintf( __( 'Busiest: %s, in your site timezone.', 'url-shortify' ), $peak['best_label'] )
                        : __( 'By weekday and hour, in your site timezone.', 'url-shortify' )
                ); ?>
                <div class="kc-us-st-card__body <?php echo $is_pro ? '' : 'kc-us-st-locked__ghost'; ?>">
                    <?php
                    if ( $is_pro ) {
                        StatsRenderer::hours_grid( $peak );
                    } else {
                        StatsRenderer::empty_state( __( 'Mon to Sun, hour by hour.', 'url-shortify' ) );
                    }
                    ?>
                </div>
                <?php if ( ! $is_pro ) {
                    StatsRenderer::locked_veil(
                        __( 'Peak times are a PRO feature', 'url-shortify' ),
                        __( 'See the weekday and hour your audience is most active, and time your next share for it.', 'url-shortify' )
                    );
                } ?>
            </div>
        </div>

        <?php if ( $is_pro || $show_promo ) : ?>
        <div class="kc-us-st-grid kc-us-st-grid--2">
            <?php $kc_us_open_card( $is_pro ); ?>
                <?php StatsRenderer::card_head( __( 'Top locations', 'url-shortify' ), __( 'Countries these clicks came from.', 'url-shortify' ) ); ?>
                <div class="kc-us-st-card__body <?php echo $is_pro ? '' : 'kc-us-st-locked__ghost'; ?>">
                    <?php StatsRenderer::bars(
                        $is_pro ? $country_rows : StatsRenderer::sample_rows( 'locations' ),
                        [ 'empty' => __( 'No locations recorded in this period.', 'url-shortify' ) ]
                    ); ?>
                </div>
            <?php $kc_us_close_card(
                $is_pro,
                __( 'Locations are a PRO feature', 'url-shortify' ),
                __( 'See which countries your clicks come from, so you know who you are actually reaching.', 'url-shortify' )
            ); ?>

            <?php $kc_us_open_card( $is_pro ); ?>
                <?php StatsRenderer::card_head( __( 'Top referrers', 'url-shortify' ), __( 'The sites sending people to this link.', 'url-shortify' ) ); ?>
                <div class="kc-us-st-card__body <?php echo $is_pro ? '' : 'kc-us-st-locked__ghost'; ?>">
                    <?php StatsRenderer::bars(
                        $is_pro ? $referrer_rows : StatsRenderer::sample_rows( 'referrers' ),
                        [ 'empty' => __( 'No referrers recorded in this period.', 'url-shortify' ) ]
                    ); ?>
                </div>
            <?php $kc_us_close_card(
                $is_pro,
                __( 'Referrers are a PRO feature', 'url-shortify' ),
                __( 'See exactly which sites send people to this link, and which ones are worth more of your time.', 'url-shortify' )
            ); ?>
        </div>
        <?php endif; ?>

        <?php if ( $is_pro || $show_promo ) : ?>
        <div class="kc-us-st-grid kc-us-st-grid--3">
            <?php
            $kc_us_tech_panels = [
                'devices' => [
                    'title' => __( 'Devices', 'url-shortify' ),
                    'rows'  => $device_rows,
                    'empty' => __( 'No devices recorded in this period.', 'url-shortify' ),
                    'lock'  => __( 'Devices are a PRO feature', 'url-shortify' ),
                    'note'  => __( 'See the split between desktop, mobile and tablet, so you know what your destination page has to cope with.', 'url-shortify' ),
                ],
                'browsers' => [
                    'title' => __( 'Browsers', 'url-shortify' ),
                    'rows'  => $browser_rows,
                    'empty' => __( 'No browsers recorded in this period.', 'url-shortify' ),
                    'lock'  => __( 'Browsers are a PRO feature', 'url-shortify' ),
                    'note'  => __( 'See which browsers your visitors use, and which ones are worth testing against.', 'url-shortify' ),
                ],
                'platforms' => [
                    'title' => __( 'Platforms', 'url-shortify' ),
                    'rows'  => $platform_rows,
                    'empty' => __( 'No platforms recorded in this period.', 'url-shortify' ),
                    'lock'  => __( 'Platforms are a PRO feature', 'url-shortify' ),
                    'note'  => __( 'See the operating systems behind your clicks, from Windows and macOS to iOS and Android.', 'url-shortify' ),
                ],
            ];

            foreach ( $kc_us_tech_panels as $kc_us_panel_key => $kc_us_panel ) :
                $kc_us_open_card( $is_pro );
                StatsRenderer::card_head( $kc_us_panel['title'] );
                ?>
                <div class="kc-us-st-card__body <?php echo $is_pro ? '' : 'kc-us-st-locked__ghost'; ?>">
                    <?php StatsRenderer::bars(
                        $is_pro ? $kc_us_panel['rows'] : StatsRenderer::sample_rows( $kc_us_panel_key ),
                        [ 'empty' => $kc_us_panel['empty'] ]
                    ); ?>
                </div>
                <?php
                $kc_us_close_card( $is_pro, $kc_us_panel['lock'], $kc_us_panel['note'] );
            endforeach;
            ?>
        </div>
        <?php endif; ?>

        <div class="kc-us-st-card">
            <?php StatsRenderer::card_head(
                __( 'Activity over the past year', 'url-shortify' ),
                __( 'Each square is a day. Darker means busier. Always the last year, whichever period is selected above.', 'url-shortify' )
            ); ?>
            <div class="kc-us-st-card__body">
                <?php if ( $has_heatmap_data ) : ?>
                    <div class="kc-us-heatmap-chart-wrapper w-full">
                        <div id="activity-heatmap" class="w-full"></div>
                        <div id="heatmap-month-row" class="kc-us-heatmap-month-row" aria-hidden="true"></div>
                    </div>
                <?php else : ?>
                    <?php StatsRenderer::empty_state( __( 'Once this link is visited, its daily rhythm will appear here.', 'url-shortify' ) ); ?>
                <?php endif; ?>
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

        <div class="kc-us-st-card kc-us-st-table">
            <?php
            $kc_us_clicks_export = $is_pro
                ? StatsRenderer::export_action( $export_url, __( 'Export Clicks Data', 'url-shortify' ) )
                : '';

            StatsRenderer::card_head(
                __( 'Click log', 'url-shortify' ),
                __( 'Every recorded click, newest first.', 'url-shortify' ),
                $kc_us_clicks_export
            );
            ?>

            <div class="kc-us-st-card__body kc-us-st-card__body--flush">
                <table id="clicks-data"
                       class="display kc-us-clicks-table"
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
                    <tbody></tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<script type="text/javascript">
	window.us_chart_data = <?php echo wp_json_encode( $chart_data ); ?>;
</script>
