<?php

declare(strict_types=1);

namespace CreatorzHive\Support;

final class MediaUploadHelper
{
    public const MAX_BYTES = 10485760;

    /**
     * @return array<string, string>
     */
    public function mimeExtensions(): array
    {
        return [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
        ];
    }

    public function writeImageThumbnail(string $sourcePath, string $destPath, int $maxWidth): bool
    {
        if (!extension_loaded('gd') || !is_file($sourcePath)) {
            return false;
        }

        $info = @getimagesize($sourcePath);
        if (!is_array($info)) {
            return false;
        }

        $src = null;
        switch ($info[2] ?? 0) {
            case IMAGETYPE_JPEG:
                $src = @imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $src = @imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $src = @imagecreatefromgif($sourcePath);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    $src = @imagecreatefromwebp($sourcePath);
                }
                break;
        }

        if ($src === false || $src === null) {
            return false;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        if ($w < 1 || $h < 1) {
            imagedestroy($src);

            return false;
        }

        $scale = min(1.0, $maxWidth / $w);
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);
        if ($dst === false) {
            imagedestroy($src);

            return false;
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);

        $dir = dirname($destPath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            imagedestroy($dst);

            return false;
        }

        $ok = imagejpeg($dst, $destPath, 85);
        imagedestroy($dst);

        return $ok;
    }
}
