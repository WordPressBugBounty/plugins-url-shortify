/* global jQuery, kcUsDomainVerify */

/**
 * Custom domain setup verification.
 *
 * Asks the server to probe a custom domain and reports whether it reaches
 * WordPress as an alias or is redirected to the main site.
 *
 * @since 2.5.1
 */
(function ($) {
	'use strict';

	var BADGE_CLASSES = [
		'bg-green-100', 'text-green-800',
		'bg-yellow-100', 'text-yellow-800',
		'bg-red-100', 'text-red-800',
		'bg-gray-100', 'text-gray-800', 'text-gray-600'
	];

	function setBusy($button, busy) {
		$button.prop('disabled', busy);
		$button.text(busy ? kcUsDomainVerify.i18n.checking : kcUsDomainVerify.i18n.verify);
	}

	function render($wrapper, data) {
		var $badge = $wrapper.find('.kc-us-domain-status-badge');

		$badge.removeClass(BADGE_CLASSES.join(' '));

		if (data.classes) {
			$badge.addClass(data.classes);
		}

		$badge.text(data.label);

		$wrapper.find('.kc-us-domain-status-message').text(data.message || '');
	}

	$(document).on('click', '.kc-us-verify-domain', function (event) {
		event.preventDefault();

		var $button = $(this);
		var $wrapper = $button.closest('.kc-us-domain-status');
		var domainId = $button.data('domain-id');

		setBusy($button, true);

		$.post(kcUsDomainVerify.ajaxUrl, {
			action: 'kc_us_verify_domain',
			security: kcUsDomainVerify.security,
			domain_id: domainId
		}).done(function (response) {
			if (response && response.success) {
				render($wrapper, response.data);
			} else {
				render($wrapper, {
					label: kcUsDomainVerify.i18n.verify,
					classes: 'bg-red-100 text-red-800',
					message: (response && response.data && response.data.message) || kcUsDomainVerify.i18n.error
				});
			}
		}).fail(function () {
			$wrapper.find('.kc-us-domain-status-message').text(kcUsDomainVerify.i18n.error);
		}).always(function () {
			setBusy($button, false);
		});
	});
})(jQuery);
