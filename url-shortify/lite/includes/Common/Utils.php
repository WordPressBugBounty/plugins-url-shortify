<?php

namespace KaizenCoders\URL_Shortify\Common;

use KaizenCoders\URL_Shortify\Helper;

class Utils {

	/**
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public static function get_permalink_pre_slug_regex() {

		$pre_slug_uri = self::get_permalink_pre_slug_uri( true );

		if ( empty( $pre_slug_uri ) ) {
			return '/';
		} else {
			return "{$pre_slug_uri}|/";
		}
	}

	/**
	 * Get parmalink structure
	 *
	 * @param bool $force
	 * @param bool $trim
	 *
	 * @return mixed|string|string[]|null
	 *
	 * @since 1.0.0
	 */
	public static function get_permalink_pre_slug_uri( $force = false, $trim = false ) {

		if ( $force ) {

			preg_match( '#^([^%]*?)%#', get_option( 'permalink_structure' ), $struct );

			$pre_slug_uri = '';
			if ( isset( $struct[1] ) ) {
				$pre_slug_uri = $struct[1];
			}

			if ( $trim ) {
				$pre_slug_uri = trim( $pre_slug_uri );
				$pre_slug_uri = preg_replace( '#^/#', '', $pre_slug_uri );
				$pre_slug_uri = preg_replace( '#/$#', '', $pre_slug_uri );
			}

			return $pre_slug_uri;

		} else {
			return '/';
		}
	}

	/**
	 * Generate Random slug
	 *
	 * @param int $length
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public static function generate_random_slug( $length = 4 ) {

		$characters = '';

		if ( US()->is_pro() ) {

			$settings = US()->get_settings();

			// $length = (int) Helper::get_data( $settings, 'links_default_link_options_slug_character_count', 4 );
			$length = (int) Helper::get_data( $settings, 'links_slug_settings_slug_character_count', 4 );

			$is_lower_case           = (bool) Helper::get_data( $settings, 'links_slug_settings_lower_case', true );
			$is_upper_case           = (bool) Helper::get_data( $settings, 'links_slug_settings_upper_case', false );
			$is_numeric              = (bool) Helper::get_data( $settings, 'links_slug_settings_numeric', true );
			$excluded_characters_str = Helper::get_data( $settings, 'links_slug_settings_excluded_characters', '' );

			if ( ! empty( $excluded_characters_str ) ) {
				$excluded_characters = array_map('trim', explode( ',', $excluded_characters_str ));
			}

			if ( $is_lower_case ) {
				$characters = 'abcdefghijklmnopqrstuvwxyz';
			}

			if ( $is_upper_case ) {
				$characters .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
			}

			if ( $is_numeric ) {
				$characters .= '0123456789';
			}

			if ( ! empty( $excluded_characters ) ) {
				$characters = str_replace( $excluded_characters, '', $characters );
			}
		}

		// Note: For lite version, we will create slug in lowercase always.
		if ( empty( $characters ) ) {
			$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
		}

		$slug = '';

		$index = strlen( $characters ) - 1;

		for ( $i = 0; $i < $length; $i ++ ) {
			$slug .= $characters[ mt_rand( 0, $index ) ];
		}

		return $slug;
	}

	/**
	 * Get unique slug which is not there in database
	 *
	 * @param int $length
	 *
	 * @return string
	 *
	 * @sinc 1.0.0
	 */
	public static function get_valid_slug( $length = 4 ) {

		$slugs = US()->db->links->get_column_by_condition( 'slug' );

		$slug = self::generate_random_slug( $length );

		while ( in_array( $slug, $slugs ) ) {
			$slug = self::generate_random_slug( $length );
		}

		return $slug;
	}

	/**
	 * Check slug is already exists or not.
	 *
	 * @param $slug
	 *
	 * @return bool
	 *
	 * @since 1.6.3
	 */
	public static function is_slug_exists( $slug ) {
		$slugs = US()->db->links->get_column_by_condition( 'slug' );

		if ( US()->is_pro() ) {
			$settings       = US()->get_settings();
			$case_sensitive = (boolean) Helper::get_data( $settings, 'general_settings_case_sensitive_slug', 0 );
			if ( $case_sensitive ) {
				return in_array( $slug, $slugs );
			}
		}

		return in_array( strtolower( $slug ), array_map( 'strtolower', $slugs ) );
	}

