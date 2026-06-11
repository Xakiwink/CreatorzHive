<?php

declare(strict_types=1);

namespace CreatorzHive\Core\Security;

/**
 * AES-256-CBC encryption for tokens stored in the database.
 */
final class TokenCrypto
{
    public const DB_PREFIX = 'czenc1:';

    public function encryptionKey(): string
    {
        $raw = trim((string) env('APP_SECRET', ''));
        if ($raw === '') {
            $raw = 'creatorzhive-insecure-dev-key-set-APP_SECRET-in-env';
        }

        return hash('sha256', $raw, true);
    }

    public function pack(string $plaintext): string
    {
        if ($plaintext === '') {
            return '';
        }

        $key = $this->encryptionKey();
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            return '';
        }

        return base64_encode($iv . $cipher);
    }

    public function unpack(string $packed): string
    {
        if ($packed === '') {
            return '';
        }

        $raw = base64_decode($packed, true);
        if ($raw === false || strlen($raw) < 17) {
            return '';
        }

        $iv = substr($raw, 0, 16);
        $cipher = substr($raw, 16);
        $key = $this->encryptionKey();
        $plain = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        return is_string($plain) ? $plain : '';
    }

    public function encryptDb(string $plaintext): string
    {
        if ($plaintext === '') {
            return '';
        }

        $packed = $this->pack($plaintext);
        if ($packed === '') {
            return '';
        }

        return self::DB_PREFIX . $packed;
    }

    public function decryptDb(string $stored): string
    {
        $stored = trim($stored);
        if ($stored === '') {
            return '';
        }

        if (strpos($stored, self::DB_PREFIX) === 0) {
            return $this->unpack(substr($stored, strlen(self::DB_PREFIX)));
        }

        return $stored;
    }

    public function isEncryptedDb(string $stored): bool
    {
        return strpos(trim($stored), self::DB_PREFIX) === 0;
    }
}
