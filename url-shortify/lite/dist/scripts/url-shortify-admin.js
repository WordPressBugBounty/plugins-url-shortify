// Apply dark mode immediately (before DOM ready) to prevent FOUC.
(function () {
    var mode = 'system';
    try {
        mode = localStorage.getItem('kc_us_theme') || 'system';
    } catch (e) {}
    var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    var isDark = (mode === 'dark') || (mode === 'system' && prefersDark);
    if (isDark) {
        document.documentElement.classList.add('kc-us-dark', 'kc-us-dark-active');
    }
}());

(function ($) {
    'use strict';

    $(document).ready(function () {
        // Link bulk action.
        if ($('.group_select').length) {
            var group_select = $('.group_select')[0].outerHTML;
            $(".kc-us-items-lists .bulkactions #bulk-action-selector-top").after(group_select);
            $('.group_select').hide();
        }
        if ($('.tag_select').length) {
            var tag_select = $('.tag_select')[0].outerHTML;
            $(".kc-us-items-lists .bulkactions #bulk-action-selector-top").after(tag_select);
            $('.tag_select').hide();
        }

        // Datepixker.
        if ($('.kc-us-date-picker').length) {
            var bulk_expiry = $('.kc-us-date-picker')[0].outerHTML;
            $(".kc-us-items-lists .bulkactions #bulk-action-selector-top").after(bulk_expiry);
            $('.bulkactions .kc-us-date-picker').hide();
        }

        // Bulk action additional dropdown/datepicker show/hide.
        $("#bulk-action-selector-top").change(function () {
            var selectedAction = $('option:selected', this).attr('value');
            if (selectedAction == 'bulk_group_move' || selectedAction == 'bulk_group_add') {
                $('.group_select').eq(1).show();
                $('.tag_select').hide();
                $('.bulkactions .kc-us-date-picker').hide();
            } else if (selectedAction == 'bulk_add_tag' || selectedAction == 'bulk_change_tag_to') {
                $('.tag_select').eq(1).show();
                $('.group_select').hide();
                $('.bulkactions .kc-us-date-picker').hide();
            } else if (selectedAction == 'bulk_add_expiry') {
                $('.group_select').hide();
                $('.tag_select').hide();
                $('.bulkactions .kc-us-date-picker').show();
            } else {
                $('.group_select').hide();
                $('.tag_select').hide();
                $('.bulkactions .kc-us-date-picker').hide();
            }
        });

        // When we click outside, close the dropdown
        $(document).on("click", function (event) {
            var $trigger = $("#kc-us-create-button");
            if ($trigger !== event.target && !$trigger.has(event.target).length) {
                $("#kc-us-create-dropdown").hide();
            }
        });

        // Toggle Dropdown
        $('#kc-us-create-button').click(function () {
            $('#kc-us-create-dropdown').toggle();
        });

        // Clicks Reports Datatable.
        if ($('#clicks-data').get(0)) {
            var $clicksTable = $('#clicks-data');
            var serverSide = $clicksTable.data('server-side') === true || $clicksTable.data('server-side') === 'true';
            var clicksDataTable = null;
            var $filterPills = $('.kc-us-filter-pill');
            var $customApply = $('#kc-us-clicks-custom-apply');
            var $customStart = $('#kc-us-start-date');
            var $customEnd = $('#kc-us-end-date');
            var $refreshButton = $('#kc-us-clicks-refresh');
            var $totalClicks = $('#kc-us-total-clicks');
            var $customControl = $('#kc-us-clicks-custom-control');
            var currentTimeFilter = $clicksTable.data('time-filter') || 'last_7_days';
            var currentStartDate = ($clicksTable.data('start-date') || '').toString();
            var currentEndDate = ($clicksTable.data('end-date') || '').toString();
            var currentLinkId = $clicksTable.data('link-id');
            var currentLinkIds = $clicksTable.data('link-ids');
            var currentDays = $clicksTable.data('days');

            function getDaysForFilter(timeFilter) {
                switch (timeFilter) {
                    case 'today':
                        return 1;
                    case 'last_7_days':
                        return 7;
                    case 'last_30_days':
                        return 30;
                    case 'last_60_days':
                        return 60;
                    case 'all_time':
                        return 0;
                    case 'custom':
                        return 0;
                    default:
                        return currentDays;
                }
            }

            function setTableState() {
                $clicksTable.attr('data-time-filter', currentTimeFilter);
                $clicksTable.attr('data-start-date', currentStartDate);
                $clicksTable.attr('data-end-date', currentEndDate);
                $clicksTable.attr('data-days', currentDays);
            }

            function syncFilterStyles() {
                $filterPills.each(function () {
                    var isActive = $(this).data('filter') === currentTimeFilter;
                    $(this)
                        .toggleClass('bg-white text-slate-900 shadow-sm', isActive)
                        .toggleClass('text-slate-500 hover:text-slate-700 hover:bg-white/60', !isActive);
                });

                if ($customControl.length) {
                    $customControl.toggleClass('hidden', currentTimeFilter !== 'custom');
                }
            }

            function syncRefreshHref() {
                if (!$refreshButton.length) {
                    return;
                }

                var url = new URL(window.location.href);
                url.searchParams.set('refresh', '1');
                url.searchParams.set('time_filter', currentTimeFilter);

                if (currentTimeFilter === 'custom') {
                    if (currentStartDate) {
                        url.searchParams.set('start_date', currentStartDate);
                    } else {
                        url.searchParams.delete('start_date');
                    }

                    if (currentEndDate) {
                        url.searchParams.set('end_date', currentEndDate);
                    } else {
                        url.searchParams.delete('end_date');
                    }
                } else {
                    url.searchParams.delete('start_date');
                    url.searchParams.delete('end_date');
                }

                $refreshButton.attr('href', url.toString());
            }

            function syncBrowserUrl() {
                var url = new URL(window.location.href);
                url.searchParams.set('time_filter', currentTimeFilter);

                if (currentTimeFilter === 'custom') {
                    if (currentStartDate) {
                        url.searchParams.set('start_date', currentStartDate);
                    } else {
                        url.searchParams.delete('start_date');
                    }

                    if (currentEndDate) {
                        url.searchParams.set('end_date', currentEndDate);
                    } else {
                        url.searchParams.delete('end_date');
                    }
                } else {
                    url.searchParams.delete('start_date');
                    url.searchParams.delete('end_date');
                }

                url.searchParams.delete('refresh');
                window.history.replaceState({}, '', url.toString());
            }

            function updateChartSummary(chartData, totalClicks) {
                if ($totalClicks.length) {
                    $totalClicks.text((totalClicks || 0).toLocaleString() + ' clicks');
                }

                if (window.usSplineChart instanceof ApexCharts && chartData && Array.isArray(chartData.dates)) {
                    window.usSplineChart.updateSeries([
                        { name: 'Total Clicks', data: chartData.total_series || [] },
                        { name: 'Unique Clicks', data: chartData.unique_series || [] }
                    ], true);
                    window.usSplineChart.updateOptions({
                        xaxis: {
                            categories: chartData.dates || []
                        }
                    }, false, true, false);
                }

                if (window.usHeatmapChart instanceof ApexCharts && chartData && Array.isArray(chartData.heatmap_series)) {
                    if (Array.isArray(chartData.heatmap_color_ranges) && chartData.heatmap_color_ranges.length) {
                        heatmapColorRanges = chartData.heatmap_color_ranges;
                        window.us_chart_data = window.us_chart_data || {};
                        window.us_chart_data.heatmap_color_ranges = chartData.heatmap_color_ranges;
                    }

                    window.usHeatmapChart.updateSeries(chartData.heatmap_series || [], true);
                    window.usHeatmapChart.updateOptions({
                        xaxis: {
                            categories: chartData.heatmap_week_starts || []
                        },
                        yaxis: {
                            categories: chartData.heatmap_day_labels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
                        },
                        plotOptions: {
                            heatmap: {
                                colorScale: {
                                    ranges: usBuildHeatmapRanges(usGetIsDarkMode())
                                }
                            }
                        }
                    }, false, true, false);

                    var heatmapContainer = document.querySelector('#activity-heatmap');
                    var heatmapMonthContainer = document.querySelector('#heatmap-month-row');
                    if (heatmapMonthContainer) {
                        if (Array.isArray(chartData.heatmap_month_labels) && chartData.heatmap_month_labels.length) {
                            heatmapMonthContainer.style.gridTemplateColumns = 'repeat(' + chartData.heatmap_month_labels.length + ', minmax(0, 1fr))';
                            heatmapMonthContainer.innerHTML = chartData.heatmap_month_labels.map(function (label) {
                                var classes = 'kc-us-heatmap-month';
                                if (label) {
                                    classes += ' kc-us-heatmap-month--label';
                                }
                                return '<span class="' + classes + '">' + (label ? label : '&nbsp;') + '</span>';
                            }).join('');
                        } else {
                            heatmapMonthContainer.innerHTML = '';
                        }
                    }

                    if (heatmapContainer) {
                        usMarkFutureHeatmapCells(heatmapContainer);
                        usApplyHeatmapCellTheme(heatmapContainer, usGetIsDarkMode());
                    }
                }
            }

            function applyStatsResponse(response) {
                if (!response || !response.success || !response.data) {
                    return;
                }

                window.us_chart_data = response.data.chart_data || window.us_chart_data;
                updateChartSummary(response.data.chart_data || {}, response.data.clicks_total || 0);
                syncRefreshHref();
                syncBrowserUrl();

                if (clicksDataTable) {
                    clicksDataTable.ajax.reload(null, true);
                }
            }

            function fetchStats() {
                if (!currentLinkId) {
                    return;
                }

                $.ajax({
                    url: usParams.ajaxurl,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'us_handle_request',
                        cmd: 'get_link_stats_chart_data',
                        security: usParams.security,
                        link_id: currentLinkId,
                        time_filter: currentTimeFilter,
                        start_date: currentStartDate,
                        end_date: currentEndDate
                    },
                    success: applyStatsResponse,
                    error: function (xhr, status, err) {
                        console.error('Link stats ajax error:', status, err);
                    }
                });
            }

            function setFilter(timeFilter, startDate, endDate) {
                currentTimeFilter = timeFilter;
                currentStartDate = startDate || '';
                currentEndDate = endDate || '';
                currentDays = getDaysForFilter(timeFilter);

                if ($customStart.length) {
                    $customStart.val(currentStartDate);
                }

                if ($customEnd.length) {
                    $customEnd.val(currentEndDate);
                }

                setTableState();
                syncFilterStyles();
                syncRefreshHref();
                syncBrowserUrl();
                fetchStats();
            }

            if ($filterPills.length) {
                $filterPills.on('click', function () {
                    var timeFilter = $(this).data('filter') || 'last_7_days';

                    if (timeFilter === 'custom') {
                        currentTimeFilter = 'custom';
                        syncFilterStyles();
                        syncRefreshHref();
                        syncBrowserUrl();
                        return;
                    }

                    setFilter(timeFilter, '', '');
                });
            }

            if ($customApply.length) {
                $customApply.on('click', function () {
                    var startDate = $customStart.length ? ($customStart.val() || '').trim() : '';
                    var endDate = $customEnd.length ? ($customEnd.val() || '').trim() : '';

                    if (!startDate || !endDate) {
                        alert('Please enter both a start date and an end date.');
                        return;
                    }

                    setFilter('custom', startDate, endDate);
                });
            }

            if ($customStart.length) {
                $customStart.on('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        $customApply.trigger('click');
                    }
                });
            }

            if ($customEnd.length) {
                $customEnd.on('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        $customApply.trigger('click');
                    }
                });
            }

            syncFilterStyles();
            setTableState();
            syncRefreshHref();

            if (serverSide) {
                clicksDataTable = $clicksTable.DataTable({
                    serverSide: true,
                    processing: true,
                    language: {
                        processing: "<div class='kc-us-loading-overlay'></div>"
                    },
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    pageLength: 10,
                    ajax: function (data, callback) {
                        var ajaxData = $.extend({}, data, {
                            action: 'us_handle_request',
                            cmd: 'get_dashboard_clicks_page',
                            security: usParams.security,
                        });

                        if ( currentLinkId ) {
                            ajaxData.link_id = currentLinkId;
                        }

                        if ( currentLinkIds ) {
                            ajaxData.link_ids = currentLinkIds;
                        }

                        if (typeof currentDays !== 'undefined' && currentDays !== null && currentDays !== '') {
                            ajaxData.days = currentDays;
                        }

                        if (currentTimeFilter) {
                            ajaxData.time_filter = currentTimeFilter;
                        }

                        if (currentTimeFilter === 'custom') {
                            ajaxData.start_date = currentStartDate;
                            ajaxData.end_date = currentEndDate;
                            ajaxData.days = 0;
                        }

                        $.ajax({
                            url: usParams.ajaxurl,
                            method: 'POST',
                            dataType: 'json',
                            data: ajaxData,
                            success: function (response) {
                                var payload = { draw: data.draw, recordsTotal: 0, recordsFiltered: 0, data: [] };
                                if (response && response.success && response.data) {
                                    payload.draw = response.data.draw || data.draw;
                                    payload.recordsTotal = response.data.recordsTotal || 0;
                                    payload.recordsFiltered = response.data.recordsFiltered || 0;
                                    payload.data = response.data.data || [];
                                }
                                callback(payload);
                            },
                            error: function (xhr, status, err) {
                                console.error('Clicks table ajax error:', status, err);
                                callback({ draw: data.draw, recordsTotal: 0, recordsFiltered: 0, data: [] });
                            }
                        });
                    },
                    order: [[5, 'desc']],
                    columns: [
                        { data: 0, orderable: false },
                        { data: 1, orderable: false },
                        { data: 2, orderable: true },
                        { data: 3, orderable: false },
                        { data: 4, orderable: false },
                        { data: 5, orderable: true },
                        { data: 6, orderable: false },
                    ],
                });
            } else {
                var sortIndex = $clicksTable.find("th[data-key='clicked_on']")[0] ? $clicksTable.find("th[data-key='clicked_on']")[0].cellIndex : 0;
                $clicksTable.DataTable({
                    order: [[sortIndex, 'desc']]
                });
            }
        }

        // Clicks Reports Datatable.
        if ($('#links-data').get(0)) {
            var sortIndex = $('#links-data').find("th[data-key='created_at']")[0].cellIndex;
            if ($('#links-data').get(0)) {
                $('#links-data').DataTable({
                    order: [[sortIndex, "desc"]]
                });
            }
        }

        // Groups Dropdown.
        if ($('.kc-us-groups').get(0)) {
            $('.kc-us-groups').select2({
                placeholder: 'Select Groups',
                allowClear: true,
                dropdownAutoWidth: true,
                width: 500,
                multiple: true
            });
        }
        
        // Tags Dropdown.
        if ($('.kc-us-tags').get(0)) {
            $('.kc-us-tags').select2({
                placeholder: 'Select Tags',
                allowClear: true,
                dropdownAutoWidth: true,
                width: 500,
                multiple: true
            });
        }

        // Tracking Pixels Dropdown.
        if ($('.kc-us-tracking-pixels').get(0)) {
            $('.kc-us-tracking-pixels').select2({
                placeholder: 'Select Tracking Pixels',
                allowClear: true,
                dropdownAutoWidth: true,
                width: 500,
                multiple: true
            });
        }

        // Datepicker format.
        $('.kc-us-date-picker').datepicker({
            dateFormat: 'yy-mm-dd'
        });

        // Social Share.
        $(".share-btn").click(function (e) {
            $('.networks-5').not($(this).next(".networks-5")).each(function () {
                $(this).removeClass("active");
                $(this).hide();
            });

            $(this).next(".networks-5").show();
            $(this).next(".networks-5").toggleClass("active");
        });

        /**
         * Get URM Presets Data.
         *
         * @since 1.5.12
         */
        $("#kc-us-utm-preset-dropdown").change(function (e) {
            e.preventDefault();

            var selectedUTMPresetID = $(this).val();
            var security = $('#kc-us-security').val();

            if (0 == selectedUTMPresetID) {
                $('#utm_id').val('');
                $('#utm_source').val('');
                $('#utm_campaign').val('');
                $('#utm_medium').val('');
                $('#utm_term').val('');
                $('#utm_content').val('');
                return;
            }

            $.ajax({
                type: "post",
                dataType: "json",
                context: this,
                url: ajaxurl,
                data: {
                    action: 'us_handle_request',
                    cmd: "get_utm_presets",
                    utm_preset_id: selectedUTMPresetID,
                    security: security,
                },
                success: function (response) {
                    if (response.status === "success") {
                        var utm_params = response.data;

                        if (utm_params.hasOwnProperty('utm_source')) {
                            $('#utm_id').val(utm_params.utm_id);
                            $('#utm_source').val(utm_params.utm_source);
                            $('#utm_campaign').val(utm_params.utm_campaign);
                            $('#utm_medium').val(utm_params.utm_medium);
                            $('#utm_term').val(utm_params.utm_term);
                            $('#utm_content').val(utm_params.utm_content);
                        }

                    } else {
                        var html = 'Something went wrong while creating short link';
                    }
                },

                error: function (err) {
                    var html = 'Something went wrong while creating short link';
                }
            });
        });

        // Select All Links banner (PRO only).
        var $banner = $('#kc-us-select-all-banner');
        if (typeof usParams !== 'undefined' && usParams.is_pro && $banner.length && $banner.data('total-links')) {
            var totalLinks = parseInt($banner.data('total-links'), 10);
            var $hiddenField = $('#kc-us-select-all-links');
            var $headerCheckbox = $('#cb-select-all-1');
            var selectAllActive = false;

            function getPageCheckboxCount() {
                return $('input[name="link_ids[]"]').length;
            }

            function showPageSelectedBanner() {
                var pageCount = getPageCheckboxCount();
                if (pageCount >= totalLinks) {
                    $banner.hide();
                    return;
                }
                selectAllActive = false;
                $hiddenField.val('0');
                $banner.html(
                    'All <strong>' + pageCount + '</strong> links on this page are selected. ' +
                    '<a href="#" id="kc-us-select-all-links-trigger" style="cursor:pointer;">Select all <strong>' + totalLinks + '</strong> links.</a>'
                ).show();
            }

            function showAllSelectedBanner() {
                selectAllActive = true;
                $hiddenField.val('1');
                $banner.html(
                    'All <strong>' + totalLinks + '</strong> links are selected. ' +
                    '<a href="#" id="kc-us-clear-selection-trigger" style="cursor:pointer;">Clear selection.</a>'
                ).show();
            }

            function resetSelectAll() {
                selectAllActive = false;
                $hiddenField.val('0');
                $banner.hide();
            }

            // Header checkbox change.
            $headerCheckbox.on('change', function () {
                if ($(this).prop('checked')) {
                    showPageSelectedBanner();
                } else {
                    resetSelectAll();
                }
            });

            // "Select all Y links" click.
            $(document).on('click', '#kc-us-select-all-links-trigger', function (e) {
                e.preventDefault();
                showAllSelectedBanner();
            });

            // "Clear selection" click.
            $(document).on('click', '#kc-us-clear-selection-trigger', function (e) {
                e.preventDefault();
                $headerCheckbox.prop('checked', false).trigger('change');
                $('input[name="link_ids[]"]').prop('checked', false);
                resetSelectAll();
            });

            // Individual checkbox unchecked resets all-links mode.
            $(document).on('change', 'input[name="link_ids[]"]', function () {
                if (!$(this).prop('checked') && selectAllActive) {
                    resetSelectAll();
                }
            });
        }

        // Bulk action confirmation for destructive actions.
        $('#doaction').on('click', function (e) {
            var selectedAction = $('#bulk-action-selector-top').val();
            var isDestructive = (selectedAction === 'bulk_delete' || selectedAction === 'bulk_reset');

            if (!isDestructive) {
                return true;
            }

            var selectAll = $('#kc-us-select-all-links').val();
            var actionLabel = (selectedAction === 'bulk_delete') ? 'delete' : 'reset statistics for';
            var message;

            if (selectAll === '1') {
                var $selectAllBanner = $('#kc-us-select-all-banner');
                var total = $selectAllBanner.length ? $selectAllBanner.data('total-links') : 'ALL';
                message = 'This will ' + actionLabel + ' ALL ' + total + ' link(s). Are you sure?';
            } else {
                var checkedCount = $('input[name="link_ids[]"]:checked').length;
                if (checkedCount === 0) {
                    return true;
                }
                message = 'Are you sure you want to ' + actionLabel + ' ' + checkedCount + ' selected link(s)?';
            }

            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });

        var redirectType = $('#kc-us-redirection-types-options').val();
        toggleTrackingPixel(redirectType);

        var dynamicRedirectType = $('#dynamic-redirect-type').val();
        toggleDynamicRedirect(dynamicRedirectType);

        var expireAfterRedirectSwitch = $("#expiration-after-redirect-switch:checked").length
        toggleRedirectAfterExpiration(expireAfterRedirectSwitch);
    });

    /**
     * Toggle Tracking Pixel dropdown.
     *
     * @param redirectType
     *
     * @since 1.8.9
     */
    function toggleTrackingPixel(redirectType) {
        $('#kc-us-tracking-pixel').hide();
        if ('pixel' === redirectType) {
            $('#kc-us-tracking-pixel').show();
        } else {
            $('#kc-us-tracking-pixel').hide();
        }
    }

    /**
     * Toggle Dynamic Redirect dropdown.
     *
     * @param redirectType
     *
     * @since 1.8.9
     */
    function toggleDynamicRedirect(redirectType) {
        $('#geo-redirect').hide();
        $('#technology-redirect').hide();
        $('#link-rotation').hide();
        $('#split-test').hide();
        $('#goal-link').hide();
        if ('geo' === redirectType) {
            $('#geo-redirect').show();
        } else if ('technology' === redirectType) {
            $('#technology-redirect').show();
        } else if ('link-rotation' === redirectType) {
            $('#link-rotation').show();
            $('#split-test').show();
            var checked = $("#split-test-switch:checked").length;
            toggleGoalLink(checked);
        }
    }

    /**
     * Show/Hide redirection URL.
     *
     * @param enable
     */
    function toggleRedirectAfterExpiration(enable) {
        if (enable) {
            $('#kc-us-expired-redirection-url').show();
        } else {
            $('#kc-us-expired-redirection-url').hide();
        }
    }

    /**
     * Toggle Goal link.
     *
     * @param splitTest
     *
     * @since 1.9.1
     */
    function toggleGoalLink(splitTest) {
        if (splitTest) {
            $('#goal-link').show();
        } else {
            $('#goal-link').hide();
        }
    }

    /* Show / Hide pixel dropdown */
    $('#kc-us-redirection-types').change(function () {
        var redirectType = $('#kc-us-redirection-types-options').val();
        toggleTrackingPixel(redirectType);
    });

    /* Show / Hide Goal Link */
    $('#split-test-switch').click(function() {
        var checked = $("#split-test-switch:checked").length;
        toggleGoalLink(checked);
    });

    /* Show / Hide Redirect URL */
    $('#expiration-after-redirect-switch').click(function() {
        let checked = $("#expiration-after-redirect-switch:checked").length;
        toggleRedirectAfterExpiration(checked);
    });

    /* Show / Hide Redirection type options */
    $('#dynamic-redirect-type').change(function () {
        var redirectType = $(this).val();
        toggleDynamicRedirect(redirectType);
    });

    /*  Add / Remove rows --- START */
    $(document).on('click', '.rowfy-addrow', function () {
        let rowfyable = $(this).closest('table');
        let lastRow = $('tbody tr:last', rowfyable).clone();
        lastRow = lastRow.removeClass('hidden');
        $('input', lastRow).val('');
        $('tbody', rowfyable).append(lastRow);

        let rowfyId = $(this).closest('.rowfy').attr('id');

        // Show delete action for all tr except first tr for the link rotation table.
        if('link-rotation-rowfy' === rowfyId) {
            var rowCount = $(this).closest('tbody').find('tr').length;
            $('tbody tr', rowfyable).find('.rowfy-deleterow').show();
            $(this).closest('tbody').find('input').not(':first').removeAttr('disabled');
            if (rowCount > 1) {
                $('tbody tr:first', rowfyable).find('.rowfy-deleterow').hide();
            }
        }
    });

    /*Delete row event*/
    $(document).on('click', '.rowfy-deleterow', function () {
        var rowCount = $(this).closest('tbody').find('tr').length;

        if (rowCount <= 1) {
            alert('Sorry...you can\'t delete this row.');
            return;
        }

        $(this).closest('tr').remove();
    });

    /*Initialize all rowfy tables*/
    $('.rowfy').each(function () {

        let rowfyId = $(this).attr('id');

        let isLinkRotation = false;
        if ('link-rotation-rowfy' === rowfyId) {
            isLinkRotation = true;
        }

        $('tbody', this).find('tr').each(function (index) {
            var actions = '<button type="button" class="rowfy-addrow text-xl text-indigo-600 mr-2"><span class="dashicons dashicons-plus-alt"></span><button type="button" class="rowfy-deleterow text-xl text-red-500"><span class="dashicons dashicons-trash"></span>';
            $(this).append('<td>' + actions + '</td>');

            // Hide delete action of first row if it's a link rotation.
            if (isLinkRotation && 0 == index) {
                let rowfyable = $(this).closest('table');
                $('tbody tr:first', rowfyable).find('.rowfy-deleterow').hide();
            }
        });

    });
    /*  Add/ Remove rows --- END */

    /* Calculate the total weight of a link rotation */
    $('.link-rotation-weight').change(function () {
        let totalWeights = 0;
        $('.link-rotation-weight').each(function () {
             let weight = $(this).val();
             totalWeights = parseInt(totalWeights) + parseInt(weight);
            if (totalWeights > 100) {
                alert('Total Weights of all links should be equal or less than 100%');
            }
        });
    });

    /* ========  themeSwitcher start ========= */

    /**
     * 3-state theme switcher: light / dark / system
     * Scoped to URL Shortify admin pages only via #wpbody-content.kc-us-dark
     */
    var kcUsTheme = {
        STORAGE_KEY: 'kc_us_theme',
        root: document.getElementById('wpbody-content'),
        labels: {
            light: 'Light',
            dark: 'Dark',
            system: 'System'
        },
        icons: {
            light: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>',
            dark: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>',
            system: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>'
        },

        get: function () {
            try {
                return localStorage.getItem(this.STORAGE_KEY) || 'system';
            } catch (e) {
                return 'system';
            }
        },

        set: function (mode) {
            try {
                localStorage.setItem(this.STORAGE_KEY, mode);
            } catch (e) {}
            this.apply(mode);
            this.updateToggle(mode);
        },

        apply: function (mode) {
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            var isDark = (mode === 'dark') || (mode === 'system' && prefersDark);
            if (isDark) {
                document.documentElement.classList.add('kc-us-dark', 'kc-us-dark-active');
                if (this.root) {
                    this.root.classList.add('kc-us-dark');
                }
                if (document.body) {
                    document.body.classList.add('kc-us-dark-active');
                }
            } else {
                document.documentElement.classList.remove('kc-us-dark', 'kc-us-dark-active');
                if (this.root) {
                    this.root.classList.remove('kc-us-dark');
                }
                if (document.body) {
                    document.body.classList.remove('kc-us-dark-active');
                }
            }

            document.dispatchEvent(new CustomEvent('kc-us-theme-changed', {
                detail: {
                    mode: mode,
                    isDark: isDark
                }
            }));
        },

        renderCurrent: function (mode) {
            return this.icons[mode] + '<span class="kc-us-theme-current-label">' + this.labels[mode] + '</span><span class="kc-us-theme-current-caret"></span>';
        },

        updateToggle: function (mode) {
            var currentBtn = document.querySelector('.kc-us-theme-current');
            if (currentBtn) {
                currentBtn.innerHTML = this.renderCurrent(mode);
            }

            var btns = document.querySelectorAll('.kc-us-theme-option');
            btns.forEach(function (btn) {
                if (btn.dataset.theme === mode) {
                    btn.classList.add('is-active');
                } else {
                    btn.classList.remove('is-active');
                }
            });
        },

        toggleMenu: function (force) {
            var toggle = document.getElementById('kc-us-theme-toggle');
            if (!toggle) { return; }
            var isOpen = toggle.classList.contains('is-open');
            var next = (typeof force === 'boolean') ? force : !isOpen;
            toggle.classList.toggle('is-open', next);
            var currentBtn = toggle.querySelector('.kc-us-theme-current');
            if (currentBtn) {
                currentBtn.setAttribute('aria-expanded', next ? 'true' : 'false');
            }
        },

        injectToggle: function () {
            if (document.getElementById('kc-us-theme-toggle')) { return; }
            var html = '<div id="kc-us-theme-toggle" class="kc-us-theme-toggle">' +
                '<button type="button" class="kc-us-theme-current" aria-haspopup="true" aria-expanded="false" title="Switch theme"></button>' +
                '<div class="kc-us-theme-menu" role="menu">' +
                    '<button type="button" class="kc-us-theme-option" data-theme="light" role="menuitem">' + this.icons.light + '<span>' + this.labels.light + '</span></button>' +
                    '<button type="button" class="kc-us-theme-option" data-theme="dark" role="menuitem">' + this.icons.dark + '<span>' + this.labels.dark + '</span></button>' +
                    '<button type="button" class="kc-us-theme-option" data-theme="system" role="menuitem">' + this.icons.system + '<span>' + this.labels.system + '</span></button>' +
                '</div>' +
            '</div>';
            document.body.insertAdjacentHTML('beforeend', html);

            var self = this;
            var currentBtn = document.querySelector('.kc-us-theme-current');
            if (currentBtn) {
                currentBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    self.toggleMenu();
                });
            }

            document.querySelectorAll('.kc-us-theme-option').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    self.set(btn.dataset.theme);
                    self.toggleMenu(false);
                });
            });

            document.addEventListener('click', function () {
                self.toggleMenu(false);
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    self.toggleMenu(false);
                }
            });
        },

        init: function () {
            var mode = this.get();
            this.apply(mode);
            this.injectToggle();
            this.updateToggle(mode);

            // React to OS-level preference changes when in system mode
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
                if (kcUsTheme.get() === 'system') {
                    kcUsTheme.apply('system');
                }
            });
        }
    };

    kcUsTheme.init();

    /* ========  themeSwitcher End ========= */

})(jQuery);

