# api.php — Explained

**File:** `backend/routes/api.php`

---

## Purpose

Registers all **JSON API routes** that return machine-readable responses (not HTML pages). These are called by the JavaScript frontend via `fetch()`. Every POST route requires CSRF validation.

---

## Route Registration Pattern

```php
router_get_action('route_name', ControllerClass::class, 'methodName', ['middleware', ...]);
router_post_action('route_name', ControllerClass::class, 'methodName', ['middleware', ...]);
```

---

## Route Inventory

### System Routes (no auth)
| Route | Method | Handler | Purpose |
|-------|--------|---------|---------|
| `ping` | GET | SystemController::ping | Health check |
| `db-test` | GET | SystemController::dbTest | DB connectivity (admin only) |
| `api_me` | GET | ApiMetaController::systemApiMe | Current user + CSRF token |
| `api_catalog` | GET | ApiMetaController::systemApiCatalog | API route catalog |

### Auth Routes
| Route | Method | Middleware | Handler |
|-------|--------|-----------|---------|
| `check_username` | GET | — | AuthController::checkUsername |
| `verify` | GET | — | AuthController::verify |
| `register` | POST | csrf | AuthController::register |
| `login` | POST | csrf | AuthController::login |
| `logout` | POST | auth, csrf | AuthController::logout |
| `forgot-password` | POST | csrf | AuthController::forgotPassword |
| `reset-password` | POST | csrf | AuthController::resetPassword |
| `resend-verification` | POST | csrf | AuthController::resendVerification |

### Posts
All post routes require `auth` + `non_admin`.

| Route | Method | Handler |
|-------|--------|---------|
| `posts` | GET | PostController::index |
| `posts_calendar` | GET | PostController::calendar |
| `post` | GET | PostController::show |
| `create_post` | POST | PostController::store |
| `update_post` | POST | PostController::update |
| `delete_post` | POST | PostController::destroy |
| `duplicate_post` | POST | PostController::duplicate |
| `bulk_posts` | POST | PostController::bulk |

### Media
All require `auth` + `non_admin`.

| Route | Method | Handler |
|-------|--------|---------|
| `upload_media` | POST | MediaController::upload |
| `media_list` | GET | MediaController::list |
| `delete_media` | POST | MediaController::delete |

### Analytics
| Route | Method | Handler |
|-------|--------|---------|
| `analytics_data` | GET | AnalyticsController::data |
| `seed_analytics` | POST | AnalyticsController::seed |

### Deals
| Route | Method | Handler |
|-------|--------|---------|
| `deals_data` | GET | DealController::data |
| `deal` | GET | DealController::show |
| `create_deal` | POST | DealController::store |
| `update_deal` | POST | DealController::update |
| `update_deal_status` | POST | DealController::updateStatus |
| `delete_deal` | POST | DealController::destroy |

### Invoices
| Route | Method | Handler |
|-------|--------|---------|
| `invoices_data` | GET | InvoiceController::list |
| `invoice` | GET | InvoiceController::show |
| `create_invoice` | POST | InvoiceController::store |
| `update_invoice` | POST | InvoiceController::update |
| `mark_invoice_paid` | POST | InvoiceController::markPaid |

### Notifications
| Route | Method | Handler |
|-------|--------|---------|
| `notifications_data` | GET | NotificationController::data |
| `notifications_count` | GET | NotificationController::unreadCount |
| `mark_read` | POST | NotificationController::postMarkRead |
| `mark_all_read` | POST | NotificationController::postMarkAllRead |
| `delete_notification` | POST | NotificationController::postDelete |
| `delete_read_notifications` | POST | NotificationController::postDeleteRead |

### Settings
| Route | Method | Handler |
|-------|--------|---------|
| `profile_data` | GET | SettingsController::profileData |
| `update_profile` | POST | SettingsController::updateProfile |
| `update_password` | POST | SettingsController::updatePassword |
| `user_sessions` | GET | SettingsController::getSessions |
| `revoke_session` | POST | SettingsController::revokeSession |
| `revoke_all_sessions` | POST | SettingsController::revokeAllSessions |
| `integrations_data` | GET | SettingsController::integrationsData |
| `connect_platform` | POST | SettingsController::connectPlatform |
| `disconnect_platform` | POST | SettingsController::disconnectPlatform |
| `notification_prefs` | GET | SettingsController::notificationPrefs |
| `update_notification_prefs` | POST | SettingsController::updateNotificationPrefs |
| `update_preferences` | POST | SettingsController::updatePreferences |

### Dashboard Data
| Route | Method | Handler |
|-------|--------|---------|
| `dashboard_data` | GET | DashboardController::data |

### Admin (role:admin required)
| Route | Method | Handler |
|-------|--------|---------|
| `admin_users` | GET | AdminUserController::usersIndex |
| `admin_create_user` | POST | AdminUserController::usersStore |
| `admin_update_user` | POST | AdminUserController::usersUpdate |
| `admin_delete_user` | POST | AdminUserController::usersDestroy |
| `admin_verify_user` | POST | AdminUserController::usersVerify |
| `admin_overview` | GET | AdminUserController::platformOverview |
| `admin_update_settings` | POST | AdminUserController::settingsUpdate |
| `admin_audit_logs` | GET | AdminUserController::auditLogsIndex |
| `admin_test_integration` | GET | AdminUserController::integrationTest |
| `admin_platform_credentials` | GET | AdminUserController::platformCredentials |
| `admin_update_platform_credentials` | POST | AdminUserController::updatePlatformCredentials |

---

## Security Notes

- All POST routes include `'csrf'` in middleware array
- Admin routes use `'role:admin'` middleware
- Creator-only routes use `'non_admin'` to block admin users from creator endpoints
- No auth routes (register, login, forgot-password) do NOT require session

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/routes/web.php` | HTML page routes (loaded before api.php) |
| `backend/core/router.php` | `router_get_action()` and `router_post_action()` functions |
| `src/Controllers/*.php` | All controllers referenced here |
| `frontend/js/*.js` | All JS files that `fetch()` these routes |
