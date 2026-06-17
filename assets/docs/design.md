# Design System: Skill Area Two

## 🎨 Color Palette
* **Section Background:** `#0F0E0E` (`rgb(15, 14, 14)`)
* **Primary Accent (Highlights / Percentages):** `#C9F31D` (`rgb(201, 243, 29)`) - *Neon Yellow/Green*
* **Heading Text:** `#FFFFFF`
* **Base Body Text:** `rgba(255, 255, 255, 0.65)`

## ✍️ Typography
* **Primary Font Family:** `Inter, sans-serif`
* **Sub-title (`.sub-title`):**
  * `font-size: 17px`
  * `font-weight: 400`
  * `text-transform: uppercase`
  * `color: #C9F31D`
* **Main Heading (`.section-title h2`):**
  * `font-size: 50px`
  * `font-weight: 500`
  * `text-transform: uppercase`
  * `color: #FFFFFF`
* **Skill Percentage (`.percent`):**
  * `font-size: 30px`
  * `font-weight: 400`
  * `color: #C9F31D`
* **Skill Title (`.skill-item-two .title`):**
  * Default body sizing around `16px`
  * `line-height: 30px`

## 📏 Layout & Spacing
* **Section Padding:**
  * `padding-top: 130px`
  * `padding-bottom: 105px`
* **Spacing:**
  * `.row.gap-40` provides a `40px` gap between skill cards
  * `.skill-item-two` uses `margin-bottom: 35px`
* **Columns:**
  * Left content/illustration: `col-lg-5`
  * Skill grid: `col-lg-7`

## ✨ Effects & Animations
* **Animation:** `fadeInUp`
* **Duration:** `1s`
* **Delay:** staggered, starting at `0.3s` for skill cards (with incremental delay values like `0.4s`, `0.5s`, `0.6s`)
* **Animation fill mode:** `both`

## 🧩 Components
* **Skill Item (`.skill-item-two`):**
  * Transparent background
  * Centered content alignment
  * Includes an icon/image plus a numeric percentage badge inside `.icon-percent`
* **Decorative Background (`.bg-lines`):**
  * Repeated spans used for visual depth and section decoration

## 📝 Notes
* The section inherits `color: rgba(255, 255, 255, 0.65)` from the parent section for body copy.
* The accent color `#C9F31D` is used for the sub-title and percentage values to reinforce the bright, tech-forward look.
