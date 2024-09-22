<?php

namespace KaizenCoders\URL_Shortify\Common;

class Notices {
	/**
	 * Show Notice
	 *
	 * @param string $message
	 * @param string $status
	 * @param bool $is_dismissible
	 *
	 * @since 1.0.0
	 */
	public function show_notice( $message = '', $status = '', $is_dismissible = true ) {

		$class = 'notice notice-success';
		if ( 'error' === $status ) {
			$class = 'notice notice-error';
		}

		if ( $is_dismissible ) {
			$class .= ' is-dismissible';
		}

		echo "<div class='{$class}'><p>{$message}</p></div>";
	}

	/**
	 * Success Message
	 *
	 * @param string $message
	 * @param bool $is_dismisible
	 *
	 * @since 1.0.0
	 */
	public function success( $message = '', $is_dismisible = true ) {
		$this->show_notice( $message, 'success', $is_dismisible );
	}

	/**
	 * Error Message
	 *
	 * @param string $message
	 * @param bool $is_dismisible
	 *
	 * @since 1.0.0
	 */
	public function error( $message = '', $is_dismisible = true ) {
		$this->show_notice( $message, 'error', $is_dismisible );
	}

	/**
	 * Warning Message
	 *
	 * @param string $message
	 * @param bool $is_dismisible
	 *
	 * @since 1.0.0
	 */
	public function warning( $message = '', $is_dismisible = true ) {
		$this->show_notice( $message, 'warning', $is_dismisible );
	}

	/**
	 * Info Message
	 *
	 * @param string $message
	 * @param bool $is_dismisible
	 *
	 * @since 1.0.0
	 */
	public function info( $message = '', $is_dismisible = true ) {
		$this->show_notice( $message, 'info', $is_dismisible );
	}

}