	public static function format_html_to_text( $html ) {

		$html = str_replace( "\r", "", $html );
		$html = str_replace( "<", "&lt;", $html );
		$html = str_replace( ">", "&gt;", $html );

		if ( strpos( $html, "*" ) === 0 ) {
			$html = substr( $html, 1 );
		}
		$html = str_replace( " ", "&nbsp;", $html );
		$html = str_replace( "\n", "<br>", $html );

		return "<code>$html</code>";
	}

	/**
	 * Get countries iso code name map
	 *
	 * @return string[]
	 *
	 * @since 1.0.4
	 */
	public static function get_countries_iso_code_name_map() {

		return array(
			'AF' => 'Afghanistan',
			'AX' => 'Aland Islands',
			'AL' => 'Albania',
			'DZ' => 'Algeria',
			'AS' => 'American Samoa',
			'AD' => 'Andorra',
			'AO' => 'Angola',
			'AI' => 'Anguilla',
			'AQ' => 'Antarctica',
			'AG' => 'Antigua And Barbuda',
			'AR' => 'Argentina',
			'AM' => 'Armenia',
			'AW' => 'Aruba',
			'AU' => 'Australia',
			'AT' => 'Austria',
			'AZ' => 'Azerbaijan',
			'BS' => 'Bahamas',
			'BH' => 'Bahrain',
			'BD' => 'Bangladesh',
			'BB' => 'Barbados',
			'BY' => 'Belarus',
			'BE' => 'Belgium',
			'BZ' => 'Belize',
			'BJ' => 'Benin',
			'BM' => 'Bermuda',
			'BT' => 'Bhutan',
			'BO' => 'Bolivia',
			'BA' => 'Bosnia And Herzegovina',
			'BW' => 'Botswana',
			'BV' => 'Bouvet Island',
			'BR' => 'Brazil',
			'IO' => 'British Indian Ocean Territory',
			'BN' => 'Brunei Darussalam',
			'BG' => 'Bulgaria',
			'BF' => 'Burkina Faso',
			'BI' => 'Burundi',
			'KH' => 'Cambodia',
			'CM' => 'Cameroon',
			'CA' => 'Canada',
			'CV' => 'Cape Verde',
			'KY' => 'Cayman Islands',
			'CF' => 'Central African Republic',
			'TD' => 'Chad',
			'CL' => 'Chile',
			'CN' => 'China',
			'CX' => 'Christmas Island',
			'CC' => 'Cocos Island',
			'CO' => 'Colombia',
			'KM' => 'Comoros',
			'CG' => 'Congo',
			'CD' => 'Congo, Democratic Republic',
			'CK' => 'Cook Islands',
			'CR' => 'Costa Rica',
			'CI' => 'Cote D\'Ivoire',
			'HR' => 'Croatia',
			'CU' => 'Cuba',
			'CY' => 'Cyprus',
			'CZ' => 'Czech Republic',
			'DK' => 'Denmark',
			'DJ' => 'Djibouti',
			'DM' => 'Dominica',
			'DO' => 'Dominican Republic',
			'EC' => 'Ecuador',
			'EG' => 'Egypt',
			'SV' => 'El Salvador',
			'GQ' => 'Equatorial Guinea',
			'ER' => 'Eritrea',
			'EE' => 'Estonia',
			'ET' => 'Ethiopia',
			'FK' => 'Falkland Islands',
			'FO' => 'Faroe Islands',
			'FJ' => 'Fiji',
			'FI' => 'Finland',
			'FR' => 'France',
			'GF' => 'French Guiana',
			'PF' => 'French Polynesia',
			'TF' => 'French Southern Territories',
			'GA' => 'Gabon',
			'GM' => 'Gambia',
			'GE' => 'Georgia',
			'DE' => 'Germany',
			'GH' => 'Ghana',
			'GI' => 'Gibraltar',
			'GR' => 'Greece',
			'GL' => 'Greenland',
			'GD' => 'Grenada',
			'GP' => 'Guadeloupe',
			'GU' => 'Guam',
			'GT' => 'Guatemala',
			'GG' => 'Guernsey',
			'GN' => 'Guinea',
			'GW' => 'Guinea-Bissau',
			'GY' => 'Guyana',
			'HT' => 'Haiti',
			'HM' => 'Heard Island & Mcdonald Islands',
			'VA' => 'Holy See (Vatican City State)',
			'HN' => 'Honduras',
			'HK' => 'Hong Kong',
			'HU' => 'Hungary',
			'IS' => 'Iceland',
			'IN' => 'India',
			'ID' => 'Indonesia',
			'IR' => 'Iran, Islamic Republic Of',
			'IQ' => 'Iraq',
			'IE' => 'Ireland',
			'IM' => 'Isle Of Man',
			'IL' => 'Israel',
			'IT' => 'Italy',
			'JM' => 'Jamaica',
			'JP' => 'Japan',
			'JE' => 'Jersey',
			'JO' => 'Jordan',
			'KZ' => 'Kazakhstan',
			'KE' => 'Kenya',
			'KI' => 'Kiribati',
			'KR' => 'Korea',
			'KW' => 'Kuwait',
			'KG' => 'Kyrgyzstan',
			'LA' => 'Lao People\'s Democratic Republic',
			'LV' => 'Latvia',
			'LB' => 'Lebanon',
			'LS' => 'Lesotho',
			'LR' => 'Liberia',
			'LY' => 'Libyan Arab Jamahiriya',
			'LI' => 'Liechtenstein',
			'LT' => 'Lithuania',
			'LU' => 'Luxembourg',
			'MO' => 'Macao',
			'MK' => 'Macedonia',
			'MG' => 'Madagascar',
			'MW' => 'Malawi',
			'MY' => 'Malaysia',
			'MV' => 'Maldives',
			'ML' => 'Mali',
			'MT' => 'Malta',
			'MH' => 'Marshall Islands',
			'MQ' => 'Martinique',
			'MR' => 'Mauritania',
			'MU' => 'Mauritius',
			'YT' => 'Mayotte',
			'MX' => 'Mexico',
			'FM' => 'Micronesia',
			'MD' => 'Moldova',
			'MC' => 'Monaco',
			'MN' => 'Mongolia',
			'ME' => 'Montenegro',
			'MS' => 'Montserrat',
			'MA' => 'Morocco',
			'MZ' => 'Mozambique',
			'MM' => 'Myanmar',
			'NA' => 'Namibia',
			'NR' => 'Nauru',
			'NP' => 'Nepal',
			'NL' => 'Netherlands',
			'AN' => 'Netherlands',
			'NC' => 'New Caledonia',
			'NZ' => 'New Zealand',
			'NI' => 'Nicaragua',
			'NE' => 'Niger',
			'NG' => 'Nigeria',
			'NU' => 'Niue',
			'NF' => 'Norfolk Island',
			'MP' => 'Northern Mariana Islands',
			'NO' => 'Norway',
			'OM' => 'Oman',
			'PK' => 'Pakistan',
			'PW' => 'Palau',
			'PS' => 'Palestinian',
			'PA' => 'Panama',
			'PG' => 'Papua New Guinea',
			'PY' => 'Paraguay',
			'PE' => 'Peru',
			'PH' => 'Philippines',
			'PN' => 'Pitcairn',
			'PL' => 'Poland',
			'PT' => 'Portugal',
			'PR' => 'Puerto Rico',
			'QA' => 'Qatar',
			'RE' => 'Reunion',
			'RO' => 'Romania',
			'RU' => 'Russian',
			'RW' => 'Rwanda',
			'BL' => 'Saint Barthelemy',
			'SH' => 'Saint Helena',
			'KN' => 'Saint Kitts And Nevis',
			'LC' => 'Saint Lucia',
			'MF' => 'Saint Martin',
			'PM' => 'Saint Pierre And Miquelon',
			'VC' => 'Saint Vincent And Grenadines',
			'WS' => 'Samoa',
			'SM' => 'San Marino',
			'ST' => 'Sao Tome And Principe',
			'SA' => 'Saudi Arabia',
			'SN' => 'Senegal',
			'RS' => 'Serbia',
			'SC' => 'Seychelles',
			'SL' => 'Sierra Leone',
			'SG' => 'Singapore',
			'SK' => 'Slovakia',
			'SI' => 'Slovenia',
			'SB' => 'Solomon Islands',
			'SO' => 'Somalia',
			'ZA' => 'South Africa',
			'GS' => 'South Georgia And Sandwich Isl.',
			'ES' => 'Spain',
			'LK' => 'Sri Lanka',
			'SD' => 'Sudan',
			'SR' => 'Suriname',
			'SJ' => 'Svalbard And Jan Mayen',
			'SZ' => 'Swaziland',
			'SE' => 'Sweden',
			'CH' => 'Switzerland',
			'SY' => 'Syrian Arab Republic',
			'TW' => 'Taiwan',
			'TJ' => 'Tajikistan',
			'TZ' => 'Tanzania',
			'TH' => 'Thailand',
			'TL' => 'Timor-Leste',
			'TG' => 'Togo',
			'TK' => 'Tokelau',
			'TO' => 'Tonga',
			'TT' => 'Trinidad And Tobago',
			'TN' => 'Tunisia',
			'TR' => 'Turkey',
			'TM' => 'Turkmenistan',
			'TC' => 'Turks And Caicos Islands',
			'TV' => 'Tuvalu',
			'UG' => 'Uganda',
			'UA' => 'Ukraine',
			'AE' => 'United Arab Emirates',
			'GB' => 'United Kingdom',
			'US' => 'United States',
			'UM' => 'United States Outlying Islands',
			'UY' => 'Uruguay',
			'UZ' => 'Uzbekistan',
			'VU' => 'Vanuatu',
			'VE' => 'Venezuela',
			'VN' => 'Viet Nam',
			'VG' => 'Virgin Islands, British',
			'VI' => 'Virgin Islands, U.S.',
			'WF' => 'Wallis And Futuna',
			'EH' => 'Western Sahara',
			'YE' => 'Yemen',
			'ZM' => 'Zambia',
			'ZW' => 'Zimbabwe',
		);
	}

