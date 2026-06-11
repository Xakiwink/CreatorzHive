<?php

declare(strict_types=1);

namespace CreatorzHive\Controllers;

use CreatorzHive\Controllers\Support\AbstractController;
use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Core\Http\JsonResponder;
use CreatorzHive\Core\Http\ViewRenderer;
use CreatorzHive\Services\ApiMetaService;
use function session_get_user;

final class ApiMetaController extends AbstractController
{
    /** @var ApiMetaService */
    private $apiMeta;

    public function __construct(
        ViewRenderer $views,
        JsonResponder $json,
        Connection $db,
        ApiMetaService $apiMeta
    ) {
        parent::__construct($views, $json, $db);
        $this->apiMeta = $apiMeta;
    }

    public function systemApiMe(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $this->json->success($this->apiMeta->authenticatedClientMeta($user), 'OK');
    }

    public function systemApiCatalog(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $role = (string) ($user['role'] ?? '');

        $this->json->success(
            [
                'api_version' => 1,
                'app' => APP_NAME,
                'app_version' => APP_VERSION,
                'envelope' => [
                    'success' => 'boolean',
                    'message' => 'string',
                    'data' => 'mixed|null',
                    'errors' => 'object (field => string[]) on validation failures',
                ],
                'routes' => $this->apiMeta->catalogRoutesForRole($role),
            ],
            'OK'
        );
    }
}
