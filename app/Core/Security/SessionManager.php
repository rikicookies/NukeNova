<?php

declare(strict_types=1);

namespace NovaNuke\Core\Security;

use RuntimeException;

final class SessionManager
{
    public function __construct(
        private readonly string $name,
        private readonly bool $secure,
        private readonly string $sameSite = 'Lax',
        private readonly int $lifetime = 7200,
    ) {
    }

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (headers_sent()) {
            throw new RuntimeException('Cannot start a session after headers have been sent.');
        }

        session_name($this->name);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $this->secure,
            'httponly' => true,
            'samesite' => $this->sameSite,
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        if (! session_start()) {
            throw new RuntimeException('The session could not be started.');
        }

        if (! isset($_SESSION['_started_at'])) {
            $_SESSION['_started_at'] = time();
        }

        if ((time() - (int) $_SESSION['_started_at']) > $this->lifetime) {
            session_regenerate_id(true);
            $_SESSION = ['_started_at' => time()];
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION[$key] ?? $default;
        unset($_SESSION[$key]);

        return $value;
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['_started_at'] = time();
    }

    public function invalidate(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['_started_at'] = time();
    }
}