	/**
	 * Get Country name based on ISO code
	 *
	 * @param string $iso_code
	 *
	 * @return string
	 *
	 * @since 1.0.4
	 */
	public static function get_country_name_from_iso_code( $iso_code = '' ) {

		$map = self::get_countries_iso_code_name_map();

		return Helper::get_data( $map, $iso_code, '' );
	}

	/**
	 * Get country icon url
	 *
	 * @param string $country
	 *
	 * @return string
	 *
	 * @since 1.0.4
	 */
	public static function get_country_icon_url( $country = '' ) {

		if ( ! empty( $country ) ) {

			$icon = str_replace( ' ', '-', strtolower( self::get_country_name_from_iso_code( $country ) ) ) . '.svg';

			if ( file_exists( KC_US_PLUGIN_ASSETS_DIR . "/images/countries/{$icon}" ) ) {
				return KC_US_PLUGIN_ASSETS_DIR_URL . "/images/countries/{$icon}";
			} else {
				return KC_US_PLUGIN_ASSETS_DIR_URL . "/images/countries/earth.svg";
			}
		}

		return '';
	}

	/**
	 * Get Browser Name Icon map
	 *
	 * @return string[]
	 *
	 * @since 1.0.4
	 */
	public static function get_browser_name_icon_map() {
		return array(
			'Opera'      => 'opera.svg',
			'Opera Mini' => 'opera.svg',
			'Explorer'   => 'explorer.svg',
			'Firebird'   => 'firebird.svg',
			'Firefox'    => 'mozilla.svg',
			'Mozilla'    => 'mozilla.svg',
			'Safari'     => 'safari.svg',
			'iPhone'     => 'apple.svg',
			'iPod'       => 'apple.svg',
			'iPad'       => 'apple.svg',
			'Chrome'     => 'chrome.svg',
			'Android'    => 'android.svg',
			'GoogleBot'  => 'robot.svg',
			'cURL'       => 'curl.svg',
//			'WebTV'      => '',
//			'Edge'       => '',
//			'Konqueror'  => '',
//			'iCab'       => '',
//			'OmniWeb'    => '',
//			'Brave'      => '',
//			'Palemoon'   => '',
//			'Iceweasel'  => '',
//			'Shiretoko'  => '',
//			'Amaya'      => '',
//			'Lynx'       => '',
//			'Wget'       => '',
//			'UCBrowser'  => '',
		);
	}

