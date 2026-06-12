# AppServiceProvider.php — Explained

**File:** `src/Providers/AppServiceProvider.php`

---

## Purpose

Wires the entire application's DI container. Called once at boot time by `Application::boot()`. Registers ~50 bindings: infrastructure, repositories, services, middleware, jobs, and controllers.

---

## Entry Point

### `register(Container $container): void`
Registers all bindings in the correct dependency order:

```
register()
  ├── AppConfig, Connection, JsonResponder, ViewRenderer, TokenCrypto, UserPayloadFormatter
  ├── GoogleAuthService (no-dependency service)
  ├── registerRepositories()
  ├── registerServices()
  ├── registerMiddleware()
  ├── registerJobs()
  └── registerControllers()
```

---

## Infrastructure (direct `set()`)

| Class | Notes |
|-------|-------|
| `AppConfig` | Hardcoded config values |
| `Connection` | PDO wrapper; depends on `AppConfig` |
| `JsonResponder` | Sends JSON responses |
| `ViewRenderer` | Renders PHP/HTML templates |
| `TokenCrypto` | AES-256-CBC encryption |
| `UserPayloadFormatter` | Strips sensitive user fields |
| `GoogleAuthService` | No constructor dependencies |

---

## Repositories (`registerRepositories`)

Most repositories are registered with a generic factory:
```php
new SomeRepository($c->get(Connection::class))
```

Two exceptions:
- **`AnalyticsRepository`**: needs `Connection` + `PostRepository`
- **`SocialAccountRepository`**: needs `Connection` + `TokenCrypto`

All 15 repositories are registered.

---

## Services (`registerServices`)

Most services registered with generic factory: `new SomeService($c->get(Connection::class))`.

Explicit factories for services with non-standard dependencies:

| Service | Extra Dependencies |
|---------|-------------------|
| `PlatformApiSecretsService` | `Connection` + `TokenCrypto` |
| `NotificationService` | `Connection` + `UserRepository` + `NotificationPreferenceRepository` + `NotificationRepository` |
| `DashboardService` | `DashboardRepository` only |
| `ApiMetaService` | `CsrfMiddleware` + `UserPayloadFormatter` |
| `PostInputNormalizer` | `Connection` + `PostRepository` |

`SettingsPageHelper` and `MediaUploadHelper` registered with direct `set()` (no dependencies).

`DealWorkflowHelper` and `AnalyticsReportHelper` use custom factories.

---

## Middleware (`registerMiddleware`)

| Middleware | Registration |
|-----------|-------------|
| `CsrfMiddleware` | `set()` — no deps |
| `RoleMiddleware` | `set()` — no deps |
| `AuthMiddleware` | `factory()` — needs `UserRepository` |

---

## Jobs (`registerJobs`)

| Job | Dependencies |
|-----|-------------|
| `PublishPostJob` | `PostRepository`, `SocialAccountRepository`, `SocialApiService`, `NotificationService`, `AnalyticsRepository`, `Connection` |
| `FetchAnalyticsJob` | `SocialAccountRepository`, `SocialApiService`, `AnalyticsService`, `Connection` |
| `CleanupMediaJob` | `Connection` |
| `SendNotificationJob` | `UserRepository`, `NotificationPreferenceRepository` |

---

## Controllers (`registerControllers`)

All 15 controllers are registered with explicit factories that wire their specific service/repository dependencies. `SystemController` (not in explicit list) falls through to the generic factory: `new SomeController(ViewRenderer, JsonResponder, Connection)`.

---

## Notes

- All registrations use `factory()` (lazy instantiation per `get()` call) except infrastructure classes registered with `set()` (eager singletons).
- The ordering within `register()` matters: infrastructure → repositories → services → middleware → jobs → controllers (each layer depends on the previous).
- The generic fallback factory at the bottom of `registerControllers()` handles controllers not in the explicit list, but today `SystemController` is the only one that uses it.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Core/Application.php` | Calls `AppServiceProvider::register()` at boot |
| `src/Core/Container.php` | The container being wired |
