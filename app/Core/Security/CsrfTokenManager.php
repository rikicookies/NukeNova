<?php

declare(strict_types=1);

namespace NovaNuke\Core\Security;

final class CsrfTokenManager
{
    private const SESSION_KEY = '_csrf_token';

    public function __construct(private readonly SessionManager $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY);

        if (! is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            $this->session->put(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public function validate(mixed $provided): bool
    {
        return is_string($provided) && hash_equals($this->token(), $provided);
    }

    public function rotate(): string
    {
        $this->session->remove(self::SESSION_KEY);

        return $this->token();
    }
}
