<?php

declare(strict_types=1);

namespace CreatorzHive\Helpers;

final class PlatformHelper
{
    /** @return list<string> */
    public static function slugs(): array
    {
        return ['instagram', 'tiktok', 'youtube', 'facebook', 'twitter'];
    }

    public static function normalize(?string $platform): ?string
    {
        if ($platform === null || trim($platform) === '') {
            return null;
        }

        $platform = strtolower(trim($platform));

        return in_array($platform, self::slugs(), true) ? $platform : null;
    }
}
