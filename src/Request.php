<?php

namespace FASTAPI;

/**
 * Class Request
 * 
 * Represents an HTTP request.
 */
class Request
{
    private $method;
    private $uri;
    private $data;
    private $properties = []; // Dynamic properties storage
    private $dynamicHeaders = []; // For dynamically added headers

    /**
     * Request constructor.
     *
     * @param string $method The HTTP method of the request.
     * @param string $uri The URI of the request.
     * @param array $data The data associated with the request (optional).
     */
    public function __construct($method, $uri, $data = [])
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->data = $data;
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string The HTTP method.
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Retrieves the URI of the request.
     *
     * @return string The URI.
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Retrieves the data associated with the request.
     *
     * @return array The data.
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Retrieves the uploaded files associated with the request.
     *
     * @return array The uploaded files.
     */
    public function getFiles()
    {
        return $_FILES;
    }

    /**
     * Retrieves the headers of the request.
     *
     * @return array An associative array containing the headers.
     */
    public function getHeaders()
    {
        $headers = [];
        foreach (getallheaders() as $name => $value) {
            $headers[$name] = $value;
        }
        return array_merge($headers, $this->dynamicHeaders);
    }

    /**
     * Retrieves the value of a specific header from the request.
     *
     * @param string $key The header key.
     * @return string|null The header value, or null if the header does not exist.
     */
    public function getHeader($key)
    {
        return $this->getHeaders()[$key] ?? null;
    }

    /**
     * Adds or updates a header.
     *
     * @param string $key The header key.
     * @param string $value The header value.
     */
    public function withHeader($key, $value)
    {
        $this->dynamicHeaders[$key] = $value;
    }

    /**
     * Removes a header.
     *
     * @param string $key The header key.
     */
    public function removeHeader($key)
    {
        unset($this->dynamicHeaders[$key]);
    }

    /**
     * Sets a dynamic attribute.
     *
     * @param string $name The attribute name.
     * @param mixed $value The attribute value.
     */
    public function setAttribute($name, $value)
    {
        $this->properties[$name] = $value;
    }

    /**
     * Gets a dynamic attribute.
     *
     * @param string $name The attribute name.
     * @return mixed|null The attribute value, or null if it does not exist.
     */
    public function getAttribute($name)
    {
        return $this->properties[$name] ?? null;
    }

    /**
     * Gets all dynamic attributes.
     *
     * @return array An array containing all attributes.
     */
    public function getAttributes(): array
    {
        return $this->properties;
    }

    /**
     * Magic method to dynamically set a property.
     *
     * @param string $name The property name.
     * @param mixed $value The property value.
     */
    public function __set($name, $value)
    {
        $this->setAttribute($name, $value);
    }

    /**
     * Magic method to dynamically get a property.
     *
     * @param string $name The property name.
     * @return mixed|null The property value, or null if it does not exist.
     */
    public function __get($name)
    {
        return $this->getAttribute($name);
    }

    /**
     * Magic method to check if a property is set.
     *
     * @param string $name The property name.
     * @return bool True if the property is set, false otherwise.
     */
    public function __isset($name)
    {
        return isset($this->properties[$name]);
    }

    /**
     * Magic method to unset a property.
     *
     * @param string $name The property name.
     */
    public function __unset($name)
    {
        unset($this->properties[$name]);
    }

    /**
     * Checks if a dynamic attribute exists.
     *
     * @param string $name The attribute name.
     * @return bool True if the attribute exists, false otherwise.
     */
    public function hasAttribute($name)
    {
        return array_key_exists($name, $this->properties);
    }

    /**
     * Converts the Request object to an associative array.
     *
     * @return array An array representation of the Request.
     */
    public function toArray()
    {
        return [
            'method' => $this->method,
            'uri' => $this->uri,
            'data' => $this->data,
            'properties' => $this->properties,
            'headers' => $this->getHeaders(),
            'files' => $this->getFiles()
        ];
    }

    /**
     * Validates the request data based on provided rules.
     *
     * @param array $rules Validation rules (e.g., ['key' => 'required|string']).
     * @return array|null An array of validation errors, or null if valid.
     */
    public function validateData(array $rules)
    {
        $errors = [];
        foreach ($rules as $key => $rule) {
            if (!isset($this->data[$key]) && strpos($rule, 'required') !== false) {
                $errors[$key][] = 'This field is required.';
            } elseif (isset($this->data[$key])) {
                if (strpos($rule, 'string') !== false && !is_string($this->data[$key])) {
                    $errors[$key][] = 'This field must be a string.';
                }
                if (strpos($rule, 'integer') !== false && !is_int($this->data[$key])) {
                    $errors[$key][] = 'This field must be an integer.';
                }
            }
        }
        return !empty($errors) ? $errors : null;
    }

    /**
     * Retrieves a query parameter from the URI.
     *
     * @param string $key The query parameter key.
     * @return string|null The query parameter value, or null if not found.
     */
    public function getQueryParam($key)
    {
        $queryParams = $this->getQueryParams();
        return $queryParams[$key] ?? null;
    }

    /**
     * Retrieves JSON-encoded data from the request body.
     *
     * @return array|null The JSON-decoded data, or null if decoding fails.
     */
    public function getJsonData()
    {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? null;
    }

    /**
     * Retrieves all query parameters from the URI.
     *
     * @return array An associative array of query parameters.
     */
    public function getQueryParams(): array
    {
        $query = parse_url($this->uri, PHP_URL_QUERY);

        if ($query === null || $query === false || $query === '') {
            return [];
        }

        parse_str($query, $queryParams);

        return $queryParams;
    }
}
