<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Wiki\src\WikiInput;
use Modules\Wiki\src\WikiLinkPresenter;
use PHPUnit\Framework\TestCase;

final class WikiLinkPresenterTest extends TestCase
{
    public function testItMarksOnlyMissingValidWikiPageLinks(): void
    {
        $html = '<p><a href="/wiki/guides:exists">Exists</a> '
            . '<a href="/wiki/guides:missing">Missing</a> '
            . '<a href="/wiki/attachments/12">Attachment</a> '
            . '<a href="https://example.com">External</a></p>';

        $result = (new WikiLinkPresenter(new WikiInput()))->markMissing($html, ['guides:exists']);

        self::assertStringContainsString('<a href="/wiki/guides:exists">Exists</a>', $result);
        self::assertStringContainsString('<a href="/wiki/guides:missing" class="wiki-link-missing">Missing</a>', $result);
        self::assertStringContainsString('<a href="/wiki/attachments/12">Attachment</a>', $result);
        self::assertStringContainsString('<a href="https://example.com">External</a>', $result);
    }

    public function testItPreservesExistingClassesOnMissingLinks(): void
    {
        $result = (new WikiLinkPresenter(new WikiInput()))->markMissing(
            '<a class="reference" href="/wiki/new-page">New</a>',
            [],
        );

        self::assertStringContainsString('class="reference wiki-link-missing"', $result);
    }
}
