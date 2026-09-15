<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Integration;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Auth\AuthController;
use NovaNuke\Auth\LoginValidator;
use NovaNuke\Auth\UserLoggedIn;
use NovaNuke\Core\Events\EventDispatcher;
use NovaNuke\Core\Database\Migrator;
use NovaNuke\Core\Security\AuthorizationService;
use NovaNuke\Core\Security\SessionManager;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\Security\RateLimiter;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\View\ViewRenderer;
use NovaNuke\Tests\Integration\Support\MySqlIntegrationTestCase;

final class CoreAuthenticationIntegrationTest extends MySqlIntegrationTestCase
{
    public function testAuthenticationSessionHistoryEventAndAuthorizationWorkTogether(): void
    {
        $migrationStatus = (new Migrator($this->db()))->status(dirname(__DIR__, 2) . '/database/migrations');
        self::assertSame([], $migrationStatus['pending']);
        self::assertSame([], $migrationStatus['missing_files']);

        $insert = $this->db()->prepare(
            'INSERT INTO users (username,email,password_hash,status,email_verified_at,created_at,updated_at) '
            . 'VALUES (:username,:email,:password,:status,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())'
        );
        $insert->execute([
            'username' => 'integration-admin',
            'email' => 'integration@example.test',
            'password' => password_hash('Correct-Horse-92!', PASSWORD_DEFAULT),
            'status' => 'active',
        ]);
        $userId = (int) $this->db()->lastInsertId();
        $this->db()->prepare(
            "INSERT INTO user_roles (user_id,role_id,created_at) SELECT :user_id,id,UTC_TIMESTAMP() FROM roles WHERE slug='administrator'"
        )->execute(['user_id' => $userId]);

        $_SESSION = [];
        $session = new SessionManager('novanuke_integration_' . bin2hex(random_bytes(4)), false);
        $session->start();
        $events = new EventDispatcher();
        $loggedInUser = null;
        $events->listen('user.logged_in', static function (object $event) use (&$loggedInUser): void {
            if ($event instanceof UserLoggedIn) $loggedInUser = $event->userId;
        });
        $auth = new AuthManager($this->db(), $session, $events);

        self::assertNull($auth->attempt('integration-admin', 'Wrong-password', '127.0.0.1', 'PHPUnit'));
        $user = $auth->attempt('integration-admin', 'Correct-Horse-92!', '127.0.0.1', 'PHPUnit');

        self::assertSame($userId, (int) $user['id']);
        self::assertSame($userId, $loggedInUser);
        self::assertSame($userId, (int) $auth->user()['id']);
        self::assertSame(1, (int) $this->db()->query('SELECT COUNT(*) FROM user_login_history')->fetchColumn());
        self::assertTrue((new AuthorizationService($this->db()))->allows($userId, 'admin.access'));
        self::assertFalse((new AuthorizationService($this->db()))->allows($userId, 'roles.manage'));

        $auth->logout();
        self::assertNull($auth->user());
    }

    public function testPostLogoutWithCsrfClosesTheAuthenticatedSession(): void
    {
        $password='Correct-Horse-93!';
        $this->db()->prepare("INSERT INTO users(username,email,password_hash,status,email_verified_at,created_at,updated_at) VALUES('logout-member','logout@example.test',:hash,'active',UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())")
            ->execute(['hash'=>password_hash($password,PASSWORD_DEFAULT)]);
        $_SESSION=[];$session=new SessionManager('novanuke_logout_'.bin2hex(random_bytes(4)),false);$session->start();
        $auth=new AuthManager($this->db(),$session,new EventDispatcher());
        self::assertNotNull($auth->attempt('logout-member',$password,'127.0.0.1','PHPUnit'));
        $csrf=new CsrfTokenManager($session);$token=$csrf->token();
        $limiter=new class implements RateLimiter{public function tooManyAttempts(string$key):bool{return false;}public function hit(string$key):void{}public function clear(string$key):void{}public function retryAfter(string$key):int{return 0;}};
        $root=dirname(__DIR__,2);$views=new ViewRenderer($root.'/resources/views',$root.'/storage/cache/twig',true,new \NovaNuke\Core\I18n\Translator('en','en',$root.'/language'));
        $controller=new AuthController($auth,new AuthorizationService($this->db()),$limiter,new LoginValidator(),$csrf,$views);
        $response=$controller->logout(new Request('POST','/logout',request:['_token'=>$token]));
        self::assertSame(302,$response->status());self::assertSame('/login',$response->header('Location'));self::assertNull($auth->user());
    }
}
