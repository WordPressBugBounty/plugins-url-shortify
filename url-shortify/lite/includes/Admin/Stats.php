<?php

namespace KaizenCoders\URL_Shortify\Admin;

class Stats {

	/**
	 * Get total clicks by link id
	 *
	 * @param array|null $link_ids
	 *
	 * @return int|string|null
	 *
	 * @since 1.0.2
	 */
	public static function get_total_clicks_by_link_ids( $link_ids = null ) {

		if ( empty( $link_ids ) ) {
			return 0;
		}

		return US()->db->clicks->get_total_by_link_ids( $link_ids );
	}

	/**
	 * Get total unique clicks by link id
	 *
	 * @param array|null $link_ids
	 *
	 * @return int|string|null
	 *
	 * @since 1.0.2
	 */
	public static function get_total_unique_clicks_by_link_ids( $link_ids = null ) {

		if ( empty( $link_ids ) ) {
			return 0;
		}

		return US()->db->clicks->get_total_unique_by_link_ids( $link_ids );
	}

	/**
	 * Get total links by group id
	 *
	 * @param int $group_id
	 *
	 * @return int|string|null
	 *
	 * @since 1.1.8
	 */
	public static function get_total_links_by_group_id( $group_id = 0 ) {
		if ( empty( $group_id ) ) {
			return 0;
		}

		return US()->db->links_groups->count_by_group_id( $group_id );
	}


}
