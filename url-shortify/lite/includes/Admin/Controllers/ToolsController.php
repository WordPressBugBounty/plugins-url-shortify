<?php

namespace KaizenCoders\URL_Shortify\Admin\Controllers;

use KaizenCoders\URL_Shortify\Common\Import;
use KaizenCoders\URL_Shortify\Helper;

class ToolsController extends BaseController {
	/**
	 * ToolsController constructor.
	 *
	 * @since 1.1.9
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Render Tools
	 *
	 * @since 1.1.9
	 */
	public function render() {
		$template_data['links'] = $this->get_tabs();

		include_once KC_US_ADMIN_TEMPLATES_DIR . '/tools.php';
	}

	/**
	 * Get links
	 *
	 * @since 1.3.4
	 */
	public function get_tabs() {
		$tabs['import'] = [
			'title' => __( 'Import', 'url-shortify' ),
			'link'  => add_query_arg( [ 'tab' => 'import' ], admin_url( 'admin.php?page=us_tools' ) ),
		];

		$tabs['trim_clicks'] = [
			'title' => __( 'Trim Clicks', 'url-shortify' ),
			'link'  => add_query_arg( [ 'tab' => 'trim_clicks' ], admin_url( 'admin.php?page=us_tools' ) ),
		];

		// Add more links.
		$tabs = apply_filters( 'kc_us_filter_tools_links', $tabs );

		$tabs['rest-api'] = [
			'title' => __( 'REST API', 'url-shortify' ),
			'link'  => add_query_arg( [ 'tab' => 'rest-api' ], admin_url( 'admin.php?page=us_tools' ) ),
		];

		$tabs['awesome_products'] = [
			'title' => __( 'Other Awesome Products', 'url-shortify' ),
			'link'  => add_query_arg( [ 'tab' => 'awesome_products' ], admin_url( 'admin.php?page=us_tools' ) ),
		];

		return $tabs;
	}

}
