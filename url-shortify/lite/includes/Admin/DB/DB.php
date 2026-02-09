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
	 * @since 1.9.1
	 * @var Clicks_Rotations
	 *
	 */
	public $clicks_rotations;

	/**
	 * @since 1.9.5
	 * @var API_Keys
	 *
	 */
	public $api_keys;

	/**
	 * @since 1.11.5
	 * @var Object|Tags
	 *
	 */
	public $tags;

	/**
	 * @since 1.11.5
	 * @var Object|Links_Tags
	 *
	 */
	public $links_tags;

	/**
	 * @since 1.12.2
	 * @var Object|Favorites_Links $this
	 *
	 */
	public $favorites_links;

	/**
	 * constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		/* @var Object|Clicks $this */
		$this->clicks = new Clicks();
		/* @var Object|Links $this */

		$this->links = new Links();

		/* @var Object|Groups $this */
		$this->groups = new Groups();

		/* @var Object|Links_Groups $this */
		$this->links_groups = new Links_Groups();

		/* @var Object|Domains $this */
		$this->domains = new Domains();

		/* @var Object|UTM_Presets $this */
		$this->utm_presets = new UTM_Presets();

		/* @var Object|Tracking_Pixels $this */
		$this->tracking_pixels = new Tracking_Pixels();

		/* @var Object|Clicks_Rotations $this */
		$this->clicks_rotations = new Clicks_Rotations();

		/* @var Object|API_Keys $this */
		$this->api_keys = new API_Keys();

		/* @var Object|Tags $this */
		$this->tags = new Tags();

		/* @var Object|Links_Tags $this */
		$this->links_tags = new Links_Tags();

		/* @var Object|Favorites_Links $this */
		$this->favorites_links = new Favorites_Links();
	}
}
