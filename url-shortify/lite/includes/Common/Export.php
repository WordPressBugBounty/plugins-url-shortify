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
					$csv[] = Helper::get_data( $d, $key, '' );
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
			header( "Content-Disposition: attachment; filename={$file_name};" );
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
		return array(
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
		);
	}
}