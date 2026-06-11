<?php

declare(strict_types=1);

namespace CreatorzHive\Controllers;

use CreatorzHive\Controllers\Support\AbstractController;
use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Core\Http\JsonResponder;
use CreatorzHive\Core\Http\ViewRenderer;
use CreatorzHive\Services\DashboardService;

final class DashboardController extends AbstractController
{
    /** @var DashboardService */
    private $dashboard;

    public function __construct(
        ViewRenderer $views,
        JsonResponder $json,
        Connection $db,
        DashboardService $dashboard
    ) {
        parent::__construct($views, $json, $db);
        $this->dashboard = $dashboard;
    }

    public function index(): void
    {
        $this->views->render('dashboard/index');
    }

    public function data(): void
    {
        $user = $this->requireAuth();
        $payload = $this->dashboard->buildPayload((int) $user['id']);
        $this->json->success($payload, 'Dashboard data loaded');
    }
}
