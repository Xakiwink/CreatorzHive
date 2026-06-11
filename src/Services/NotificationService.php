<?php

declare(strict_types=1);

namespace CreatorzHive\Services;

use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Repositories\NotificationPreferenceRepository;
use CreatorzHive\Repositories\NotificationRepository;
use CreatorzHive\Repositories\UserRepository;

final class NotificationService
{
    /** @var Connection */
    private $db;

    /** @var UserRepository */
    private $users;

    /** @var NotificationPreferenceRepository */
    private $preferences;

    /** @var NotificationRepository */
    private $notifications;

    public function __construct(
        Connection $db,
        UserRepository $users,
        NotificationPreferenceRepository $preferences,
        NotificationRepository $notifications
    ) {
        $this->db = $db;
        $this->users = $users;
        $this->preferences = $preferences;
        $this->notifications = $notifications;
    }

    public function prefs(int $userId)
    {
        $row = $this->preferences->preferenceGetByUserId($userId);
            if ($row === null) {
                return [
                    'email_post_published' => 1,
                    'email_post_failed' => 1,
                    'email_deal_updated' => 1,
                    'email_invoice_paid' => 1,
                    'email_weekly_summary' => 1,
                    'push_post_published' => 1,
                    'push_deal_updated' => 1,
                ];
            }
        
            return $row;
    }

    public function userEmail(int $userId)
    {
        $u = $this->users->findById($userId);
        
            return (string) ($u['email'] ?? '');
    }

    public function maybeEmail(int $userId, string $prefKey, string $subject, string $html)
    {
        $p = $this->prefs($userId);
            if (empty($p[$prefKey])) {
                return;
            }
            $to = $this->userEmail($userId);
            if ($to === '') {
                return;
            }
            mailer_send($to, $subject, $html);
    }

    public function allowInApp(int $userId, string $type)
    {
        $p = $this->prefs($userId);
            switch ($type) {
                case 'post_published':
                    return !empty($p['push_post_published']);
                case 'deal_updated':
                case 'deal_completed':
                    return !empty($p['push_deal_updated']);
                case 'post_failed':
                case 'invoice_paid':
                case 'welcome':
                default:
                    return true;
            }
    }

    public function createInApp(
    int $userId,
    string $type,
    string $title,
    string $body = '',
    string $actionUrl = '',
    ?string $icon = null
)
    {
        if (!notification_service_allow_in_app($userId, $type)) {
                return;
            }
            notification_create($userId, $type, $title, $body, $actionUrl, $icon);
    }

    public function postPublished(int $userId, string $postTitle, int $postId)
    {
        $url = route_url('planner', ['highlight_post' => (string) $postId]);
            $this->createInApp(
                $userId,
                'post_published',
                'Post published',
                '“' . $postTitle . '” is now live.',
                $url,
                '✅'
            );
            $this->maybeEmail(
                $userId,
                'email_post_published',
                'Your post was published',
                '<p>“' . htmlspecialchars($postTitle, ENT_QUOTES, 'UTF-8') . '” is now published.</p><p><a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">Open planner</a></p>'
            );
    }

    public function postFailed(int $userId, string $postTitle, string $reason)
    {
        $url = route_url('planner');
            $this->createInApp(
                $userId,
                'post_failed',
                'Post failed',
                '“' . $postTitle . '” — ' . $reason,
                $url,
                '❌'
            );
            $this->maybeEmail(
                $userId,
                'email_post_failed',
                'A post failed to publish',
                '<p>“' . htmlspecialchars($postTitle, ENT_QUOTES, 'UTF-8') . '”</p><p>' . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') . '</p>'
            );
    }

    public function dealStatusChanged(int $userId, string $dealTitle, string $newStatus)
    {
        $url = route_url('deals');
            $this->createInApp(
                $userId,
                'deal_updated',
                'Deal updated',
                '“' . $dealTitle . '” moved to ' . $newStatus . '.',
                $url,
                '🤝'
            );
            $this->maybeEmail(
                $userId,
                'email_deal_updated',
                'Deal status updated',
                '<p>“' . htmlspecialchars($dealTitle, ENT_QUOTES, 'UTF-8') . '” is now <strong>' . htmlspecialchars($newStatus, ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
            );
    }

    public function dealCompleted(int $userId, string $dealTitle, int $dealId)
    {
        $url = route_url('deals', ['id' => (string) $dealId]);
            $this->createInApp(
                $userId,
                'deal_completed',
                'Deal completed 🎉',
                '“' . $dealTitle . '” is marked completed.',
                $url,
                '🏆'
            );
            $this->maybeEmail(
                $userId,
                'email_deal_updated',
                'Deal completed',
                '<p>Congratulations — “' . htmlspecialchars($dealTitle, ENT_QUOTES, 'UTF-8') . '” is completed.</p>'
            );
    }

    public function invoicePaid(int $userId, string $invoiceNumber, string $amount)
    {
        $url = route_url('invoices');
            $this->createInApp(
                $userId,
                'invoice_paid',
                'Invoice paid',
                $invoiceNumber . ' · ' . $amount,
                $url,
                '💰'
            );
            $this->maybeEmail(
                $userId,
                'email_invoice_paid',
                'Invoice marked paid',
                '<p>' . htmlspecialchars($invoiceNumber, ENT_QUOTES, 'UTF-8') . ' (' . htmlspecialchars($amount, ENT_QUOTES, 'UTF-8') . ') was marked paid.</p>'
            );
    }

    public function welcomeUser(int $userId, string $name)
    {
        $url = route_url('dashboard');
            $this->createInApp(
                $userId,
                'welcome',
                'Welcome to CreatorzHive',
                'Hi ' . $name . ' — your workspace is ready.',
                $url,
                '👋'
            );
    }

}
