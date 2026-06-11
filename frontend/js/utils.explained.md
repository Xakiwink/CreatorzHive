# utils.js — Explained

**File:** `frontend/js/utils.js`

---

## Purpose

Shared utility functions exposed as `window.Utils`. Loaded on every page and used by all other JS files.

---

## Functions (all on `Utils` object)

### `formatDate(dateStr, format='medium'): string`
Formats a date string using `Intl`/`toLocaleDateString`:
- `'medium'` (default): `"Jun 10, 2025"`
- `'short'`: `"Jun 10"`
- Returns empty string for invalid dates

### `formatCurrency(amount, currency='TZS'): string`
Formats a number as currency using `Intl.NumberFormat` with 0 decimal places. Defaults to TZS.

### `timeAgo(dateStr): string`
Returns a human-readable relative time string: `"2 days ago"`, `"3 hours ago"`, `"just now"`.

### `debounce(fn, delay=300): function`
Returns a debounced version of `fn`. Resets timer on each call. Used for search inputs.

### `truncate(str, l=100): string`
Truncates a string to `l` characters, appending `…`. Returns original if short enough.

### `copyToClipboard(text): Promise<boolean>`
Copies text to clipboard:
1. Uses modern `navigator.clipboard.writeText()` when available
2. Falls back to `textarea` + `execCommand('copy')` for older browsers

### `getQueryParam(k): string|null`
Returns a URL query parameter value from `window.location.search`.

### `pluralize(count, word): string`
Returns `"1 post"` or `"3 posts"`.

### `escapeHtml(str): string`
Escapes `&`, `<`, `>`, `'`, `"` to HTML entities. Used when inserting untrusted text into innerHTML.

---

## Notes

- The entire object is assigned to `window.Utils` for global access.
- All functions are arrow functions on an object literal (no class, no module system).

---

## Related Files

All frontend JS files use `Utils.*` functions.
