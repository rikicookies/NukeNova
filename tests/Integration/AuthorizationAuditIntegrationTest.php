<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Integration;

use NovaNuke\Core\Security\AuthorizationAudit;
use NovaNuke\Tests\Integration\Support\MySqlIntegrationTestCase;

final class AuthorizationAuditIntegrationTest extends MySqlIntegrationTestCase
{
    public function testHealthyAuthorizationStatePassesAndMissingSuperPermissionIsDetected(): void
    {
        $user=$this->createActiveSuperAdministrator();

        $checks=(new AuthorizationAudit($this->db()))->run();
        $byLabel=[];
        foreach($checks as $check) $byLabel[$check['label']]=$check;

        self::assertTrue($byLabel['Active Super Administrator']['passed']);
        self::assertTrue($byLabel['Core permission catalog']['passed']);
        self::assertTrue($byLabel['Super Administrator core permissions']['passed']);
        self::assertTrue($byLabel['Public roles']['passed']);
        self::assertTrue($byLabel['Administrative role access']['passed']);

        $statement=$this->db()->prepare(
            "DELETE rp FROM role_permissions rp "
            . "INNER JOIN roles r ON r.id=rp.role_id "
            . "INNER JOIN permissions p ON p.id=rp.permission_id "
            . "WHERE r.slug='super-administrator' AND p.slug='settings.manage'"
        );
        $statement->execute();

        $checks=(new AuthorizationAudit($this->db()))->run();
        $byLabel=[];
        foreach($checks as $check) $byLabel[$check['label']]=$check;

        self::assertFalse($byLabel['Super Administrator core permissions']['passed']);
        self::assertStringContainsString('settings.manage',$byLabel['Super Administrator core permissions']['detail']);
        self::assertGreaterThan(0,$user);
    }

    private function createActiveSuperAdministrator(): int
    {
        $statement=$this->db()->prepare(
            'INSERT INTO users (username,email,password_hash,status,email_verified_at,created_at,updated_at) '
            . "VALUES ('rc_security_admin','rc-security@example.test',:password,'active',UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())"
        );
        $statement->execute(['password'=>password_hash('Security-Test-Password-92!',PASSWORD_DEFAULT)]);
        $user=(int)$this->db()->lastInsertId();

        $role=(int)$this->db()->query("SELECT id FROM roles WHERE slug='super-administrator'")->fetchColumn();
        self::assertGreaterThan(0,$role);

        $assign=$this->db()->prepare(
            'INSERT INTO user_roles (user_id,role_id,created_at) VALUES (:user,:role,UTC_TIMESTAMP())'
        );
        $assign->execute(['user'=>$user,'role'=>$role]);

        return $user;
    }
}
