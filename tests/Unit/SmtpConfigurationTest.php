<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use InvalidArgumentException;
use NovaNuke\Core\Mail\SmtpConfiguration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SmtpConfigurationTest extends TestCase
{
    public function testItAcceptsACompleteEncryptedConfiguration(): void
    {
        $configuration = new SmtpConfiguration(
            'mail.example.test', 465, 'noreply@example.test', 'secret', 'ssl', 15,
            'noreply@example.test', 'NovaNuke',
        );
        self::assertSame('ssl', $configuration->encryption);
        self::assertSame(465, $configuration->port);
    }

    #[DataProvider('invalidConfigurations')]
    public function testItRejectsUnsafeOrIncompleteConfiguration(array $values): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SmtpConfiguration(...$values);
    }

    /** @return iterable<string, array{array<int, mixed>}> */
    public static function invalidConfigurations(): iterable
    {
        $valid = ['mail.example.test', 465, 'user@example.test', 'secret', 'ssl', 15, 'from@example.test', 'NovaNuke'];
        $values = $valid; $values[0] = 'https://mail.example.test'; yield 'scheme in host' => [$values];
        $values = $valid; $values[1] = 70000; yield 'invalid port' => [$values];
        $values = $valid; $values[3] = ''; yield 'missing password' => [$values];
        $values = $valid; $values[4] = 'none'; yield 'unencrypted transport' => [$values];
        $values = $valid; $values[7] = "NovaNuke\r\nBcc: bad@example.test"; yield 'header newline' => [$values];
    }
}
