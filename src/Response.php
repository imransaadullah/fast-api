<?php

namespace FASTAPI;


class Response
{
    private $headers = [];
    private $body;
    private $statusCode = 200;
    private $cookies = [];

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function addCookie(string $name, string $value, int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true): self
    {
        $this->cookies[] = compact('name', 'value', 'expire', 'path', 'domain', 'secure', 'httpOnly');
        return $this;
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    // Getter and Setter methods for body
    public function setBody(string $body) : self
    {
        $this->body = $body;
        return $this;
    }

    public function getBody(): string
    {
        return $this->body ?? '';
    }

    public function setJsonResponse(array $data, int $statusCode = 200): self
    {
        $this->setHeader('Content-Type', 'application/json')
            ->setBody(json_encode($data))
            ->setStatusCode($statusCode)
            ->calculateAndSetContentLength();

        return $this;
    }

    public function setHtmlResponse(string $html, int $statusCode = 200): self
    {
        $this->setHeader('Content-Type', 'text/html')
            ->setBody($html)
            ->setStatusCode($statusCode)
            ->calculateAndSetContentLength();

        return $this;
    }

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

    public function setErrorResponse(string $errorMessage, int $statusCode = 500): self
    {
        return $this->setJsonResponse(['error' => $errorMessage], $statusCode);
    }

    public function renderHtmlTemplate(string $templatePath, array $variables = []): self
    {
        ob_start();
        extract($variables);
        include $templatePath;
        $html = ob_get_clean();
        return $this->setHtmlResponse($html);
    }

    public function setEtag(string $etag): self
    {
        $this->setHeader('ETag', $etag);
        return $this;
    }

    public function validateEtag(string $expectedEtag): bool
    {
        $actualEtag = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        return $actualEtag === $expectedEtag;
    }

    public function setLastModified(\DateTime $lastModified): self
    {
        $this->setHeader('Last-Modified', $lastModified->format('D, d M Y H:i:s') . ' GMT');
        return $this;
    }

    public function validateLastModified(\DateTime $expectedLastModified): bool
    {
        $actualLastModified = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '');
        $expectedTimestamp = $expectedLastModified->getTimestamp();
        return $actualLastModified && $actualLastModified >= $expectedTimestamp;
    }

    public function setStreamingResponse(callable $streamCallback, int $statusCode = 200): self
    {
        $this->setStatusCode($statusCode);
        ob_start();
        call_user_func($streamCallback);
        $this->setBody(ob_get_clean());
        $this->calculateAndSetContentLength();

        return $this;
    }

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

    private function setContentDisposition(string $filename): self
    {
        $this->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"');
        return $this;
    }

    private function calculateAndSetContentLength(): self
    {
        $contentLength = strlen($this->body);
        $this->setHeader('Content-Length', $contentLength);
        return $this;
    }
}