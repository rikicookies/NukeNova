<?php

declare(strict_types=1);

return [
    'name' => env('SESSION_NAME', 'novanuke_session'),
    'secure' => env_bool('SESSION_SECURE', false),
    'same_site' => env('SESSION_SAME_SITE', 'Lax'),
    'lifetime' => 7200,
];
