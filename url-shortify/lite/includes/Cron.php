<?php

namespace KaizenCoders\URL_Shortify;

class Cron {

	public function init() {

		if ( ! wp_next_scheduled( 'regenerate_json_links_daily' ) ) {
			wp_schedule_event( time(), 'daily', 'regenerate_json_links_daily' );
		}

		add_action( 'regenerate_json_links_daily', [ $this, 'regenerate_json_links' ] );
		add_action( 'regenerate_json_links', [ $this, 'regenerate_json_links' ] );
	}

	/**
	 * Regenerate JSON links
	 *
	 * @since 1.5.1
	 */
	public function regenerate_json_links() {
		Helper::regenerate_json_links();
	}

	/**
	 * Schedule Cron for regenerate JSON links
	 *
	 * @since 1.5.1
	 */
	public static function schedule_cron_for_regenerate_json_links() {
		wp_clear_scheduled_hook( 'regenerate_json_links' );
		wp_schedule_single_event( time() + 5, 'regenerate_json_links' );
	}

}