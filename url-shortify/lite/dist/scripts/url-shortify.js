(function ($) {
    'use strict';

    function validURL(str) {
        var pattern = new RegExp('^(https?:\\/\\/)?' + // protocol
            '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|' + // domain name
            '((\\d{1,3}\\.){3}\\d{1,3}))' + // OR ip (v4) address
            '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*' + // port and path
            '(\\?[;&a-z\\d%_.~+=-]*)?' + // query string
            '(\\#[-a-z\\d_]*)?$', 'i'); // fragment locator
        return !!pattern.test(str);
    }

    function injectCopyTooltipStyles() {
        if (document.getElementById('kc-us-copy-tooltip-styles')) {
            return;
        }
        var style = document.createElement('style');
        style.id = 'kc-us-copy-tooltip-styles';
        style.textContent =
            '.kc-us-copy-tooltip{position:absolute;background:#1f2937;color:#fff;font-size:12px;font-weight:500;line-height:1;padding:6px 10px;border-radius:4px;white-space:nowrap;pointer-events:none;z-index:100000;box-shadow:0 2px 8px rgba(0,0,0,.18);transform:translate(-50%,-100%);animation:kcUsCopyVibrate .25s ease-out both}' +
            '.kc-us-copy-tooltip::after{content:"";position:absolute;top:100%;left:50%;margin-left:-4px;border:4px solid transparent;border-top-color:#1f2937}' +
            '@keyframes kcUsCopyVibrate{0%,100%{transform:translate(-50%,-100%) translateX(0)}33%{transform:translate(-50%,-100%) translateX(-4px)}66%{transform:translate(-50%,-100%) translateX(4px)}}';
        document.head.appendChild(style);
    }

    function showCopyTooltip(targetEl, message) {
        injectCopyTooltipStyles();
        var rect = targetEl.getBoundingClientRect();
        var $tooltip = $('<div class="kc-us-copy-tooltip"></div>').text(message);
        $('body').append($tooltip);
        $tooltip.css({
            top: rect.top + window.pageYOffset - 4,
            left: rect.left + window.pageXOffset + (rect.width / 2)
        });
        setTimeout(function () {
            $tooltip.fadeOut(250, function () { $tooltip.remove(); });
        }, 1100);
    }

    $(document).ready(function () {
        // Copy Short Link
        var elem = '.kc-us-copy-to-clipboard';

        if ($(elem).get(0)) {

            let clipboardLink = new ClipboardJS(elem);

            clipboardLink.on(
                'success', function (e) {

                    let elem = e.trigger;

                    $(elem).find('.kc-us-link').select();

                    showCopyTooltip(elem, 'Copied!');
                }
            );
        }

        // Generate Short Links for Posts & Pages from POSTS & PAGES list
        $(".kc_us_create_short_link").click(function (e) {

            e.preventDefault();

            var post_id = $(this).attr('data-post_id');
            var security = $(this).attr('data-us-security');

            $(this).find('.kc_us_loading').show();

            $.ajax({
                type: "post",
                dataType: "json",
                context: this,
                url: ajaxurl,
                data: {
                    action: 'us_handle_request',
                    cmd: "create_short_link",
                    post_id: post_id,
                    security: security
                },
                success: function (response) {
                    if (response.status === "success") {
                        $(this).parent('.us_short_link').html(response.html);
                    } else {
                        $(this).find('.kc_us_loading').hide();
                    }
                },

                error: function (err) {
                    $(this).find('.kc_us_loading').hide();
                }
            });
        });


        /**
         * Generate Short link from Dashboard Widget
         *
         * @since 1.2.5
         */
        $("#kc-us-dashboard-short-link").click(function (e) {

            e.preventDefault();

            var targetURL = $('#kc-us-target-url').val();
            var slug = $('#kc-us-slug').val();
            var security = $('#kc-us-security').val();
            var domain = $('#kc-us-domain').val();

            $('#kc-us-error-message').hide();
            $('#kc-us-success-message').hide();

            // Reported inline, like every other error in this widget. A blocking
            // browser alert was the odd one out and could not be styled or read
            // by assistive tech alongside the field it refers to.
            if (!validURL(targetURL)) {
                $('#kc-us-error-message')
                    .text('Enter a full URL, including https://')
                    .show();
                $('#kc-us-target-url').trigger('focus');
                return;
            }

            $(this).find('.kc_us_loading').show();

            $.ajax({
                type: "post",
                dataType: "json",
                context: this,
                url: ajaxurl,
                data: {
                    action: 'us_handle_request',
                    cmd: "create_short_link",
                    slug: slug,
                    url: targetURL,
                    security: security,
                    domain: domain
                },
                success: function (response) {
                    if (response.status === "success") {

                        var link = response.link;

                        var html = 'Short Link : <span class="kc-flex kc-us-copy-to-clipboard" data-clipboard-text="' + link + '" id="link-25"><input type="text" readonly="true" style="width: 65%;" onclick="this.select();" value="' + link + '" class="kc-us-link"></span>';

                        $('#kc-us-success-message').html(html);

                        $('#kc-us-success-message').show();
                    } else {
                        var html = 'Something went wrong while creating short link';
                        if (response.message) {
                            html = response.message;
                        }

                        $('#kc-us-error-message').html(html);
                        $('#kc-us-error-message').show();
                    }

                    $('.kc_us_loading').hide();
                },

                error: function (err) {
                    var html = 'Something went wrong while creating short link';
                    $('#kc-us-error-message').html(html);
                    $('#kc-us-error-message').show();

                    $('.kc_us_loading').hide();
                }
            });
        });


        /**
         * Public facing url shortener.
         *
         * @since 1.3.10
         */
        var $shortener = $('.kcus-shortener');

        function kcusMessage($el, text) {
            if (text) {
                $el.text(text).prop('hidden', false);
            } else {
                $el.text('').prop('hidden', true);
            }
        }

        /**
         * Copy text to the clipboard.
         *
         * navigator.clipboard only exists in a secure context, so plain http://
         * sites fall back to a hidden textarea. If both routes fail the field is
         * selected so the visitor can copy it themselves.
         *
         * @return {Promise<boolean>} resolves true when the text was copied.
         */
        function kcusCopy(text, $field) {
            if (window.navigator && navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text).then(function () {
                    return true;
                }).catch(function () {
                    return kcusLegacyCopy(text, $field);
                });
            }

            return $.Deferred().resolve(kcusLegacyCopy(text, $field)).promise();
        }

        function kcusLegacyCopy(text, $field) {
            var area = document.createElement('textarea');

            area.value = text;
            area.setAttribute('readonly', '');
            area.style.position = 'fixed';
            area.style.top = '-1000px';
            document.body.appendChild(area);
            area.select();

            var copied = false;

            try {
                copied = document.execCommand('copy');
            } catch (err) {
                copied = false;
            }

            document.body.removeChild(area);

            // Last resort: leave the link selected so it can be copied by hand.
            if (!copied && $field && $field.length) {
                $field.trigger('focus').trigger('select');
            }

            return copied;
        }

        $shortener.on('submit', '.generate-short-link-form', function (e) {
            e.preventDefault();

            var $form = $(this);
            var $root = $form.closest('.kcus-shortener');
            var $button = $root.find('#kc-us-submit-btn');
            var $spinner = $button.find('.kc_us_loading');
            var $error = $root.find('#kc-us-error-msg');
            var $result = $root.find('#kc-us-result');

            var targetURL = $.trim($root.find('#kc-us-target-url').val());
            var security = $root.find('#kc-us-security').val();

            kcusMessage($error, '');

            if (!validURL(targetURL)) {
                kcusMessage($error, 'Enter a full URL, including https://');
                $root.find('#kc-us-target-url').trigger('focus');
                return;
            }

            $button.prop('disabled', true);
            $spinner.prop('hidden', false);

            $.ajax({
                type: 'post',
                dataType: 'json',
                url: usParams.ajaxurl,
                data: {
                    action: 'us_handle_request',
                    cmd: 'create_short_link',
                    url: targetURL,
                    security: security
                }
            }).done(function (response) {
                if (response && response.status === 'success') {
                    $root.find('#kc-us-source-url').text(targetURL).attr('title', targetURL);
                    $root.find('#kc-us-short-url').val(response.link);
                    kcusMessage($root.find('#kc-us-copied'), '');
                    $result.prop('hidden', false);
                    $root.find('#kc-us-copy-btn').trigger('focus');
                } else {
                    kcusMessage($error, (response && response.message) || 'That link could not be shortened. Please try again.');
                }
            }).fail(function () {
                kcusMessage($error, 'That link could not be shortened. Please try again.');
            }).always(function () {
                $button.prop('disabled', false);
                $spinner.prop('hidden', true);
            });
        });

        $shortener.on('click', '#kc-us-copy-btn', function () {
            var $button = $(this);
            var $root = $button.closest('.kcus-shortener');
            var $field = $root.find('#kc-us-short-url');
            var $copied = $root.find('#kc-us-copied');
            var original = $button.data('kcus-label') || $button.text();

            $button.data('kcus-label', original);

            $.when(kcusCopy($field.val(), $field)).done(function (ok) {
                if (ok) {
                    $button.text('Copied');
                    kcusMessage($copied, 'Short link copied to your clipboard.');

                    window.setTimeout(function () {
                        $button.text(original);
                        kcusMessage($copied, '');
                    }, 2000);
                } else {
                    kcusMessage($copied, 'Press Ctrl+C or Cmd+C to copy the selected link.');
                }
            });
        });
    });


})(jQuery);
