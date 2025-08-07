<?php

namespace KaizenCoders\URL_Shortify\Admin\Controllers;

class ResourcesController extends BaseController {
	/**
	 * ResourcesController constructor.
	 *
	 * @since 1.10.7
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Render Tools
	 *
	 * @since 1.10.7
	 */
	public function render() {
		include_once KC_US_ADMIN_TEMPLATES_DIR . '/resources.php';
	}
}
