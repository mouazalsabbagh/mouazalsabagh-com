# Execution Plan — Remaining Work

Four work streams, sequenced by dependency. Each is a separate git commit, fully reversible.
**External dependency (yours):** rotate the live Anthropic key + DB/admin password before
anything goes to the cloud (Stream 4). Streams 1–3 are local and need no secrets.

---

## Stream 1 — EN/AR internationalization  *(no blockers — first)*
**Goal:** make the English/Arabic layer from the workbook real on the site.
1. Export `Localization` sheet → `assets/i18n/strings.json` (`{ "nav.home": {"en":"Home","ar":"الرئيسية"}, ... }`).
2. Add `assets/js/i18n.js` — reads `?lang=` / `localStorage`, swaps `data-i18n` text, toggles `dir="rtl"` + an Arabic webfont on `<html>`.
3. Tag a pilot page (nav + key labels) with `data-i18n` attributes; wire a language toggle.
4. Verify in-browser: switch EN↔AR, confirm RTL flips and text swaps; 0 console errors.
**Deliverable:** working language switch on the pilot page + reusable JSON/JS for the rest.
**Risk:** low. Pure frontend, additive.

## Stream 2 — Security hardening (code)  *(local; do before cloud)*
**Goal:** implement `SECURITY-FIXES.md` in code, tested against the local PHP server.
1. Create `rec/config.php` (git-ignored) holding secrets + `$ALLOWED_ORIGINS`; placeholders for new key/hash.
2. Refactor `rec/ai.php` + `rec/collect.php` to `require config.php`; remove inline secrets.
3. Replace CORS `*` with the allowlist; add CSRF tokens to admin forms; switch admin login to `password_verify`.
4. Delete/repoint dead `assets/php/form-process.php` (fix its placeholder email).
5. Test locally: form save, admin login (hashed pass), CSRF reject on forged POST, `config.php` → 403.
**Deliverable:** hardened backend that still passes the end-to-end smoke test.
**Risk:** medium — touches live backend logic; mitigated by local re-test. **You still rotate the real secrets.**

## Stream 3 — Template page create/edit/draft  *(after Stream 2's hardened auth)*
**Goal:** the `pages`-table workflow from NEXT-STEPS section B.
1. Add the `pages` table (DDL already in the workbook) to local MySQL.
2. Build `rec/pages.php` (admin-only, reuses hardened session + CSRF): actions `new | edit | draft | publish | list`.
3. Tokenize a canonical template (`{{title}}`, `{{slug}}`, `{{hero}}`, `{{body}}`, `{{og_image}}`, `{{lang}}`).
4. On publish → render template + stored fields to static HTML in the live folder + add the card link.
   Drafts live in `work/_drafts/` with `noindex` until published.
5. Verify: create a draft → preview → publish → file appears, link works, broken-asset sweep = 0.
**Deliverable:** a working page manager; new case-study/project pages in minutes, EN+AR aware.
**Risk:** medium — new code path; kept behind admin auth, writes static output (site stays fast).

## Stream 4 — Cloud deployment prep  *(last; no live changes until you rotate secrets)*
**Goal:** everything staged for a one-shot cPanel upload.
1. Regenerate the clean deploy bundle (now including i18n + hardened backend + pages.php).
2. Write a precise cPanel runbook: create DB/user, upload, set PHP version, place `config.php`, verify 403/CORS/admin.
3. Pre-flight checklist: 0 broken assets, secrets only in `config.php`, `.htaccess` denies it.
**Deliverable:** a deploy folder + runbook you can execute the moment secrets are rotated.
**Risk:** low — prep only; the actual deploy + rotation stay in your hands.

---

### Suggested order & checkpoints
`Stream 1 → 2 → 3 → 4`, pausing after each for your review.
I'll start with **Stream 1** on your go-ahead (it's self-contained and shows immediate, visible results).
