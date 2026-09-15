<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Config\ConfigRepository;
use NovaNuke\Core\Mail\MailDeliveryAcceptance;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MailDeliveryAcceptanceTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir() . '/novanuke-mail-acceptance-' . bin2hex(random_bytes(5)) . '.json';
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
    }

    public function testAllThreeRealWorkflowsAreRequired(): void
    {
        $acceptance = new MailDeliveryAcceptance($this->smtpConfig(), $this->path, 'https://example.test');
        self::assertFalse($acceptance->passed());
        self::assertCount(3, $acceptance->run());

        $acceptance->record('registration-verification');
        $acceptance->record('password-reset');
        self::assertFalse($acceptance->passed());

        $acceptance->record('email-change');
        self::assertTrue($acceptance->passed());
    }

    public function testAcceptanceIsInvalidatedWhenSmtpConfigurationChanges(): void
    {
        $acceptance = new MailDeliveryAcceptance($this->smtpConfig(), $this->path, 'https://example.test');
        foreach (array_keys(MailDeliveryAcceptance::WORKFLOWS) as $workflow) {
            $acceptance->record($workflow);
        }
        self::assertTrue($acceptance->passed());

        $changed = $this->smtpConfig('different-secret');
        self::assertFalse((new MailDeliveryAcceptance($changed, $this->path, 'https://example.test'))->passed());
    }

    public function testAcceptanceIsInvalidatedWhenSiteUrlChanges(): void
    {
        $acceptance = new MailDeliveryAcceptance($this->smtpConfig(), $this->path, 'https://example.test');
        foreach (array_keys(MailDeliveryAcceptance::WORKFLOWS) as $workflow) {
            $acceptance->record($workflow);
        }
        self::assertTrue($acceptance->passed());
        self::assertFalse((new MailDeliveryAcceptance($this->smtpConfig(), $this->path, 'https://www.example.test'))->passed());
    }

    public function testLogMailerCannotRecordProductionAcceptance(): void
    {
        $this->expectException(RuntimeException::class);
        (new MailDeliveryAcceptance(new ConfigRepository(['mail' => ['mailer' => 'log']]), $this->path))
            ->record('password-reset');
    }

    public function testInvalidSmtpConfigurationCannotBeRecorded(): void
    {
        $invalid = new ConfigRepository([
            'mail' => [
                'mailer' => 'smtp',
                'host' => 'bad host',
                'port' => 70000,
                'username' => 'mailer',
                'password' => 'secret',
                'encryption' => 'none',
                'from_address' => 'invalid',
                'from_name' => 'NovaNuke',
            ],
        ]);
        $this->expectException(RuntimeException::class);
        (new MailDeliveryAcceptance($invalid, $this->path))->record('password-reset');
    }

    public function testCorruptAcceptanceStateNeverPasses(): void
    {
        file_put_contents($this->path, '{not-json');
        self::assertFalse((new MailDeliveryAcceptance($this->smtpConfig(), $this->path, 'https://example.test'))->passed());
    }

    private function smtpConfig(string $password = 'secret-value'): ConfigRepository
    {
        return new ConfigRepository([
            'app' => ['url' => 'https://example.test'],
            'mail' => [
                'mailer' => 'smtp',
                'host' => 'smtp.example.test',
                'port' => 465,
                'username' => 'mailer@example.test',
                'password' => $password,
                'encryption' => 'ssl',
                'timeout' => 15,
                'from_address' => 'mailer@example.test',
                'from_name' => 'NovaNuke',
            ],
        ]);
    }
}
