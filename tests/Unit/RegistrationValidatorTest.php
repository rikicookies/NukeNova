<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Auth\PasswordPolicy;
use NovaNuke\Auth\RegistrationValidator;
use PHPUnit\Framework\TestCase;

final class RegistrationValidatorTest extends TestCase
{
    public function testItAcceptsAValidRegistration(): void
    {
        $validator = new RegistrationValidator(new PasswordPolicy());

        self::assertSame([], $validator->validate([
            'username' => 'new.member',
            'email' => 'member@example.test',
            'password' => 'a-long-member-password',
            'password_confirmation' => 'a-long-member-password',
        ]));
    }

    public function testItRejectsInvalidIdentityFields(): void
    {
        $errors = (new RegistrationValidator(new PasswordPolicy()))->validate([
            'username' => '../bad user',
            'email' => 'not-an-email',
            'password' => 'a-long-member-password',
            'password_confirmation' => 'a-long-member-password',
        ]);

        self::assertArrayHasKey('username', $errors);
        self::assertArrayHasKey('email', $errors);
    }

    public function testItAppliesTheSharedPasswordPolicy(): void
    {
        $errors = (new RegistrationValidator(new PasswordPolicy()))->validate([
            'username' => 'member',
            'email' => 'member@example.test',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        self::assertArrayHasKey('password', $errors);
    }
}
