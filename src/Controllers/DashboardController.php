<?php

declare(strict_types=1);

namespace CreatorzHive\Controllers;

use CreatorzHive\Controllers\Support\AbstractController;
use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Core\Http\JsonResponder;
use CreatorzHive\Core\Http\ViewRenderer;
use CreatorzHive\Services\DashboardService;
use Throwable;

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
        try {
            $payload = $this->dashboard->buildPayload((int) $user['id']);
            $this->json->success($payload, 'Dashboard data loaded');
        } catch (Throwable $e) {
            // Log error but return safe fallback data
            error_log('Dashboard data error: ' . $e->getMessage());
            $this->json->success([
                'stats' => [
                    'total_posts' => 0,
                    'published_posts' => 0,
                    'scheduled_posts' => 0,
                    'total_followers' => 0,
                    'avg_engagement_rate' => 0,
                    'total_revenue' => 0,
                    'active_deals' => 0,
                    'unread_notifications' => 0,
                    'trend_posts' => 0,
                    'trend_published' => 0,
                    'trend_scheduled' => 0,
                    'trend_followers' => 0,
                ],
                'recent_posts' => [],
                'upcoming_posts' => [],
                'platform_status' => [],
                'post_status_breakdown' => [],
            ], 'Dashboard data loaded');
        }
    }
}
