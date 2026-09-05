<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\I18n\Translator;
use PHPUnit\Framework\TestCase;

final class TranslatorTest extends TestCase
{
    public function testItUsesLocaleFallbackAndSafeParameters(): void
    {
        $directory = $this->catalogues(['en' => ['hello' => 'Hello {name}', 'fallback' => 'Fallback'], 'es' => ['hello' => 'Hola {name}']]);
        $translator = new Translator('es', 'en', $directory);

        self::assertSame('Hola Riki', $translator->translate('hello', ['name' => 'Riki']));
        self::assertSame('Fallback', $translator->translate('fallback'));
        self::assertSame('missing.key', $translator->translate('missing.key'));
        self::assertSame('Hola {name}', $translator->translate('hello', ['name' => ['Riki']]));
        self::assertSame('Hello Riki', (function () use ($translator): string {
            $translator->setLocale('en');
            return $translator->translate('hello', ['name' => 'Riki']);
        })());
    }

    public function testNamespacesKeepModuleMessagesIndependent(): void
    {
        $core = $this->catalogues(['en' => ['title' => 'Core']]);
        $module = $this->catalogues(['en' => ['title' => 'News']]);
        $translator = new Translator('en', 'en', $core);
        $translator->addNamespace('news', $module);

        self::assertSame('Core', $translator->translate('title'));
        self::assertSame('News', $translator->translate('news::title'));
    }

    public function testInvalidKeysAndNamespacesAreNotLoaded(): void
    {
        $translator = new Translator('en', 'en', $this->catalogues(['en' => []]));
        self::assertSame('../secret', $translator->translate('../secret'));
        self::assertSame('unknown::title', $translator->translate('unknown::title'));
    }

    public function testInvalidConfiguredLocalesFallBackSafely(): void
    {
        $translator = new Translator('../es', 'bad-locale', $this->catalogues(['en' => ['title' => 'English']]));
        self::assertSame('en', $translator->locale());
        self::assertSame('English', $translator->translate('title'));
    }

    /** @param array<string,array<string,string>> $catalogues */
    private function catalogues(array $catalogues): string
    {
        $directory = sys_get_temp_dir() . '/novanuke-i18n-' . bin2hex(random_bytes(5));
        mkdir($directory, 0700, true);
        foreach ($catalogues as $locale => $messages) {
            file_put_contents($directory . '/' . $locale . '.json', json_encode($messages, JSON_THROW_ON_ERROR));
        }
        $this->directories[] = $directory;
        return $directory;
    }

    /** @var list<string> */
    private array $directories = [];

    protected function tearDown(): void
    {
        foreach ($this->directories as $directory) {
            foreach (glob($directory . '/*') ?: [] as $file) unlink($file);
            rmdir($directory);
        }
    }
}