// Confirm Deletion
function confirmDelete() {
    return confirm('Are you sure you want to delete short link?');
}

// Confirm reseting of stats
function confirmReset() {
    return confirm('Are you sure you want to reset statistics of short link?');
}

/**
 * Toggle favorite link.
 *
 * @since 1.12.2
 */
jQuery(document).on('click', '.us-star-toggle', function() {
    var $this = jQuery(this);
    var linkId = $this.data('id');
    
    // Safety check: if usParams or security is missing, log an error
    if ( typeof usParams === 'undefined' || ! usParams.security ) {
        console.error('URL Shortify: Security nonce is missing. Please check script localization.');
        return;
    }

    jQuery.post(ajaxurl, {
        action: 'us_handle_request',
        cmd: 'toggle_favorite',
        link_id: linkId,
        security: usParams.security,
    }, function(response) {
        if (response.success) {
            $this.toggleClass('starred');
            var isStarred = $this.hasClass('starred');
            $this.html(isStarred ? '<span class="dashicons dashicons-star-filled"></span>' : '<span class="dashicons dashicons-star-empty"></span>');
            $this.attr('title', isStarred ? 'Remove from Favorites' : 'Add to Favorites');
        } else {
            alert(response.data.message);
        }
    });
});

function usUpdateLinkStatusToggle($button, status) {
    var isEnabled = parseInt(status, 10) === 1;
    var statusLabel = isEnabled ? 'Enabled' : 'Disabled';
    var statusTip = isEnabled ? 'Click to Disable' : 'Click to Enable';
    var trackBg = isEnabled
        ? '#6366f1'
        : '#64748b';
    var trackBorder = isEnabled
        ? 'rgba(99, 102, 241, 0.45)'
        : 'rgba(71, 85, 105, 0.45)';
    var thumbBg = isEnabled
        ? '#ffffff'
        : '#cbd5e1';
    var thumbBorder = isEnabled ? 'rgba(255, 255, 255, 0.40)' : 'rgba(148, 163, 184, 0.2)';
    var thumbTransform = isEnabled ? 'translateX(1.25rem)' : 'translateX(0)';
    var trackBoxShadow = isEnabled
        ? 'inset 0 1px 2px rgba(15, 23, 42, 0.10)'
        : 'inset 0 1px 2px rgba(15, 23, 42, 0.14)';
    var thumbBoxShadow = isEnabled
        ? '0 1px 2px rgba(15, 23, 42, 0.18)'
        : '0 1px 2px rgba(15, 23, 42, 0.16)';

    $button
        .attr('data-status', isEnabled ? '1' : '0')
        .attr('aria-checked', isEnabled ? 'true' : 'false')
        .attr('aria-label', statusLabel + '. ' + statusTip)
        .attr('title', statusTip)
        .find('.us-link-status-track')
        .css({
            backgroundColor: trackBg,
            borderColor: trackBorder,
            boxShadow: trackBoxShadow
        })
        .end()
        .find('.us-link-status-thumb')
        .css({
            backgroundColor: thumbBg,
            borderColor: thumbBorder,
            boxShadow: thumbBoxShadow,
            transform: thumbTransform
        })
        .end()
        .find('.us-link-status-label')
        .text(statusLabel);
}

