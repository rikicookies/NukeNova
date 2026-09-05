<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Security\AdminAccessGate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AdminAccessGateTest extends TestCase
{
    #[DataProvider('protectedPaths')]
    public function testItProtectsOnlyTheAdministrationNamespace(string $path): void
    {
        self::assertTrue((new AdminAccessGate())->protects(Request::create('GET', $path)));
    }

    public static function protectedPaths(): array
    {
        return [['/admin'], ['/admin/users'], ['/admin/modules/1']];
    }

    #[DataProvider('publicPaths')]
    public function testItDoesNotCaptureSimilarPublicPaths(string $path): void
    {
        self::assertFalse((new AdminAccessGate())->protects(Request::create('GET', $path)));
    }

    public static function publicPaths(): array
    {
        return [['/'], ['/administrator'], ['/news/admin']];
    }

    public function testItRedirectsGuestsAndForbidsUsersWithoutPanelAccess(): void
    {
        $gate = new AdminAccessGate();
        $request = Request::create('GET', '/admin/themes');

        self::assertSame(302, $gate->guard($request, null, false)?->status());
        self::assertSame('/login', $gate->guard($request, null, false)?->header('Location'));
        self::assertSame(403, $gate->guard($request, ['id' => 8], false)?->status());
        self::assertNull($gate->guard($request, ['id' => 8], true));
        self::assertNull($gate->guard(Request::create('GET', '/news'), null, false));
    }
}
