# `src/Support/` — Shared Helper Classes

## 1. Folder Purpose

Stateless utility classes that encapsulate reusable logic shared across controllers and services. Each class handles a specific cross-cutting concern (input normalization, payload formatting, file operations, workflow helpers).

## 2. Files Overview

| File | Purpose | Used By |
|------|---------|---------|
| `PostInputNormalizer.php` | Sanitizes and normalizes post form input | `PostController` |
| `UserPayloadFormatter.php` | Formats user arrays for API/view responses | `AuthController`, `SettingsController` |
| `MediaUploadHelper.php` | Validates and moves uploaded media files | `MediaController` |
| `DealWorkflowHelper.php` | Deal status transition validation | `DealController` |
| `AnalyticsReportHelper.php` | Builds the full analytics report payload | `AnalyticsController` |
| `SettingsPageHelper.php` | Avatar resizing and settings form processing | `SettingsController` |

## 3. Design Notes

- All classes are stateless — they take inputs and return outputs (no constructor injection needed for most)
- Not registered in `AppServiceProvider`; controllers construct them directly or call static methods
- `SettingsPageHelper::resizeAvatarSquare()` does center-crop + resize to 200×200 px using GD, overwrites the original file in-place
- `AnalyticsReportHelper` assembles the complete chart payload from `AnalyticsRepository` data

## 4. Improvement suggestions

- Register helpers via DI for easier testing (mock file operations in `MediaUploadHelper`)
- Extract `AnalyticsReportHelper` chart-building into named builder classes as chart types grow
