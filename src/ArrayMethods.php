<?php

namespace FASTAPI;

/**
 * Class ArrayMethods
 * 
 * Provides static methods for manipulating arrays.
 */
class ArrayMethods
{
    private function __construct()
    {
        // do nothing
    }

    private function __clone()
    {
        // do nothing
    }

    /**
     * Removes any empty or false values from the array and returns the cleaned array.
     *
     * @param array $array The array to be cleaned
     * @return array The cleaned array
     */
    public static function clean($array)
    {
        return array_filter($array, function ($item) {
            return !empty($item);
        });
    }
    
    /**
     * Trims whitespace from each value in the array and returns the trimmed array.
     *
     * @param array $array The array to be trimmed
     * @return array The trimmed array
     */
    public static function trim($array)
    {
        return array_map(function ($item) {
            return trim($item);
        }, $array);
    }

    /**
     * Recursively converts an array into a stdClass object.
     * If a value within the array is also an array, it will be converted to an object as well.
     *
     * @param array $array The array to be converted
     * @return object The converted stdClass object
     */
    public static function toObject($array)
    {
        $result = new \stdClass();
        
        foreach ($array as $key => $value)
        {
            if (is_array($value))
            {
                $result->{$key} = self::toObject($value);
            }
            else
            {
                $result->{$key} = $value;
            }
        }

        return $result;
    }

    /**
     * Recursively flattens a multi-dimensional array into a single-dimensional array.
     *
     * @param array $array The array to be flattened
     * @param array $return The result array (used in recursion)
     * @return array The flattened array
     */
    public static function flatten($array, $return = array())
    {
        foreach ($array as $key => $value)
        {
            if (is_array($value) || is_object($value))
            {
                $return = self::flatten($value, $return);
            }
            else
            {
                $return[] = $value;
            }
        }

        return $return;
    }

    /**
     * Returns the first element of the array.
     *
     * @param array $array The array to retrieve the first element from
     * @return mixed|null The first element of the array, or null if the array is empty
     */
    public static function first($array)
    {
        if (is_array($array) && !empty($array)) {
            reset($array);
            return current($array);
        }

        return null;
    }

    /**
     * Returns the last element of the array.
     *
     * @param array $array The array to retrieve the last element from
     * @return mixed|null The last element of the array, or null if the array is empty
     */
    public static function last($array)
    {
        if (is_array($array) && !empty($array)) {
            return end($array);
        }

        return null;
    }

    /**
     * Retrieves the value associated with the given key from the array.
     * If the key exists, the corresponding value is returned; otherwise, the default value is returned.
     *
     * @param array $array The array to retrieve the value from
     * @param mixed $key The key to look for
     * @param mixed $default The default value to return if the key doesn't exist (optional)
     * @return mixed The value associated with the key, or the default value if the key doesn't exist
     */
    public static function get($array, $key, $default = null)
    {
        if (is_array($array) && array_key_exists($key, $array)) {
            return $array[$key];
        }

        return $default;
    }

    /**
     * Checks if the given key exists in the array.
     *
     * @param array $array The array to check for the key
     * @param mixed $key The key to check
     * @return bool True if the key exists in the array, false otherwise
     */
    public static function has($array, $key)
    {
        return is_array($array) && array_key_exists($key, $array);
    }

    /**
     * Removes the element with the specified key from the array.
     * If the key doesn't exist, the array remains unchanged.
     *
     * @param array $array The array to remove the element from
     * @param mixed $key The key of the element to remove
     * @return array The modified array
     */
    public static function remove($array, $key)
    {
        if (is_array($array) && array_key_exists($key, $array)) {
            unset($array[$key]);
        }

        return $array;
    }

    /**
     * Returns an array of all the keys in the given array.
     *
     * @param array $array The array to get the keys from
     * @return array An array of keys
     */
    public static function keys($array)
    {
        if (is_array($array)) {
            return array_keys($array);
        }

        return [];
    }
}
