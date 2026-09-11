<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\DemoContent\src\NovaTechCommunityDataset;
use PHPUnit\Framework\TestCase;

final class DemoContentDatasetTest extends TestCase
{
    public function testDatasetContainsTheExpectedRealisticVolume(): void
    {
        self::assertCount(12, NovaTechCommunityDataset::users());
        self::assertCount(10, NovaTechCommunityDataset::news());
        self::assertCount(8, NovaTechCommunityDataset::pages());
        self::assertCount(10, NovaTechCommunityDataset::downloads());
        self::assertCount(10, NovaTechCommunityDataset::links());
        self::assertCount(36, NovaTechCommunityDataset::comments());
        self::assertCount(5, NovaTechCommunityDataset::polls());
        self::assertCount(7, NovaTechCommunityDataset::friendships());
        self::assertCount(12, NovaTechCommunityDataset::messages());
    }

    public function testUsernamesAndContentSlugsAreUnique(): void
    {
        $usernames = array_column(NovaTechCommunityDataset::users(), 'username');
        $newsSlugs = array_column(NovaTechCommunityDataset::news(), 'slug');
        $pageSlugs = array_column(NovaTechCommunityDataset::pages(), 'slug');
        $downloadSlugs = array_column(NovaTechCommunityDataset::downloads(), 'slug');

        self::assertSame($usernames, array_values(array_unique($usernames)));
        self::assertSame($newsSlugs, array_values(array_unique($newsSlugs)));
        self::assertSame($pageSlugs, array_values(array_unique($pageSlugs)));
        self::assertSame($downloadSlugs, array_values(array_unique($downloadSlugs)));
    }

    public function testContentDemonstratesFormatsAndAudienceRules(): void
    {
        foreach (NovaTechCommunityDataset::pages() as $page) {
            self::assertContains($page['access'], ['public', 'members', 'vip', 'roles']);
        }
        self::assertContains('html', array_column(NovaTechCommunityDataset::pages(), 'format'));
        self::assertContains('markdown', array_column(NovaTechCommunityDataset::pages(), 'format'));
        self::assertContains('vip', array_column(NovaTechCommunityDataset::pages(), 'access'));
        self::assertContains('draft', array_column(NovaTechCommunityDataset::news(), 'status'));
        self::assertMatchesRegularExpression('/[A-Z]/', NovaTechCommunityDataset::PASSWORD);
        self::assertMatchesRegularExpression('/[a-z]/', NovaTechCommunityDataset::PASSWORD);
        self::assertMatchesRegularExpression('/[0-9]/', NovaTechCommunityDataset::PASSWORD);
        self::assertGreaterThanOrEqual(16, strlen(NovaTechCommunityDataset::PASSWORD));
    }
}
