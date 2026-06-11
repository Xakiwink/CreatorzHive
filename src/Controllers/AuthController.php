<?php

declare(strict_types=1);

namespace CreatorzHive\Controllers;

use CreatorzHive\Controllers\Support\AbstractController;
use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Core\Http\JsonResponder;
use CreatorzHive\Core\Http\ViewRenderer;
use CreatorzHive\Repositories\UserRepository;
use CreatorzHive\Services\AdminService;
use CreatorzHive\Services\AuthRateLimitService;
use CreatorzHive\Services\AuthService;
use CreatorzHive\Services\NotificationService;
use function mailer_send_password_reset_otp_email;
use function mailer_send_verification_email;
use function now;
use function request_all;
use function request_get;
use function request_ip;
use function request_json_body;
use function route_url;
use function session_destroy_all;
use function session_regenerate_safe;
use function session_set_user;
use function validator_validate;

final class AuthController extends AbstractController
{
    /** @var AuthService */
    private $auth;

    /** @var AuthRateLimitService */
    private $rateLimit;

    /** @var AdminService */
    private $admin;

    /** @var UserRepository */
    private $users;

    /** @var NotificationService */
    private $notifications;

    public function __construct(
        ViewRenderer $views,
        JsonResponder $json,
        Connection $db,
        AuthService $auth,
        AuthRateLimitService $rateLimit,
        AdminService $admin,
        UserRepository $users,
        NotificationService $notifications
    ) {
        parent::__construct($views, $json, $db);
        $this->auth = $auth;
        $this->rateLimit = $rateLimit;
        $this->admin = $admin;
        $this->users = $users;
        $this->notifications = $notifications;
    }

    public function loginPage()
    {
        $this->views->render('auth/login');
    }

    public function registerPage()
    {
        $this->views->render('auth/register');
    }

    public function forgotPage()
    {
        $this->views->render('auth/forgot-password');
    }

    public function resetPage()
    {
        $this->views->render('auth/reset-password');
    }

    public function verifyPage()
    {
        $this->views->render('auth/verify-email');
    }

    public function logoutPage()
    {
        session_destroy_all();
            $this->redirect(route_url('login'));
    }

    public function register()
    {
        if (!$this->admin->settingBool('registration_enabled', true)) {
                $this->json->error('New registrations are temporarily disabled by the administrator.', 403);
            }
        
            $payload = array_merge(request_all(), request_json_body());
            $payload['name'] = trim((string) ($payload['name'] ?? ''));
            $payload['username'] = strtolower(trim((string) ($payload['username'] ?? '')));
            $payload['email'] = strtolower(trim((string) ($payload['email'] ?? '')));
            $payload['role'] = trim((string) ($payload['role'] ?? 'creator'));
        
            $ip = request_ip();
            $rKey = 'ip:' . $ip . ':register';
            $rRow = $this->rateLimit->row($rKey);
            $this->rateLimit->resetIfExpired($rRow, 60);
            if ((int) ($rRow['tokens'] ?? 0) >= 3) {
                $this->json->error('Too many registration attempts from this network. Try again later.', 429);
            }
            $this->rateLimit->incrementRegisterAttempts($rKey, $rRow);
        
            $validation = validator_validate($payload, [
                'name' => 'required|max:255',
                'username' => 'required|min:3|max:100|unique:users,username',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8|confirmed',
                'role' => 'in:creator,brand',
                'terms' => 'required',
            ]);
        
            if (!$validation['valid']) {
                $this->json->error('Validation failed', 422, $validation['errors']);
            }
        
            if (!preg_match('/^[A-Za-z0-9._-]{3,100}$/', (string) $payload['username'])) {
                $this->json->error('Validation failed', 422, [
                    'username' => ['Username may only contain letters, numbers, dots, dashes, and underscores.'],
                ]);
            }
        
            $userId = $this->users->create([
                'name' => (string) $payload['name'],
                'username' => (string) $payload['username'],
                'email' => (string) $payload['email'],
                'password' => $this->auth->hashPassword((string) $payload['password']),
                'role' => $payload['role'] ?? 'creator',
            ]);
        
            $token = $this->auth->generateVerificationToken($userId);
            mailer_send_verification_email((string) $payload['email'], (string) $payload['name'], $token);
        
            $this->notifications->welcomeUser($userId, (string) $payload['name']);
        
            $this->json->success(null, 'Registration successful. Please verify your email.');
    }

