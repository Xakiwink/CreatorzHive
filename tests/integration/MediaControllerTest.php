<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\Support\IntegrationTestCase;

final class MediaControllerTest extends IntegrationTestCase
{
    private function loginAsDavid(): void
    {
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();
        $this->dispatchRoute('POST', 'login', [
            'email' => 'david@creatorzhive.com',
            'password' => 'Creator@1234',
        ]);
    }

    public function testMediaListAsAuthenticatedUser(): void
    {
        $this->requireDatabase();
        $this->loginAsDavid();

        $res = $this->dispatchRoute('GET', 'media_list', []);

        $this->assertSame(200, $res->httpStatus);
        $this->assertTrue($res->payload['success'] ?? false);
        $this->assertIsArray($res->payload['data']['files'] ?? null);
    }

    public function testUploadAndDeleteImage(): void
    {
        $this->requireDatabase();
        $uploadRoot = dirname(__DIR__, 2) . '/public/uploads';
        $monthDir = $uploadRoot . '/' . date('Y/m');
        if (!is_dir($monthDir) && !@mkdir($monthDir, 0775, true)) {
            $this->markTestSkipped('Cannot create upload month directory for media test');
        }
        if (!is_writable($monthDir)) {
            $this->markTestSkipped('Upload directory is not writable in this environment');
        }

        $this->loginAsDavid();

        $tmp = tempnam(sys_get_temp_dir(), 'cz-media-');
        $this->assertNotFalse($tmp);
        $img = imagecreatetruecolor(10, 10);
        imagejpeg($img, $tmp);
        imagedestroy($img);

        $_FILES = [
            'file' => [
                'name' => 'test.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => $tmp,
                'error' => UPLOAD_ERR_OK,
                'size' => (int) filesize($tmp),
            ],
        ];

        $res = $this->dispatchRoute('POST', 'upload_media', []);
        @unlink($tmp);
        unset($_FILES);

        $this->assertSame(200, $res->httpStatus);
        $this->assertTrue($res->payload['success'] ?? false);
        $id = (int) (($res->payload['data']['id'] ?? 0));
        $this->assertGreaterThan(0, $id);

        $del = $this->dispatchRoute('POST', 'delete_media', ['id' => $id]);
        $this->assertSame(200, $del->httpStatus);
        $this->assertTrue($del->payload['success'] ?? false);
    }
}
