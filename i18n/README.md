# i18n — English / Arabic localization

Self-contained EN/AR localization layer for the site. Built from the `Localization`
sheet of `database/mouaz-db-schema-and-localization.xlsx`.

## Files
| File | Purpose |
|------|---------|
| `strings.json` | Translation data: `{ "_meta": {...}, "strings": { "nav.home": {"en","ar"}, … } }` (24 keys) |
| `i18n.js` | ~2 KB runtime: resolves language, swaps text/attrs, flips `dir=rtl`, persists choice |
| `demo.html` | Working pilot page — nav + form with a language toggle (open it to see EN↔AR) |
| `export_i18n.py` | Regenerates `strings.json` from the workbook |
| `README.md` | This file |

## How language is chosen
`?lang=ar` (URL)  →  `localStorage.site_lang`  →  `<html lang>`  →  `_meta.default` (`en`).

## Add it to a real page
1. Tag elements:
   ```html
   <a data-i18n="nav.home">Home</a>
   <input data-i18n-attr="placeholder:form.email">
   <button data-i18n-toggle>العربية</button>   <!-- auto-relabels -->
   ```
2. Load the runtime (adjust the relative path per page depth):
   ```html
   <script src="i18n/i18n.js" data-i18n-src="i18n/strings.json"></script>
   ```
   From `work/case-study/*.html` use `../../i18n/i18n.js` and `data-i18n-src="../../i18n/strings.json"`.
3. Optional RTL CSS hook — the script adds `dir="rtl"` and a `.rtl` class to `<html>`:
   ```css
   html[dir="rtl"] .your-row { flex-direction: row-reverse; }
   ```

## API
`I18N.setLang('ar')` · `I18N.toggle()` · `I18N.t('nav.home')` · `I18N.lang` · event `i18n:applied`.

## Update translations
Edit the workbook's `Localization` sheet → `python3 i18n/export_i18n.py` → commit the new `strings.json`.
Later this same JSON can seed the MySQL `translations` table (Stream 3).

## Verified
EN↔AR text + placeholder swap, `dir` ltr↔rtl flip, persistence, 0 console errors (browser-tested).