    public function login()
    {
        $payload = array_merge(request_all(), request_json_body());
            $payload['email'] = strtolower(trim((string) ($payload['email'] ?? '')));
            $payload['login'] = strtolower(trim((string) ($payload['login'] ?? '')));
            $identifier = $payload['login'] !== '' ? (string) $payload['login'] : (string) $payload['email'];
        
            $validation = validator_validate($payload, [
                'email' => 'max:255',
                'login' => 'max:255',
                'password' => 'required',
            ]);
        
            if (!$validation['valid']) {
                $this->json->error('Validation failed', 422, $validation['errors']);
            }
        
            if ($identifier === '') {
                $this->json->error('Validation failed', 422, [
                    'email' => ['Email or username is required.'],
                ]);
            }
        
            $ip = request_ip();
            $key = 'ip:' . $ip . ':login';
            $rateRow = $this->rateLimit->row($key);
            $this->rateLimit->resetIfExpired($rateRow, 15);
        
            $identifierKey = 'login_identifier:' . hash('sha256', $identifier);
            $identifierRateRow = $this->rateLimit->row($identifierKey);
            $this->rateLimit->resetIfExpired($identifierRateRow, 15);
        
            $attempts = (int) ($rateRow['tokens'] ?? 0);
            $identifierAttempts = (int) ($identifierRateRow['tokens'] ?? 0);
            if ($attempts >= 5 || $identifierAttempts >= 5) {
                $this->json->error('Too many login attempts. Try again later.', 429);
            }
        
            $user = filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false
                ? $this->users->findByEmail($identifier)
                : $this->users->findByUsername($identifier);
        
            if ($user === null) {
                $this->auth->dummyPasswordCheck((string) $payload['password']);
                $this->rateLimit->incrementLoginAttempts($key, $rateRow);
                $this->rateLimit->incrementRateAttempts($identifierKey, $identifierRateRow);
                $this->json->error('Invalid credentials', 401);
            }
        
            if (!$this->auth->checkPassword((string) $payload['password'], (string) $user['password'])) {
                $this->rateLimit->incrementLoginAttempts($key, $rateRow);
                $this->rateLimit->incrementRateAttempts($identifierKey, $identifierRateRow);
                $failedAttempts = max(((int) ($rateRow['tokens'] ?? 0)) + 1, ((int) ($identifierRateRow['tokens'] ?? 0)) + 1);
                $this->rateLimit->maybeSendLoginLockoutAlert($user, $failedAttempts, $ip);
                $this->json->error('Invalid credentials', 401);
            }
        
            if ((int) $user['is_active'] !== 1) {
                $this->json->error('Account disabled', 403);
            }
        
            if ($this->admin->settingBool('require_email_verification', true) && (int) ($user['email_verified'] ?? 0) !== 1) {
                $this->json->error('Please verify your email before signing in.', 403);
            }
        
            session_regenerate_safe();
            unset($user['password']);
            session_set_user($user);
            $this->users->updateLastLogin((int) $user['id']);
        
            $this->rateLimit->reset($rateRow);
            $this->rateLimit->reset($identifierRateRow);
        
            $this->json->success(['redirect' => route_url('dashboard')], 'Login successful');
    }

    public function logout()
    {
        session_destroy_all();
            $this->json->success(['redirect' => route_url('login')], 'Logged out successfully');
    }

    public function forgotPassword()
    {
        if (!$this->admin->settingBool('forgot_password_enabled', true)) {
                $this->json->error('Password recovery is temporarily disabled by the administrator.', 403);
            }
        
            $payload = array_merge(request_all(), request_json_body());
            $payload['email'] = strtolower(trim((string) ($payload['email'] ?? '')));
            $validation = validator_validate($payload, ['email' => 'required|email']);
        
            $ip = request_ip();
            $requestKey = 'ip:' . $ip . ':forgot_password';
            $requestRow = $this->rateLimit->row($requestKey);
            $this->rateLimit->resetIfExpired($requestRow, 60);
        
            if ((int) ($requestRow['tokens'] ?? 0) >= 5) {
                $this->json->error('Too many reset requests from this network. Try again later.', 429);
            }
        
            if ($validation['valid']) {
                $user = $this->users->findByEmail((string) ($payload['email'] ?? ''));
                if ($user !== null) {
                    if ($this->auth->hasRecentPasswordResetRequest((int) $user['id'])) {
                        $this->json->error('Please wait at least 60 seconds before requesting another code.', 429);
                    }
        
                    $otp = $this->auth->generatePasswordResetOtp((int) $user['id']);
                    mailer_send_password_reset_otp_email((string) $user['email'], (string) $user['name'], $otp);
                }
            }
            $this->rateLimit->incrementRateAttempts($requestKey, $requestRow);
        
            $this->json->success(null, 'If the email exists, a password reset code has been sent.');
    }

