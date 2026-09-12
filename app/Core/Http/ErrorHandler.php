<?php

declare(strict_types=1);

namespace NovaNuke\Core\Http;

use ErrorException;
use NovaNuke\Core\Http\Routing\MethodNotAllowed;
use NovaNuke\Core\Http\Routing\RouteNotFound;
use Throwable;
use NovaNuke\Core\Logging\SensitiveDataRedactor;

final class ErrorHandler
{
    public function __construct(
        private readonly bool $debug,
        private readonly string $logPath,
        private readonly SensitiveDataRedactor $redactor = new SensitiveDataRedactor(),
        private readonly ?string $projectRoot = null,
    ) {
    }

    public function register(): void
    {
        set_error_handler(static function (int $severity, string $message, string $file, int $line) {
            if ((error_reporting() & $severity) === 0) {
                return false;
            }
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
    }

    public function render(Throwable $error): Response
    {
        $id = bin2hex(random_bytes(6));
        $status = match (true) {
            $error instanceof RouteNotFound => 404,
            $error instanceof MethodNotAllowed => 405,
            default => 500,
        };

        $this->writeLog($id, $error);

        $message = $this->debug
            ? $error::class . ': ' . $this->redactor->redact($error->getMessage())
            : ($status === 500 ? "An unexpected error occurred. Reference: {$id}" : $error->getMessage());

        return Response::html(
            '<!doctype html><html lang="en"><meta charset="utf-8"><title>Error</title>'
            . '<h1>' . $status . '</h1><p>' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>',
            $status,
        );
    }

    private function logFile(string $file): string
    {
        if ($this->debug) {
            return $file;
        }

        $root = $this->projectRoot !== null ? realpath($this->projectRoot) : null;
        $resolved = realpath($file);
        if ($root !== false && $root !== null && $resolved !== false && str_starts_with($resolved, $root . DIRECTORY_SEPARATOR)) {
            return '[APP]/' . str_replace(DIRECTORY_SEPARATOR, '/', substr($resolved, strlen($root) + 1));
        }

        return '[INTERNAL]';
    }

    private function writeLog(string $id, Throwable $error): void
    {
        $directory = dirname($this->logPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $line = sprintf(
            "[%s] %s %s: %s in %s:%d%s",
            gmdate('c'),
            $id,
            $error::class,
            $this->redactor->redact($error->getMessage()),
            $this->logFile($error->getFile()),
            $error->getLine(),
            PHP_EOL,
        );
        error_log($line, 3, $this->logPath);
    }
}
