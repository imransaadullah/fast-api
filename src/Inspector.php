<?php

namespace FASTAPI;

use FASTAPI\ArrayMethods as ArrayMethods;
use FASTAPI\StringMethods as StringMethods;

/**
 * Class Inspector
 * 
 * Provides inspection capabilities for classes, properties, and methods using Reflection.
 */
class Inspector
{
	private static $instance = null; // Singleton instance

	protected $_class;

	protected $_meta = array(
		"class" => array(),
		"properties" => array(),
		"methods" => array()
	);

	protected $_properties = array();
	protected $_methods = array();

	/**
     * Private constructor to prevent direct instantiation.
     *
     * @param string $class The name of the class to inspect.
     */
	private function __construct($class)
	{
		$this->_class = $class;
	}

	/**
     * Prevent cloning of the instance.
     */
	private function __clone() {}

	/**
     * Prevent unserialization of the instance.
     */
	public function __wakeup()
	{
		throw new \Exception("Cannot unserialize a singleton.");
	}

	/**
     * Retrieves the singleton instance of the Inspector class.
     *
     * @param string $class The name of the class to inspect.
     * @return Inspector The singleton instance.
     */
	public static function getInstance($class)
	{
		if (self::$instance === null) {
			self::$instance = new self($class);
		}

		return self::$instance;
	}

	/**
     * Retrieves the comment block for the inspected class.
     *
     * @return string The comment block for the class.
     */
	protected function _getClassComment()
	{
		$reflection = new \ReflectionClass($this->_class);
		return $reflection->getDocComment();
	}

	/**
     * Retrieves the properties of the inspected class.
     *
     * @return array An array of ReflectionProperty objects representing the properties of the class.
     */
	protected function _getClassProperties()
	{
		$reflection = new \ReflectionClass($this->_class);
		return $reflection->getProperties();
	}

	/**
     * Retrieves the methods of the inspected class.
     *
     * @return array An array of ReflectionMethod objects representing the methods of the class.
     */
	protected function _getClassMethods()
	{
		$reflection = new \ReflectionClass($this->_class);
		return $reflection->getMethods();
	}

	/**
     * Retrieves the comment block for a specified property of the inspected class.
     *
     * @param string $property The name of the property.
     * @return string The comment block for the property.
     */
	protected function _getPropertyComment($property)
	{
		$reflection = new \ReflectionProperty($this->_class, $property);
		return $reflection->getDocComment();
	}

	/**
     * Retrieves the comment block for a specified method of the inspected class.
     *
     * @param string $method The name of the method.
     * @return string The comment block for the method.
     */
	protected function _getMethodComment($method)
	{
		$reflection = new \ReflectionMethod($this->_class, $method);
		return $reflection->getDocComment();
	}

	/**
     * Parses the comment block and extracts meta information.
     *
     * @param string $comment The comment block to parse.
     * @return array An array containing the extracted meta information.
     */
	protected function _parse($comment)
	{
		$meta = array();
		$pattern = "(@[a-zA-Z]+\s*[a-zA-Z0-9, ()_]*)";
		$matches = StringMethods::match($comment, $pattern);

		if ($matches != null) {
			foreach ($matches as $match) {
				$parts = ArrayMethods::clean(
					ArrayMethods::trim(
						StringMethods::split($match, "[\s]", 2)
					)
				);

				$meta[$parts[0]] = true;

				if (sizeof($parts) > 1) {
					$meta[$parts[0]] = ArrayMethods::clean(
						ArrayMethods::trim(
							StringMethods::split($parts[1], ",")
						)
					);
				}
			}
		}

		return $meta;
	}

	/**
     * Retrieves metadata for the inspected class.
     *
     * @return array Metadata for the class.
     */
	public function getClassMeta()
	{
		if (!isset($_meta["class"])) {
			$comment = $this->_getClassComment();

			if (!empty($comment)) {
				$_meta["class"] = $this->_parse($comment);
			} else {
				$_meta["class"] = null;
			}
		}

		return $_meta["class"];
	}

	/**
     * Retrieves the properties of the inspected class.
     *
     * @return array An array containing the names of the class properties.
     */
	public function getClassProperties()
	{
		if (!isset($_properties)) {
			$properties = $this->_getClassProperties();

			foreach ($properties as $property) {
				$_properties[] = $property->getName();
			}
		}

		return $_properties;
	}

	/**
     * Retrieves the methods of the inspected class.
     *
     * @return array An array containing the names of the class methods.
     */
	public function getClassMethods()
	{
		if (!isset($_methods)) {
			$methods = $this->_getClassMethods();

			foreach ($methods as $method) {
				$_methods[] = $method->getName();
			}
		}

		return $_methods;
	}

	/**
     * Retrieves metadata for a specified property of the inspected class.
     *
     * @param string $property The name of the property.
     * @return array Metadata for the property.
     */
	public function getPropertyMeta($property)
	{
		if (!isset($_meta["properties"][$property])) {
			$comment = $this->_getPropertyComment($property);

			if (!empty($comment)) {
				$_meta["properties"][$property] = $this->_parse($comment);
			} else {
				$_meta["properties"][$property] = null;
			}
		}

		return $_meta["properties"][$property];
	}

	/**
     * Retrieves metadata for a specified method of the inspected class.
     *
     * @param string $method The name of the method.
     * @return array Metadata for the method.
     */
	public function getMethodMeta($method)
	{
		if (!isset($_meta["actions"][$method])) {
			$comment = $this->_getMethodComment($method);

			if (!empty($comment)) {
				$_meta["methods"][$method] = $this->_parse($comment);
			} else {
				$_meta["methods"][$method] = null;
			}
		}

		return $_meta["methods"][$method];
	}
}
