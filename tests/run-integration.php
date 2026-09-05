<?php

declare(strict_types=1);

use PHPUnit\TextUI\Application;

require dirname(__DIR__) . '/vendor/autoload.php';

$environment = dirname(__DIR__) . '/.env.testing';
if (is_file($environment)) {
    Dotenv\Dotenv::createImmutable(dirname(__DIR__), '.env.testing')->safeLoad();
}

putenv('NOVANUKE_RUN_INTEGRATION=1');
$_ENV['NOVANUKE_RUN_INTEGRATION'] = '1';
$_SERVER['NOVANUKE_RUN_INTEGRATION'] = '1';

exit((new Application())->run(['phpunit', '--testsuite', 'Integration']));
