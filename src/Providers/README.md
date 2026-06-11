# `src/Providers/` — Service Providers

## 1. Folder Purpose

Wires the dependency injection container. Service providers register all class bindings in `AppServiceProvider::register()`, making the entire OOP layer instantiable via `$container->make()`.

## 2. Files Overview

| File | Purpose |
|------|---------|
| `AppServiceProvider.php` | Registers ~50 bindings: infrastructure → repositories → services → middleware → jobs → controllers |

## 3. Binding Order

Bindings are registered in dependency order to avoid forward-reference issues:

1. Infrastructure (`Connection`, `AppConfig`, `TokenCrypto`)
2. Repositories (all `*Repository` classes)
3. Services (all `*Service` classes)
4. Middleware (`AuthMiddleware`, `RoleMiddleware`, `CsrfMiddleware`)
5. Jobs (`PublishPostJob`, `FetchAnalyticsJob`, etc.)
6. Controllers (all `*Controller` classes)

## 4. Special Cases

- `AnalyticsRepository` requires `PostRepository` as an extra dependency
- `SocialAccountRepository` requires `TokenCrypto`
- `SystemController` uses a generic factory closure as a fallback

## 5. Improvement suggestions

- Split into domain-specific providers (`AuthServiceProvider`, `DealServiceProvider`) as the app grows
- Consider lazy bindings for controllers to reduce boot cost
