<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Wiki\src\WikiInput;
use Modules\Wiki\src\WikiLinkIndexer;
use PHPUnit\Framework\TestCase;

final class WikiLinkIndexerTest extends TestCase
{
    public function testItExtractsUniqueSafeInternalWikiTargetsFromMarkdown(): void
    {
        $markdown = <<<'MARKDOWN'
[Guide](/wiki/guides:getting-started)
[Same guide](/wiki/guides:getting-started#install)
[Encoded path](/wiki/guides%3Ainstallation?from=start)
[External](https://example.com/wiki/elsewhere)
![Image](/wiki/media:logo)
`[Code](/wiki/internal:example)`
[Invalid traversal](/wiki/../secret)
MARKDOWN;

        self::assertSame(
            ['guides:getting-started', 'guides:installation'],
            (new WikiLinkIndexer(new WikiInput()))->targets($markdown),
        );
    }

    public function testItReturnsNoTargetsForEmptyMarkdown(): void
    {
        self::assertSame([], (new WikiLinkIndexer(new WikiInput()))->targets('  '));
    }
}
