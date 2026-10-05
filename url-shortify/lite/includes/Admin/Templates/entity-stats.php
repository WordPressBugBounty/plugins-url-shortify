<?php
/**
 * Statistics for a set of links - a group or a tag.
 *
 * The two screens were byte-identical apart from the word "group" or "tag", so
 * they share this and differ only in $kc_us_entity.
 *
 * @var array  $data          Prepared by Group/TagStatsController.
 * @var string $kc_us_entity  'group' or 'tag'.
 *
 * @package KaizenCoders\URL_Shortify
 * @since   2.7.0
 */

use KaizenCoders\URL_Shortify\Admin\Controllers\ClicksController;
use KaizenCoders\URL_Shortify\Admin\Controllers\StatsController;
use KaizenCoders\URL_Shortify\Admin\StatsRenderer;
use KaizenCoders\URL_Shortify\Common\Utils;
use KaizenCoders\URL_Shortify\Helper;

$kc_us_entity = isset( $kc_us_entity ) && 'tag' === $kc_us_entity ? 'tag' : 'group';
$is_tag       = ( 'tag' === $kc_us_entity );

$page_refresh_url = Utils::get_current_page_refresh_url();

// Resolved through the same gate the controller uses, so the pills, the table's
// data attributes and the figures cannot describe a period this plan may not ask
// for. Clamping only the custom range here let a free site request all time
// through the query string.
$time_filter = StatsController::sanitize_time_filter( Helper::get_data( $_GET, 'time_filter', '' ) );

$current_start_date = ( 'custom' === $time_filter ) ? Helper::get_data( $_GET, 'start_date', '' ) : '';
$current_end_date   = ( 'custom' === $time_filter ) ? Helper::get_data( $_GET, 'end_date', '' ) : '';

$periods = [
	'today'        => __( 'Today', 'url-shortify' ),
	'last_7_days'  => __( '7 days', 'url-shortify' ),
	'last_30_days' => __( '30 days', 'url-shortify' ),
	'last_60_days' => __( '2 months', 'url-shortify' ),
	'all_time'     => __( 'All time', 'url-shortify' ),
];

if ( ! US()->is_pro() ) {
	$periods = array_intersect_key( $periods, array_flip( [ 'today', 'last_7_days' ] ) );
}

$buttons = [];

foreach ( $periods as $filter => $label ) {
	$buttons[ $filter ] = [
		'label' => $label,
		'url'   => Utils::get_stats_filter_url( [ 'time_filter' => $filter ] ),
		'class' => $filter === $time_filter ? 'active' : 'inactive',
	];
}

$entity_id = Helper::get_data( $data, 'id', '' );

$export_url = $is_tag
	? Helper::get_tag_action_url( $entity_id, 'export' )
	: Helper::get_group_action_url( $entity_id, 'export' );

$export_links_url = $is_tag
	? Helper::get_tag_action_url( $entity_id, 'export_links' )
	: Helper::get_group_action_url( $entity_id, 'export_links' );

$reports     = Helper::get_data( $data, 'reports', [] );
$clicks_data = Helper::get_data( $reports, 'clicks', [] );

$click_data_for_graph = Helper::get_data( $data, 'click_data_for_graph', [] );
$chart_data           = Helper::get_data( $data, 'chart_data', [] );

$has_chart_data = ! empty( $chart_data )
	&& ! empty( Helper::get_data( $chart_data, 'dates', [] ) )
	&& array_sum( array_map( 'intval', Helper::get_data( $chart_data, 'total_series', [] ) ) ) > 0;

$has_heatmap_data = ! empty( $chart_data )
	&& ! empty( Helper::get_data( $chart_data, 'heatmap_series', [] ) )
	&& ! empty( Helper::get_data( $chart_data, 'has_clicks_data', false ) );

$links = Helper::get_data( $data, 'links', [] );

$entity_link_ids = [];

foreach ( (array) $links as $link ) {
	$entity_link_ids[] = absint( Helper::get_data( $link, 'id', 0 ) );
}

$entity_link_ids = array_filter( $entity_link_ids );

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

$total_clicks = 0;

if ( ! empty( $click_data_for_graph ) ) {
	$total_clicks = array_sum( array_map( 'intval', array_values( $click_data_for_graph ) ) );
}

