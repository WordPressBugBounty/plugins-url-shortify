<?php

namespace KaizenCoders\URL_Shortify\Common;

use KaizenCoders\URL_Shortify\Helper;

class Export {
	/**
	 * Generate CSV.
	 *
	 * @param $headers array
	 *
	 * @param $data array
	 *
	 * @return string
	 *
	 * @since 1.6.5
	 */
	public function generate_csv( $headers, $data ) {

		// Don't have data? Bail early.
		if ( empty( $headers ) || empty( $data ) ) {
			return '';
		}

		$csv_output = implode( ',', $headers );
		$csv_output .= "\n";

		if ( Helper::is_forechable( $data ) ) {
			foreach ( $data as $d ) {
				$csv = array();
				foreach ( $headers as $key => $header ) {
					$value = Helper::get_data( $d, $key, '' );

					if ( 'created_at' === $key ) {
						/*
						 * A zero date formats as "-0001-11-30", which is not a
						 * date any importer can read back. Export an empty cell
						 * instead so a re-import falls back to a sensible value.
						 */
						$value = ( empty( $value ) || '0000-00-00 00:00:00' === $value )
							? ''
							: Helper::get_formatted_datetime( $value );
					}

					$csv[] = $value;
				}

				$csv_output .= '"' . implode( '","', $csv ) . '"';
				$csv_output .= "\n";
			}
		}

		return $csv_output;
	}

	/**
	 * Download CSV Data
	 *
	 * @param $csv_data
	 *
	 * @param $file_name
	 *
	 * @return void
	 *
	 * @since 1.6.5
	 */
	public function download_csv( $csv_data, $file_name ) {
		if ( empty( $csv_data ) ) {
			$message = __( 'No data available', 'url-shortify' );
			exit();
		} else {
			ob_end_clean();
			header( 'Pragma: public' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header( 'Cache-Control: private', false );
			header( 'Content-Type: application/octet-stream' );
			$safe_filename = sanitize_file_name( $file_name );
			header( 'Content-Disposition: attachment; filename="' . $safe_filename . '"' );
			header( 'Content-Transfer-Encoding: binary' );

			echo wp_kses_post( $csv_data );
			exit;
		}
	}

	/**
	 * Get Clicks info headers.
	 *
	 * @return array
	 *
	 * @since 1.6.5
	 */
	public function get_clicks_info_headers() {
		return array(
			'name'            => __( 'Title', 'url-shortify' ),
			'uri'             => __( 'Slug', 'url-shortify' ),
			'host'            => __( 'Domain', 'url-shortify' ),
			'referer'         => __( 'Referer', 'url-shortify' ),
			'is_first_click'  => __( 'First Click', 'url-shortify' ),
			'is_robot'        => __( 'Robot', 'url-shortify' ),
			'os'              => __( 'OS', 'url-shortify' ),
			'device'          => __( 'Device', 'url-shortify' ),
			'browser_type'    => __( 'Browser', 'url-shortify' ),
			'browser_version' => __( 'Browser Version', 'url-shortify' ),
			'ip'              => __( 'IP Address', 'url-shortify' ),
			'created_at'      => __( 'Created At', 'url-shortify' ),
		);
	}

	/**
	 * Get links headers.
	 *
	 * @return array
	 *
	 * @since 1.6.5
	 */
	public function get_links_headers() {
		$headers = array(
			'id'                => __( 'ID', 'url-shortify' ),
			'name'              => __( 'Title', 'url-shortify' ),
			'description'       => __( 'Description', 'url-shortify' ),
			'slug'              => __( 'Slug', 'url-shortify' ),
			'url'               => __( 'Target URL', 'url-shortify' ),
			'nofollow'          => __( 'Nofollow', 'url-shortify' ),
			'track_me'          => __( 'Track', 'url-shortify' ),
			'sponsored'         => __( 'Sponsored', 'url-shortify' ),
			'params_forwarding' => __( 'Parameter Forwarding', 'url-shortify' ),
			'redirect_type'     => __( 'Redirect Type', 'url-shortify' ),
			'created_at'        => __( 'Created At', 'url-shortify' ),
			'status'            => __( 'Status', 'url-shortify' ),
			'groups'            => __( 'Groups', 'url-shortify' ),
		);

		if ( US()->is_pro() ) {
			$headers['tags'] = __( 'Tags', 'url-shortify' );
		}

		return $headers;
	}

	/**
	 * Add the status, group and tag columns to a set of link rows.
	 *
	 * Shared so every export of links carries the same columns. The Links
	 * screen used to build these inline while the group and tag statistics
	 * screens exported neither, which meant a file from one of those screens
	 * could not be imported back without losing the assignments.
	 *
	 * @param array $links Link rows, by reference-safe copy.
	 *
	 * @return array
	 *
	 * @since 2.6.1
	 */
	public function decorate_links( $links = array() ) {
		if ( ! Helper::is_forechable( $links ) ) {
			return $links;
		}

		$link_ids = wp_list_pluck( $links, 'id' );

		$links_ids_group_ids = US()->db->links_groups->get_group_ids_by_link_ids( $link_ids );

		// Queried fresh rather than via get_all_id_name_map(), which returns a
		// map built when the plugin booted - a group added since would be
		// missing from it, and the link would export without that group.
		$group_id_name_map = US()->db->groups->get_id_name_map();

		$links_ids_tag_ids = array();
		$tag_id_name_map   = array();

		if ( US()->is_pro() ) {
			$links_ids_tag_ids = US()->db->links_tags->get_tag_ids_by_link_ids( $link_ids );
			$tag_id_name_map   = US()->db->tags->get_id_name_map();
		}

		foreach ( $links as &$link ) {
			$link_id = Helper::get_data( $link, 'id', 0 );

			$link['status'] = 1 === (int) Helper::get_data( $link, 'status', 0 )
				? __( 'Enabled', 'url-shortify' )
				: __( 'Disabled', 'url-shortify' );

			$group_ids      = ! empty( $links_ids_group_ids[ $link_id ] ) ? $links_ids_group_ids[ $link_id ] : array();
			$link['groups'] = $this->names_from_ids( $group_ids, $group_id_name_map );

			if ( US()->is_pro() ) {
				$tag_ids      = ! empty( $links_ids_tag_ids[ $link_id ] ) ? $links_ids_tag_ids[ $link_id ] : array();
				$link['tags'] = $this->names_from_ids( $tag_ids, $tag_id_name_map );
			}
		}

		unset( $link );

		return $links;
	}

	/**
	 * Join group or tag names for a CSV cell.
	 *
	 * Pipe separated rather than comma separated. The importer falls back to
	 * splitting on commas, so a group genuinely named "Tips, Tricks" used to
	 * come back as two groups on the next import.
	 *
	 * @param array $ids
	 * @param array $id_name_map
	 *
	 * @return string
	 *
	 * @since 2.6.1
	 */
	protected function names_from_ids( $ids, $id_name_map ) {
		$names = array();

		foreach ( (array) $ids as $id ) {
			$name = Helper::get_data( $id_name_map, $id, '' );

			if ( '' !== $name ) {
				$names[] = $name;
			}
		}

		return implode( '|', $names );
	}
}