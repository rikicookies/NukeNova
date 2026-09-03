<?php

declare(strict_types=1);

namespace NovaNuke\Installer;

use RuntimeException;

final class EnvWriter
{
    /** @param array<string, string|int|bool> $values */
    public function write(string $path, array $values): void
    {
        $lines = [];

        foreach ($values as $key => $value) {
            if (! preg_match('/^[A-Z][A-Z0-9_]*$/', $key)) {
                throw new RuntimeException("Invalid environment key: {$key}");
            }

            $lines[] = $key . '=' . $this->encode($value);
        }

        $temporary = $path . '.tmp-' . bin2hex(random_bytes(6));
        $content = implode(PHP_EOL, $lines) . PHP_EOL;

        if (file_put_contents($temporary, $content, LOCK_EX) === false) {
            throw new RuntimeException('Could not write the temporary environment file.');
        }

        @chmod($temporary, 0600);

        if (! rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException('Could not activate the environment file.');
        }
    }

    private function encode(string|int|bool $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        return '"' . addcslashes($value, "\\\"\n\r") . '"';
    }
}