	/**
	 * Get Browser icon
	 *
	 * @param string $browser
	 *
	 * @return string
	 *
	 * @since 1.0.4
	 */
	public static function get_browser_icon_by_name( $browser = '' ) {

		$map = self::get_browser_name_icon_map();

		return Helper::get_data( $map, $browser, 'default.svg' );
	}

	/**
	 * Get browser icon url
	 *
	 * @param string $browser
	 *
	 * @return string
	 *
	 * @since 1.0.4
	 */
	public static function get_browser_icon_url( $browser = '' ) {

		$browser = ! empty( $browser ) ? $browser : 'default';

		$icon = self::get_browser_icon_by_name( $browser );

		if ( empty( $icon ) ) {
			$icon = 'default.svg';
		}

		return KC_US_PLUGIN_ASSETS_DIR_URL . "/images/browsers/{$icon}";
	}

	/**
	 * Get device icon url
	 *
	 * @param string $device
	 *
	 * @return string
	 *
	 * @sicne 1.0.4
	 */
	public static function get_device_icon_url( $device = '' ) {

		$icon = ! empty( $device ) ? strtolower( $device ) . '.svg' : 'desktop.svg';

		return KC_US_PLUGIN_ASSETS_DIR_URL . "/images/devices/{$icon}";
	}

