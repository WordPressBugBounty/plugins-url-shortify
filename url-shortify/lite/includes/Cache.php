<?php

namespace KaizenCoders\URL_Shortify;

/**
 * Class Cache
 *
 * @since 1.0.0
 */
class Cache {
	/**
	 * Cache version
	 *
	 * @since 1.2.4
	 * @var string
	 *
	 */
	static $version = '1.0';

	/**
	 * Cache Prefix
	 *
	 * @since 1.0.0
	 * @var string
	 *
	 */
	static $prefix = 'kc_us_';

	/** @var bool */
	static $enabled = true;

	/**
	 * Get prefix
	 *
	 * @since 1.2.9
	 * @return string
	 *
	 */
	static function get_prefix() {
		return self::$prefix . str_replace( '.', '', self::$version ) . '_';
	}

	/**
	 * @return mixed|void
	 */
	static function get_default_transient_expiration() {
		return apply_filters( 'cache_default_expiration', 10 );
	}

	/**
	 * @param     $key
	 * @param     $value
	 * @param int $expiration Seconds
	 *
	 * @return bool
	 */
	static function set_transient( $key, $value, $expiration = 3600 ) {
		if ( ! self::$enabled ) {
			return false;
		}
		if ( ! $expiration ) {
			$expiration = self::get_default_transient_expiration();
		}

		return set_transient( self::get_prefix() . 'cache_' . $key, $value, $expiration );
	}

	/**
	 * @since 1.0.0
	 *
	 * @param string $key
	 *
	 * @return bool|mixed
	 *
	 */
	static function get_transient( $key ) {
		if ( ! self::$enabled ) {
			return false;
		}

		return get_transient( self::get_prefix() . 'cache_' . $key );
	}

	/**
	 * @since 1.0.0
	 *
	 * @param $key
	 *
	 */
	static function delete_transient( $key ) {
		delete_transient( self::get_prefix() . 'cache_' . $key );
	}

	/**
	 * Only sets if key is not falsy
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $value
	 * @param string $group
	 *
	 * @param string $key
	 */
	static function set( $key, $value, $group ) {
		if ( ! $key ) {
			return;
		}

		wp_cache_set( (string) $key, $value, self::get_prefix() . $group );
	}

	/**
	 * Only gets if key is not falsy
	 *
	 * @since 1.0.0
	 *
	 * @param       $group
	 * @param false $force
	 * @param null  $found
	 *
	 * @param       $key
	 *
	 * @return false|mixed
	 *
	 */
	static function get( $key, $group, $force = false, &$found = null ) {
		if ( ! $key ) {
			return false;
		}

		return wp_cache_get( (string) $key, self::get_prefix() . $group, $force, $found );
	}

	/**
	 * @since 1.0.0
	 *
	 * @param string $group
	 *
	 * @param string $key
	 *
	 * @return bool
	 *
	 */
	static function exists( $key, $group ) {
		if ( ! $key ) {
			return false;
		}
		$found = false;

		wp_cache_get( (string) $key, self::get_prefix() . $group, false, $found );

		return $found;
	}


	/**
	 * Only deletes if key is not falsy
	 *
	 * @since 1.0.0
	 *
	 * @param string $group
	 *
	 * @param string $key
	 */
	static function delete( $key, $group ) {
		if ( ! $key ) {
			return;
		}

		wp_cache_delete( (string) $key, self::get_prefix() . $group );
	}

	/**
	 * Generate key
	 *
	 * @since 1.4.7
	 *
	 * @param string $string
	 *
	 * @return string
	 *
	 */
	static function generate_key( $string = '' ) {

		if ( empty( $string ) ) {
			return 'dummy';
		}

		return md5( $string );
	}

}
