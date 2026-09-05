<?php

declare(strict_types=1);

namespace NovaNuke\Installer;

use NovaNuke\Core\I18n\LocaleRegistry;

final class InstallationValidator
{
    public function __construct(private readonly ?LocaleRegistry $locales = null) {}
    /** @return array<string,string> */ public function availableLocales():array{return$this->locales?->all()??['en'=>'English','es'=>'Español'];}
    /** @param array<string, mixed> $input
     *  @return array<string, string>
     */
    public function validate(array $input): array
    {
        $errors = [];

        $this->requiredLength($errors, $input, 'site_name', 2, 100);
        $this->requiredLength($errors, $input, 'admin_username', 3, 32);
        $this->requiredLength($errors, $input, 'database_host', 1, 255);
        $this->requiredLength($errors, $input, 'database_username', 1, 128);

        if (! filter_var($input['site_url'] ?? null, FILTER_VALIDATE_URL)) {
            $errors['site_url'] = 'Enter a valid site URL including http:// or https://.';
        }

        if (! ($this->locales?->supports((string)($input['locale']??'')) ?? in_array($input['locale'] ?? null, ['en', 'es'], true))) {
            $errors['locale'] = 'Select an available language.';
        }

        if (! in_array($input['timezone'] ?? null, timezone_identifiers_list(), true)) {
            $errors['timezone'] = 'Select a valid PHP timezone.';
        }

        if (! preg_match('/^[a-zA-Z0-9_]{1,64}$/', (string) ($input['database_name'] ?? ''))) {
            $errors['database_name'] = 'Use only letters, numbers and underscores for the database name.';
        }

        $port = filter_var($input['database_port'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 65535],
        ]);
        if ($port === false) {
            $errors['database_port'] = 'Enter a valid database port.';
        }

        if (! preg_match('/^[a-zA-Z0-9_.-]{3,32}$/', (string) ($input['admin_username'] ?? ''))) {
            $errors['admin_username'] = 'Use 3-32 letters, numbers, dots, underscores or hyphens.';
        }

        if (! filter_var($input['admin_email'] ?? null, FILTER_VALIDATE_EMAIL)) {
            $errors['admin_email'] = 'Enter a valid administrator email.';
        }

        $password = (string) ($input['admin_password'] ?? '');
        if (strlen($password) < 12 || strlen($password) > 255) {
            $errors['admin_password'] = 'Use a password between 12 and 255 characters.';
        }
        if (! hash_equals($password, (string) ($input['admin_password_confirmation'] ?? ''))) {
            $errors['admin_password_confirmation'] = 'The passwords do not match.';
        }

        return $errors;
    }

    /** @param array<string, string> $errors
     *  @param array<string, mixed> $input
     */
    private function requiredLength(array &$errors, array $input, string $key, int $min, int $max): void
    {
        $value = trim((string) ($input[$key] ?? ''));
        $length = mb_strlen($value);

        if ($length < $min || $length > $max) {
            $errors[$key] = "This field must contain {$min}-{$max} characters.";
        }
    }
}
