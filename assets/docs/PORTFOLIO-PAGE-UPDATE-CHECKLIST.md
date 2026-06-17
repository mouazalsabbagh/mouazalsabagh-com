# Portfolio Page Update Checklist

## Reference Templates
- **Master Template**: [header-footer-template.html](../../templates/header-footer-template.html)
- **Design System CSS**: [assets/css/design-system.css](assets/css/design-system.css)
- **Custom Scripts**: [assets/js/script.js](assets/js/script.js) (sticky nav, scroll-to-top)

---

## CSS Link Order (Required in Every Page)

```html
<!-- Vendor & template CSS -->
<link rel="stylesheet" href="assets/css/flaticon.min.css">
<link rel="stylesheet" href="assets/css/fontawesome-5.14.0.min.css">
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/nice-select.min.css">
<link rel="stylesheet" href="assets/css/animate.min.css">
<link rel="stylesheet" href="assets/css/slick.min.css">
<link rel="stylesheet" href="assets/css/style.css">

<!-- Design System (LAST—overrides template styles) -->
<link rel="stylesheet" href="assets/css/design-system.css">
```

---

## JavaScript Link Order (Required Before `</body>`)

```html
<script src="assets/js/jquery-3.6.0.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/appear.min.js"></script>
<script src="assets/js/slick.min.js"></script>
<script src="assets/js/jquery.nice-select.min.js"></script>
<script src="assets/js/imagesloaded.pkgd.min.js"></script>
<script src="assets/js/isotope.pkgd.min.js"></script>
<script src="assets/js/wow.min.js"></script>
<script src="assets/js/script.js"></script>

<!-- Populate footer copyright year -->
<script>
    document.getElementById('copyright-year').textContent = new Date().getFullYear();
</script>
```

---

## Page Status by Category

### ✅ COMPLIANT (7 pages)
Pages that already follow the template structure and include design-system.css:
- `contact.html` (Just fixed!)
- `design-system.html` (Reference)
- `header-footer-template.html` (Reference)
- `master-template.html` (Reference)
- `project-detail-template.html` (Reference)
- `index.html`
- `value-acceleration.html`

**Action**: No changes needed. Verify sticky navbar works when scrolling.

---

### 🔧 REQUIRES UPDATE (Main Pages - 8 pages)

These pages use the old Noxfolio template without design-system.css and need updating:

#### 1. **about.html**
**Current Issues**:
- Missing `design-system.css` link ✗
- Header structure correct but needs logo fix (check for duplicate wrappers)
- Footer uses text logo instead of image logo
- No sticky navbar CSS (relies on style.css defaults)

**Update Steps**:
1. **Add CSS Link**: After `style.css`, add: `<link rel="stylesheet" href="assets/css/design-system.css">`
2. **Header**: Verify logo structure matches template (line 43-46 of template):
   ```html
   <div class="logo-outer">
       <div class="logo"><a href="index.html"><img src="assets/images/logos/logo.png" alt="Logo" title="Logo"></a></div>
   </div>
   ```
3. **Footer**: Replace text-based logo with image logo (template lines 200-204):
   ```html
   <div class="logo-outer">
       <div class="logo"><a href="index.html"><img src="./assets/images/logos/logo.png" alt="Logo" title="Logo"></a></div>
   </div>
   ```
4. **Verify JS Scripts**: Ensure all 9 script files are present before `</body>`
5. **Custom CSS**: Check for inline `<style>` blocks and migrate to design-system.css if found

---

#### 2. **ai-engine.html**
**Current Issues**:
- Missing `design-system.css` link ✗
- Header uses `class="main-header"` (missing `header-two`) — check if intentional for different style
- Footer structure likely non-standard
- No sticky navbar

**Update Steps**:
1. **Add CSS Link**: After `style.css`, add: `<link rel="stylesheet" href="assets/css/design-system.css">`
2. **Header**: Update to `class="main-header header-two"` if standardizing
3. **Header Logo**: Apply template structure (lines 43-46)
4. **Footer**: Replace with template footer (lines 197-234)
5. **Navigation**: Verify all menu items link correctly
6. **Verify JS Scripts**: All 9 scripts present

---

#### 3. **blog.html**
**Current Issues**:
- Missing `design-system.css` link ✗
- Header and footer likely need updates
- No sticky navbar styling

**Update Steps**:
1. **Add CSS Link**: After `style.css`, add: `<link rel="stylesheet" href="assets/css/design-system.css">`
2. **Header**: Fix logo structure (template lines 43-46)
3. **Header**: Update to `class="main-header header-two"`
4. **Footer**: Apply template footer structure
5. **Verify JS Scripts**: All 9 scripts present
6. **Check Custom CSS**: Any inline styles for blog filters or cards

---

