<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Auth\AccountDeletionInput;
use PHPUnit\Framework\TestCase;

final class AccountDeletionInputTest extends TestCase
{
    public function testItAcceptsAnExactUsernameAndPassword(): void
    {
        self::assertSame([], (new AccountDeletionInput())->validate('riki', 'riki', 'secret'));
    }

    public function testItRejectsAnInexactConfirmationAndMissingPassword(): void
    {
        $errors = (new AccountDeletionInput())->validate('Riki', 'riki', '');
        self::assertArrayHasKey('confirmation', $errors);
        self::assertArrayHasKey('password', $errors);
    }
}
