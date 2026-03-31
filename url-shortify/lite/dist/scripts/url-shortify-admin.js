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

            if (serverSide) {
                $clicksTable.DataTable({
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

                        var linkId = $clicksTable.data('link-id');
                        var linkIds = $clicksTable.data('link-ids');
                        var days = $clicksTable.data('days');

                        if ( linkId ) {
                            ajaxData.link_id = linkId;
                        }

                        if ( linkIds ) {
                            ajaxData.link_ids = linkIds;
                        }

                        if ( days ) {
                            ajaxData.days = days;
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
                        { orderable: false },
                        { orderable: false },
                        { orderable: true },
                        { orderable: false },
                        { orderable: false },
                        { orderable: true },
                        { orderable: false },
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

jQuery(window).ready(function () {
});

window.usSplineChart = window.usSplineChart || null;
window.usHeatmapChart = window.usHeatmapChart || null;

// Wrap your code to ensure $ is recognized as jQuery
 jQuery(document).ready(function($) {
    'use strict';

    if ( typeof us_chart_data === 'undefined' ) {
        return;
    }

    var chartDataAvailable = Array.isArray(us_chart_data.dates) && us_chart_data.dates.length;
    if ( $('#spline-area-chart').length && chartDataAvailable ) {
        var splineContainer = document.querySelector("#spline-area-chart");
        
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
                foreColor: '#475569',
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
            stroke: { curve: 'smooth', width: 3 },
            dataLabels: { enabled: false },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'light',
                    gradientToColors: ['#a78bfa', '#34d399'],
                    shadeIntensity: 0.75,
                    opacityFrom: 0.9,
                    opacityTo: 0.25,
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
                axisBorder: { show: true, color: 'rgba(148,163,184,0.4)' },
                axisTicks: { color: 'rgba(148,163,184,0.4)' }
            },
            yaxis: {
                labels: { style: { colors: '#94a3b8' } },
                tickAmount: 4
            },
            colors: ['#6366f1', '#34d399'],
            grid: {
                borderColor: 'rgba(148,163,184,0.25)',
                strokeDashArray: 4
            },
            tooltip: {
                theme: 'dark',
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
    if ( $('#activity-heatmap').length && heatmapSeriesReady && heatmapHasClicksData ) {
        if ( window.usHeatmapChart instanceof ApexCharts ) {
            window.usHeatmapChart.destroy();
        }
        var heatmapOptions = {
            series: us_chart_data.heatmap_series,
            chart: {
                height: 260,
                type: 'heatmap',
                toolbar: { show: false },
                background: 'transparent',
                events: {
                    // Handle legend hover for better highlighting
                    legendClick: function(chartContext, seriesIndex) {
                        // Prevent default toggle behavior
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
                    shadeIntensity: 0.6,
                    radius: 4,
                    distributed: false,
                    enableShades: true,
                    useFillColorAsStroke: true,
                    strokeWidth: 3,
                    strokeColor: '#ffffff',
                    cellHeight: 18,
                    colorScale: {
                        ranges: heatmapColorRanges.length > 0 ? heatmapColorRanges : [
                            { from: 0, to: 0, color: '#f0fdf4', name: '0 clicks' },
                            { from: 1, to: 10, color: '#d3fcca', name: '1-10 clicks' }
                        ]
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
                        var dateValue = metaPoint && metaPoint.meta ? metaPoint.meta : value;
                        return dateValue ? new Date(dateValue).toLocaleDateString() : value;
                    }
                },
                y: {
                    formatter: function (value) {
                        return value + ' clicks';
                    }
                }
            },
            states: {
                // Enhanced hover effect
                hover: {
                    filter: {
                        type: 'darken',
                        value: 0.25
                    }
                },
                active: {
                    filter: {
                        type: 'darken',
                        value: 0.15
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
        var heatmapContainer = document.querySelector("#activity-heatmap");
        if ( heatmapContainer ) {
            heatmapContainer.innerHTML = '';
            window.usHeatmapChart = new ApexCharts(heatmapContainer, heatmapOptions);
            window.usHeatmapChart.render();

            // Add custom legend interaction handling
            var legendItems = heatmapContainer.querySelectorAll('.apexcharts-legend-series');
            legendItems.forEach(function(item, index) {
                item.addEventListener('mouseenter', function() {
                    // Get the range for this legend item
                    var range = heatmapColorRanges[index];
                    if (!range) return;

                    // Get the color from range
                    var rangeColor = range.color || '#22c55e';

                    // Highlight cells in this range
                    var allRects = heatmapContainer.querySelectorAll('.apexcharts-heatmap-rect');
                    allRects.forEach(function(rect) {
                        var cellValue = parseInt(rect.getAttribute('val')) || 0;
                        // Check if cell value is in this range
                        var isInRange = cellValue >= range.from && cellValue <= range.to;
                        
                        if (isInRange) {
                            rect.style.strokeWidth = '4';
                            rect.style.filter = 'saturate(1.5) brightness(1.1)';
                            
                            // Add glow effect using box-shadow with range color
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

                    // Highlight the legend item
                    item.style.backgroundColor = 'rgba(34, 197, 94, 0.1)';
                    item.style.fontWeight = '600';
                    item.style.borderLeft = '3px solid ' + rangeColor;
                    item.style.paddingLeft = '5px';
                });

                item.addEventListener('mouseleave', function() {
                    // Reset all cells
                    var allRects = heatmapContainer.querySelectorAll('.apexcharts-heatmap-rect');
                    allRects.forEach(function(rect) {
                        rect.style.strokeWidth = '3';
                        rect.style.filter = 'none';
                        rect.style.boxShadow = 'none';
                        rect.classList.remove('inactive');
                        rect.classList.remove('active-highlight');
                    });

                    // Reset legend item
                    item.style.backgroundColor = 'transparent';
                    item.style.fontWeight = '400';
                    item.style.borderLeft = 'none';
                    item.style.paddingLeft = '0px';
                });
            });

            // Helper function to convert hex color to RGB
            function hexToRgb(hex) {
                var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
                return result ? {
                    r: parseInt(result[1], 16),
                    g: parseInt(result[2], 16),
                    b: parseInt(result[3], 16)
                } : { r: 34, g: 197, b: 94 };
            }
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
});