#### 4. **blog-details.html**
**Current Issues**:
- Missing `design-system.css` link ✗
- Header structure needs verification
- Footer needs updating
- No sticky navbar

**Update Steps**:
1. **Add CSS Link**: After `style.css`, add: `<link rel="stylesheet" href="assets/css/design-system.css">`
2. **Header**: Fix logo + update class to `header-two`
3. **Footer**: Apply template footer
4. **Verify JS Scripts**: All 9 scripts present
5. **Preserve Page Content**: Blog article structure stays intact

---

#### 5. **project-details.html**
**Current Issues**:
- Missing `design-system.css` link ✗
- Header and footer need updates
- Related projects section may have custom CSS

**Update Steps**:
1. **Add CSS Link**: After `style.css`, add: `<link rel="stylesheet" href="assets/css/design-system.css">`
2. **Header**: Fix logo + update class to `header-two`
3. **Footer**: Apply template footer
4. **Verify JS Scripts**: All 9 scripts present
5. **Check Custom CSS**: Related projects hover effects (migrate to design-system.css if present)

---

#### 6. **projects-masonry.html**
**Current Issues**:
- Missing `design-system.css` link ✗
- Header and footer need updates
- Isotope filter may have custom CSS

**Update Steps**:
1. **Add CSS Link**: After `style.css`, add: `<link rel="stylesheet" href="assets/css/design-system.css">`
2. **Header**: Fix logo + update class to `header-two`
3. **Footer**: Apply template footer
4. **Verify JS Scripts**: All 9 scripts present
5. **Check Custom CSS**: Filter buttons styling

---

#### 7. **services.html**
**Current Issues**:
- Missing `design-system.css` link ✗
- Header and footer need updates
- Pricing section may have custom styles

**Update Steps**:
1. **Add CSS Link**: After `style.css`, add: `<link rel="stylesheet" href="assets/css/design-system.css">`
2. **Header**: Fix logo + update class to `header-two`
3. **Footer**: Apply template footer
4. **Verify JS Scripts**: All 9 scripts present
5. **Check Custom CSS**: Service cards, pricing tables

---

#### 8. **404.html**
**Current Issues**:
- Missing `design-system.css` link ✗
- Has sidebar form modal (unusual for error page)
- Header and footer need updates

**Update Steps**:
1. **Add CSS Link**: After `style.css`, add: `<link rel="stylesheet" href="assets/css/design-system.css">`
2. **Header**: Fix logo + update class to `header-two`
3. **Footer**: Add proper template footer (currently just has hidden sidebar form)
4. **Verify JS Scripts**: All 9 scripts present
5. **Optional**: Consider whether 404 page needs full footer or simplified version

---

### 📄 GRID/LIST PAGES (5 pages)

These pages display collections and need standard headers/footers:
- `projects.html`
- `projects-grid.html`
- `works.html`
- `blog.html` (already covered above)
- `design-system.html` (reference)

**Action**: Apply same updates as main pages above (add design-system.css, fix header/footer, verify scripts)

---

### 🎨 CUSTOM DESIGN PAGES (20 pages - Case Studies)

**Location**: `work/case-study/` folder