$last_updated_on = Helper::get_data( $data, 'last_updated_on', time() );
$elapsed_time    = Utils::get_elapsed_time( $last_updated_on );

$click_history = new ClicksController();
$click_history->set_columns( ClicksController::get_table_columns() );

$is_pro     = US()->is_pro();
$show_promo = US()->can_show_premium_promotion();

$overview = Helper::get_data( $data, 'overview', [] );
// The overview carries the period for PRO; free sites get it on its own, since
// the standings below need to know whether a comparison window exists.
$period   = Helper::get_data( $overview, 'period', Helper::get_data( $data, 'period', [] ) );
$kpis     = Helper::get_data( $overview, 'kpis', [] );
$insights = Helper::get_data( $overview, 'insights', [] );
$channels = Helper::get_data( $overview, 'channels', [] );
$peak     = Helper::get_data( $overview, 'peak', [] );
$members  = Helper::get_data( $data, 'members', [] );

// The same icons the click log and the standings use, so a browser looks the
// same wherever it is named on the screen.
$device_rows   = StatsRenderer::rows_from_map( Helper::get_data( $data, 'device_info', [] ), 6, [ Utils::class, 'get_device_icon_url' ] );
$browser_rows  = StatsRenderer::rows_from_map( Helper::get_data( $data, 'browser_info', [] ), 6, [ Utils::class, 'get_browser_icon_url' ] );
$platform_rows = StatsRenderer::rows_from_map( Helper::get_data( $data, 'os_info', [] ), 6, [ Utils::class, 'get_platform_icon_url' ] );

// Collapse referrer URLs to the host; the path is noise and one site arrives
// under many of them.
$referrer_map = [];

