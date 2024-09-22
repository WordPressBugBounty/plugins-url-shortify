<?php


namespace KaizenCoders\URL_Shortify\Common;

use KaizenCoders\URL_Shortify\Helper;

/**
 * Class GeoLocation
 *
 * @package KaizenCoders\URL_Shortify\Common
 *
 * @since 1.4.4
 */
class GeoLocation {
	/**
	 * API endpoints for looking up user IP address.
	 *
	 * @var array
	 */
	private static $ip_lookup_apis = array(
		'ipify'             => 'http://api.ipify.org/',
		'ipecho'            => 'http://ipecho.net/plain',
		'ident'             => 'http://ident.me',
		'whatismyipaddress' => 'http://bot.whatismyipaddress.com',
	);

	/**
	 * API endpoints for geolocating an IP address
	 *
	 * @var array
	 */
	private static $geoip_apis = array(
		'ipinfo.io'  => 'https://ipinfo.io/%s/json',
		'ip-api.com' => 'http://ip-api.com/json/%s',
	);

	/**
	 * Get external ip address
	 *
	 * @since 1.4.4
	 */
	public static function get_external_ip_address() {
		$external_ip_address = '0.0.0.0';

		$transient_name = '';
		if ( 'UNKNOWN' !== Helper::get_ip() ) {
			$transient_name      = 'external_ip_address_' . Helper::get_ip();
			$external_ip_address = get_transient( $transient_name );
		}

		if ( false === $external_ip_address ) {
			$external_ip_address     = '0.0.0.0';
			$ip_lookup_services      = self::$ip_lookup_apis;
			$ip_lookup_services_keys = array_keys( $ip_lookup_services );
			shuffle( $ip_lookup_services_keys );

			foreach ( $ip_lookup_services_keys as $service_name ) {
				$service_endpoint = $ip_lookup_services[ $service_name ];
				$response         = wp_safe_remote_get( $service_endpoint, array( 'timeout' => 2 ) );

				if ( ! is_wp_error( $response ) && rest_is_ip_address( $response['body'] ) ) {
					$external_ip_address =  Helper::clean( $response['body']  );
					break;
				}
			}

			set_transient( $transient_name, $external_ip_address, WEEK_IN_SECONDS );
		}

		return $external_ip_address;
	}

	/**
	 * Geolocate an IP address.
	 *
	 * @param string $ip_address IP Address.
	 * @param bool $fallback If true, fallbacks to alternative IP detection (can be slower).
	 * @param bool $api_fallback If true, uses geolocation APIs if the database file doesn't exist (can be slower).
	 *
	 * @return array
	 */
	public static function geolocate_ip( $ip_address = '', $fallback = false, $api_fallback = true ) {

		if ( empty( $ip_address ) ) {
			$ip_address = Helper::get_ip();
		}

		$country_code = self::get_country_code_from_headers();

		/**
		 * Get geolocation filter.
		 *
		 * @param array $geolocation Geolocation data, including country, state, city, and postcode.
		 * @param string $ip_address IP Address.
		 *
		 * @since 3.9.0
		 */
		$geolocation = apply_filters(
			'kc_us_get_geolocation',
			array(
				'country'  => $country_code,
				'state'    => '',
				'city'     => '',
				'postcode' => '',
			),
			$ip_address
		);

		// If we still haven't found a country code, let's consider doing an API lookup.
		if ( '' === $geolocation['country'] && $api_fallback ) {
			$geolocation['country'] = self::geolocate_via_api( $ip_address );
		}

		// It's possible that we're in a local environment, in which case the geolocation needs to be done from the
		// external address.
		if ( '' === $geolocation['country'] && $fallback ) {
			$external_ip_address = self::get_external_ip_address();

			// Only bother with this if the external IP differs.
			if ( '0.0.0.0' !== $external_ip_address && $external_ip_address !== $ip_address ) {
				return self::geolocate_ip( $external_ip_address, false, $api_fallback );
			}
		}

		return array(
			'country'  => $geolocation['country'],
			'state'    => $geolocation['state'],
			'city'     => $geolocation['city'],
			'postcode' => $geolocation['postcode'],
		);
	}

	/**
	 * Fetches the country code from the request headers, if one is available.
	 *
	 * @return string The country code pulled from the headers, or empty string if one was not found.
	 * @since 3.9.0
	 */
	private static function get_country_code_from_headers() {
		$country_code = '';

		$headers = array(
			'MM_COUNTRY_CODE',
			'GEOIP_COUNTRY_CODE',
			'HTTP_CF_IPCOUNTRY',
			'HTTP_X_COUNTRY_CODE',
		);

		foreach ( $headers as $header ) {
			if ( empty( $_SERVER[ $header ] ) ) {
				continue;
			}

			$country_code = strtoupper( sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) );
			break;
		}

		return $country_code;
	}

	/**
	 * Use APIs to Geolocate the user.
	 *
	 * @param string $ip_address IP address.
	 *
	 * @return string
	 */
	private static function geolocate_via_api( $ip_address ) {
		$country_code = get_transient( 'geoip_' . $ip_address );

		if ( false === $country_code ) {
			$geoip_services = self::$geoip_apis;

			if ( empty( $geoip_services ) ) {
				return '';
			}

			$geoip_services_keys = array_keys( $geoip_services );

			shuffle( $geoip_services_keys );

			foreach ( $geoip_services_keys as $service_name ) {
				$service_endpoint = $geoip_services[ $service_name ];
				$response         = wp_safe_remote_get( sprintf( $service_endpoint, $ip_address ), array( 'timeout' => 2 ) );

				if ( ! is_wp_error( $response ) && $response['body'] ) {
					switch ( $service_name ) {
						case 'ipinfo.io':
							$data         = json_decode( $response['body'] );
							$country_code = isset( $data->country ) ? $data->country : '';
							break;
						case 'ip-api.com':
							$data         = json_decode( $response['body'] );
							$country_code = isset( $data->countryCode ) ? $data->countryCode : '';
							break;
						default:
							$country_code = '';
							break;
					}

					$country_code = sanitize_text_field( strtoupper( $country_code ) );

					if ( $country_code ) {
						break;
					}
				}
			}

			set_transient( 'geoip_' . $ip_address, $country_code, WEEK_IN_SECONDS );
		}

		return $country_code;
	}

}