**Note**: These pages use a **different design system** with orange accent (#f59e0b) instead of lime (#C9F31D). 

**Sample Files**:
- `work/case-study/case-study-aliexpress.html`
- `work/case-study/case-study-flynas.html`
- `work/case-study/case-study-gamers8.html`
- (18 more case studies)

**Decision Required**: 
- **Option A**: Update to use main portfolio design-system.css (lime accent)
- **Option B**: Keep custom design but ensure headers/footers are standards

**Recommendation**: Option B (preserve custom branding per case study)

**Action**: 
1. Verify each has proper header/footer template
2. Create `case-study-design-system.css` for custom styling if needed
3. Ensure all 9 JS scripts are included

---

### 🔗 PREVIEW PAGES (48 pages)

**Location**: `work/project-preview/` folder

**Current Status**: Likely minimal preview pages with limited content

**Sample Files**:
- `work/project-preview/project-preview-aliexpress.html`
- `work/project-preview/project-preview-flynas.html`
- `work/project-preview/project-preview-gamers8.html`
- (45 more previews)

**Action Plan**:
1. Audit 2-3 preview pages to understand structure
2. If they're minimal stubs: Add standard header/footer + design-system.css
3. If they're content-heavy: Apply full update process

---

## Implementation Priority

### Phase 1: Critical (Core Pages - Do First)
Priority order for maximum impact:
1. `about.html` — High-traffic page
2. `services.html` — Conversion page
3. `blog.html` — Content hub
4. `project-details.html` — Portfolio showcase
5. `projects-masonry.html` — Gallery page

**Estimated Time**: 30-45 minutes (5-10 min per page × 5 pages)

### Phase 2: Secondary (Collection Pages)
6. `projects.html`
7. `projects-grid.html`
8. `works.html`
9. `blog-details.html`

**Estimated Time**: 30-40 minutes

### Phase 3: Utilities (Rarely Visited)
10. `404.html` — Error page
11. `value-acceleration.html` — Specialty page

**Estimated Time**: 10-15 minutes

### Phase 4: Batch Updates (Long Tail - Optional)
12. Case study pages (20 files) — Assess first
13. Preview pages (48 files) — Audit first

---

## Quick Audit Checklist (Per Page)

Use this quick checklist when updating each page:

```
□ Check: <link rel="stylesheet" href="assets/css/design-system.css"> present?
□ Check: Header has class="main-header header-two"?
□ Check: Logo structure is: <div class="logo-outer"><div class="logo"><a>...
□ Check: Footer has: <div class="logo-outer"><div class="logo"><a>... (not text logo)?
□ Check: All 9 JS scripts present before </body>?
□ Check: <body class="home-two">?
□ Check: Inline <style> blocks identified and flagged?
□ Check: No duplicate class attributes in HTML?
□ Browser Test: Sticky navbar appears at 50px scroll?
□ Browser Test: Footer copyright year is current?
```

---

## Common Fixes by Issue Type

### Issue: "Missing design-system.css"
**Fix**: Add this line after `<link ... style.css">`:
```html
<link rel="stylesheet" href="assets/css/design-system.css">
```

### Issue: "Header class missing header-two"
**Fix**: Change from:
```html
<header class="main-header">
```
To:
```html
<header class="main-header header-two">
```

### Issue: "Duplicate logo divs in header"
**Fix**: Ensure only one wrapper:
```html
<!-- CORRECT -->
<div class="logo-outer">
    <div class="logo"><a href="index.html"><img src="assets/images/logos/logo.png"...></a></div>
</div>

<!-- WRONG (duplicate) -->
<div class="logo-outer">
    <div class="logo">
    <div class="logo"><a href="index.html"><img src="assets/images/logos/logo.png"...></a></div>
</div>
```

### Issue: "Footer has text logo instead of image"
**Fix**: Replace:
```html
<a href="index.html">MOUAZ<span>.</span></a>
```
With:
```html
<div class="logo-outer">
    <div class="logo"><a href="index.html"><img src="./assets/images/logos/logo.png" alt="Logo"></a></div>
</div>
```

### Issue: "JavaScript not loading"
**Fix**: Ensure before `</body>`:
```html
<script src="assets/js/jquery-3.6.0.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/script.js"></script>
```

---

## Verification Steps

After updating each page:

1. **Open in Browser**: `file:///c:/Users/.../mouazalsabbagh.com/[page-name].html`
2. **Scroll Test**: Scroll down 50px, verify navbar darkens and shrinks
3. **Logo Test**: Verify logo image loads (not broken)
4. **Footer Test**: Verify footer year shows current year
5. **Mobile Test**: Check responsive menu works (hamburger on <768px)
6. **Links Test**: Verify 3-4 navigation links work

---

## Notes

- All pages use `class="home-two"` on `<body>` (required for theme-btn styling)
- Sticky navbar triggers at 50px scroll (see `initStickyGlassNavbar()` in script.js)
- Design tokens in CSS variables: `--primary: #C9F31D`, `--border: rgba(255,255,255,0.10)`, etc.
- Font loading via Google Fonts CDN in style.css (no need to add separately)
- All pages use Bootstrap 5 grid (`col-lg-*`, `col-md-*`, etc.)

---

## Files Modified Tracking

Once you update pages, mark them here:

- [x] contact.html — DONE (May 25, 2026)
- [x] about.html — DONE (May 25, 2026) - Added design-system.css
- [x] ai-engine.html — DONE (May 25, 2026) - Added design system and fixed footer
- [x] blog.html — DONE (May 25, 2026) - Added design-system.css
- [x] blog-details.html — DONE (May 25, 2026) - Full update
- [x] project-details.html — DONE (May 25, 2026) - Added design-system.css
- [x] projects-masonry.html — DONE (May 25, 2026) - Added design-system.css
- [x] services.html — DONE (May 25, 2026) - Added design-system.css
- [x] 404.html — DONE (May 25, 2026) - Replaced hidden sidebar with proper footer
- [x] projects.html — DONE (May 25, 2026) - Migrated CSS, Full update
- [x] projects-grid.html — DONE (May 25, 2026) - Body class update
- [x] works.html — DONE (May 25, 2026) - Migrated CSS, Full update
- [x] value-acceleration.html — N/A (Contains only JS scratchpad data)

---

**Next Step**: Choose Phase 1 pages above and execute updates using provided template code snippets.
