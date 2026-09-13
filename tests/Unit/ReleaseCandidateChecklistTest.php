<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\System\ReleaseCandidateChecklist;
use NovaNuke\Core\Version;
use PHPUnit\Framework\TestCase;

final class ReleaseCandidateChecklistTest extends TestCase
{
    public function testCleanFixturePassesRcSourceChecklist(): void
    {
        $root=$this->fixtureRoot();
        try{
            $this->copyDistributionFixture($root);

            foreach((new ReleaseCandidateChecklist($root))->run() as $check){
                self::assertTrue($check['passed'],$check['name'].': '.$check['detail']);
            }
        }finally{
            $this->removeTree($root);
        }
    }

    public function testRcChecklistRejectsEnvironmentAndRuntimeArtifacts(): void
    {
        $root=$this->fixtureRoot();
        try{
            $this->copyDistributionFixture($root);

            file_put_contents($root.'/.env',"APP_ENV=testing\n");
            file_put_contents($root.'/storage/installed.lock',"{}\n");
            file_put_contents($root.'/storage/logs/novanuke.log',"test\n");
            file_put_contents($root.'/storage/cache/generated.php',"<?php\n");
            file_put_contents($root.'/storage/private/backups/example.sql',"secret\n");

            $byName=[];
            foreach((new ReleaseCandidateChecklist($root))->run() as $check){
                $byName[$check['name']]=$check;
            }

            self::assertFalse($byName['Clean source package']['passed']);
            self::assertStringContainsString('.env',$byName['Clean source package']['detail']);
            self::assertStringContainsString('storage/installed.lock',$byName['Clean source package']['detail']);
            self::assertStringContainsString('storage/logs/novanuke.log',$byName['Clean source package']['detail']);
            self::assertStringContainsString('storage/cache/generated.php',$byName['Clean source package']['detail']);
            self::assertStringContainsString('storage/private/backups/example.sql',$byName['Clean source package']['detail']);
        }finally{
            $this->removeTree($root);
        }
    }

    public function testComposerExposesRcSourceValidation(): void
    {
        $root=dirname(__DIR__,2);
        $composer=json_decode((string)file_get_contents($root.'/composer.json'),true,32,JSON_THROW_ON_ERROR);
        self::assertSame([
            '@php bin/cms rc:check',
            '@php bin/cms release:check',
            '@php bin/cms release:smoke',
            '@php bin/cms theme:check',
        ],$composer['scripts']['check:rc-source']??null);
    }

    private function fixtureRoot(): string
    {
        $root=sys_get_temp_dir().'/novanuke-rc-'.bin2hex(random_bytes(6));
        if(!mkdir($root,0770,true)&&!is_dir($root)){
            throw new \RuntimeException('Unable to create RC fixture root.');
        }
        return $root;
    }

    private function copyDistributionFixture(string $targetRoot): void
    {
        $source=dirname(__DIR__,2);
        $files=[
            'README.md',
            'composer.json',
            'composer.lock',
            '.env.example',
            'public/index.php',
            'public/.htaccess',
            'public/.user.ini',
            'public/uploads/.htaccess',
            'bootstrap/app.php',
            'storage/private/.htaccess',
            'docs/INSTALLATION.md',
            'docs/PRODUCTION.md',
            'docs/PRODUCTION_HARDENING.md',
            'docs/UPDATING.md',
            'docs/SECURITY_CHECKLIST.md',
            'docs/CLEAN_INSTALL_CHECKLIST.md',
            'docs/KNOWN_ISSUES.md',
            'docs/TESTING.md',
            'docs/RELEASE.md',
            'docs/RC_ACCEPTANCE.md',
            'docs/RECOVERY.md',
            'docs/RELEASE_NOTES_'.Version::CURRENT.'.md',
        ];

        foreach($files as $relative){
            $target=$targetRoot.'/'.$relative;
            if(!is_dir(dirname($target))&&!mkdir(dirname($target),0770,true)&&!is_dir(dirname($target))){
                throw new \RuntimeException('Unable to create fixture directory.');
            }
            if(!copy($source.'/'.$relative,$target)){
                throw new \RuntimeException("Unable to copy fixture file {$relative}.");
            }
        }

        foreach([
            'app',
            'storage/cache',
            'storage/logs',
            'storage/sessions',
            'storage/private/backups',
        ] as $directory){
            if(!is_dir($targetRoot.'/'.$directory)
                &&!mkdir($targetRoot.'/'.$directory,0770,true)
                &&!is_dir($targetRoot.'/'.$directory)){
                throw new \RuntimeException("Unable to create fixture directory {$directory}.");
            }
        }
    }

    private function removeTree(string $root): void
    {
        if(!is_dir($root)) return;
        $iterator=new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root,\FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach($iterator as $item){
            $item->isDir()?@rmdir($item->getPathname()):@unlink($item->getPathname());
        }
        @rmdir($root);
    }
}
