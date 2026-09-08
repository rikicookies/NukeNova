<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class UserDirectoryTest extends TestCase
{
    public function testDirectoryHasAStaticRouteBeforeProfileRoutes(): void
    {
        $routes = (string) file_get_contents(dirname(__DIR__, 2) . '/routes/account.php');
        self::assertStringContainsString("router->get('/users'", $routes);
        self::assertLessThan(strpos($routes, "router->get('/users/{username}'"), strpos($routes, "router->get('/users'"));
    }

    public function testDirectoryDoesNotSelectPrivateUserData(): void
    {
        $repository = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Auth/ProfileRepository.php');
        $method = substr($repository, (int) strpos($repository, 'public function directory'), (int) strpos($repository, '/** @param', strpos($repository, 'public function directory')) - (int) strpos($repository, 'public function directory'));
        self::assertStringContainsString("u.status='active'", $method);
        self::assertStringContainsString('profile_visibility', $method);
        self::assertStringNotContainsString('u.email', $method);
    }
}
