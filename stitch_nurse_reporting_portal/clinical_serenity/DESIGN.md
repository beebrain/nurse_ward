# Design System Specification: The Clinical Sanctuary

## 1. Overview & Creative North Star
**Creative North Star: "The Ethereal Clinic"**

In high-stakes healthcare environments, digital interfaces often feel cluttered, cold, or overly technical. This design system rejects the "dashboard fatigue" of traditional nursing software. Instead, it adopts an editorial, high-end approach we call **The Ethereal Clinic**. 

By leveraging intentional asymmetry, breathable white space, and a sophisticated layering of soft tones, we create an environment that feels authoritative yet calming. We move beyond the "grid of boxes" by using overlapping surfaces and high-contrast typography scales (Manrope for headlines, Inter for utility). This system is designed to reduce the cognitive load on medical staff through a "quiet" UI that only speaks when necessary.

---

### 2. Colors: Tonal Depth vs. Structural Lines

This system is built on the **"No-Line" Rule**. To maintain a premium, modern feel, 1px solid borders for sectioning are strictly prohibited. Boundaries are defined solely through tonal shifts and surface nesting.

#### Surface Hierarchy & Nesting
Treat the UI as a physical stack of fine, semi-translucent materials.
*   **Base Layer:** Use `surface` (#f9f9ff) for the primary application background.
*   **Sectioning:** Use `surface_container_low` (#f2f3fc) to define large content areas.
*   **Information Hubs:** Use `surface_container_lowest` (#ffffff) for primary cards or data entry modules to make them "pop" against the darker background.
*   **The Glass Rule:** For floating navigation or modals, utilize `surface_variant` (#e0e2ea) with a 60% opacity and a `backdrop-blur` of 20px. This creates a "frosted glass" effect that keeps the staff oriented within the application's spatial depth.

#### Signature Textures
Avoid flat, "dead" colors for primary actions. 
*   **Hero CTAs:** Use a subtle linear gradient (Top-Left to Bottom-Right) transitioning from `primary` (#005dac) to `primary_container` (#1976d2). This adds a "jewel-like" polish that signifies importance without visual noise.

---

### 3. Typography: The Editorial Balance

We use a dual-typeface system to balance clinical precision with human warmth.

*   **Display & Headlines (Manrope):** Use `display-lg` to `headline-sm` for page titles and high-level patient metrics. Manrope’s geometric yet friendly curves provide an authoritative, "magazine-style" look that breaks the monotony of data-heavy screens.
*   **Body & Utility (Inter):** Use `body-lg` through `label-sm` for all patient records, medical notes, and interface controls. Inter’s high x-height ensures maximum readability during long shifts under hospital lighting.
*   **Hierarchy Note:** Use `on_surface_variant` (#414752) for secondary metadata to create a clear visual distinction from the primary `on_surface` (#181c21) text.

---

### 4. Elevation & Depth: Atmospheric Layering

Traditional drop shadows are too "heavy" for a healthcare context. We use **Tonal Layering** and **Ambient Shadows** to convey importance.

*   **The Layering Principle:** Place a `surface_container_lowest` card (Pure White) onto a `surface_container` background. The slight shift in hex value provides enough contrast for the eye to perceive depth without a single line being drawn.
*   **Ambient Shadows:** For elevated elements (like active patient charts), use a custom shadow: `0px 12px 32px rgba(0, 95, 175, 0.06)`. By tinting the shadow with `surface_tint` (#005faf), the elevation feels like natural light passing through a blue-tinted lens.
*   **Ghost Borders:** If a boundary is required for accessibility in data grids, use `outline_variant` at **15% opacity**. It should be felt, not seen.

---

### 5. Components: Fluidity & Softness

All components utilize the **Roundedness Scale**, specifically `md` (0.75rem / 12px) for cards and `full` for buttons, creating a "friendly" and "safe" tactile feel.

*   **Buttons:**
    *   *Primary:* Gradient fill (Primary to Primary-Container) with `on_primary` text.
    *   *Secondary:* `secondary_container` (#98f994) background with `on_secondary_container` (#0c7521) text. Use for positive actions like "Confirm Vitals."
*   **Input Fields:** Use `surface_container_high` (#e6e8f0) as the fill color with no border. Upon focus, transition to a `primary` ghost border (20% opacity).
*   **Cards & Lists:** **Strictly forbid dividers.** Separate patient list items using the `3` (1rem) spacing token. To separate groups, shift the background color of the even-numbered items to `surface_container_low`.
*   **Medical Chips:** Use `secondary_fixed` (#98f994) for "Stable" statuses and `error_container` (#ffdad6) for "Urgent" alerts. Keep these pill-shaped (`full` roundedness).
*   **Patient Timeline (Special Component):** Instead of a vertical line, use a series of staggered `surface_container_highest` blocks with varying heights to create a rhythmic, editorial flow of medical events.

---

### 6. Do’s and Don'ts

#### Do
*   **Do** use `20` (7rem) spacing for top-level page margins to create an "expensive," airy feel.
*   **Do** use `primary_fixed_dim` for icons to keep them legible but softer than pure black.
*   **Do** nest containers (Lowest on Low) to create hierarchy without clutter.

#### Don't
*   **Don't** use 100% black (#000000) for text. Use `on_surface` (#181c21) for a softer, premium contrast.
*   **Don't** use standard "Drop Shadows." Use our Ambient Shadow spec with the blue tint.
*   **Don't** use dividers or lines to separate content. Let the white space (Spacing Scale `4` or `5`) do the work.
*   **Don't** use sharp corners. Everything in a nursing context should feel approachable and "soft" to the touch.