# Operations Manual — mouazalsabbagh.com

A structured how-to for running, editing, deploying, and maintaining the site + backend.

---

## Table of Contents
1. [Project layout](#1-project-layout)
2. [Local development](#2-local-development)
3. [Database](#3-database)
4. [The recommendation backend (`rec/`)](#4-the-recommendation-backend-rec)
5. [Editing & optimizing assets](#5-editing--optimizing-assets)
6. [Creating a new page from the template](#6-creating-a-new-page-from-the-template)
7. [Deploying to the cloud (cPanel)](#7-deploying-to-the-cloud-cpanel)
8. [Security checklist](#8-security-checklist)
9. [Troubleshooting](#9-troubleshooting)

---

## 1. Project layout
```
/                       root HTML pages (index, about, works, contact, …)
/assets/css|js|images   front-end assets (images are WebP)
/assets/docs            small docs kept in deploy (12 MB)
/work/case-study/       36 case-study pages
/work/project-preview/  40 project-preview pages
/rec/                   PHP backend: collect.php, ai.php, .htaccess, php.ini
/_archive/              126 MB of large PDFs — LOCAL ONLY, git-ignored, not deployed
/.claude/launch.json    dev-server definitions (php-backend, static)
*-template.html         page templates (master, project-detail, header-footer)
SECURITY-FIXES.md       security remediation plan
docs-reports/           these reports
```

## 2. Local development
**Start the all-in-one server** (serves static + PHP):
```
php-backend  →  http://localhost:8000
```
Started via the preview tooling (config in `.claude/launch.json`). Equivalent raw command:
```bash
/opt/homebrew/bin/php -S localhost:8000 -t .
```
- `static` (`http://localhost:8765`, Python) is optional — only needed if PHP is off.
- If `:8000` goes dark, just restart the `php-backend` server.

## 3. Database
| Task | Command |
|------|---------|
| Start / stop MySQL | `brew services start mysql` / `brew services stop mysql` |
| Root shell (local, no pw) | `mysql -u root` |
| App-user shell | `mysql -u omemfste_mouaz_recuser -p omemfste_mouaz_recommendations` |
| Inspect submissions | `SELECT id, rec_name, status, submitted_at FROM recommendations;` |

Schema is auto-created by `installTable()` in `collect.php` on the first save — no manual DDL.

## 4. The recommendation backend (`rec/`)
- **`collect.php`** — routes via `?action=`:
  - `save` (POST JSON) → validate → insert → email owner → `{success,id}`
  - `admin` → login page / dashboard (stats, list, status updates, CSV export)
  - `login` / `logout` / `status` / `export`
- **`ai.php`** — POST `{prompt}` → proxies to Anthropic (key server-side), 10 req/IP/hour rate limit.
- **Admin URL:** `…/rec/collect.php?action=admin`

## 5. Editing & optimizing assets
**Images → WebP** (already done for existing images; for NEW images):
```bash
python3 - <<'PY'
from PIL import Image; import sys,os
s=sys.argv[1]; im=Image.open(s).convert("RGB")
im.save(os.path.splitext(s)[0]+".webp","webp",quality=82,method=6)
PY
```
Then reference the `.webp` in HTML and delete the original.

**Minify CSS** (source SASS lives in `assets/sass/`):
```bash
npx clean-css-cli assets/css/style.css -o assets/css/style.css
```

## 6. Creating a new page from the template
1. **Copy** the canonical template for the page type (e.g. `project-detail-template.html`).
2. **Save as a draft** in `work/_drafts/<slug>.html`; add `<meta name="robots" content="noindex">`.
3. **Fill content** — title, hero (WebP), metrics, body, and `og:image`/Twitter meta.
4. **Preview** at `http://localhost:8000/work/_drafts/<slug>.html`.
5. **Publish** — move the file to its live folder and add its card link in `works.html` / `projects.html`.
6. **Verify** — run the broken-asset sweep (see Troubleshooting) → expect 0 missing refs.

> Naming: use **kebab-case** slugs (`case-study-my-project.html`) to avoid the case-sensitivity 404s
> seen on the Linux host. Run `dedupe-commands.sh` first if duplicates exist.

## 7. Deploying to the cloud (cPanel)
1. Create the MySQL DB + user in cPanel (matching `collect.php` / `config.php`).
2. Create `rec/config.php` on the server (new secrets) — never commit it.
3. Upload the site **excluding** `_archive/`, `.git/`, `node_modules/`.
4. cPanel → Select PHP Version → PHP ≥ 8.0 with `pdo_mysql` enabled.
5. Smoke-test: submit the form, log into the dashboard, confirm `config.php` → 403.
   (Full checklist in `NEXT-STEPS.md` → section A.)

## 8. Security checklist
- [ ] Secrets in untracked `config.php`, not in `ai.php`/`collect.php`.
- [ ] `.htaccess` denies `config.php` (403).
- [ ] CORS limited to the real domain (no `*`).
- [ ] Admin password hashed (`password_hash`), distinct from DB password.
- [ ] CSRF tokens on admin POST forms.
- [ ] Dead `assets/php/form-process.php` removed.
- See `SECURITY-FIXES.md` for exact code.

## 9. Troubleshooting
| Symptom | Cause / Fix |
|---------|-------------|
| `collect.php` → 500 "Database connection failed" | MySQL not running / wrong creds. `brew services start mysql`; check `config.php`. |
| Save returns `status: 000` (curl) | PHP server stopped — restart `php-backend`. |
| Images 404 on live host but OK on Mac | Filename case mismatch (Linux is case-sensitive). Use kebab-case; run `dedupe-commands.sh`. |
| `php` not found by preview tool | Use absolute path `/opt/homebrew/bin/php` in `launch.json`. |
| Find broken local asset refs | ```python3``` sweep: walk HTML, resolve each `src/href`, report missing (used this session; 0 expected). |
| `ai.php` errors | Check the key in `config.php`, rate limit (10/IP/hr), and host outbound HTTPS. |
