/**
 * Broken Link Checker — AJAX batch scan with live progress bar.
 *
 * Globals injected via wp_localize_script as `kcUsBlc`:
 *   ajaxUrl  {string}
 *   security {string}
 *   i18n     {object}
 *
 * @since 1.13.1
 */
/* global kcUsBlc */
(function ($) {
	'use strict';

	var blc = {
		// State
		isRunning:    false,
		totalLinks:   0,
		currentOffset: 0,
		batchSize:    5,
		scanBroken:   0,
		scanOk:       0,
		scanErrors:   0,

		// DOM references (populated in init)
		$scanBtn:      null,
		$progressWrap: null,
		$progressBar:  null,
		$progressPct:  null,
		$statusText:   null,
		// Stat card values (update in place during scan)
		$liveBroken:    null,
		$liveOk:        null,
		$liveErrors:    null,
		$liveScanned:   null,
		$liveUnscanned: null,
		// Progress-bar live counters
		$pbScanned:    null,
		$pbOk:         null,
		$pbBroken:     null,
		$pbErrors:     null,

		/**
		 * Wire up DOM references and event listeners.
		 */
		init: function () {
			blc.$scanBtn      = $('#kc-us-blc-scan-btn');
			blc.$progressWrap = $('#kc-us-blc-progress-wrap');
			blc.$progressBar  = $('#kc-us-blc-progress-fill');
			blc.$progressPct  = $('#kc-us-blc-progress-pct');
			blc.$statusText   = $('#kc-us-blc-status-text');
			// Stat cards
			blc.$liveBroken    = $('#kc-us-blc-live-broken');
			blc.$liveOk        = $('#kc-us-blc-live-ok');
			blc.$liveErrors    = $('#kc-us-blc-live-errors');
			blc.$liveScanned   = $('#kc-us-blc-live-scanned');
			blc.$liveUnscanned = $('#kc-us-blc-live-unscanned');
			// Progress-bar counters
			blc.$pbScanned    = $('#kc-us-blc-live-scanned-progress');
			blc.$pbOk         = $('#kc-us-blc-live-ok-progress');
			blc.$pbBroken     = $('#kc-us-blc-live-broken-progress');
			blc.$pbErrors     = $('#kc-us-blc-live-errors-progress');

			blc.$scanBtn.on('click', function (e) {
				e.preventDefault();
				if (!blc.isRunning) {
					blc.startScan();
				}
			});
		},

		/**
		 * First AJAX call: find out how many links need scanning.
		 */
		startScan: function () {
			blc.isRunning     = true;
			blc.currentOffset = 0;
			blc.scanBroken    = 0;
			blc.scanOk        = 0;
			blc.scanErrors    = 0;

			blc.$scanBtn.prop('disabled', true).text(kcUsBlc.i18n.scanning);
			blc.$progressWrap.show();
			blc.setProgress(0, 0);
			blc.$statusText.text(kcUsBlc.i18n.starting).css('color', '');

			$.ajax({
				url:    kcUsBlc.ajaxUrl,
				method: 'POST',
				data: {
					action:   'kc_us_start_broken_link_scan',
					security: kcUsBlc.security,
				},
				success: function (res) {
					if (!res.success) {
						blc.handleError(res.data ? res.data.message : kcUsBlc.i18n.error);
						return;
					}
					blc.totalLinks = parseInt(res.data.total, 10) || 0;

					if (blc.totalLinks === 0) {
						blc.$statusText.text(kcUsBlc.i18n.noLinks);
						blc.resetButton();
						return;
					}

					blc.runNextBatch();
				},
				error: function () {
					blc.handleError(kcUsBlc.i18n.error);
				},
			});
		},

		/**
		 * Recursive batch loop — calls itself until done.
		 */
		runNextBatch: function () {
			$.ajax({
				url:    kcUsBlc.ajaxUrl,
				method: 'POST',
				data: {
					action:     'kc_us_scan_broken_links_batch',
					security:   kcUsBlc.security,
					offset:     blc.currentOffset,
					batch_size: blc.batchSize,
				},
				success: function (res) {
					if (!res.success) {
						blc.handleError(res.data ? res.data.message : kcUsBlc.i18n.error);
						return;
					}

					var d = res.data;
					blc.currentOffset = d.processed;
					blc.scanBroken   += d.broken;
					blc.scanOk       += d.ok;
					blc.scanErrors   += d.errors;

					blc.updateLiveStats(d.processed);

					if (d.done) {
						blc.finishScan();
					} else {
						blc.runNextBatch();
					}
				},
				error: function () {
					blc.handleError(kcUsBlc.i18n.error);
				},
			});
		},

		/**
		 * Update progress bar and live counters.
		 *
		 * @param {number} processed  Links checked so far.
		 */
		updateLiveStats: function (processed) {
			blc.setProgress(processed, blc.totalLinks);

			var msg = kcUsBlc.i18n.processed + ' ' + processed + ' / ' + blc.totalLinks;
			blc.$statusText.text(msg);

			// Update stat cards
			if (blc.$liveScanned.length)   { blc.$liveScanned.text(processed); }
			if (blc.$liveBroken.length)    { blc.$liveBroken.text(blc.scanBroken); }
			if (blc.$liveOk.length)        { blc.$liveOk.text(blc.scanOk); }
			if (blc.$liveErrors.length)    { blc.$liveErrors.text(blc.scanErrors); }
			if (blc.$liveUnscanned.length) { blc.$liveUnscanned.text(Math.max(0, blc.totalLinks - processed)); }
			// Update progress-bar counters
			if (blc.$pbScanned.length) { blc.$pbScanned.text(processed); }
			if (blc.$pbBroken.length)  { blc.$pbBroken.text(blc.scanBroken); }
			if (blc.$pbOk.length)      { blc.$pbOk.text(blc.scanOk); }
			if (blc.$pbErrors.length)  { blc.$pbErrors.text(blc.scanErrors); }
		},

		/**
		 * Move the progress bar to the given position.
		 *
		 * @param {number} value  Current value.
		 * @param {number} max    Maximum value.
		 */
		setProgress: function (value, max) {
			var pct = max > 0 ? Math.min(100, Math.round((value / max) * 100)) : 0;
			blc.$progressBar.css('width', pct + '%').attr('aria-valuenow', pct);
			blc.$progressPct.text(pct + '%');
		},

		/**
		 * Called when the last batch reports done=true.
		 */
		finishScan: function () {
			blc.isRunning = false;
			blc.setProgress(blc.totalLinks, blc.totalLinks);
			blc.$statusText.text(kcUsBlc.i18n.done).css('color', '#16a34a');

			// Reload after a short delay so the user can see the completion message.
			setTimeout(function () {
				window.location.reload();
			}, 1500);
		},

		/**
		 * Reset button + show error message.
		 *
		 * @param {string} msg
		 */
		handleError: function (msg) {
			blc.isRunning = false;
			blc.resetButton();
			blc.$statusText.text(kcUsBlc.i18n.errorPrefix + ': ' + msg).css('color', '#dc2626');
		},

		/**
		 * Re-enable the scan button.
		 */
		resetButton: function () {
			blc.$scanBtn.prop('disabled', false).text(kcUsBlc.i18n.runScan);
		},
	};

	$(document).ready(function () {
		blc.init();
	});

})(jQuery);
