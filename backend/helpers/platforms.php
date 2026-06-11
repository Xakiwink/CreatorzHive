<?php

declare(strict_types=1);

/** @return list<string> */
function platform_slugs_list(): array
{
    return \CreatorzHive\Helpers\PlatformHelper::slugs();
}

function platform_normalize_slug(?string $platform): ?string
{
    return \CreatorzHive\Helpers\PlatformHelper::normalize($platform);
}
