<?php

declare(strict_types=1);

/** Auth service compat for jobs and legacy callers. */

function auth_service_hash_password(string $password): string
{
    return \CreatorzHive\Core\Application::instance()->get(\CreatorzHive\Services\AuthService::class)->hashPassword($password);
}

function auth_service_check_password(string $password, string $hash): bool
{
    return \CreatorzHive\Core\Application::instance()->get(\CreatorzHive\Services\AuthService::class)->checkPassword($password, $hash);
}

function auth_service_dummy_password_check(string $password): void
{
    \CreatorzHive\Core\Application::instance()->get(\CreatorzHive\Services\AuthService::class)->dummyPasswordCheck($password);
}

function auth_service_generate_verification_token(int $userId): string
{
    return \CreatorzHive\Core\Application::instance()->get(\CreatorzHive\Services\AuthService::class)->generateVerificationToken($userId);
}

function auth_service_generate_password_reset_token(int $userId): string
{
    return \CreatorzHive\Core\Application::instance()->get(\CreatorzHive\Services\AuthService::class)->generatePasswordResetToken($userId);
}

function auth_service_generate_password_reset_otp(int $userId): string
{
    return \CreatorzHive\Core\Application::instance()->get(\CreatorzHive\Services\AuthService::class)->generatePasswordResetOtp($userId);
}

function auth_service_validate_reset_token(string $token): ?array
{
    return \CreatorzHive\Core\Application::instance()->get(\CreatorzHive\Services\AuthService::class)->validateResetToken($token);
}

function auth_service_validate_reset_otp(int $userId, string $otp): ?array
{
    return \CreatorzHive\Core\Application::instance()->get(\CreatorzHive\Services\AuthService::class)->validateResetOtp($userId, $otp);
}

function auth_service_validate_verification_token(string $token): ?array
{
    return \CreatorzHive\Core\Application::instance()->get(\CreatorzHive\Services\AuthService::class)->validateVerificationToken($token);
}
