<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CorePostCsrfContractTest extends TestCase
{
    public function testCoreAndAdminPostControllersContainCsrfValidation(): void
    {
        $root = dirname(__DIR__, 2);
        $files = [
            'app/Auth/AccountController.php',
            'app/Auth/AccountSecurityController.php',
            'app/Auth/AccountEmailController.php',
            'app/Auth/AuthController.php',
            'app/Auth/RegistrationController.php',
            'app/Auth/PasswordResetController.php',
            'app/Admin/UsersController.php',
            'app/Admin/RolesController.php',
            'app/Admin/ModulesController.php',
            'app/Admin/ThemesController.php',
            'app/Admin/MenusController.php',
        ];

        foreach ($files as $file) {
            $source = (string) file_get_contents($root . '/' . $file);
            self::assertStringContainsString('csrf->validate', $source, $file);
        }
    }

    public function testCoreAndAdminRouteFilesDoNotIntroduceRawStateChangingGetRoutes(): void
    {
        $root = dirname(__DIR__, 2);
        foreach (['routes/account.php', 'routes/admin.php', 'routes/auth.php', 'routes/registration.php', 'routes/passwords.php'] as $file) {
            $source = (string) file_get_contents($root . '/' . $file);
            self::assertDoesNotMatchRegularExpression(
                '/\$router->get\(\s*[\"\'][^\"\']*\/(?:delete|remove|logout|save|update|grant|revoke)(?:\/|[\"\'])[^;\n]*;/i',
                $source,
                $file,
            );
        }
    }
}
