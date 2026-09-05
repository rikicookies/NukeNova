<?php

declare(strict_types=1);

namespace NovaNuke\Core\Http;

final class Response
{
    /** @param array<string, string> $headers */
    public function __construct(
        private readonly string $content = '',
        private readonly int $status = 200,
        private readonly array $headers = ['Content-Type' => 'text/html; charset=UTF-8'],
    ) {
    }

    public static function html(string $content, int $status = 200): self
    {
        return new self($content, $status);
    }

    public static function json(array $data, int $status = 200): self
    {
        return new self(
            json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8'],
        );
    }

    /** @param array<string,string> $headers */
    public static function xml(string $content, int $status = 200, array $headers = []): self
    {
        return new self($content, $status, array_replace([
            'Content-Type' => 'application/xml; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ], $headers));
    }

    public static function redirect(string $location, int $status = 302): self
    {
        if (! str_starts_with($location, '/')) {
            throw new \InvalidArgumentException('Redirects must use a local absolute path.');
        }

        return new self('', $status, ['Location' => $location]);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function header(string $name): ?string
    {
        foreach ($this->headers as $header => $value) {
            if (strcasecmp($header, $name) === 0) return $value;
        }
        return null;
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}", true);
        }

        echo $this->content;
    }
}
