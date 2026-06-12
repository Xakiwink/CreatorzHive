# mailer.php — Explained

**File:** `backend/core/mailer.php`

---

## Purpose

All email sending functionality via PHPMailer. Provides low-level send, pre-built transactional email functions, and a simple template renderer for `backend/storage/email-templates/`.

---

## Functions

### `mailer_send(string $to, string $subject, string $htmlBody, string $textBody): bool`

Low-level email sender. Reads SMTP config from environment:

| Env Var | Default |
|---------|---------|
| `MAIL_HOST` | `localhost` |
| `MAIL_PORT` | `25` |
| `MAIL_USERNAME` | `''` |
| `MAIL_PASSWORD` | `''` |
| `MAIL_FROM_ADDRESS` | `noreply@creatorzhive.com` |
| `MAIL_FROM_NAME` | `CreatorzHive` |

`AltBody` auto-generated as `strip_tags($htmlBody)` if no text body provided.

Failures are caught silently and logged to `backend/storage/logs/mail-YYYY-MM-DD.log`.

### Pre-built Email Functions

| Function | Template | Subject |
|----------|----------|---------|
| `mailer_send_verification_email($to, $name, $token)` | `verify-email.html` | "Verify your CreatorzHive email" |
| `mailer_send_password_reset_email($to, $name, $token)` | `reset-password.html` | "Reset your CreatorzHive password" |
| `mailer_send_password_reset_otp_email($to, $name, $otp)` | `reset-password.html` | "Your CreatorzHive password reset code" |
| `mailer_send_login_lockout_alert_email($to, $name, $ip, $ua)` | `notification-generic.html` | Security alert |

### `mailer_render_template_by_name(string $templateFile, array $data): string`

Simple template renderer:
1. Reads `backend/storage/email-templates/{templateFile}`
2. Replaces `{{key}}` placeholders with `$data` values
3. Falls back to basic `<p>Hello {{name}}</p><p>{{body}}</p>` if template file missing

---

## Template Variables

Templates use `{{variableName}}` syntax. Common variables:
- `{{name}}` — recipient's name
- `{{url}}` — action URL
- `{{otp}}` — 6-digit OTP code
- `{{expires_minutes}}` — expiry time
- `{{subject}}` / `{{body}}` — for generic template

---

## Notes

- PHPMailer is the only `composer.json` production dependency
- Email sending failure is non-fatal — silently returns `false` and logs error
- `MAIL_ENCRYPTION` env var not currently wired up (TLS would need `$mail->SMTPSecure` setting)

---

## Related Files

| File | Relationship |
|------|-------------|
| `composer.json` | `phpmailer/phpmailer` dependency |
| `backend/storage/email-templates/` | HTML template files |
| `src/Controllers/AuthController.php` | Calls verification and reset email functions |
| `src/Services/AuthRateLimitService.php` | Calls lockout alert function |
