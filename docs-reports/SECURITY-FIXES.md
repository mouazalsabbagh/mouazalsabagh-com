# Security Remediation Plan — `rec/` backend

> **Plan only.** No PHP files were modified. Apply the steps below yourself.
> Every change is reversible via git (`git diff`, `git checkout -- <file>`).

---

## 🔴 STEP 0 — Do this FIRST, outside the code (urgent)

The secrets are **already in git history and were live on the server**. Moving
them to a config file does NOT un-expose them. You must invalidate the old ones:

1. **Revoke the Anthropic API key** at <https://console.anthropic.com/settings/keys>
   (the key `sk-ant-api03-CeSbb-…` in `rec/ai.php`) and generate a new one.
2. **Rotate the MySQL password** in cPanel → MySQL Databases (the value
   `CwiA~7oL*pmt` in `rec/collect.php`).
3. **Choose a NEW, different admin password** — it is currently identical to the
   DB password, so one leak compromises both.

---

## STEP 1 — Create `rec/config.php` (untracked; already in `.gitignore`)

```php
<?php
// rec/config.php — secrets live here ONLY. Never commit this file.
// Set these to the NEW values you created in Step 0.

define('ANTHROPIC_API_KEY', 'sk-ant-NEW-KEY-HERE');

define('DB_HOST', 'localhost');
define('DB_NAME', 'omemfste_mouaz_recommendations');
define('DB_USER', 'omemfste_mouaz_recuser');
define('DB_PASS', 'NEW-DB-PASSWORD-HERE');

// Store the admin password as a HASH, not plaintext. Generate once with:
//   php -r "echo password_hash('your-new-admin-pass', PASSWORD_DEFAULT), PHP_EOL;"
define('ADMIN_PASS_HASH', '$2y$10$REPLACE_WITH_GENERATED_HASH');

define('SITE_OWNER_EMAIL', 'mo3az.e.s@gmail.com');
define('SITE_NAME', 'Mouaz AlSabbagh — Recommendation System');

// Allowed cross-origin callers (production + www). '' blocks all cross-origin.
$ALLOWED_ORIGINS = [
    'https://mouazalsabbagh.com',
    'https://www.mouazalsabbagh.com',
];
```

Protect it from direct web access — add to `rec/.htaccess`:

```apache
<Files "config.php">
    Require all denied
</Files>
```

---

## STEP 2 — `rec/ai.php`

**Remove** the hardcoded key (lines ~10-11) and the wildcard CORS, **replace** with:

```php
require __DIR__ . '/config.php';

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $ALLOWED_ORIGINS, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Vary: Origin');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');
```

The rest of `ai.php` already references `ANTHROPIC_API_KEY` — now sourced from config.
(The IP rate-limit logic is fine to keep.)

---

## STEP 3 — `rec/collect.php`

1. **Delete** the `CONFIG` block (lines ~8-15) and `require __DIR__ . '/config.php';`
   at the top instead. `getDB()` already uses `DB_*` constants — no further change.

2. **Lock down CORS** on the `save` action (lines ~89-91). Replace the
   `Access-Control-Allow-Origin: *` with the same allowlist check as Step 2.

3. **Hash the admin password** — the login check (line ~151) becomes:
   ```php
   if (password_verify($pass, ADMIN_PASS_HASH)) {
   ```

4. **Add CSRF protection** to the admin forms (login / status / logout):
   - After `session_start()`, ensure a token exists:
     ```php
     if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
     ```
   - In every admin `<form method="POST">`, add a hidden field:
     ```php
     <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
     ```
   - At the top of each POST handler (`save` is JSON/API so exempt; `login`,
     `status`, `logout`), verify:
     ```php
     if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
         http_response_code(403); exit('Invalid CSRF token');
     }
     ```

5. **Optional hardening**: set a session cookie policy near the top:
   ```php
   session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => true]);
   ```

---

## STEP 4 — Delete dead file

`assets/php/form-process.php` is unused (placeholder `yourname@domain.com`,
superseded by `collect.php`). Remove it:

```bash
git rm assets/php/form-process.php
```

---

## STEP 5 — Verify & commit

```bash
php -l rec/ai.php && php -l rec/collect.php          # syntax check
git status                                           # confirm config.php is NOT staged
git add -A && git commit -m "Phase 1: harden rec/ backend (secrets, CORS, CSRF)"
```

After deploying, confirm:
- `https://yourdomain/rec/config.php` returns **403** (not the PHP source).
- Admin login works with the new password; the recommendation form still saves.
- A cross-origin `fetch` from another domain is now blocked.
```
```
