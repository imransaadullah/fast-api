<?php

namespace FASTAPI;

/**
 * Class StringMethods
 * 
 * Provides various static methods for manipulating strings.
 */
class StringMethods
{
    // Properties

    /**
     * Default delimiter used in regular expressions.
     * @var string
     */
    private static $_delimiter = "#";

    /**
     * Default delimiter used in regular expressions.
     * @var array
     */
    private static $_singular = array(
        "(matr)ices$" => "\\1ix",
        "(vert|ind)ices$" => "\\1ex",
        "^(ox)en" => "\\1",
        "(alias)es$" => "\\1",
        "([octop|vir])i$" => "\\1us",
        "(cris|ax|test)es$" => "\\1is",
        "(shoe)s$" => "\\1",
        "(o)es$" => "\\1",
        "(bus|campus)es$" => "\\1",
        "([m|l])ice$" => "\\1ouse",
        "(x|ch|ss|sh)es$" => "\\1",
        "(m)ovies$" => "\\1\\2ovie",
        "(s)eries$" => "\\1\\2eries",
        "([^aeiouy]|qu)ies$" => "\\1y",
        "([lr])ves$" => "\\1f",
        "(tive)s$" => "\\1",
        "(hive)s$" => "\\1",
        "([^f])ves$" => "\\1fe",
        "(^analy)ses$" => "\\1sis",
        "((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$" => "\\1\\2sis",
        "([ti])a$" => "\\1um",
        "(p)eople$" => "\\1\\2erson",
        "(m)en$" => "\\1an",
        "(s)tatuses$" => "\\1\\2tatus",
        "(c)hildren$" => "\\1\\2hild",
        "(n)ews$" => "\\1\\2ews",
        "([^u])s$" => "\\1"
    );

    /**
     * Default delimiter used in regular expressions.
     * @var array
     */
    private static $_plural = array(
        "^(ox)$" => "\\1\\2en",
        "([m|l])ouse$" => "\\1ice",
        "(matr|vert|ind)ix|ex$" => "\\1ices",
        "(x|ch|ss|sh)$" => "\\1es",
        "([^aeiouy]|qu)y$" => "\\1ies",
        "(hive)$" => "\\1s",
        "(?:([^f])fe|([lr])f)$" => "\\1\\2ves",
        "sis$" => "ses",
        "([ti])um$" => "\\1a",
        "(p)erson$" => "\\1eople",
        "(m)an$" => "\\1en",
        "(c)hild$" => "\\1hildren",
        "(buffal|tomat)o$" => "\\1\\2oes",
        "(bu|campu)s$" => "\\1\\2ses",
        "(alias|status|virus)" => "\\1es",
        "(octop)us$" => "\\1i",
        "(ax|cris|test)is$" => "\\1es",
        "s$" => "s",
        "$" => "s"
    );

    // Methods...

    /**
     * Prevents direct instantiation of this class.
     * @access private
     */
    private function __construct()
    {
        // do nothing
    }

    /**
     * Prevents cloning of this class.
     * @access private
     */
    private function __clone()
    {
        // do nothing
    }

    /**
     * Normalizes a pattern by enclosing it with the default delimiter.
     * @param string $pattern The pattern to normalize.
     * @return string The normalized pattern.
     */
    private static function _normalize($pattern)
    {
        return self::$_delimiter . trim($pattern, self::$_delimiter) . self::$_delimiter;
    }

    /**
     * Returns the current delimiter used in regular expressions.
     *
     * @return string The delimiter used in regular expressions.
     */
    public static function getDelimiter()
    {
        return self::$_delimiter;
    }
    
    /**
     * Sets the delimiter to be used in regular expressions.
     *
     * @param string $delimiter The new delimiter to be set.
     * @return void
     */
    public static function setDelimiter($delimiter)
    {
        self::$_delimiter = $delimiter;
    }

    /**
     * Finds all matches of a pattern in a string.
     *
     * @param string $string The input string to search within.
     * @param string $pattern The regular expression pattern to search for.
     * @return array|null An array containing all matches found or null if no matches are found.
     */
    public static function match($string, $pattern)
    {
        preg_match_all(self::_normalize($pattern), $string, $matches, PREG_PATTERN_ORDER);
        if (!empty($matches[1])) {
            return $matches[1];
        }
        if (!empty($matches[0])) {
            return $matches[0];
        }
        return null;
    }

    /**
     * Splits a string by a regular expression pattern.
     *
     * @param string $string The string to split.
     * @param string $pattern The regular expression pattern to split by.
     * @param int|null $limit (optional) Maximum number of splits. Defaults to null.
     * @return array An array containing the split parts of the string.
     */
    public static function split($string, $pattern, $limit = null)
    {
        $flags = PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE;
        return preg_split(self::_normalize($pattern), $string, $limit, $flags);
    }

    /**
     * Replaces characters in a string based on a mask.
     *
     * @param string $string The input string to sanitize.
     * @param string|array $mask The mask used for sanitization.
     * @return string The sanitized string.
     */
    public static function sanitize($string, $mask)
    {
        if (is_array($mask)) {
            $parts = $mask;
        } else if (is_string($mask)) {
            $parts = str_split($mask);
        } else {
            return $string;
        }

        foreach ($parts as $part) {
            $normalized = self::_normalize("\\{$part}");
            $string = preg_replace(
                "{$normalized}m",
                "\\{$part}",
                $string
            );
        }

        return $string;
    }

    /**
     * Returns unique characters from a string.
     *
     * @param string $string The input string.
     * @return string A string containing unique characters from the input string.
     */
    public static function unique($string)
    {
        $unique = "";
        $parts = str_split($string);

        foreach ($parts as $part) {
            if (!strstr($unique, $part)) {
                $unique .= $part;
            }
        }

        return $unique;
    }

    /**
     * Returns the position of the first occurrence of a substring in a string.
     *
     * @param string $string The input string.
     * @param string $substring The substring to search for.
     * @param int|null $offset (optional) The offset to start searching from. Defaults to null.
     * @return int The position of the substring, or -1 if not found.
     */
    public static function indexOf($string, $substring, $offset = null)
    {
        $position = strpos($string, $substring, $offset);
        if (!is_int($position)) {
            return -1;
        }
        return $position;
    }

    
    public static function singular($string)
    {
        $result = $string;
        
        foreach (self::$_singular as $rule => $replacement)
        {
            $rule = self::_normalize($rule);
        
            if (preg_match($rule, $string))
            {
                $result = preg_replace($rule, $replacement, $string);
                break;
            }
        }
        
        return $result;
    }

    public static function plural($string)
    {
        $result = $string;
        
        foreach (self::$_plural as $rule => $replacement)
        {
            $rule = self::_normalize($rule);
        
            if (preg_match($rule, $string))
            {
                $result = preg_replace($rule, $replacement, $string);
                break;
            }
        }
        
        return $result;
    }

    public static function toCamelCase($str, $delimeter = '-') {
        $words = explode($delimeter, $str);
        $camelCase = lcfirst(implode('', array_map('ucfirst', $words)));
        return $camelCase;
    }

    /**
     * Replaces occurrences of a delimiter in a string with a new delimiter.
     *
     * @param string $str The input string.
     * @param string $oldDelimiter The old delimiter to be replaced.
     * @param string $newDelimiter The new delimiter to replace the old delimiter with.
     * @return string The input string with the delimiters replaced.
     */
    public static function replaceString($str, $oldDelimiter, $newDelimiter) {
        $newStr = str_replace($oldDelimiter, $newDelimiter, $str);
        return $newStr;
    }
}
