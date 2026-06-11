<?php

declare(strict_types=1);

namespace CreatorzHive\Controllers;

use CreatorzHive\Controllers\Support\AbstractController;
use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Core\Http\JsonResponder;
use CreatorzHive\Core\Http\ViewRenderer;
use CreatorzHive\Repositories\NotificationRepository;
use function request_get;
use function request_post;
use function session_get_user;

final class NotificationController extends AbstractController
{
    /** @var NotificationRepository */
    private $notifications;

    public function __construct(
        ViewRenderer $views,
        JsonResponder $json,
        Connection $db,
        NotificationRepository $notifications
    ) {
        parent::__construct($views, $json, $db);
        $this->notifications = $notifications;
    }

    public function index(): void
    {
        $this->views->render('notifications/notifications');
    }

    public function data(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $userId = (int) $user['id'];
        $tab = (string) request_get('tab', 'all');
        $page = max(1, (int) request_get('page', 1));
        $perPage = max(1, min(50, (int) request_get('per_page', 20)));
        $offset = ($page - 1) * $perPage;

        $unreadOnly = $tab === 'unread';
        $category = null;
        if (in_array($tab, ['posts', 'deals', 'invoices'], true)) {
            $category = $tab;
        }

        $items = $this->notifications->getByUser($userId, $unreadOnly, $perPage, $offset, $category);
        $total = $this->notifications->countForUser($userId, $unreadOnly, $category);
        $hasMore = ($offset + count($items)) < $total;
        $unreadCount = $this->notifications->countUnread($userId);

        $this->json->success([
            'notifications' => $items,
            'unread_count' => $unreadCount,
            'has_more' => $hasMore,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
        ], 'Notifications loaded');
    }

    public function unreadCount(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $this->json->success([
            'unread_count' => $this->notifications->countUnread((int) $user['id']),
        ], 'OK');
    }

    public function postMarkRead(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $id = (int) request_post('id', 0);
        if ($id < 1) {
            $this->json->error('Invalid notification', 422);

            return;
        }

        $this->notifications->markRead($id, (int) $user['id']);
        $this->json->success(null, 'Marked read');
    }

    public function postMarkAllRead(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $this->notifications->markAllRead((int) $user['id']);
        $this->json->success(null, 'All marked read');
    }

    public function postDelete(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $id = (int) request_post('id', 0);
        if ($id < 1) {
            $this->json->error('Invalid notification', 422);

            return;
        }

        $this->notifications->delete($id, (int) $user['id']);
        $this->json->success(null, 'Deleted');
    }

    public function postDeleteRead(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $this->notifications->deleteRead((int) $user['id']);
        $this->json->success(null, 'Read notifications cleared');
    }
}
