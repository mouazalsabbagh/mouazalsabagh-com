# Status Report — mouazalsabbagh.com

**Date:** 2026-06-03
**Repo:** local git (`main`), 8 commits, no remote
**Working tree:** clean

---

## 1. Site at a glance

| Metric | Value |
|--------|-------|
| Pages (root) | 19 |
| Case studies (`work/case-study/`) | 36 |
| Project previews (`work/project-preview/`) | 40 |
| Deployed `assets/` total | **23 MB** (was ~180 MB) |
| — images | 5 MB (WebP) |
| — docs | 12 MB |
| Archived PDFs (local-only, `_archive/`) | 126 MB |
| Broken internal links / assets | **0** (verified in browser) |

## 2. Stack

- **Frontend:** static HTML/CSS/JS (jQuery 3.6 + Bootstrap + Slick + Isotope + WOW). No build system.
- **Backend (`rec/`):** PHP — `collect.php` (recommendation form → MySQL + admin dashboard), `ai.php` (Anthropic API proxy).
- **DB:** MySQL — table `recommendations` (23 columns), auto-created by `installTable()`.

## 3. Local dev environment (working)

| Component | Detail |
|-----------|--------|
| PHP | 8.5.6 (Homebrew, `/opt/homebrew/bin/php`), `pdo_mysql` enabled |
| MySQL | 9.6.0 (Homebrew service, autostarts at login) |
| Python | 3.14.1 |
| `php-backend` server | `http://localhost:8000` — serves site + PHP |
| `static` server | `http://localhost:8765` — static only (optional) |
| DB / user | `omemfste_mouaz_recommendations` / `omemfste_mouaz_recuser` @ localhost |
| Configs | `.claude/launch.json` (both servers) |

**Verified end-to-end:** form submit → `collect.php` save → MySQL row → admin login → dashboard renders submission. ✅
**Not yet tested:** `ai.php` → Anthropic (blocked on a fresh API key).

## 4. Open risks

| Severity | Item |
|----------|------|
| 🔴 Critical | Live Anthropic API key + DB/admin password are in plaintext in `rec/ai.php`, `rec/collect.php`, and in git history. **Must be revoked/rotated by the owner.** |
| 🟠 Medium | `project-preview/*` pages are unstyled stubs (load no CSS). |
| 🟡 Low | No favicon, no `og:image`/social meta (SEO/sharing gap). |
| 🟡 Low | `dedupe-commands.sh` (3 renames + 12 dup deletions) generated but **not yet run**. |

## 5. Security remediation status

Plan written in `SECURITY-FIXES.md` (config extraction, CORS allowlist, CSRF, password hashing, dead-file removal). **Not applied** — user deferred backend edits.
