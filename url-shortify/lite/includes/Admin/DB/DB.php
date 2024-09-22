<?php

namespace KaizenCoders\URL_Shortify\Admin\DB;

/**
 * class
 *
 * @since 1.0.0
 */
class DB {
	/**
	 *
	 * @since 1.0.0
	 *
	 * @var Object|Links
	 *
	 */
	public $links;

	/**
	 * @since 1.0.0
	 * @var Object|Clicks
	 *
	 */
	public $clicks;

	/**
	 * @since 1.1.3
	 * @var Object|Groups
	 *
	 */
	public $groups;

	/**
	 * @since 1.1.3
	 * @var Object|Links_Groups
	 *
	 */
	public $links_groups;

	/**
	 * @var Object|Domains
	 */
	public $domains;

	/**
	 * @var Object|UTM_Presets
	 */
	public $utm_presets;

	/**
	 * @var object Tracking_Pixels
	 */
	public $tracking_pixels;

	/**
	 * @var Clicks_Rotations
	 *
	 * @since 1.9.1
	 */
	public $clicks_rotations;

	/**
	 * @var API_Keys
	 *
	 * @since 1.9.5
	 */
	public $api_keys;

	/**
	 * constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->clicks           = new Clicks();
		$this->links            = new Links();
		$this->groups           = new Groups();
		$this->links_groups     = new Links_Groups();
		$this->domains          = new Domains();
		$this->utm_presets      = new UTM_Presets();
		$this->tracking_pixels  = new Tracking_Pixels();
		$this->clicks_rotations = new Clicks_Rotations();
		$this->api_keys         = new API_Keys();
	}
}
