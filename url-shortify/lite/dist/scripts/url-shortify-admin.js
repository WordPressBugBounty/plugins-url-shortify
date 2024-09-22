(function ($) {
    'use strict';

    $(document).ready(function () {

        // Link bulk action.
        if ($('.group_select').length) {
            var group_select = $('.group_select')[0].outerHTML;
            $(".kc-us-items-lists .bulkactions #bulk-action-selector-top").after(group_select);
            $('.group_select').hide();
        }

        // Datepixker.
        if ($('.kc-us-date-picker').length) {
            var bulk_expiry = $('.kc-us-date-picker')[0].outerHTML;
            $(".kc-us-items-lists .bulkactions #bulk-action-selector-top").after(bulk_expiry);
            $('.bulkactions .kc-us-date-picker').hide();
        }

        // Bulk action additional dropdown/datepicker show/hide.
        $("#bulk-action-selector-top").change(function () {
            if ($('option:selected', this).attr('value') == 'bulk_group_move' || $('option:selected', this).attr('value') == 'bulk_group_add') {
                $('.group_select').eq(1).show();
                $('.bulkactions .kc-us-date-picker').hide();
            } else if ($('option:selected', this).attr('value') == 'bulk_add_expiry') {
                $('.group_select').hide();
                $('.bulkactions .kc-us-date-picker').show();
            } else {
                $('.group_select').hide();
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
            var sortIndex = $('#clicks-data').find("th[data-key='clicked_on']")[0].cellIndex;

            if ($('#clicks-data').get(0)) {
                $('#clicks-data').DataTable({
                    order: [[sortIndex, "desc"]]
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

    // themeSwitcher
    const themeSwitcher = document.getElementById('themeSwitcher');

    // Theme Vars
    const userTheme = localStorage.getItem('theme');
    const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches;

    // Initial Theme Check
    const themeCheck = () => {
        if (userTheme === 'dark' || (!userTheme && systemTheme)) {
            document.documentElement.classList.add('dark');
            return;
        }
    };

    // Manual Theme Switch
    const themeSwitch = () => {
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
            return;
        }

        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    };

    if (themeSwitcher) {
        // call theme switch on clicking buttons
        themeSwitcher.addEventListener('click', () => {
            themeSwitch();
        });

        // invoke theme check on initial load
        themeCheck();
    }
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
