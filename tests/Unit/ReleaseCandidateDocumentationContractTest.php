<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ReleaseCandidateDocumentationContractTest extends TestCase
{
    public function testReadmeAndRcDocsDescribeCurrentAcceptanceFlow(): void
    {
        $root=dirname(__DIR__,2);
        $readme=(string)file_get_contents($root.'/README.md');
        $acceptance=(string)file_get_contents($root.'/docs/RC_ACCEPTANCE.md');
        $clean=(string)file_get_contents($root.'/docs/CLEAN_INSTALL_CHECKLIST.md');
        $updating=(string)file_get_contents($root.'/docs/UPDATING.md');

        self::assertStringContainsString('third Release Candidate for the 0.4.0 line',$readme);
        self::assertStringNotContainsString('package remains alpha',strtolower($readme));
        self::assertStringContainsString('composer check:rc-source',$acceptance);
        self::assertStringContainsString('composer check:site',$acceptance);
        self::assertStringContainsString('composer check:release',$acceptance);
        self::assertStringContainsString('backup:verify',$acceptance);
        self::assertStringContainsString('SMTP',$acceptance);
        self::assertStringContainsString('current NovaNuke beta/Release Candidate line',$clean);
        self::assertStringContainsString('--from=CURRENT_INSTALLED_VERSION',$updating);
    }

    public function testRcSourceCheckIsDatabaseIndependentAndSeparateFromInstalledSiteChecks(): void
    {
        $root=dirname(__DIR__,2);
        $cli=(string)file_get_contents($root.'/bin/cms');
        $composer=json_decode((string)file_get_contents($root.'/composer.json'),true,32,JSON_THROW_ON_ERROR);

        $rcStart=strpos($cli,"if (\$command === 'rc:check')");
        $releaseStart=strpos($cli,"if (\$command === 'release:check')",$rcStart);
        self::assertNotFalse($rcStart);
        self::assertNotFalse($releaseStart);
        self::assertLessThan($releaseStart,$rcStart);

        $script=$composer['scripts']['check:rc-source']??[];
        self::assertNotContains('@php bin/cms site:check',$script);
        self::assertNotContains('@php bin/cms production:check',$script);
    }
}