foreach ( (array) Helper::get_data( $data, 'referrers_info', [] ) as $referrer => $count ) {
	$host = wp_parse_url( (string) $referrer, PHP_URL_HOST );

	if ( empty( $host ) ) {
		$host = (string) $referrer;
	}

	$host = preg_replace( '/^www\./i', '', $host );

	// Clicks with no referrer are bucketed under a label, not a host. They are
	// reported as direct in the channels card, where they belong.
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

?>

<div class="wrap">
    <div class="kc-us-st font-sans">

        <div class="kc-us-st-head">
            <div class="kc-us-st-head__main">
                <p class="kc-us-st-eyebrow">
                    <?php echo esc_html( $is_tag ? __( 'Tag statistics', 'url-shortify' ) : __( 'Group statistics', 'url-shortify' ) ); ?>
                </p>

                <h1 class="kc-us-st-title">
                    <?php echo esc_html( stripslashes( Helper::get_data( $data, 'name', '' ) ) ); ?>
                </h1>

                <div class="kc-us-st-sub">
                    <span>
                        <?php
                        printf(
                            /* translators: %s: number of links. */
                            esc_html( _n( '%s link', '%s links', count( $entity_link_ids ), 'url-shortify' ) ),
                            esc_html( number_format_i18n( count( $entity_link_ids ) ) )
                        );
                        ?>
                    </span>

                    <?php if ( ! empty( $members['dormant'] ) ) : ?>
                        <span>
                            <?php
                            printf(
                                /* translators: %s: number of links with no clicks. */
                                esc_html__( '%s with no clicks', 'url-shortify' ),
                                esc_html( number_format_i18n( $members['dormant'] ) )
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

            <div class="kc-us-st-head__aside">
                <?php
                if ( $is_pro ) {
                    echo StatsRenderer::export_action( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the helper.
                        $export_links_url,
                        __( 'Export Links', 'url-shortify' )
                    );
                }
                ?>
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
        <?php elseif ( ! $is_pro && $show_promo ) : ?>
            <div class="kc-us-st-card kc-us-st-locked">
                <?php StatsRenderer::locked_veil(
                    __( 'Headline figures and trends are a PRO feature', 'url-shortify' ),
                    $is_tag
                        ? __( 'See clicks, unique clicks, visitors and repeat rate for everything carrying this tag, each against the previous period.', 'url-shortify' )
                        : __( 'See clicks, unique clicks, visitors and repeat rate for this whole group, each against the previous period.', 'url-shortify' )
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
                        <?php
                        printf(
                            /* translators: %s: formatted number of clicks. */
                            esc_html__( '%s clicks in this period', 'url-shortify' ),
                            esc_html( number_format_i18n( $total_clicks ) )
                        );
                        ?>
                    </span>

                    <?php
                    /*
                     * Comparison toggle. A link rather than a JS control, so the state
                     * lives in the URL and can be bookmarked, shared and reloaded.
                     */
                    if ( $is_pro ) :
                        $kc_us_comparing   = ! empty( Helper::get_data( $_GET, 'compare', '' ) );
                        $kc_us_compare_url = $kc_us_comparing
                            ? remove_query_arg( [ 'compare', 'compare_metric' ] )
                            : add_query_arg( 'compare', 'links' );
                        $kc_us_compare     = Helper::get_data( $chart_data, 'compare', [] );
                        $kc_us_truncated   = Helper::get_data( $kc_us_compare, 'truncated', [] );
                        ?>
                        <span class="kc-us-st-card__note">
                            <a class="kc-us-compare-toggle text-indigo-600 hover:text-indigo-700"
                               href="<?php echo esc_url( $kc_us_compare_url ); ?>"
                               aria-pressed="<?php echo $kc_us_comparing ? 'true' : 'false'; ?>">
                                <?php echo $kc_us_comparing ? esc_html__( 'Showing a line per link — switch back to the total', 'url-shortify' ) : esc_html__( 'Compare links', 'url-shortify' ); ?>
                            </a>

                            <?php if ( $kc_us_comparing && ! empty( $kc_us_truncated['of'] ) && $kc_us_truncated['of'] > $kc_us_truncated['shown'] ) : ?>
                                <span class="text-gray-400">
                                    <?php
                                    printf(
                                        /* translators: 1: number of links charted, 2: total number of links */
                                        esc_html__( '(top %1$d of %2$d by clicks)', 'url-shortify' ),
                                        (int) $kc_us_truncated['shown'],
                                        (int) $kc_us_truncated['of']
                                    );
                                    ?>
                                </span>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="kc-us-st-periods">
                        <?php foreach ( $buttons as $button ) : ?>
                            <a href="<?php echo esc_url( $button['url'] ); ?>"
                               class="<?php echo 'active' === $button['class'] ? 'is-active' : ''; ?>">
                                <?php echo esc_html( $button['label'] ); ?>
                            </a>
                        <?php endforeach; ?>

                        <?php if ( $is_pro ) : ?>
                            <button type="button"
                                    id="kc-us-range-pill"
                                    class="<?php echo 'custom' === $time_filter ? 'is-active' : ''; ?>">
                                <span class="dashicons dashicons-calendar-alt" style="width:14px;height:14px;font-size:14px;vertical-align:middle;margin-right:3px;" aria-hidden="true"></span><?php esc_html_e( 'Custom', 'url-shortify' ); ?>
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if ( $is_pro ) : ?>
                        <div id="kc-us-range-control" class="<?php echo ( 'custom' === $time_filter ) ? '' : 'hidden'; ?> inline-flex flex-wrap items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5">
                            <input type="text"
                                   id="kc-us-range-start"
                                   class="kc-us-date-picker w-28 rounded-md border border-gray-200 bg-white px-2 py-1 text-sm text-gray-700"
                                   placeholder="<?php esc_attr_e( 'Start date', 'url-shortify' ); ?>"
                                   value="<?php echo esc_attr( $current_start_date ); ?>" />
                            <span class="text-xs font-medium text-gray-400">&rarr;</span>
                            <input type="text"
                                   id="kc-us-range-end"
                                   class="kc-us-date-picker w-28 rounded-md border border-gray-200 bg-white px-2 py-1 text-sm text-gray-700"
                                   placeholder="<?php esc_attr_e( 'End date', 'url-shortify' ); ?>"
                                   value="<?php echo esc_attr( $current_end_date ); ?>" />
                            <button type="button"
                                    id="kc-us-range-apply"
                                    class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1 text-sm font-medium text-white hover:bg-indigo-700"
                                    data-error="<?php esc_attr_e( 'Enter both a start date and an end date.', 'url-shortify' ); ?>">
                                <?php esc_html_e( 'Apply', 'url-shortify' ); ?>
                            </button>
                        </div>
                    <?php endif; ?>

                    <a href="<?php echo esc_url( $page_refresh_url ); ?>"
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
                        __( 'Once these links are clicked, the trend will appear here.', 'url-shortify' ),
                        __( 'No clicks in this period', 'url-shortify' )
                    ); ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="kc-us-st-card kc-us-st-table">
            <?php StatsRenderer::card_head(
                $is_tag ? __( 'Links with this tag', 'url-shortify' ) : __( 'Links in this group', 'url-shortify' ),
                __( 'Ranked by clicks in this period. The ones at the bottom are the ones to look at.', 'url-shortify' )
            ); ?>
            <div class="kc-us-st-card__body kc-us-st-card__body--flush">
                <?php
                /*
                 * Which links are in here and how much each is used is the plain
                 * answer this screen owes everyone. The audience behind those
                 * clicks, and the change against the previous period, are PRO.
                 */
                StatsRenderer::member_table( $members, $is_pro && ! empty( $period['bounded'] ), $is_pro );
                ?>
            </div>

            <?php if ( ! $is_pro && $show_promo && ! empty( $members['rows'] ) ) : ?>
                <p class="kc-us-st-card__upsell">
                    <?php esc_html_e( 'PRO adds each link\'s share of this traffic, the devices, browsers and platforms behind it, and how it has moved since the previous period.', 'url-shortify' ); ?>
                    <a href="<?php echo esc_url( US()->get_landing_page_url( true ) ); ?>"><?php esc_html_e( 'Upgrade to PRO', 'url-shortify' ); ?></a>
                </p>
            <?php endif; ?>
        </div>

        <?php if ( $is_pro || $show_promo ) : ?>
        <div class="kc-us-st-grid kc-us-st-grid--2">
            <?php $kc_us_open_card( $is_pro ); ?>
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
                            [ 'label' => __( 'Search', 'url-shortify' ), 'value' => 72 ],
                            [ 'label' => __( 'Social', 'url-shortify' ), 'value' => 48 ],
                            [ 'label' => __( 'Direct & apps', 'url-shortify' ), 'value' => 30 ],
                        ] );
                    }
                    ?>
                </div>
            <?php $kc_us_close_card(
                $is_pro,
                __( 'Traffic channels are a PRO feature', 'url-shortify' ),
                __( 'Know whether these links are carried by search, social, email or direct sharing, so you can put effort where it already works.', 'url-shortify' )
            ); ?>

            <?php $kc_us_open_card( $is_pro ); ?>
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
            <?php $kc_us_close_card(
                $is_pro,
                __( 'Peak times are a PRO feature', 'url-shortify' ),
                __( 'See the weekday and hour your audience is most active, and time your next share for it.', 'url-shortify' )
            ); ?>
        </div>
        <?php endif; ?>

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
                <?php StatsRenderer::card_head( __( 'Top referrers', 'url-shortify' ), __( 'The sites sending people to these links.', 'url-shortify' ) ); ?>
                <div class="kc-us-st-card__body <?php echo $is_pro ? '' : 'kc-us-st-locked__ghost'; ?>">
                    <?php StatsRenderer::bars(
                        $is_pro ? $referrer_rows : StatsRenderer::sample_rows( 'referrers' ),
                        [ 'empty' => __( 'No referrers recorded in this period.', 'url-shortify' ) ]
                    ); ?>
                </div>
            <?php $kc_us_close_card(
                $is_pro,
                __( 'Referrers are a PRO feature', 'url-shortify' ),
                __( 'See exactly which sites send people here, and which ones are worth more of your time.', 'url-shortify' )
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
                    'note'  => __( 'See the split between desktop, mobile and tablet, so you know what your destination pages have to cope with.', 'url-shortify' ),
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
                    <?php StatsRenderer::empty_state( __( 'Once these links are visited, their daily rhythm will appear here.', 'url-shortify' ) ); ?>
                <?php endif; ?>
            </div>
        </div>

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
                       data-link-ids="<?php echo esc_attr( implode( ',', $entity_link_ids ) ); ?>"
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