jQuery(document).on('click', '.us-link-status-toggle', function (e) {
    e.preventDefault();

    var $button = jQuery(this);

    if ($button.data('busy')) {
        return;
    }

    if (typeof usParams === 'undefined' || !usParams.security) {
        console.error('URL Shortify: Security nonce is missing. Please check script localization.');
        return;
    }

    var ajaxUrl = (typeof usParams !== 'undefined' && usParams.ajaxurl) ? usParams.ajaxurl : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');

    if (!ajaxUrl) {
        console.error('URL Shortify: AJAX URL is missing.');
        return;
    }

    $button.data('busy', true).addClass('opacity-60 cursor-wait');

    jQuery.post(ajaxUrl, {
        action: 'us_handle_request',
        cmd: 'toggle_link_status',
        link_id: $button.data('link-id'),
        security: usParams.security,
    }, function (response) {
        if (response && response.success && response.data) {
            usUpdateLinkStatusToggle($button, response.data.status);
        } else {
            alert((response && response.data && response.data.message) ? response.data.message : 'Unable to update link status.');
        }
    }).fail(function () {
        alert('Unable to update link status. Please try again.');
    }).always(function () {
        $button.data('busy', false).removeClass('opacity-60 cursor-wait');
    });
});

jQuery(window).ready(function () {
});

