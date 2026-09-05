<?php

declare(strict_types=1);

namespace NovaNuke\Core\Http;

final class Request
{
    /** @param array<string, string> $attributes */
    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly array $query = [],
        private readonly array $request = [],
        private readonly array $cookies = [],
        private readonly array $files = [],
        private readonly array $server = [],
        private array $attributes = [],
    ) {
    }

    public static function capture(): self
    {
        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $_SERVER['REQUEST_URI'] ?? '/',
            $_GET,
            $_POST,
            $_COOKIE,
            $_FILES,
            $_SERVER,
        );
    }

    public static function create(string $method, string $uri): self
    {
        $query = [];
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);

        return new self(strtoupper($method), $uri, $query);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        $path = rawurldecode((string) parse_url($this->uri, PHP_URL_PATH));
        $normalized = '/' . trim($path, '/');

        return $normalized === '/' ? '/' : rtrim($normalized, '/');
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->request[$key] ?? $this->query[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function allInput(): array
    {
        return array_replace($this->query, $this->request);
    }

    /** @return array<string,mixed>|null */
    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;
        return is_array($file) ? $file : null;
    }

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function withAttributes(array $attributes): self
    {
        $clone = clone $this;
        $clone->attributes = array_merge($clone->attributes, $attributes);

        return $clone;
    }

    public function ip(): string
    {
        $ip = (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    public function userAgent(): string
    {
        return mb_substr((string) ($this->server['HTTP_USER_AGENT'] ?? ''), 0, 500);
    }

    public function referer(): string
    {
        $value = (string) ($this->server['HTTP_REFERER'] ?? '');
        return strlen($value) <= 2048 && ! preg_match('/[\x00-\x1F\x7F]/', $value) ? $value : '';
    }
}
