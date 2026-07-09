<?php

declare(strict_types=1);

namespace CreatorzHive\Controllers;

use CreatorzHive\Controllers\Support\AbstractController;
use CreatorzHive\Core\Http\JsonResponder;
use CreatorzHive\Core\Http\ViewRenderer;
use function route_url;
use function session_get_user;

final class SystemController extends AbstractController
{
    public function home(): void
    {
        $user = session_get_user();
        if ($user !== null) {
            $landing = (string) ($user['role'] ?? '') === 'admin' ? 'admin-dashboard' : 'dashboard';
            $this->redirect(route_url($landing));

            return;
        }

        require_once base_path('frontend/pages/home.php');
    }

    public function ping()
    {
        $this->json->success(
                [
                    'ok' => true,
                    'app' => APP_NAME,
                    'version' => APP_VERSION,
                    'environment' => APP_ENV,
                    'time' => gmdate('c'),
                ],
                'pong'
            );
    }

    public function dbTest()
    {
        try {
                $row = $this->db->fetchOne('SELECT VERSION() AS version');
                $this->json->success(['mysql_version' => $row['version'] ?? null], 'Database connection successful');
            } catch (\Throwable $e) {
                $this->json->error('Database connection failed', 500, ['db' => $e->getMessage()]);
            }
    }

    public function privacyPolicy(): void
    {
        require_once base_path('frontend/pages/privacy-policy.php');
    }

    public function termsOfService(): void
    {
        require_once base_path('frontend/pages/terms-of-service.php');
    }

}