	/**
	 * Get Current Page URL
	 *
	 * @return string
	 *
	 * @since 1.2.4
	 */
	public static function get_current_page_url() {
		return '//' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}

	/**
	 * Get current page refresh URL
	 *
	 * @return string
	 *
	 * @since 1.2.4
	 */
	public static function get_current_page_refresh_url() {
		$current_page_url = self::get_current_page_url();

		return add_query_arg( array( 'refresh' => 1 ), $current_page_url );
	}

	/**
	 * Get stats filter URL
	 *
	 * @param array $args
	 *
	 * @since 1.4.7
	 */
	public static function get_stats_filter_url( $args = array() ) {

		$current_page_url = self::get_current_page_url();

		$args['refresh'] = 1;

		return add_query_arg( $args, $current_page_url );
	}

	/**
	 * Get elapsed time
	 *
	 * @param string $time
	 *
	 * @return string
	 *
	 * @since 1.2.4
	 */
	public static function get_elapsed_time( $time = '' ) {

		$time = time() - $time;

		$tokens = array(
			31536000 => 'Year',
			2592000  => 'Month',
			604800   => 'Week',
			86400    => 'Day',
			3600     => 'Hour',
			60       => 'Minute',
			1        => 'Second',
		);

		foreach ( $tokens as $unit => $text ) {

			if ( $time == 0 ) {
				return __( 'Just Now', 'url-shortify' );
			}

			if ( $time < $unit ) {
				continue;
			}

			$numberOfUnits = floor( $time / $unit );

			return $numberOfUnits . ' ' . $text . ( ( $numberOfUnits > 1 ) ? 's' : '' ) . ' ago';
		}
	}

	/**
	 * Get Website title from URL
	 *
	 * @param $url
	 *
	 * @return mixed
	 *
	 * @since 1.2.5
	 */
	public static function get_title_from_url( $url ) {

		if ( empty( $url ) ) {
			return '';
		}

		$str = file_get_contents( $url );

		if ( strlen( $str ) > 0 ) {
			$str = trim( preg_replace( '/\s+/', ' ', $str ) ); // supports line breaks inside <title>

			preg_match( '/\<title\>(.*)\<\/title\>/i', $str, $title ); // ignore case

			return substr( sanitize_text_field( $title[1] ), 0, 250 );
		}

		return $url;
	}

	/**
	 * Get the current domain
	 *
	 * @return string
	 *
	 * @since 1.3.8
	 */
	public static function get_the_current_domain() {

		if ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ) {
			$url = "https://";
		} else {
			$url = "http://";
		}
		// Append the host(domain name, ip) to the URL.
		$url .= $_SERVER['HTTP_HOST'];

