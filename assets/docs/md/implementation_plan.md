# Goal Description

Update the styling, structure, and theme of the Recommendation Letter Generator pages (`rec/index.html` and `rec/index-ar.html`) to align with the global design system and `header-footer-template.html`. This includes incorporating the global header and footer, applying the dark theme, and importing the standard CSS/JS assets.

## User Review Required

> [!WARNING]
> Because these pages are located in a sub-folder (`/rec/`), all asset paths (images, CSS, JS) from the template will be updated to use relative paths like `../assets/`. Please let me know if these pages should be moved out of the `rec` folder instead.

> [!IMPORTANT]
> The current forms have a white background (`#f4f4f4` and `#fff`). They will be completely visually overhauled to use the dark theme (`--bg-body`, `--bg-card`, `--text-body`, `--primary`) from `design-system.css`.

## Open Questions

- Should the language switcher (English/العربية) be integrated into the main navbar, or remain as a standalone button on the page? By default, I will leave it as a styled button near the top of the form area.

## Proposed Changes

### CSS/JS Inclusions
- Add all the requested CSS dependencies in the `<head>` of both pages (`style.css`, `design-system.css`, `animate.min.css`, `bootstrap.min.css`, `nice-select.min.css`, `magnific-popup.min.css`).
- Add the required JS dependencies (`jquery`, `bootstrap`, `nice-select`, `wow`, `script.js`) at the end of the `<body>`.

### Template Structure (`header-footer-template.html`)
- Wrap the body content in `<div class="page-wrapper">` and add the preloader.
- Inject the sticky glassmorphism `<header class="main-header header-two">` and the `<footer>` component into both files.
- Update internal links within the header/footer (e.g., `href="../index.html"`) and image paths (e.g., `src="../assets/images/logos/logo.png"`) to account for the `rec/` subdirectory.

### Form Styling
- Modify the inline `<style>` blocks in both `rec/index.html` and `rec/index-ar.html` to leverage global CSS variables from `design-system.css` instead of hardcoded hex values.
- Form inputs, textareas, and select dropdowns will be given `var(--bg-card)` backgrounds, `var(--border)` borders, and white text to match the dark UI.
- The "Generate" buttons will be styled with the primary accent color `var(--primary)` and `var(--bg-body)` text to match the global CTAs.

## Verification Plan

### Manual Verification
1. I will visually verify that both `rec/index.html` and `rec/index-ar.html` load with the dark theme and have identical headers and footers to the rest of the site.
2. I will ensure that the Arabic page correctly maintains RTL directionality and Arabic typography while adopting the new dark theme.
