<?php

declare(strict_types=1);

use NovaNuke\Core\Http\Request;

$application = require dirname(__DIR__) . '/bootstrap/app.php';
$application->kernel()->handle(Request::capture())->send();
