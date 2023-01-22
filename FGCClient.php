<?php

declare(strict_types=1);

namespace Isikiyski\Client;

interface CurlableInterface
{
    public function setTimeout(int $seconds);

    public function setHeaders(array $headers): CurlableInterface;

    public function getHttpStatusCode(): int;

    public function getRawResponse(): mixed;

    public function getParsedResponse(): mixed;

    public function getErrorCode(): ?int;

    public function getErrorMessage(): ?string;

    public function getResponseHeaders(): array;

    public function getRequestHeaders(): array;
}

class FGCClient implements CurlableInterface
{
    private const ALLOWED_METHODS = ['post', 'get', 'put', 'delete'];

    private const DEFAULT_TIMEOUT = 30;

    private array $opts = [
        'http' => [
            'ignore_errors' => true,
            'request_fulluri' => true,
            'protocol_version' => '1.0',
        ],
    ];

    private ?int $errNo = null;

    private ?string $errStr = null;

    private array $requestHeaders = [];

    private array $responseHeaders = [];

    private mixed $result = null;

    /**
     * @param string $url
     */
    public function __construct(
        private ?string $url = null,
    ) {
        set_error_handler([$this, 'warningHandler']);
    }

    /**
     * @param string $mname
     * @param mixed $args
     * @return FGCClient
     */
    public function __call(string $mname, mixed $args = []): FGCClient
    {
        if (!in_array($mname, self::ALLOWED_METHODS)) {
            throw new \Exception('HTTP method must be one of: ' . json_encode(self::ALLOWED_METHODS));
        }

        $url = (isset($args[0]) && $args[0] != '') ? $args[0] : null;

        if (!$url && !$this->url) {
            throw new \Exception('URL must be passed either in constructor or in the method call!');
        }

        $this->prepareRequestOptions($mname, $args[1] ?? null);

        try {
            $this->result = file_get_contents(
                $url ?? $this->url, false, stream_context_create($this->opts)
            );
        } catch (\Throwable $e) {
            $this->errNo = (int)$e->getCode();
            $this->errStr = $e->getMessage();
        }

        $this->responseHeaders = $http_response_header ?? [];

        return $this;
    }

    /**
     * @param int $seconds
     * @return FGCClient
     */
    public function setTimeout(int $seconds): FGCClient
    {
        $this->opts['http']['timeout'] = $seconds;

        return $this;
    }

    /**
     * @param array $headers
     * @return FGCClient
     */
    public function setHeaders(array $headers): FGCClient
    {
        $parsed = [];

        $parseHeaders = function (string $key, string $value) use (&$parsed): void {
            $this->requestHeaders[trim($key)] = trim($value);
            $parsed[] = "$key: $value";
        };

        if ($this->isAssoc($headers)) {
            foreach ($headers as $key => $value) {
                $parseHeaders($key, $value);
            }
        } else {
            foreach ($headers as $header) {
                list($key, $value) = explode(':', $header, 2);
                $parseHeaders($key, $value);
            }
        }

        // for performance optimization
        $parsed[] = 'Connection: close';
        $this->requestHeaders['Connection'] = 'close';

        $this->opts['http']['header'] = $parsed;

        return $this;
    }

    /**
     * @return int
     */
    public function getHttpStatusCode(): int
    {
        if (isset($this->responseHeaders[0])) {
            preg_match_all('/\d{3}/', $this->responseHeaders[0], $matches);

            return (int)$matches[0][0];
        }

        return 0;
    }

    /**
     * @return mixed
     */
    public function getRawResponse(): mixed
    {
        return $this->result;
    }

    /**
     * @return ?array
     */
    public function getParsedResponse(): ?array
    {
        if (!$this->result) {
            return null;
        }

        if (is_string($this->result)) {
            return json_decode($this->result, true);
        }

        // prolly never happen, JIC
        if (is_object($this->result)) {
            return (array) $this->result;
        }

        return null;
    }

    /**
     * @return ?int
     */
    public function getErrorCode(): ?int
    {
        return $this->errNo;
    }

    /**
     * @return ?string
     */
    public function getErrorMessage(): ?string
    {
        return $this->errStr;
    }

    /**
     * @return array
     */
    public function getResponseHeaders(): array
    {
        $out = [];

        $headersCount = count($this->responseHeaders);
        // skip first because it's the http code
        for ($i = 1; $i < $headersCount; $i++) {
            $parts = explode(':', $this->responseHeaders[$i]);

            $out[strtolower(array_shift($parts))] = count($parts) > 1
                ? trim(implode(':', $parts))
                : trim($parts[0]);
        }

        return $out;
    }

    /**
     * @return array
     */
    public function getRequestHeaders(): array
    {
        return $this->requestHeaders;
    }

    /**
     * @return void
     */
    public function close(): void
    {
        $this->result = null;
        $this->errNo = null;
        $this->errStr = null;
        $this->responseHeaders = [];
        $this->requestHeaders = [];
        $this->opts['http']['method'] = null;
        $this->opts['http']['content'] = null;
        $this->opts['http']['header'] = null;
    }

    /**
     * @param string $method
     * @param mixed $payload
     * @return void
     */
    private function prepareRequestOptions(string $method, mixed $payload): void
    {
        $method = $this->opts['http']['method'] = strtoupper($method);

        if ($method != 'DELETE' && !is_null($payload)) {
            $this->opts['http']['content'] = is_string($payload) ? $payload : json_encode($payload);
        }

        $this->opts['http']['timeout'] = isset($this->opts['http']['timeout'])
            ? $this->opts['http']['timeout']
            : self::DEFAULT_TIMEOUT;
    }

    /**
     * @param mixed $errNo
     * @param mixed $errStr
     * @return void
     */
    private function warningHandler(mixed $errNo = null, mixed $errStr = null): void
    {
        $this->errNo = $errNo;
        $this->errStr = $errStr;
    }

    /**
     * @param array $arr
     * @return bool
     */
    private function isAssoc(array $arr): bool
    {
        if ($arr === []) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
