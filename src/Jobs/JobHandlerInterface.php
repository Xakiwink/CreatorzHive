<?php

declare(strict_types=1);

namespace CreatorzHive\Jobs;

interface JobHandlerInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function handle(array $payload): void;
}
