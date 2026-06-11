<?php

declare(strict_types=1);

namespace CreatorzHive\Jobs;

use CreatorzHive\Repositories\NotificationPreferenceRepository;
use CreatorzHive\Repositories\UserRepository;
use RuntimeException;

final class SendNotificationJob implements JobHandlerInterface
{
    /** @var UserRepository */
    private $users;

    /** @var NotificationPreferenceRepository */
    private $preferences;

    public function __construct(UserRepository $users, NotificationPreferenceRepository $preferences)
    {
        $this->users = $users;
        $this->preferences = $preferences;
    }

    public function handle(array $payload): void
    {
        $userId = (int) ($payload['user_id'] ?? 0);
        $type = (string) ($payload['type'] ?? '');
        $data = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : [];

        if ($userId < 1 || $type === '') {
            throw new RuntimeException('user_id and type required');
        }

        $prefKey = $this->preferenceKeyForType($type);
        if ($prefKey !== null) {
            $prefs = $this->preferences->preferenceGetByUserId($userId);
            if ($prefs !== null && empty($prefs[$prefKey])) {
                return;
            }
        }

        $user = $this->users->findById($userId);
        if ($user === null) {
            throw new RuntimeException('User not found');
        }

        $email = (string) ($user['email'] ?? '');
        if ($email === '') {
            return;
        }

        $name = (string) ($user['name'] ?? 'there');
        $data = array_merge(
            [
                'subject' => 'CreatorzHive',
                'body' => 'You have a new notification.',
            ],
            $data
        );
        $subject = (string) $data['subject'];
        $template = (string) ($data['template'] ?? 'notification-generic');
        if (strlen($template) > 5 && substr($template, -5) === '.html') {
            $template = substr($template, 0, -5);
        }

        $body = mailer_render_template_by_name($template . '.html', array_merge($data, ['name' => $name, 'subject' => $subject]));
        mailer_send($email, $subject, $body);
    }

    private function preferenceKeyForType(string $type): ?string
    {
        switch ($type) {
            case 'post_published':
                return 'email_post_published';
            case 'post_failed':
                return 'email_post_failed';
            case 'deal_updated':
                return 'email_deal_updated';
            case 'invoice_paid':
                return 'email_invoice_paid';
            case 'weekly_summary':
                return 'email_weekly_summary';
            default:
                return null;
        }
    }
}
