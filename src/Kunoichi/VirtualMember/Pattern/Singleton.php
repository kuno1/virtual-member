<?php

namespace Kunoichi\VirtualMember\Pattern;

/**
 * Singleton pattern.
 *
 * @package kvm
 */
abstract class Singleton {

	/**
	 * @var static[] Instances.
	 */
	private static $instances = [];

	/**
	 * Constructor.
	 */
	final protected function __construct() {
		$this->init();
	}

	/**
	 * Execute inside constructor.
	 */
	protected function init() {
		// Do something.
	}

	/**
	 * Get instance.
	 *
	 * @return static
	 */
	public static function get_instance() {
		$class_name = get_called_class();
		if ( ! isset( self::$instances[ $class_name ] ) ) {
			self::$instances[ $class_name ] = new $class_name();
		}
		return self::$instances[ $class_name ];
	}
}
