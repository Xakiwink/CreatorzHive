# `src/Helpers/` — Global Helper Utilities

## 1. Folder Purpose

Lightweight utility classes used across the codebase that don't belong to a specific domain.

## 2. Files Overview

| File | Purpose | Used By |
|------|---------|---------|
| `PlatformHelper.php` | Canonical platform slugs and normalization | Repositories, `backend/helpers/platforms.php` |

## 3. Design Notes

- `PlatformHelper::slugs()` returns the authoritative list of supported social platform identifiers: `instagram`, `tiktok`, `youtube`, `facebook`, `twitter`
- `PlatformHelper::normalize(string $input)` lowercases and maps aliases (e.g. `x` → `twitter`) to canonical slugs
- Exposed as procedural functions via `backend/helpers/platforms.php` for compat code

## 4. Improvement suggestions

- If more platform-agnostic utilities accumulate (e.g. currency helpers, timezone helpers), add them here or consider splitting into domain-specific helper files
