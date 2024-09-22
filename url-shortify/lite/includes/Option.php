<?php

namespace KaizenCoders\URL_Shortify;

class Option {
	/**
	 * @since 1.0.0
	 * @var string
	 *
	 */
	static $prefix = 'kc_us_';

	/**
	 * Get option
	 *
	 * @since 1.0.0
	 *
	 * @param string $default
	 *
	 * @param string $option
	 *
	 * @return bool|mixed|void|null
	 *
	 */
	public static function get( $option = '', $default = '' ) {

		if ( empty( $option ) ) {
			return null;
		}

		$option = self::$prefix . $option;

		return get_option( $option, $default );

	}

	/**
	 * Set Option
	 *
	 * @since 1.0.0
	 *
	 * @param string $value
	 * @param bool   $autoload
	 *
	 * @param string $option
	 *
	 * @return bool|null
	 *
	 */
	public static function set( $option = '', $value = '', $autoload = true ) {

		if ( empty( $option ) ) {
			return null;
		}

		$option = self::$prefix . $option;

		return update_option( $option, $value, $autoload );
	}

	/**
	 * Add Option
	 *
	 * @since 1.0.0
	 *
	 * @param string $value
	 *
	 * @param string $option
	 *
	 * @return bool|null
	 *
	 */
	public static function add( $option = '', $value = '', $autoload = false ) {

		if ( empty( $option ) ) {
			return null;
		}

		$option = self::$prefix . $option;

		return add_option( $option, $value, '', $autoload );

	}

	/**
	 * Delete option
	 *
	 * @since 1.0.0
	 *
	 * @param string $option
	 *
	 * @return bool|null
	 *
	 */
	public static function delete( $option = null ) {

		if ( empty( $option ) ) {
			return null;
		}

		$option = self::$prefix . $option;

		return delete_option( $option );
	}

}
