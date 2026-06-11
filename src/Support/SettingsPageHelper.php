<?php

declare(strict_types=1);

namespace CreatorzHive\Support;

final class SettingsPageHelper
{
    private const AVATAR_MAX_BYTES = 2097152;

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function publicUser(array $user): array
    {
        unset($user['password']);

        $av = (string) ($user['avatar_url'] ?? '');
        if ($av !== '' && preg_match('#^https?://#i', $av) !== 1) {
            $user['avatar_url'] = upload_url(ltrim($av, '/'));
        }

        return $user;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function normalizeProfilePayload(array $input): array
    {
        return [
            'name' => trim((string) ($input['name'] ?? '')),
            'username' => strtolower(trim((string) ($input['username'] ?? ''))),
            'bio' => isset($input['bio']) ? trim((string) $input['bio']) : '',
            'website_url' => trim((string) ($input['website_url'] ?? '')),
            'timezone' => trim((string) ($input['timezone'] ?? 'Africa/Dar_es_Salaam')),
        ];
    }

    public function avatarUploadErrorMessage(int $code): string
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'Avatar file is too large (max 2MB).';
            case UPLOAD_ERR_PARTIAL:
                return 'Avatar upload was interrupted. Please try again.';
            case UPLOAD_ERR_NO_FILE:
                return 'No avatar file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
            case UPLOAD_ERR_CANT_WRITE:
            case UPLOAD_ERR_EXTENSION:
                return 'Server could not store the avatar. Contact support.';
            default:
                return 'Avatar upload failed.';
        }
    }

    /**
     * @param array<string, mixed> $file
     */
    public function processAvatarUpload(array $file, int $userId): ?string
    {
        if ($userId < 1 || !isset($file['tmp_name']) || !is_uploaded_file((string) $file['tmp_name'])) {
            return null;
        }

        if ((int) ($file['size'] ?? 0) > self::AVATAR_MAX_BYTES) {
            return null;
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

        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        if (!isset($map[$mime])) {
            return null;
        }

        $dir = public_path('uploads/avatars');
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }

        $ext = $map[$mime];
        $name = 'user_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $relative = 'uploads/avatars/' . $name;
        $dest = public_path($relative);

        if (!move_uploaded_file($tmp, $dest)) {
            return null;
        }

        $this->resizeAvatarSquare($dest, 200);

        return $relative;
    }

    private function resizeAvatarSquare(string $path, int $size): void
    {
        if (!extension_loaded('gd') || !is_file($path)) {
            return;
        }

        $info = @getimagesize($path);
        if (!is_array($info)) {
            return;
        }

        $src = null;
        switch ($info[2] ?? 0) {
            case IMAGETYPE_JPEG:
                $src = @imagecreatefromjpeg($path);
                break;
            case IMAGETYPE_PNG:
                $src = @imagecreatefrompng($path);
                break;
            case IMAGETYPE_GIF:
                $src = @imagecreatefromgif($path);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    $src = @imagecreatefromwebp($path);
                }
                break;
        }

        if ($src === false || $src === null) {
            return;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        if ($w < 1 || $h < 1) {
            imagedestroy($src);

            return;
        }

        $side = min($w, $h);
        $srcX = (int) floor(($w - $side) / 2);
        $srcY = (int) floor(($h - $side) / 2);

        $dst = imagecreatetruecolor($size, $size);
        if ($dst === false) {
            imagedestroy($src);

            return;
        }

        imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $size, $size, $side, $side);
        imagedestroy($src);

        switch ($info[2] ?? 0) {
            case IMAGETYPE_JPEG:
                imagejpeg($dst, $path, 88);
                break;
            case IMAGETYPE_PNG:
                imagepng($dst, $path, 8);
                break;
            case IMAGETYPE_GIF:
                imagegif($dst, $path);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagewebp')) {
                    imagewebp($dst, $path, 88);
                }
                break;
        }

        imagedestroy($dst);
    }
}
