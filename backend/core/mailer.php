<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

function mailer_send(string $to, string $subject, string $htmlBody, string $textBody = ''): bool
{
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = (string) env('MAIL_HOST', 'localhost');
        $mail->Port = (int) env('MAIL_PORT', 25);
        $mail->SMTPAuth = true;
        $mail->Username = (string) env('MAIL_USERNAME', '');
        $mail->Password = (string) env('MAIL_PASSWORD', '');
        $mail->setFrom((string) env('MAIL_FROM_ADDRESS', 'noreply@creatorzhive.com'), (string) env('MAIL_FROM_NAME', 'CreatorzHive'));
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody !== '' ? $textBody : strip_tags($htmlBody);

        return $mail->send();
    } catch (Exception $e) {
        $line = '[' . now() . '] ' . $e->getMessage();
        $logPath = storage_path('logs/mail-' . date('Y-m-d') . '.log');
        $written = @file_put_contents($logPath, $line . PHP_EOL, FILE_APPEND);
        if ($written === false) {
            error_log($line);
        }

        return false;
    }
}

function mailer_send_verification_email(string $to, string $name, string $token): bool
{
    $url = rtrim((string) env('APP_URL', 'http://localhost'), '/') . '/?route=verify-email&token=' . urlencode($token);
    $html = mailer_render_template_by_name('verify-email.html', ['name' => $name, 'url' => $url]);

    return mailer_send($to, 'Verify your CreatorzHive email', $html);
}

function mailer_send_password_reset_email(string $to, string $name, string $token): bool
{
    $url = rtrim((string) env('APP_URL', 'http://localhost'), '/') . '/?route=reset-password&token=' . urlencode($token);
    $html = mailer_render_template_by_name('reset-password.html', ['name' => $name, 'url' => $url]);

    return mailer_send($to, 'Reset your CreatorzHive password', $html);
}

function mailer_send_password_reset_otp_email(string $to, string $name, string $otp): bool
{
    $url = rtrim((string) env('APP_URL', 'http://localhost'), '/') . '/?route=reset-password';
    $html = mailer_render_template_by_name('reset-password.html', [
        'name' => $name,
        'otp' => $otp,
        'url' => $url,
        'expires_minutes' => '10',
    ]);

    return mailer_send($to, 'Your CreatorzHive password reset code', $html);
}

function mailer_send_login_lockout_alert_email(string $to, string $name, string $ip, string $userAgent): bool
{
    $subject = 'Security Alert: Repeated failed sign-in attempts';
    $safeIp = htmlspecialchars($ip, ENT_QUOTES, 'UTF-8');
    $safeUa = htmlspecialchars(substr($userAgent, 0, 180), ENT_QUOTES, 'UTF-8');
    $body = '<p>We detected repeated failed sign-in attempts for your CreatorzHive account.</p>'
        . '<p><strong>IP:</strong> ' . $safeIp . '<br><strong>Device:</strong> ' . $safeUa . '</p>'
        . '<p>If this was not you, please reset your password immediately.</p>';
    $html = mailer_render_template_by_name('notification-generic.html', [
        'subject' => $subject,
        'name' => $name,
        'body' => $body,
    ]);

    return mailer_send($to, $subject, $html);
}

function mailer_render_template_by_name(string $templateFile, array $data): string
{
    $path = storage_path('email-templates/' . ltrim($templateFile, '/'));
    $html = is_file($path)
        ? (string) file_get_contents($path)
        : '<p>Hello {{name}}</p><p>{{body}}</p>';

    foreach ($data as $key => $value) {
        if (is_scalar($value) || $value === null) {
            $html = str_replace('{{' . $key . '}}', (string) $value, $html);
        }
    }

    return $html;
}
