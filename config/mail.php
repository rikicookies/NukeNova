<?php

declare(strict_types=1);

return [
    'mailer' => env('MAIL_MAILER', 'log'),
    'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@localhost'),
    'from_name' => env('MAIL_FROM_NAME', env('APP_NAME', 'NovaNuke')),
    'log_path' => NOVANUKE_ROOT . '/storage/logs/mail.log',
];
