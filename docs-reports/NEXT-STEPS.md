# Next Steps — To-Do Plan

Ordered roadmap to (A) get the **PHP dashboard live in the cloud**, then (B) stand up a
**template-based page creation / edit / draft** workflow. Checkboxes are actionable items.

---

## 🔴 GATE 0 — Rotate secrets (blocks everything cloud-facing)

These must happen before any deploy, by the owner, outside the code:

- [ ] Revoke the exposed Anthropic API key → console.anthropic.com → issue a new one.
- [ ] Rotate the MySQL password in cPanel.
- [ ] Choose a **new, different** admin password (currently identical to the DB password).
- [ ] Apply `SECURITY-FIXES.md` (config.php extraction, CORS allowlist, CSRF, password hash, delete `form-process.php`).

---

## A. Access the PHP Dashboard in the cloud

**Goal:** reach `https://mouazalsabbagh.com/rec/collect.php?action=admin` on the live host with real data.

### A1 — Provision the database (cPanel)
- [ ] cPanel → MySQL Databases → create DB `omemfste_mouaz_recommendations`.
- [ ] Create user `omemfste_mouaz_recuser`, set the **new** password, grant ALL on that DB.
- [ ] (No schema work needed — `installTable()` auto-creates the 23-column table on first save.)

### A2 — Configure & upload the backend
- [ ] Create `rec/config.php` on the server with the new key + DB pass + `ADMIN_PASS_HASH` (never commit it).
- [ ] Confirm `rec/.htaccess` denies direct access to `config.php` (returns 403).
- [ ] Set CORS allowlist to `https://mouazalsabbagh.com` (+ `www`).
- [ ] Upload `rec/` (`collect.php`, `ai.php`, `.htaccess`, `php.ini`, `index.html`, `index-ar.html`).
- [ ] Verify host PHP ≥ 8.0 and `pdo_mysql` is enabled (cPanel → Select PHP Version).

### A3 — Smoke-test in the cloud
- [ ] Open the form, submit a test recommendation → expect `{"success":true,"id":...}`.
- [ ] `…/rec/collect.php?action=admin` → log in with the new admin password → see the test row.
- [ ] Test `ai.php` with a short prompt (now that the new key is live) → expect a generated letter.
- [ ] Confirm `…/rec/config.php` returns **403**, not source.
- [ ] Delete the test submission from the dashboard.

---

## B. Page creation / edit / draft from the final template

**Goal:** a repeatable workflow to spin up new case-study / project pages from a canonical template,
with a draft (unpublished) stage before going live.

### B1 — Choose the canonical template
- [ ] Decide the single source-of-truth template per page type:
      - Case study → derive a `work/case-study/_TEMPLATE.html` from a polished existing one.
      - Project detail → `project-detail-template.html` (already present).
      - Shared chrome → `header-footer-template.html` / `master-template.html`.
- [ ] Replace lorem-ipsum/demo content in the template with `{{PLACEHOLDER}}` tokens
      (title, slug, hero image, metrics, body, OG meta).

### B2 — Draft workflow (static-site approach)
- [ ] Create a `work/_drafts/` folder (git-ignored or `noindex`) for in-progress pages.
- [ ] New page = copy template → fill tokens → save in `_drafts/` → preview via `php-backend`.
- [ ] "Publish" = move file from `_drafts/` to its live folder + add the card link on `works.html`/`projects.html`.
- [ ] Add `<meta name="robots" content="noindex">` to drafts so they never get indexed early.

### B3 — (Optional) PHP-driven page manager
*If you want the dashboard itself to create/edit/draft pages:*
- [ ] Add a `pages` table (id, slug, type, status[draft|published], json_fields, html, updated_at).
- [ ] Extend `collect.php` (or a new `pages.php`) with admin-only actions: `page_new`, `page_edit`, `page_draft`, `page_publish`.
- [ ] Render published pages from the template + stored fields; write static HTML on publish (keeps the site fast).
- [ ] Reuse the existing admin session/auth + add CSRF (per `SECURITY-FIXES.md`).

### B4 — Consistency guardrails
- [ ] Run the dedupe step (`dedupe-commands.sh`) so naming is consistent before adding new pages.
- [ ] Resolve the unstyled `project-preview/*` stubs (they load no CSS) — fold them into the template.
- [ ] Add favicon + `og:image`/Twitter meta to the template so every new page ships with them.

---

## C. Nice-to-haves (post-launch)
- [ ] Compress the 126 MB archived PDFs with `ghostscript` (`brew install ghostscript`) before linking any.
- [ ] Add a tiny build step (npm `terser` + `clean-css`) if JS/CSS churn increases.
- [ ] Consider PHP includes for the shared header/footer (the deferred templating refactor).
