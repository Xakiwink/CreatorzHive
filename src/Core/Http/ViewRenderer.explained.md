# ViewRenderer.php — Explained

**File:** `src/Core/Http/ViewRenderer.php`
**Namespace:** `CreatorzHive\Core\Http`

---

## Purpose

Renders PHP template files from `frontend/pages/`. Injected into all controllers as `$this->views`. In production it delegates to `http_view()` (the procedural bridge); the fallback implementation does a direct `extract()` + `require` for environments where the compat layer isn't loaded.

---

## Method: `render(string $template, array $data = []): void`

**Production path** (when `http_view` function exists):
Delegates to `http_view($template, $data)` defined in `backend/http.php`.

**Fallback path** (tests/standalone):
1. Resolve path: `frontend/pages/{template}.php` or `.html`
2. `extract($data, EXTR_SKIP)` — makes `$data` keys available as `$variableName` in template
3. `ob_start()` → `require` → `ob_get_clean()` → `echo` → `exit`

**Template resolution:**
- Template name `'dashboard/index'` → `frontend/pages/dashboard/index.php`
- Tries `.php` first, falls back to `.html`
- Throws `RuntimeException` if neither exists

---

## Template Variables

Data passed to `render()` is available as variables in the template:
```php
$this->views->render('settings/profile', ['settings_panel' => 'security']);
// In template: $settings_panel === 'security'
```

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/http.php` | `http_view()` production implementation (handles layout wrapping) |
| `frontend/pages/` | All PHP template files |
| `src/Controllers/Support/AbstractController.php` | `$this->views` property |
