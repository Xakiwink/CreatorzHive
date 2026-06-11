<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\TestCase;

/**
 * HTTP-like API tests: sets superglobals, starts session, dispatches the router.
 */
abstract class IntegrationTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/public/index.php',
            'SCRIPT_NAME' => '/public/index.php',
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_ACCEPT' => 'application/json',
        ];

        $this->resetRateLimitsForTests();
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        parent::tearDown();
    }

    private function resetRateLimitsForTests(): void
    {
        try {
            db_query(
                'DELETE FROM rate_limits WHERE `key` LIKE :ip OR `key` LIKE :identifier OR `key` LIKE :alert',
                [
                    'ip' => 'ip:%',
                    'identifier' => 'login_identifier:%',
                    'alert' => 'login_alert:%',
                ]
            );
        } catch (\Throwable $e) {
            // DB may be unavailable; tests will skip.
        }
    }

    /**
     * @param array<string, string|int|float|bool> $post Form fields (merged with CSRF _token)
     */
    protected function dispatchRoute(string $method, string $route, array $post = []): TestResponseException
    {
        $root = dirname(__DIR__, 2);

        require_once $root . '/backend/config/app.php';
        date_default_timezone_set((string) APP_TIMEZONE);

        error_handler_register();
        session_start_safe();
        $token = csrf_generate_token();

        $_SERVER['REQUEST_METHOD'] = strtoupper($method);
        $_GET['route'] = $route;
        $_POST = array_merge($post, ['_token' => $token]);

        router_reset();
        require $root . '/backend/routes/web.php';
        router_api_mode(true);
        require $root . '/backend/routes/api.php';

        try {
            router_dispatch();
        } catch (TestResponseException $e) {
            return $e;
        }

        $this->fail('Expected JSON response (TestResponseException), got nothing.');
    }

    /**
     * Use a distinct IP so parallel test methods do not share rate limits.
     */
    protected function uniqueClientIp(): string
    {
        return '10.' . random_int(0, 255) . '.' . random_int(0, 255) . '.' . random_int(1, 254);
    }

    protected function requireDatabase(): void
    {
        try {
            db_fetch('SELECT 1');
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database not available: ' . $e->getMessage());
        }
    }
}