window.usSplineChart = window.usSplineChart || null;
window.usHeatmapChart = window.usHeatmapChart || null;

// Wrap your code to ensure $ is recognized as jQuery
 jQuery(document).ready(function($) {
    'use strict';

    $('.us-link-status-toggle').each(function () {
        usUpdateLinkStatusToggle($(this), $(this).data('status'));
    });

    if ( typeof us_chart_data === 'undefined' ) {
        return;
    }

    var chartDataAvailable = Array.isArray(us_chart_data.dates) && us_chart_data.dates.length;
    if ( $('#spline-area-chart').length && chartDataAvailable ) {
        var splineContainer = document.querySelector("#spline-area-chart");
        var isDarkMode = document.documentElement.classList.contains('kc-us-dark') ||
            document.documentElement.classList.contains('kc-us-dark-active') ||
            document.body.classList.contains('kc-us-dark-active') ||
            (document.getElementById('wpbody-content') && document.getElementById('wpbody-content').classList.contains('kc-us-dark'));
        
        // Show skeleton loader
        splineContainer.classList.add('loading');
        splineContainer.innerHTML = '<div class="chart-skeleton">' +
            '<div class="skeleton-line"></div>' +
            '<div class="skeleton-line"></div>' +
            '<div class="skeleton-line"></div>' +
            '<div class="skeleton-line"></div>' +
            '<div class="skeleton-line"></div>' +
            '</div>';
        
        if ( window.usSplineChart instanceof ApexCharts ) {
            window.usSplineChart.destroy();
        }
        var splineOptions = {
            series: [
                { name: 'Total Clicks', data: us_chart_data.total_series },
                { name: 'Unique Clicks', data: us_chart_data.unique_series }
            ],
            chart: {
                height: 260,
                type: 'area',
                toolbar: { show: false },
                foreColor: isDarkMode ? '#cbd5e1' : '#475569',
                background: 'transparent',
                fontFamily: 'Inter, system-ui, sans-serif',
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
                    animateGradually: {
                        enabled: true,
                        delay: 150
                    },
                    dynamicAnimation: {
                        enabled: true,
                        speed: 350
                    }
                }
            },
            theme: {
                mode: isDarkMode ? 'dark' : 'light'
            },
            stroke: { curve: 'smooth', width: 3 },
            dataLabels: { enabled: false },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: isDarkMode ? 'dark' : 'light',
                    gradientToColors: isDarkMode ? ['#818cf8', '#34d399'] : ['#a78bfa', '#34d399'],
                    shadeIntensity: isDarkMode ? 0.45 : 0.75,
                    opacityFrom: isDarkMode ? 0.42 : 0.9,
                    opacityTo: isDarkMode ? 0.08 : 0.25,
                    stops: [0, 60, 100]
                }
            },
            markers: { 
                size: 0, 
                hover: { sizeOffset: 6 },
                shape: 'circle'
            },
            xaxis: {
                type: 'datetime',
                categories: us_chart_data.dates,
                labels: { style: { colors: '#94a3b8' } },
                axisBorder: { show: true, color: isDarkMode ? 'rgba(71,85,105,0.85)' : 'rgba(148,163,184,0.4)' },
                axisTicks: { color: isDarkMode ? 'rgba(71,85,105,0.85)' : 'rgba(148,163,184,0.4)' }
            },
            yaxis: {
                labels: { style: { colors: '#94a3b8' } },
                tickAmount: 4
            },
            colors: ['#6366f1', '#34d399'],
            grid: {
                borderColor: isDarkMode ? 'rgba(71,85,105,0.65)' : 'rgba(148,163,184,0.25)',
                strokeDashArray: 4
            },
            tooltip: {
                theme: isDarkMode ? 'dark' : 'light',
                marker: { show: true },
                y: {
                    formatter: function (value) {
                        return value.toLocaleString() + ' clicks';
                    }
                }
            }
        };
        
        // Render chart and hide loading skeleton
        splineContainer.innerHTML = '';
        splineContainer.classList.remove('loading');
        window.usSplineChart = new ApexCharts(splineContainer, splineOptions);
        window.usSplineChart.render();
    }

    var heatmapSeriesReady = Array.isArray(us_chart_data.heatmap_series) && us_chart_data.heatmap_series.length;
    var heatmapHasClicksData = !!us_chart_data.has_clicks_data;
    var heatmapCategories = Array.isArray(us_chart_data.heatmap_week_starts) ? us_chart_data.heatmap_week_starts : [];
    var heatmapMonthLabels = Array.isArray(us_chart_data.heatmap_month_labels) ? us_chart_data.heatmap_month_labels : [];
    var dayLabels = Array.isArray(us_chart_data.heatmap_day_labels) ? us_chart_data.heatmap_day_labels : ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
    var heatmapColorRanges = Array.isArray(us_chart_data.heatmap_color_ranges) ? us_chart_data.heatmap_color_ranges : [];
    var heatmapDarkPalette = ['#2b3440', '#153b2a', '#14532d', '#166534', '#15803d', '#22c55e', '#7ee787'];

    function usGetIsDarkMode() {
        return document.documentElement.classList.contains('kc-us-dark') ||
            document.documentElement.classList.contains('kc-us-dark-active') ||
            document.body.classList.contains('kc-us-dark-active') ||
            (document.getElementById('wpbody-content') && document.getElementById('wpbody-content').classList.contains('kc-us-dark'));
    }

    function usBuildHeatmapRanges(isDarkTheme) {
        var ranges = heatmapColorRanges.length ? heatmapColorRanges : [
            { from: 0, to: 0, color: '#f4f7fb', name: '0 clicks' },
            { from: 1, to: 10, color: '#edf9f1', name: '1-10 clicks' }
        ];

        if (!isDarkTheme) {
            return ranges;
        }

        return ranges.map(function (range, index) {
            if (index === 0) {
                return {
                    from: range.from,
                    to: range.to,
                    name: range.name,
                    color: '#2b3440'
                };
            }

            return {
                from: range.from,
                to: range.to,
                name: range.name,
                color: heatmapDarkPalette[Math.min(index, heatmapDarkPalette.length - 1)]
            };
        });
    }

    function usApplyHeatmapCellTheme(heatmapContainer, isDarkTheme) {
        if (!heatmapContainer) {
            return;
        }

        heatmapContainer.querySelectorAll('.apexcharts-heatmap-rect').forEach(function(rect) {
            if (rect.classList.contains('kc-us-heatmap-future')) {
                rect.style.setProperty('filter', 'none', 'important');
                rect.style.boxShadow = 'none';
                rect.style.opacity = '0';
                rect.style.pointerEvents = 'none';
                rect.style.stroke = 'transparent';
                return;
            }

            rect.style.setProperty('filter', 'none', 'important');
            rect.style.boxShadow = 'none';
        });
    }

    function usMarkFutureHeatmapCells(heatmapContainer) {
        if (!heatmapContainer || !Array.isArray(us_chart_data.heatmap_series)) {
            return;
        }

        var heatmapSeriesGroups = heatmapContainer.querySelectorAll('.apexcharts-heatmap-series');

        heatmapSeriesGroups.forEach(function(group) {
            var seriesIndex = parseInt(group.getAttribute('data:realIndex'), 10);
            if (Number.isNaN(seriesIndex) || !us_chart_data.heatmap_series[seriesIndex]) {
                return;
            }

            var points = Array.isArray(us_chart_data.heatmap_series[seriesIndex].data) ? us_chart_data.heatmap_series[seriesIndex].data : [];
            var rects = group.querySelectorAll('.apexcharts-heatmap-rect');

            rects.forEach(function(rect, pointIndex) {
                var point = points[pointIndex];

                if (!rect || !point) {
                    return;
                }

                var isFutureCell = !!point.future;
                rect.classList.toggle('kc-us-heatmap-future', isFutureCell);

                if (isFutureCell) {
                    rect.style.opacity = '0';
                    rect.style.pointerEvents = 'none';
                    rect.style.stroke = 'transparent';
                } else {
                    rect.style.opacity = '';
                    rect.style.pointerEvents = '';
                    rect.style.stroke = '';
                }
            });
        });
    }

    function usRenderActivityHeatmap() {
        var heatmapContainer = document.querySelector('#activity-heatmap');
        if (!heatmapContainer || !heatmapSeriesReady || !heatmapHasClicksData) {
            return;
        }

        var isDarkTheme = usGetIsDarkMode();
        var heatmapThemeColorRanges = usBuildHeatmapRanges(isDarkTheme);

        if (window.usHeatmapChart instanceof ApexCharts) {
            window.usHeatmapChart.destroy();
        }

        var heatmapOptions = {
            series: us_chart_data.heatmap_series,
            chart: {
                height: 260,
                type: 'heatmap',
                toolbar: { show: false },
                foreColor: isDarkTheme ? '#cbd5e1' : '#475569',
                background: 'transparent',
                events: {
                    legendClick: function () {
                        return false;
                    }
                }
            },
            legend: {
                show: true,
                position: 'bottom',
                horizontalAlign: 'center',
                floating: false,
                fontSize: 12,
                labels: {
                    colors: isDarkTheme ? '#e2e8f0' : '#475569'
                },
                markers: {
                    width: 12,
                    height: 12,
                    radius: 2,
                    strokeWidth: 0
                },
                itemMargin: {
                    horizontal: 8,
                    vertical: 4
                },
                onItemHover: {
                    highlightDataSeries: true
                },
                onItemClick: {
                    toggleDataSeries: false
                }
            },
            plotOptions: {
                heatmap: {
                    shadeIntensity: isDarkTheme ? 0.25 : 0.6,
                    radius: 4,
                    distributed: false,
                    enableShades: !isDarkTheme,
                    useFillColorAsStroke: true,
                    strokeWidth: 1.5,
                    strokeColor: isDarkTheme ? 'rgba(71, 85, 105, 0.72)' : 'rgba(226, 232, 240, 0.92)',
                    cellHeight: 18,
                    colorScale: {
                        ranges: heatmapThemeColorRanges
                    }
                }
            },
            dataLabels: { enabled: false },
            tooltip: {
                theme: 'dark',
                shared: false,
                intersect: true,
                x: {
                    formatter: function (value, opts) {
                        var seriesIndex = opts && opts.seriesIndex ? opts.seriesIndex : 0;
                        var dataPointIndex = opts && opts.dataPointIndex ? opts.dataPointIndex : 0;
                        var metaPoint = us_chart_data.heatmap_series[ seriesIndex ] && us_chart_data.heatmap_series[ seriesIndex ].data[ dataPointIndex ];
                        if (metaPoint && metaPoint.future) {
                            return '';
                        }
                        var dateValue = metaPoint && metaPoint.meta ? metaPoint.meta : value;
                        return dateValue ? new Date(dateValue).toLocaleDateString() : value;
                    }
                },
                y: {
                    formatter: function (value) {
                        if (value === null || typeof value === 'undefined') {
                            return '';
                        }
                        return value + ' clicks';
                    }
                }
            },
            states: {
                hover: {
                    filter: {
                        type: isDarkTheme ? 'none' : 'darken',
                        value: isDarkTheme ? 0 : 0.25
                    }
                },
                active: {
                    filter: {
                        type: isDarkTheme ? 'none' : 'darken',
                        value: isDarkTheme ? 0 : 0.15
                    }
                }
            },
            grid: {
                padding: {
                    left: 0,
                    right: 0,
                    top: 0,
                    bottom: 0
                },
                borderColor: 'transparent'
            },
            xaxis: {
                type: 'category',
                categories: heatmapCategories,
                tickPlacement: 'between',
                labels: { show: false },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                opposite: false,
                labels: { show: false },
                categories: dayLabels
            }
        };

        heatmapContainer.innerHTML = '';
        window.usHeatmapChart = new ApexCharts(heatmapContainer, heatmapOptions);
        window.usHeatmapChart.render();
        usMarkFutureHeatmapCells(heatmapContainer);
        usApplyHeatmapCellTheme(heatmapContainer, isDarkTheme);

        var legendItems = heatmapContainer.querySelectorAll('.apexcharts-legend-series');
        legendItems.forEach(function(item, index) {
            item.addEventListener('mouseenter', function() {
                var range = heatmapThemeColorRanges[index];
                if (!range) return;

                var rangeColor = range.color || '#22c55e';
                var allRects = heatmapContainer.querySelectorAll('.apexcharts-heatmap-rect');
                allRects.forEach(function(rect) {
                    if (rect.classList.contains('kc-us-heatmap-future')) {
                        return;
                    }

                    var cellValue = parseInt(rect.getAttribute('val')) || 0;
                    var isInRange = cellValue >= range.from && cellValue <= range.to;

                    if (isInRange) {
                        rect.style.strokeWidth = '4';
                        rect.style.filter = isDarkTheme ? 'saturate(1.1) brightness(1.03)' : 'saturate(1.5) brightness(1.1)';
                        var rgb = hexToRgb(rangeColor);
                        rect.style.boxShadow = '0 0 6px 2px rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ', 0.6)';
                        rect.classList.add('active-highlight');
                        rect.classList.remove('inactive');
                    } else {
                        rect.classList.add('inactive');
                        rect.classList.remove('active-highlight');
                        rect.style.boxShadow = 'none';
                    }
                });

                item.style.backgroundColor = isDarkTheme ? 'rgba(148, 163, 184, 0.12)' : 'rgba(34, 197, 94, 0.1)';
                item.style.fontWeight = '600';
                item.style.borderLeft = '3px solid ' + rangeColor;
                item.style.paddingLeft = '5px';
            });

            item.addEventListener('mouseleave', function() {
                var allRects = heatmapContainer.querySelectorAll('.apexcharts-heatmap-rect');
                allRects.forEach(function(rect) {
                    if (rect.classList.contains('kc-us-heatmap-future')) {
                        return;
                    }

                    rect.style.strokeWidth = '3';
                    rect.style.filter = 'none';
                    rect.style.boxShadow = 'none';
                    rect.classList.remove('inactive');
                    rect.classList.remove('active-highlight');
                });

                usMarkFutureHeatmapCells(heatmapContainer);
                usApplyHeatmapCellTheme(heatmapContainer, isDarkTheme);
                item.style.backgroundColor = 'transparent';
                item.style.fontWeight = '400';
                item.style.borderLeft = 'none';
                item.style.paddingLeft = '0px';
            });
        });

        function hexToRgb(hex) {
            var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return result ? {
                r: parseInt(result[1], 16),
                g: parseInt(result[2], 16),
                b: parseInt(result[3], 16)
            } : { r: 34, g: 197, b: 94 };
        }

        var heatmapMonthContainer = document.querySelector('#heatmap-month-row');
        if ( heatmapMonthContainer ) {
            if ( heatmapMonthLabels.length ) {
                heatmapMonthContainer.style.gridTemplateColumns = 'repeat(' + heatmapMonthLabels.length + ', minmax(0, 1fr))';
                heatmapMonthContainer.innerHTML = heatmapMonthLabels.map(function(label) {
                    var classes = 'kc-us-heatmap-month';
                    if ( label ) {
                        classes += ' kc-us-heatmap-month--label';
                    }
                    return '<span class="' + classes + '">' + ( label ? label : '&nbsp;' ) + '</span>';
                }).join('');
            } else {
                heatmapMonthContainer.innerHTML = '';
            }
        }
    }

    if ( $('#activity-heatmap').length && heatmapSeriesReady && heatmapHasClicksData ) {
        usRenderActivityHeatmap();
        document.addEventListener('kc-us-theme-changed', function () {
            usRenderActivityHeatmap();
        });
    }
});
