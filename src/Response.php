<?php

namespace FASTAPI;

/**
 * Class Response
 * 
 * Represents an HTTP response.
 */
class Response
{
    /**
     * @var array An array containing headers to be sent with the response.
     */
    private $headers = [];

    /**
     * @var mixed The body content of the response.
     */    
    private $body;

    /**
     * @var int The HTTP status code of the response.
     */
    private $statusCode = 200;

    /**
     * @var array An array containing cookies to be set with the response.
     */
    private $cookies = [];

    /**
     * Sets a header for the response.
     *
     * @param string $name The header name.
     * @param string $value The header value.
     * @return self This Response instance.
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Adds a cookie to the response.
     *
     * @param string $name The cookie name.
     * @param string $value The cookie value.
     * @param int $expire The expiration time of the cookie (optional).
     * @param string $path The path on the server where the cookie will be available (optional).
     * @param string $domain The domain that the cookie is available to (optional).
     * @param bool $secure Whether the cookie should only be transmitted over a secure HTTPS connection (optional).
     * @param bool $httpOnly Whether the cookie should be accessible only through the HTTP protocol (optional).
     * @return self This Response instance.
     */
    public function addCookie(string $name, string $value, int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true): self
    {
        $this->cookies[] = compact('name', 'value', 'expire', 'path', 'domain', 'secure', 'httpOnly');
        return $this;
    }

    /**
     * Sets the status code for the response.
     *
     * @param int $statusCode The HTTP status code.
     * @return self This Response instance.
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Retrieves the status code of the response.
     *
     * @return int The HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    // Getter and Setter methods for body

    /**
     * Sets the body content for the response.
     *
     * @param string $body The body content.
     * @return self This Response instance.
     */
    public function setBody(string $body) : self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Retrieves the body content of the response.
     *
     * @return string The body content.
     */
    public function getBody(): string
    {
        return $this->body ?? '';
    }

    /**
     * Sets a JSON response with the provided data and status code.
     *
     * @param array $data The data to be encoded as JSON.
     * @param int $statusCode The HTTP status code of the response (optional, default is 200).
     * @return self This Response instance.
     */
    public function setJsonResponse(array $data, int $statusCode = 200): self
    {
        $this->setHeader('Content-Type', 'application/json')
            ->setBody(json_encode($data))
            ->setStatusCode($statusCode)
            ->calculateAndSetContentLength();

        return $this;
    }

    /**
     * Sets an HTML response with the provided HTML content and status code.
     *
     * @param string $html The HTML content.
     * @param int $statusCode The HTTP status code of the response (optional, default is 200).
     * @return self This Response instance.
     */
    public function setHtmlResponse(string $html, int $statusCode = 200): self
    {
        $this->setHeader('Content-Type', 'text/html')
            ->setBody($html)
            ->setStatusCode($statusCode)
            ->calculateAndSetContentLength();

        return $this;
    }

    /**
     * Sets a file response with the content of the file specified by the file path,
     * along with optional filename and status code.
     *
     * @param string $filePath The path to the file to be sent in the response.
     * @param string|null $filename The filename to be used when downloading the file (optional).
     * @param int $statusCode The HTTP status code of the response (optional, default is 200).
     * @return self This Response instance.
     */
    public function setFileResponse(string $filePath, string $filename = null, int $statusCode = 200): self
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return $this->setErrorResponse('File not found or not readable', 404);
        }

        $this->setHeader('Content-Type', mime_content_type($filePath))
            ->setContentDisposition($filename ?? basename($filePath))
            ->setBody(file_get_contents($filePath))
            ->setStatusCode($statusCode)
            ->calculateAndSetContentLength();

        return $this;
    }

    /**
     * Sets an error response with the provided error message and status code.
     * The response is formatted as JSON.
     *
     * @param string $errorMessage The error message.
     * @param int $statusCode The HTTP status code of the response (optional, default is 500).
     * @return self This Response instance.
     */
    public function setErrorResponse(string $errorMessage, int $statusCode = 500): self
    {
        return $this->setJsonResponse(['error' => $errorMessage], $statusCode);
    }

    /**
     * Renders an HTML template located at the specified path with optional variables,
     * and sets the rendered HTML as the response content.
     *
     * @param string $templatePath The path to the HTML template file.
     * @param array $variables The variables to be passed to the template (optional).
     * @return self This Response instance.
     */
    public function renderHtmlTemplate(string $templatePath, array $variables = []): self
    {
        ob_start();
        extract($variables);
        include $templatePath;
        $html = ob_get_clean();
        return $this->setHtmlResponse($html);
    }

    /**
     * Sets the ETag header of the response.
     *
     * @param string $etag The ETag value.
     * @return self This Response instance.
     */
    public function setEtag(string $etag): self
    {
        $this->setHeader('ETag', $etag);
        return $this;
    }

    /**
     * Validates the ETag of the request against the provided ETag value.
     *
     * @param string $expectedEtag The expected ETag value.
     * @return bool True if the ETag is valid, false otherwise.
     */
    public function validateEtag(string $expectedEtag): bool
    {
        $actualEtag = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        return $actualEtag === $expectedEtag;
    }

    /**
     * Sets the Last-Modified header of the response.
     *
     * @param \DateTime $lastModified The last modified timestamp.
     * @return self This Response instance.
     */
    public function setLastModified(\DateTime $lastModified): self
    {
        $this->setHeader('Last-Modified', $lastModified->format('D, d M Y H:i:s') . ' GMT');
        return $this;
    }

    /**
     * Validates the Last-Modified header of the request against the provided last modified timestamp.
     *
     * @param \DateTime $expectedLastModified The expected last modified timestamp.
     * @return bool True if the Last-Modified header is valid, false otherwise.
     */
    public function validateLastModified(\DateTime $expectedLastModified): bool
    {
        $actualLastModified = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '');
        $expectedTimestamp = $expectedLastModified->getTimestamp();
        return $actualLastModified && $actualLastModified >= $expectedTimestamp;
    }

    /**
     * Sets a streaming response using the provided stream callback function.
     *
     * @param callable $streamCallback The callback function that streams the response content.
     * @param int $statusCode The HTTP status code of the response (optional, default is 200).
     * @return self This Response instance.
     */
    public function setStreamingResponse(callable $streamCallback, int $statusCode = 200): self
    {
        $this->setStatusCode($statusCode);
        ob_start();
        call_user_func($streamCallback);
        $this->setBody(ob_get_clean());
        $this->calculateAndSetContentLength();

        return $this;
    }

    /**
     * Sends the HTTP response to the client.
     */
    public function send()
    {
        // Send cookies
        foreach ($this->cookies as $cookie) {
            setcookie($cookie['name'], $cookie['value'], $cookie['expire'], $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httpOnly']);
        }

        // Send headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // Send HTTP status code
        http_response_code($this->getStatusCode());

        // Output the response body
        exit($this->getBody());
    }

    /**
     * Sets the Content-Disposition header with the specified filename for file download.
     * This method is used internally to set the disposition of the response content.
     *
     * @param string $filename The filename to be used for the Content-Disposition header.
     * @return self This Response instance.
     */
    private function setContentDisposition(string $filename): self
    {
        $this->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"');
        return $this;
    }

    /**
     * Calculates the content length of the response body and sets the Content-Length header.
     * This method is used internally to set the length of the response content.
     *
     * @return self This Response instance.
     */
    private function calculateAndSetContentLength(): self
    {
        $contentLength = strlen($this->body);
        $this->setHeader('Content-Length', $contentLength);
        return $this;
    }
}