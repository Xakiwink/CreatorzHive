<?php

declare(strict_types=1);

namespace CreatorzHive\Controllers;

use CreatorzHive\Controllers\Support\AbstractController;
use CreatorzHive\Core\Http\JsonResponder;
use CreatorzHive\Core\Http\ViewRenderer;

final class SystemController extends AbstractController
{

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

}
