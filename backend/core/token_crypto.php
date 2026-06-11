<?php

declare(strict_types=1);

use CreatorzHive\Core\Security\TokenCrypto;

/** Prefix for AES-encrypted values stored in MySQL TEXT columns. */
const TOKEN_CRYPTO_DB_PREFIX = 'czenc1:';

function token_crypto_resolve(): TokenCrypto
{
    static $fallback;

    if (class_exists(\CreatorzHive\Core\Application::class)) {
        $app = \CreatorzHive\Core\Application::instance();
        if ($app !== null) {
            $container = $app->container();
            if ($container->has(TokenCrypto::class)) {
                return $container->get(TokenCrypto::class);
            }
        }
    }

    if ($fallback === null) {
        $fallback = new TokenCrypto();
    }

    return $fallback;
}

function token_crypto_encryption_key(): string
{
    return token_crypto_resolve()->encryptionKey();
}

function token_crypto_pack(string $plaintext): string
{
    return token_crypto_resolve()->pack($plaintext);
}

function token_crypto_unpack(string $packed): string
{
    return token_crypto_resolve()->unpack($packed);
}

function token_crypto_encrypt_db(string $plaintext): string
{
    return token_crypto_resolve()->encryptDb($plaintext);
}

function token_crypto_decrypt_db(string $stored): string
{
    return token_crypto_resolve()->decryptDb($stored);
}

function token_crypto_is_encrypted_db(string $stored): bool
{
    return token_crypto_resolve()->isEncryptedDb($stored);
}
