# `src/Repositories/` — Data Access

## 1. Folder Purpose

All SQL access for domain entities. Uses `CreatorzHive\Core\Database\Connection` (prepared statements).

## 2. Files Overview

| Repository | Table(s) |
|------------|----------|
| `UserRepository` | `users` — includes `findByGoogleId`, `linkGoogleId`, `createOAuthUser` |
| `PostRepository` | `posts`, relations |
| `MediaFileRepository` | `media_files` |
| `DealRepository` | `deals`, `deal_posts` |
| `InvoiceRepository` | `invoices` |
| `AnalyticsRepository` | `analytics`, snapshots |
| `NotificationRepository` | `notifications` |
| `TagRepository` | `tags` |
| `SocialAccountRepository` | `social_accounts` |
| `JobQueueRepository` | `job_queue` |
| `AuditLogRepository` | `audit_logs` |
| Others | Sessions, prefs, email verifications |

## 3. User + Google OAuth

- `findByGoogleId(string $googleId)` — primary OAuth lookup
- `linkGoogleId(int $userId, string $googleId)` — link existing email account
- `createOAuthUser(array $data)` — new user with random password hash
- `suggestAvailableUsername(string $email, string $name)` — unique username generation

## 4. Improvement suggestions

- Shared `Repository` base for common `findById` patterns.
- Return typed DTOs instead of associative arrays over time.
