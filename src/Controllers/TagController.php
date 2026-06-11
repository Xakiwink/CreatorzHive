<?php

declare(strict_types=1);

namespace CreatorzHive\Controllers;

use CreatorzHive\Controllers\Support\AbstractController;
use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Core\Http\JsonResponder;
use CreatorzHive\Core\Http\ViewRenderer;
use CreatorzHive\Repositories\TagRepository;
use function request_all;
use function request_json_body;
use function session_get_user;
use function validator_validate;

final class TagController extends AbstractController
{
    /** @var TagRepository */
    private $tags;

    public function __construct(
        ViewRenderer $views,
        JsonResponder $json,
        Connection $db,
        TagRepository $tags
    ) {
        parent::__construct($views, $json, $db);
        $this->tags = $tags;
    }

    public function index(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $this->json->success($this->tags->getByUser((int) $user['id']), 'Tags loaded');
    }

    public function store(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $payload = array_merge(request_all(), request_json_body());
        $validation = validator_validate($payload, [
            'name' => 'required|max:100',
            'color' => 'max:7',
        ]);

        if (!$validation['valid']) {
            $this->json->error('Validation failed', 422, $validation['errors']);

            return;
        }

        $name = trim((string) $payload['name']);
        $color = isset($payload['color']) && preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $payload['color'])
            ? (string) $payload['color']
            : '#6C5CE7';

        $userId = (int) $user['id'];
        $existing = $this->tags->findByName($userId, $name);
        if ($existing !== null) {
            $this->json->success($existing, 'Tag already exists');

            return;
        }

        $id = $this->tags->create($userId, $name, $color);
        $row = $this->tags->findById($id);
        $this->json->success($row, 'Tag created');
    }
}