    public function resetPassword()
    {
        $payload = array_merge(request_all(), request_json_body());
            $token = trim((string) ($payload['token'] ?? ''));
            $email = strtolower(trim((string) ($payload['email'] ?? '')));
            $otp = trim((string) ($payload['otp'] ?? ''));
        
            $validation = validator_validate($payload, [
                'token' => 'max:255',
                'email' => 'max:255',
                'otp' => 'max:12',
                'password' => 'required|min:8|confirmed',
            ]);
        
            if (!$validation['valid']) {
                $this->json->error('Validation failed', 422, $validation['errors']);
            }
        
            if ($token === '' && ($email === '' || $otp === '')) {
                $this->json->error('Validation failed', 422, [
                    'email' => ['Email is required when reset token is missing.'],
                    'otp' => ['OTP is required when reset token is missing.'],
                ]);
            }
        
            if ($token === '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $this->json->error('Validation failed', 422, [
                    'email' => ['The email must be a valid email.'],
                ]);
            }
        
            if ($token === '' && !preg_match('/^\d{6}$/', $otp)) {
                $this->json->error('Validation failed', 422, [
                    'otp' => ['OTP must be a 6-digit code.'],
                ]);
            }
        
            $ip = request_ip();
            $verifyKey = 'ip:' . $ip . ':reset_verify';
            $verifyRow = $this->rateLimit->row($verifyKey);
            $this->rateLimit->resetIfExpired($verifyRow, 15);
            if ((int) ($verifyRow['tokens'] ?? 0) >= 10) {
                $this->json->error('Too many reset attempts. Try again later.', 429);
            }
        
            $row = null;
            if ($token !== '') {
                $row = $this->auth->validateResetToken($token);
            } else {
                $user = $this->users->findByEmail($email);
                if ($user !== null) {
                    $row = $this->auth->validateResetOtp((int) $user['id'], $otp);
                }
            }
        
            if ($row === null) {
                $this->rateLimit->incrementRateAttempts($verifyKey, $verifyRow);
                $attemptsUsed = ((int) ($verifyRow['tokens'] ?? 0)) + 1;
                $remainingAttempts = max(0, 10 - $attemptsUsed);
                $remainingLabel = $remainingAttempts === 1 ? 'attempt' : 'attempts';
                $this->json->error(
                    'Invalid or expired reset code. ' . $remainingAttempts . ' ' . $remainingLabel . ' remaining.',
                    400,
                    ['otp' => ['Invalid or expired reset code. ' . $remainingAttempts . ' ' . $remainingLabel . ' remaining.']]
                );
            }
        
            $this->users->updatePassword((int) $row['user_id'], $this->auth->hashPassword((string) $payload['password']));
            $this->auth->markPasswordResetUsed((int) $row['id']);
            $this->rateLimit->reset($verifyRow);
        
            $this->json->success(['redirect' => route_url('login')], 'Password reset successful');
    }

    public function verify()
    {
        $token = (string) request_get('token', '');
            if ($token === '') {
                $this->json->error('Verification token is required', 422);
            }
        
            $row = $this->auth->validateVerificationToken($token);
            if ($row === null) {
                $this->json->error('Invalid or expired verification link', 400);
            }
        
            $this->users->verifyEmail((int) $row['user_id']);
            $this->auth->markEmailVerificationUsed((int) $row['id']);
        
            $this->json->success(['redirect' => route_url('login')], 'Email verified successfully');
    }

    public function checkUsername()
    {
        $username = strtolower(trim((string) request_get('username', '')));
            if ($username === '') {
                $this->json->error('Username is required', 422);
            }
        
            if (!preg_match('/^[A-Za-z0-9._-]{3,100}$/', $username)) {
                $this->json->error('Validation failed', 422, [
                    'username' => ['Username format is invalid.'],
                ]);
            }
        
            $excludeId = (int) request_get('exclude_user_id', 0);
            $existing = $this->users->findByUsername($username);
            $available = $existing === null
                || ($excludeId > 0 && (int) ($existing['id'] ?? 0) === $excludeId);
        
            $this->json->success(['available' => $available], 'Username check complete');
    }

    public function resendVerification()
    {
        $payload = array_merge(request_all(), request_json_body());
            $validation = validator_validate($payload, ['email' => 'required|email']);
        
            if (!$validation['valid']) {
                $this->json->error('Validation failed', 422, $validation['errors']);
            }
        
            $email = (string) ($payload['email'] ?? '');
            $user = $this->users->findByEmail($email);
        
            if ($user !== null && (int) ($user['email_verified'] ?? 0) === 0) {
                $token = $this->auth->generateVerificationToken((int) $user['id']);
                mailer_send_verification_email((string) $user['email'], (string) $user['name'], $token);
            }
        
            $this->json->success(null, 'If an unverified account exists for that email, we sent a new verification link.');
    }

}
