# Progress Report — Optimization & Local Setup

**Period:** this session · **Baseline:** commit `1be1eb8`

The work ran in the user-requested order **Phase 3 → Phase 2 → Phase 1**, followed by local-environment bring-up.

---

## Commit timeline

| Commit | Summary |
|--------|---------|
| `1be1eb8` | Baseline snapshot (safety net before any change) |
| `709bb2b` | **Phase 3** — remove junk/dev files; fix dead links; `.gitignore` |
| `918e471` | **Phase 2** — remove dead `isotope.min.js` |
| `9bd5897` | **Phase 2** — WebP images (−30 MB) + minify CSS |
| `63148b0` | Fix wrong-depth asset paths in 22 project-preview stubs |
| `8d5ff5b` | Move 126 MB unreferenced PDFs out of deploy + fix CV link |
| `ac9c08e` | **Phase 1** — security remediation guide (plan only) |
| `9736536` | Add `php-backend` dev config + session permission allowlist |

---

## Phase 3 — Structure & cleanup ✅
- Initialized git as a safety net; added `.gitignore`.
- Deleted 6 one-off dev scripts + 5 `.DS_Store`.
- Fixed **all** dead links on live pages: 5 repointed (`unknown-soldier-league` → `unknown-soldier`), 12 dead `service-details` buttons removed.
- Generated `dedupe-commands.sh` (3 case-fix renames + 12 duplicate-page deletions) — **left for owner review**.

## Phase 2 — Weight reduction ✅ (browser-verified, 0 broken assets)
- Converted **272 images to WebP: 34 MB → 4 MB (88% smaller)**; rewrote 395 references across 46 files; removed originals.
- Minified `style.css` (156→120 K) and `design-system.css` (19→16 K).
- Removed dead `isotope.min.js`.
- Relocated **126 MB** of unreferenced case-study PDFs to git-ignored `_archive/` (`assets/docs` 138 MB → 12 MB).
- Fixed broken "Download PDF CV" button → `cv-2026-mouaz-alsabbagh.html`.
- **Net: deployed assets 180 MB → 23 MB.**

## Phase 1 — Security 📋 (plan only, by request)
- Authored `SECURITY-FIXES.md`: move secrets to untracked `rec/config.php`, lock CORS to domain, add CSRF tokens, hash admin password, delete dead `form-process.php`.
- **Not applied** — backend code untouched per user's choice.

## Local environment bring-up ✅
- Installed Homebrew + PHP 8.5.6 + MySQL 9.6 (assisted; user ran the Homebrew/PHP install).
- Saved `php-backend` + `static` server configs to `.claude/launch.json`.
- Created local DB/user matching `collect.php` config (no code changes needed).
- **Smoke test passed end-to-end:** save → DB row `id=1` → admin dashboard shows the submission.

---

## What was deliberately NOT done
- No real `ai.php` → Anthropic call (would spend credits on a key slated for rotation).
- No backend security edits (plan-only, per user).
- `dedupe-commands.sh` not executed.
- No cloud deployment yet.
