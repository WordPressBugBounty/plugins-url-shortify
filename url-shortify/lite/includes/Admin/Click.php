<?php

namespace KaizenCoders\URL_Shortify\Admin;

use KaizenCoders\URL_Shortify\Admin\DB\Clicks;
use KaizenCoders\URL_Shortify\Common\Browser;
use KaizenCoders\URL_Shortify\Common\GeoLocation;
use KaizenCoders\URL_Shortify\Helper;

/**
 * Class Click
 *
 * @since   1.0.2
 * @package KaizenCoders\URL_Shortify\Admin
 *
 */
class Click {

	/**
	 * @since 1.0.2
	 * @var array|int
	 *
	 */
	public $link_id = 0;

	/**
	 * @since 1.0.2
	 * @var Clicks|null
	 *
	 */
	public $db = null;

	/**
	 * @since 1.0.2
	 * @var string
	 *
	 */
	public $slug = null;

	/**
	 * @since 1.0.2
	 * @var string|null
	 *
	 */
	public $uri = null;

	/**
	 * @since 1.0.2
	 * @var null
	 *
	 */
	public $host = null;

	/**
	 * @since 1.0.2
	 * @var null
	 *
	 */
	public $referer = null;

	/**
	 * @since 1.0.2
	 * @var int
	 *
	 */
	public $is_first_click = 0;

	/**
	 * @since 1.0.2
	 * @var int
	 *
	 */
	public $is_robot = 0;

	/**
	 * @since 1.0.2
	 * @var null
	 *
	 */
	public $user_agent = null;

	/**
	 * @since 1.0.2
	 * @var null
	 *
	 */
	public $os = null;

	/**
	 * @since 1.0.2
	 * @var null
	 *
	 */
	public $device = null;

	/**
	 * @since 1.0.2
	 * @var null
	 *
	 */
	public $browser_type = null;

	/**
	 * @since 1.0.2
	 * @var null
	 *
	 */
	public $browser_version = null;

	/**
	 * @since 1.0.2
	 * @var null
	 *
	 */
	public $visitor_id = null;

	/**
	 * @since 1.0.2
	 * @var null
	 *
	 */
	public $country = null;

	/**
	 * @since 1.0.2
	 * @var null
	 *
	 */
	public $ip = null;

	/**
	 * @since 1.8.0
	 * @var array
	 *
	 */
	public $link_data = [];

	/**
	 * @since 1.0.2
	 * @var null
	 *
	 */
	public $created_at = null;

	/**
	 * Click constructor.
	 *
	 * @since 1.0.2
	 *
	 * @param null  $slug
	 * @param array $link_data
	 *
	 * @param null  $link_id
	 */
	public function __construct( $link_id = null, $slug = null, $link_data = [] ) {

		$this->link_id   = $link_id;
		$this->slug      = $slug;
		$this->db        = new Clicks();
		$this->link_data = $link_data;

		$browser = new Browser();

		$this->is_first_click = 0;

		$ip = Helper::get_ip();

		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$ip = '::1';
		}

		$this->ip = $ip;

		try {

			$geo_data = GeoLocation::geolocate_ip( $this->ip, true );

			$country = Helper::get_data( $geo_data, 'country', '' );

		} catch ( \Exception $e ) {
			$country = null;
		}

		$this->country = $country;

		$this->referer = Helper::get_data( $_SERVER, 'HTTP_REFERER', '', true );
		$this->uri     = Helper::get_data( $_SERVER, 'REQUEST_URI', '', true );

		$this->user_agent = $browser->getUserAgent();

		$this->browser_type    = $browser->getBrowser();
		$this->browser_version = $browser->getVersion();
		$this->host            = gethostbyaddr( $this->ip );

		$this->is_robot = $browser->isRobot();
		$this->os       = $browser->getPlatform();

		$device = 'Desktop';
		if ( $browser->isMobile() ) {
			$device = 'Mobile';
		} elseif ( $browser->isTablet() ) {
			$device = 'Tablet';
		}

		$this->device = $device;

	}

	/**
	 * Track link clicks
	 *
	 * @since 1.0.2
	 */
	public function track() {
		// Set First Click.
		$cookie_name        = 'kc_us_click_' . $this->link_id;
		$cookie_expire_time = time() + 60 * 60 * 24 * 30; // Expire in 30 days
		if ( ! isset( $_COOKIE[ $cookie_name ] ) ) {
			setcookie( $cookie_name, $this->slug, $cookie_expire_time, '/' );
			$this->is_first_click = 1;
		}

		// Set Visitor Cookie
		$visitor_cookie             = 'kc_us_visitor';
		$visitor_cookie_expire_time = time() + 60 * 60 * 24 * 365; // Expire in 1 year
		if ( ! isset( $_COOKIE[ $visitor_cookie ] ) ) {
			$this->visitor_id = $this->generate_unique_visitor_id();
			setcookie( $visitor_cookie, $this->visitor_id, $visitor_cookie_expire_time, '/' );
		} else {
			$this->visitor_id = $_COOKIE[ $visitor_cookie ];
		}

		$this->created_at = Helper::get_current_date_time();

		// Saving.
		$click_id = $this->save();

		$total_clicks  = (int) Helper::get_data( $this->link_data, 'total_clicks', 0 );
		$unique_clicks = (int) Helper::get_data( $this->link_data, 'unique_clicks', 0 );

		if ( 1 == $this->is_first_click ) {
			$unique_clicks = $unique_clicks + 1;
		}

		if ( ! empty( $this->link_id ) ) {
			// Update total clicks and unique clicks.
			US()->db->links->update( $this->link_id, [
				'total_clicks'  => $total_clicks + 1,
				'unique_clicks' => $unique_clicks,
			] );
		}

		return $click_id;
	}

	/**
	 * Generate unique id
	 *
	 * @since 1.0.2
	 * @return string
	 *
	 */
	public function generate_unique_visitor_id() {
		return uniqid();
	}

	/**
	 * Prepare data to save.
	 *
	 * @since 1.7.4
	 * @return mixed|void
	 *
	 */
	public function get_prepared_data() {

		$defaults_data = $this->db->get_column_defaults();

		$data = [];
		foreach ( $defaults_data as $column => $value ) {
			if ( property_exists( $this, $column ) ) {
				$data[ $column ] = $this->$column;
			} else {
				$data[ $column ] = $value;
			}
		}

		return apply_filters( 'kc_us_clicks_data', $data, $this->link_id );
	}

	/**
	 * Save tracking data
	 *
	 * @since 1.0.2
	 */
	public function save() {
		$data = $this->get_prepared_data();

		return $this->db->save( $data );
	}

}