		return $url;
	}

	/**
	 * Remove WWW & PORT from URL
	 *
	 * @param $url
	 *
	 * @return string|string[]|null
	 *
	 * @since 1.3.8
	 */
	public static function remove_www_and_port( $url ) {
		$url = parse_url( $url );

		$host = Helper::get_data( $url, 'host', '' );

		// Remove WWW.
		return preg_replace( '/^www\./', '', $host );
	}

	/**
	 * Remove HTTP & HTTPS from url
	 *
	 * @param $url
	 *
	 * @return string|string[]|null
	 *
	 * @since 1.3.8
	 */
	public static function remove_http( $url ) {
		//remove scheme and port
		$url = parse_url( $url );

		$host = Helper::get_data( $url, 'host', '' );

		//remove http & https
		return preg_replace( '/^http(s)\./', '', $host );
	}

	/**
	 * Get the clean domain
	 *
	 * @param $url
	 *
	 * @return string|string[]|null
	 *
	 * @since 1.3.8
	 */
	public static function get_the_clean_domain( $url ) {
		$url = 'https://' . ltrim( ltrim( $url, 'http://' ), 'https://' );

		return self::remove_www_and_port( $url );
	}

	/**
	 * Generate hash
	 *
	 * @param int $length
	 *
	 * @return false|string
	 *
	 * @since 1.5.1
	 */
	public static function generate_hash( $length = 16 ) {
		$str  = microtime() . uniqid( '', true );
		$salt = substr( md5( $str ), 0, $length );

		return substr( hash( "sha256", $str . $salt ), 0, 16 );
	}

	/**
	 * Get device map.
	 *
	 * @return array
	 *
	 * @since 1.7.4
	 */
	public static function get_device_map() {
		return array(
			'Desktop' => __( 'Desktop', 'url-shortify' ),
			'Tablet'  => __( 'Tablet', 'url-shortify' ),
			'Mobile'  => __( 'Mobile', 'url-shortify' ),
		);
	}

	/**
	 * Get browser map.
	 *
	 * @return array
	 *
	 * @since 1.7.4
	 */
	public static function get_browser_map() {
		return array(
			'Silk'              => __( 'Amazon Silk', 'url-shortify' ),
			'Android'           => __( 'Android', 'url-shortify' ),
			'Chrome'            => __( 'Chrome', 'url-shortify' ),
			'Edge'              => __( 'Edge', 'url-shortify' ),
			'Firefox'           => __( 'Firefox', 'url-shortify' ),
			'Internet Explorer' => __( 'Internet Explorer', 'url-shortify' ),
			'Opera'             => __( 'Opera', 'url-shortify' ),
			'Safari'            => __( 'Safari', 'url-shortify' ),
		);
	}

	/**
	 * Get OS map.
	 *
	 * @return array
	 *
	 * @since 1.7.4
	 */
	public static function get_os_map() {
		return array(
			'Android' => __( 'Android', 'url-shortify' ),
			'Linux'   => __( 'Linux', 'url-shortify' ),
			'Apple'   => __( 'Mac', 'url-shortify' ),
			'Windows' => __( 'Windows', 'url-shortify' ),
		);
	}

	/**
	 * Generate Random Hash.
	 *
	 * @return string
	 *
	 * @since 1.9.5
	 */
	public static function rest_random_hash() {
		if ( ! function_exists( 'openssl_random_pseudo_bytes' ) ) {
			return sha1( wp_rand() );
		}

		return bin2hex( openssl_random_pseudo_bytes( 20 ) );
	}

	/**
	 * Check if URL is valid or not.
	 *
	 * @param $url
	 * @param $allow_deep_link
	 *
	 * @return bool
	 */
	public static function validate_url( $url = '', $allow_deep_link = false ) {
		// Validate a normal URL
		if ( preg_match( "/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",
			$url ) ) {
			return true;
		}

		if ( $allow_deep_link ) {
			// Check if it's a deep link
			return preg_match( '/^[a-zA-Z]+:\/\/.+$/', $url ) === 1;
		}

		return false;
	}
}
