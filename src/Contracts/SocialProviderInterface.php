<?php

declare(strict_types=1);

namespace CreatorzHive\Contracts;

interface SocialProviderInterface
{
    public function getPlatformSlug(): string;

    public function isConfigured(): bool;

    public function authorizeUrl(string $state): string;

    public function completeConnection(int $userId, string $code): array;

    public function refreshToken(array $account): array;

    public function revokeAccess(array $account): bool;

    public function publish(array $account, array $post): array;

    public function getAnalytics(array $account, string $date): array;
}
