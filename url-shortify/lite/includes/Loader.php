<?php

/**
 * Register all actions and filters for the plugin
 *
 * @link       https://kaizencoders.com
 * @since      1.0.0
 *
 * @package    Url_Shortify
 * @subpackage Url_Shortify/includes
 */

namespace KaizenCoders\URL_Shortify;

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    Url_Shortify
 * @subpackage Url_Shortify/includes
 * @author     KaizenCoders <hello@kaizencoders.com>
 */
class Loader {
	/**
	 * The array of actions registered with WordPress.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array $actions The actions registered with WordPress to fire when the plugin loads.
	 */
	protected $actions;

	/**
	 * The array of filters registered with WordPress.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array $filters The filters registered with WordPress to fire when the plugin loads.
	 */
	protected $filters;

	/**
	 * The array of classes to intitialize
	 *
	 * @since 1.0.0
	 * @var array
	 *
	 */
	protected $classes;

	/**
	 * Initialize the collections used to maintain the actions and filters.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->actions = [];
		$this->filters = [];
		$this->classes = [];

	}

	/**
	 * Add a new action to the collection to be registered with WordPress.
	 *
	 * @since    1.0.0
	 *
	 * @param object $component A reference to the instance of the object on which the action is defined.
	 * @param string $callback  The name of the function definition on the $component.
	 * @param int      Optional    $priority         The priority at which the function should be fired.
	 * @param int      Optional    $accepted_args    The number of arguments that should be passed to the $callback.
	 *
	 * @param string $hook      The name of the WordPress action that is being registered.
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a new filter to the collection to be registered with WordPress.
	 *
	 * @since    1.0.0
	 *
	 * @param object $component A reference to the instance of the object on which the filter is defined.
	 * @param string $callback  The name of the function definition on the $component.
	 * @param int      Optional    $priority         The priority at which the function should be fired.
	 * @param int      Optional    $accepted_args    The number of arguments that should be passed to the $callback.
	 *
	 * @param string $hook      The name of the WordPress filter that is being registered.
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * A utility function that is used to register the actions and hooks into a single
	 * collection.
	 *
	 * @since    1.0.0
	 * @access   private
	 *
	 * @param string $hook      The name of the WordPress filter that is being registered.
	 * @param object $component A reference to the instance of the object on which the filter is defined.
	 * @param string $callback  The name of the function definition on the $component.
	 * @param int      Optional    $priority         The priority at which the function should be fired.
	 * @param int      Optional    $accepted_args    The number of arguments that should be passed to the $callback.
	 *
	 * @param array  $hooks     The collection of hooks that is being registered (that is, actions or filters).
	 *
	 * @return   array                                   The collection of actions and filters registered with WordPress.
	 */
	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {

		$hooks[] = [
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		];

		return $hooks;

	}

	/**
	 * @since 1.0.0
	 *
	 * @param $class
	 *
	 */
	public function add_class( $class ) {
		$this->classes[] = $class;
	}

	/**
	 * Register the filters and actions with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {

		foreach ( $this->filters as $hook ) {
			\add_filter( $hook['hook'], [
				$hook['component'],
				$hook['callback'],
			], $hook['priority'], $hook['accepted_args'] );
		}

		foreach ( $this->actions as $hook ) {
			\add_action( $hook['hook'], [
				$hook['component'],
				$hook['callback'],
			], $hook['priority'], $hook['accepted_args'] );
		}

		foreach ( $this->classes as $class ) {

			if ( class_exists( $class ) ) {
				$object = new $class();
				$object->init();
			}
		}

	}

}
