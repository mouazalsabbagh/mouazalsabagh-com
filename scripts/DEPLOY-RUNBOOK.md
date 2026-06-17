# cPanel Deploy Runbook — mouazalsabbagh.com

A one-shot, do-it-in-order checklist to put the site + recommendation backend +
page manager live on cPanel. Local streams (i18n, hardened backend, pages.php)
are already built and committed; this is the upload + configure step.

> **GATE 0 — rotate secrets first (you, outside the code).** Code changes do NOT
> un-expose the old key/password that were once committed. Do these before go-live:
> 1. Revoke the old Anthropic API key at console.anthropic.com → create a new one.
> 2. Rotate the MySQL password in cPanel → MySQL Databases.
> 3. Choose a **new, different** admin password (not equal to the DB password).
> You will paste these three new values into `rec/config.php` **on the server** (Step 4).

---

## 1. Build the upload bundle (local)
```bash
bash scripts/build-deploy.sh
```
Produces `dist/deploy/` (the folder to upload) and `dist/deploy.zip`. The script
refuses to run if `rec/config.php` or a real API key would be included.

## 2. Create the database (cPanel → MySQL Databases)
- Create database: `omemfste_mouaz_recommendations` (cPanel prefixes your user).
- Create user: `omemfste_mouaz_recuser`, set the **new** rotated password.
- Add the user to the database with **ALL PRIVILEGES**.
- No schema step needed — `recommendations` and `pages` tables auto-create on first use.

## 3. Upload the files
- cPanel → File Manager → `public_html/` (or a subfolder if you prefer a path).
- Upload `dist/deploy.zip` → **Extract** it there, then move the contents of
  `deploy/` up into `public_html/` so `index.html` sits at the web root.
  (FTP alternative: upload the contents of `dist/deploy/` directly.)

## 4. Create `rec/config.php` ON THE SERVER (never upload the local one)
- In File Manager, copy `rec/config.example.php` → `rec/config.php`.
- Edit `rec/config.php` and set the **rotated** values from Gate 0:
  - `ANTHROPIC_API_KEY` → the new key (or set it as an env var and leave the fallback).
  - `DB_NAME` / `DB_USER` / `DB_PASS` → the real cPanel DB + new password.
  - `ADMIN_PASS_HASH` → generate locally and paste the hash:
    ```bash
    php -r "echo password_hash('your-new-admin-pass', PASSWORD_DEFAULT), PHP_EOL;"
    ```
  - Confirm `$ALLOWED_ORIGINS` lists `https://mouazalsabbagh.com` (+ `www`); remove the localhost entry.

## 5. Set the PHP version (cPanel → Select PHP Version)
- PHP ≥ 8.0 with the `pdo_mysql` extension enabled (curl too, for `ai.php`).

## 6. Smoke-test on the live host
- [ ] `https://mouazalsabbagh.com/` loads; spot-check images/CSS (no 404s in devtools).
- [ ] `https://mouazalsabbagh.com/rec/config.php` returns **403** (not PHP source). ← critical
- [ ] Recommendation form submits → `{"success":true,"id":…}` and a row appears.
- [ ] `…/rec/collect.php?action=admin` → log in with the **new** admin password.
- [ ] `…/rec/pages.php` → create a draft → publish → file appears, card link works.
- [ ] `ai.php` returns a generated letter (now that the new key is live).
- [ ] A cross-origin `fetch` from another domain is blocked (CORS allowlist).
- [ ] Delete the test submission / test page.

## 7. Pre-flight checklist (must all be true)
- [ ] Secrets exist **only** in `rec/config.php` on the server (git-ignored; never committed).
- [ ] `rec/.htaccess` denies `config.php` and `config.example.php` (403 verified in Step 6).
- [ ] CORS limited to the real domain (no `*`).
- [ ] Admin login uses the bcrypt hash; CSRF rejects forged POSTs (returns 403).
- [ ] 0 broken **asset** references in deployed pages (verified locally before upload).

---

### Notes
- `_archive/` (126 MB local PDFs), `docs-reports/`, `handbook/`, `scripts/`,
  `database/`, `templates/`, and `i18n/demo.html` are intentionally **not** deployed.
- Known pre-existing issue (non-blocking): some `work/project-preview/*` stubs have
  case-mismatched internal `.html` links; run `scripts/dedupe-commands.sh` (review first)
  to normalize names before linking them from the galleries.
