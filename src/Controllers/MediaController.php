<?php

declare(strict_types=1);

namespace CreatorzHive\Controllers;

use CreatorzHive\Controllers\Support\AbstractController;
use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Core\Http\JsonResponder;
use CreatorzHive\Core\Http\ViewRenderer;
use CreatorzHive\Repositories\MediaFileRepository;
use CreatorzHive\Support\MediaUploadHelper;
use function public_path;
use function request_all;
use function request_file;
use function request_get;
use function request_json_body;
use function session_get_user;
use function upload_url;

final class MediaController extends AbstractController
{
    /** @var MediaFileRepository */
    private $media;

    /** @var MediaUploadHelper */
    private $uploads;

    public function __construct(
        ViewRenderer $views,
        JsonResponder $json,
        Connection $db,
        MediaFileRepository $media,
        MediaUploadHelper $uploads
    ) {
        parent::__construct($views, $json, $db);
        $this->media = $media;
        $this->uploads = $uploads;
    }

    public function index(): void
    {
        $this->views->render('media/index');
    }

    public function list(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);
        }

        $type = request_get('type', '');
        $type = in_array($type, ['image', 'video'], true) ? $type : null;
        $page = max(1, (int) request_get('page', 1));
        $perPage = min(50, max(1, (int) request_get('per_page', 24)));

        $all = $this->media->fileGetByUser((int) $user['id'], $type);
        $total = count($all);
        $offset = ($page - 1) * $perPage;
        $slice = array_slice($all, $offset, $perPage);
        $slice = array_map(static function (array $r): array {
            $r['cdn_url'] = upload_url(ltrim((string) $r['cdn_url'], '/'));
            if (!empty($r['thumbnail_url'])) {
                $r['thumbnail_url'] = upload_url(ltrim((string) $r['thumbnail_url'], '/'));
            }

            return $r;
        }, $slice);

        $this->json->success([
            'files' => $slice,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $perPage > 0 ? (int) ceil($total / $perPage) : 0,
            ],
        ], 'Media loaded');
    }

    public function upload(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);
        }

        $file = request_file('file');
        if ($file === null || !$this->isUploadedFile($file)) {
            $this->json->error('No file uploaded', 422);
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $this->json->error('Upload failed', 422);
        }

        if (($file['size'] ?? 0) > MediaUploadHelper::MAX_BYTES) {
            $this->json->error('File too large (max 10MB)', 422);
        }

        $tmp = (string) $file['tmp_name'];
        $mime = '';
        if (extension_loaded('fileinfo')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            if ($fi !== false) {
                $mime = (string) finfo_file($fi, $tmp);
                finfo_close($fi);
            }
        }

        $mimeMap = $this->uploads->mimeExtensions();
        if (!isset($mimeMap[$mime])) {
            $this->json->error('Invalid file type', 422);
        }

        $ext = $mimeMap[$mime];
        $year = date('Y');
        $month = date('m');
        $dir = public_path('uploads/' . $year . '/' . $month);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            $this->json->error('Could not create upload directory', 500);
        }

        $base = bin2hex(random_bytes(16)) . '.' . $ext;
        $relative = 'uploads/' . $year . '/' . $month . '/' . $base;
        $dest = public_path($relative);

        if (!$this->storeUploadedFile($tmp, $dest)) {
            $this->json->error('Could not save file', 500);
        }

        $width = null;
        $height = null;
        $thumbRelative = null;
        if (strpos($mime, 'image/') === 0) {
            $info = @getimagesize($dest);
            if (is_array($info)) {
                $width = (int) $info[0];
                $height = (int) $info[1];
            }
            $thumbBase = 'thumb_' . preg_replace('/\.[^.]+$/', '', $base) . '.jpg';
            $thumbRelative = 'uploads/' . $year . '/' . $month . '/' . $thumbBase;
            $thumbPath = public_path($thumbRelative);
            if (!$this->uploads->writeImageThumbnail($dest, $thumbPath, 300)) {
                $thumbRelative = null;
            }
        }

        $id = $this->media->fileCreate([
            'user_id' => (int) $user['id'],
            'file_name' => $base,
            'original_name' => (string) ($file['name'] ?? $base),
            'file_path' => $relative,
            'cdn_url' => $relative,
            'thumbnail_url' => $thumbRelative,
            'mime_type' => $mime,
            'file_size' => (int) ($file['size'] ?? 0),
            'width' => $width,
            'height' => $height,
            'duration' => null,
        ]);

        $this->json->success([
            'id' => $id,
            'file_path' => $relative,
            'cdn_url' => upload_url($relative),
            'thumbnail_url' => $thumbRelative !== null ? upload_url($thumbRelative) : null,
            'mime_type' => $mime,
        ], 'Upload complete');
    }

    public function delete(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);
        }

        $payload = array_merge(request_all(), request_json_body());
        $id = (int) ($payload['id'] ?? $payload['media_id'] ?? 0);
        if ($id < 1) {
            $this->json->error('Invalid media', 422);
        }

        if (!$this->media->fileDelete($id, (int) $user['id'])) {
            $this->json->error('Media not found', 404);
        }

        $this->json->success(null, 'Media deleted');
    }

    /**
     * @param array<string, mixed> $file
     */
    private function isUploadedFile(array $file): bool
    {
        if (!isset($file['tmp_name'])) {
            return false;
        }

        $tmp = (string) $file['tmp_name'];

        return is_uploaded_file($tmp)
            || (defined('CREATORZHIVE_PHPUNIT') && is_readable($tmp));
    }

    private function storeUploadedFile(string $tmp, string $dest): bool
    {
        if (is_uploaded_file($tmp)) {
            return move_uploaded_file($tmp, $dest);
        }

        if (defined('CREATORZHIVE_PHPUNIT') && is_readable($tmp)) {
            return copy($tmp, $dest);
        }

        return false;
    }
}
