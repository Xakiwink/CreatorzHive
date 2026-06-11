<?php

declare(strict_types=1);

namespace CreatorzHive\Controllers\Support;

use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Core\Http\JsonResponder;
use CreatorzHive\Core\Http\ViewRenderer;

abstract class AbstractController
{
    /** @var ViewRenderer */
    protected $views;

    /** @var JsonResponder */
    protected $json;

    /** @var Connection */
    protected $db;

    public function __construct(ViewRenderer $views, JsonResponder $json, Connection $db)
    {
        $this->views = $views;
        $this->json = $json;
        $this->db = $db;
    }

    protected function redirect(string $url): void
    {
        if (function_exists('http_redirect')) {
            http_redirect($url);

            return;
        }

        header('Location: ' . $url);
        exit;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function sessionUser(): ?array
    {
        return function_exists('session_get_user') ? session_get_user() : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function requireAuth(): array
    {
        $user = $this->sessionUser();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);
            exit;
        }

        return $user;
    }
}
