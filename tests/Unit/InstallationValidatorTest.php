<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Installer\InstallationValidator;
use PHPUnit\Framework\TestCase;

final class InstallationValidatorTest extends TestCase
{
    public function testItAcceptsAValidInstallationRequest(): void
    {
        self::assertSame([], (new InstallationValidator())->validate($this->validInput()));
    }

    public function testItRejectsAnUnsafeDatabaseName(): void
    {
        $input = $this->validInput();
        $input['database_name'] = 'novanuke`; DROP DATABASE mysql;';

        self::assertArrayHasKey('database_name', (new InstallationValidator())->validate($input));
    }

    public function testItRequiresMatchingLongPasswords(): void
    {
        $input = $this->validInput();
        $input['admin_password'] = 'short';
        $input['admin_password_confirmation'] = 'different';
        $errors = (new InstallationValidator())->validate($input);

        self::assertArrayHasKey('admin_password', $errors);
        self::assertArrayHasKey('admin_password_confirmation', $errors);
    }

    /** @return array<string, string|int> */
    private function validInput(): array
    {
        return [
            'site_name' => 'NovaNuke',
            'site_url' => 'http://novanuke.test',
            'locale' => 'es',
            'timezone' => 'America/Los_Angeles',
            'database_host' => '127.0.0.1',
            'database_port' => 3306,
            'database_name' => 'novanuke',
            'database_username' => 'root',
            'admin_username' => 'riki',
            'admin_email' => 'riki@example.test',
            'admin_password' => 'a-secure-test-password',
            'admin_password_confirmation' => 'a-secure-test-password',
        ];
    }
}
