<?php

namespace KaizenCoders\URL_Shortify;


class Request {
	/**
	 * Is installing WP
	 *
	 * @return boolean
	 */
	public function is_installing_wp() {
		return defined( 'WP_INSTALLING' );
	}

	/**
	 * Is admin
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_admin_backend() {
		return is_admin();
	}

	/**
	 * Is ajax
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_ajax() {
		return ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || defined( 'DOING_AJAX' );
	}

	/**
	 * Is rest
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_rest() {
		return defined( 'REST_REQUEST' );
	}

	/**
	 * Is cron
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_cron() {
		return ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) || defined( 'DOING_CRON' );
	}

	/**
	 * Is frontend
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_frontend() {
		return ( ! $this->is_admin_backend() || ! $this->is_ajax() ) && ! $this->is_cron() && ! $this->is_rest();
	}

	/**
	 * Is cli
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_cli() {
		return defined( 'WP_CLI' ) && WP_CLI;
	}


}