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
        : mailer_fallback_template($templateFile, $data);

    foreach ($data as $key => $value) {
        if (is_scalar($value) || $value === null) {
            $html = str_replace('{{' . $key . '}}', mailer_template_value($key, $value), $html);
        }
    }

    return mailer_remove_unresolved_placeholders($html);
}

function mailer_fallback_template(string $templateFile, array $data): string
{
    switch (basename($templateFile)) {
        case 'verify-email.html':
            return '<h2>Welcome to CreatorzHive, {{name}}</h2>'
                . '<p>Please verify your email by clicking the link below:</p>'
                . '<p><a href="{{url}}">Verify Email</a></p>';

        case 'reset-password.html':
            if (trim((string) ($data['otp'] ?? '')) !== '') {
                return '<h2>Password Recovery OTP</h2>'
                    . '<p>Hello {{name}}, use this one-time code to reset your password:</p>'
                    . '<p style="font-size:28px;letter-spacing:4px;font-weight:700;margin:16px 0;">{{otp}}</p>'
                    . '<p>This code expires in {{expires_minutes}} minutes.</p>'
                    . '<p>Then continue here: <a href="{{url}}">Reset Password</a></p>';
            }

            return '<h2>Password Reset</h2>'
                . '<p>Hello {{name}}, reset your password by clicking the link below:</p>'
                . '<p><a href="{{url}}">Reset Password</a></p>';

        case 'notification-generic.html':
        default:
            return '<!DOCTYPE html><html><head><meta charset="utf-8"><title>{{subject}}</title></head>'
                . '<body style="font-family: Inter, system-ui, sans-serif; line-height: 1.5; color: #1e293b;">'
                . '<p>Hi {{name}},</p><div>{{body}}</div>'
                . '<p style="margin-top:24px;color:#64748b;font-size:12px;">CreatorzHive</p>'
                . '</body></html>';
    }
}

function mailer_template_value(string $key, $value): string
{
    if ($value === null) {
        return '';
    }

    if ($key === 'body') {
        return (string) $value;
    }

    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function mailer_remove_unresolved_placeholders(string $html): string
{
    return (string) preg_replace('/\{\{[A-Za-z0-9_]+\}\}/', '', $html);
}
