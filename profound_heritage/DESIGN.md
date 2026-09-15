---
name: Profound Heritage
colors:
  surface: '#f9f9f9'
  surface-dim: '#dadada'
  surface-bright: '#f9f9f9'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f3f3f3'
  surface-container: '#eeeeee'
  surface-container-high: '#e8e8e8'
  surface-container-highest: '#e2e2e2'
  on-surface: '#1a1c1c'
  on-surface-variant: '#45464e'
  inverse-surface: '#2f3131'
  inverse-on-surface: '#f1f1f1'
  outline: '#76777f'
  outline-variant: '#c6c6cf'
  surface-tint: '#525d80'
  primary: '#081534'
  on-primary: '#ffffff'
  primary-container: '#1e2a4a'
  on-primary-container: '#8691b7'
  inverse-primary: '#bac5ee'
  secondary: '#b51a1e'
  on-secondary: '#ffffff'
  secondary-container: '#d93633'
  on-secondary-container: '#fffbff'
  tertiary: '#161717'
  on-tertiary: '#ffffff'
  tertiary-container: '#2b2b2b'
  on-tertiary-container: '#939292'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dae2ff'
  primary-fixed-dim: '#bac5ee'
  on-primary-fixed: '#0d1a39'
  on-primary-fixed-variant: '#3a4667'
  secondary-fixed: '#ffdad6'
  secondary-fixed-dim: '#ffb4ac'
  on-secondary-fixed: '#410003'
  on-secondary-fixed-variant: '#93000e'
  tertiary-fixed: '#e4e2e1'
  tertiary-fixed-dim: '#c8c6c5'
  on-tertiary-fixed: '#1b1c1c'
  on-tertiary-fixed-variant: '#474747'
  background: '#f9f9f9'
  on-background: '#1a1c1c'
  surface-variant: '#e2e2e2'
  surface-white: '#FFFFFF'
  on-surface-charcoal: '#2C2C2C'
typography:
  display-lg:
    fontFamily: Montserrat
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 56px
    letterSpacing: -0.02em
  display-lg-mobile:
    fontFamily: Montserrat
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Montserrat
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 40px
    letterSpacing: -0.01em
  headline-sm:
    fontFamily: Montserrat
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  body-lg:
    fontFamily: Montserrat
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Montserrat
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-md:
    fontFamily: Montserrat
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0.05em
  label-sm:
    fontFamily: Montserrat
    fontSize: 12px
    fontWeight: '700'
    lineHeight: 16px
    letterSpacing: 0.08em
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  base: 8px
  gutter: 24px
  margin-mobile: 16px
  margin-desktop: 80px
  container-max: 1200px
---

## Brand & Style

The design system is defined by a sense of **Corporate Professionalism** and **Established Authority**. It shifts from the previous organic warmth to a more structured, high-contrast aesthetic that emphasizes institutional strength and clarity. The brand personality is dependable, serious, and impactful, targeting an audience that values efficiency and clear communication.

The visual style leans into **Modern Minimalism** with a focus on bold color blocks and rigid structure. By utilizing a high-contrast palette of deep navy and bright red against clinical white, the UI evokes a sense of urgency and importance. The design avoids unnecessary decorative flourishes, favoring clean lines, generous whitespace, and sharp typographic hierarchy to guide the user through information-dense environments.

## Colors

The color palette is architected for maximum legibility and professional hierarchy. 

- **Primary (Deep Blue):** The foundational anchor for the identity. It is used exclusively for structural elements such as headers, navigation systems, and section dividers to establish a trustworthy frame.
- **Accent (Bright Red):** Reserved for critical touchpoints. Use this for primary call-to-action buttons, status indicators, and urgent notices to ensure they break the blue/white dominance.
- **Surface (White):** The primary canvas. Used for all main content areas and cards to maintain a clean, "paper-like" clarity.
- **Secondary Surface (Light Gray):** Employed for background differentiation in tiered layouts, such as sidebar containers or secondary content sections, to prevent visual fatigue.
- **On-Surface (Charcoal):** The primary vehicle for communication. Used for body text and footers to ensure AA/AAA contrast compliance against both white and light gray surfaces.

## Typography

This design system utilizes **Montserrat** across all typographic levels to create a unified, geometric, and modern appearance. The typeface's wide aperture and high x-height provide excellent readability in high-contrast environments.

Headlines should be set with tight tracking to appear as solid blocks of information, reinforcing the institutional feel. For body text, weight is kept at 400 (Regular) to ensure that the dense charcoal color doesn't overwhelm the white background. Labels and small metadata should use 600 or 700 weight in uppercase to create a distinct visual "tag" that separates them from narrative text.

## Layout & Spacing

The layout is governed by a **Fixed Grid** system that prioritizes alignment and mathematical balance. 

- **Desktop:** A 12-column grid with a 1200px maximum width. Elements should snap to the grid to maintain a "blueprint" aesthetic. 
- **Mobile:** A 4-column fluid grid.

The spacing rhythm is strictly 8px-based. Generous vertical margins (80px+) are used between sections to allow the high-contrast elements to stand out without feeling cluttered. Section dividers should be 1px or 2px lines in Primary Deep Blue to clearly demarcate changes in content subjects.

## Elevation & Depth

To maintain the professional and serious tone, this design system avoids heavy shadows and skeuomorphism. Instead, it uses **Low-Contrast Outlines** and **Tonal Layering**.

- **Primary Depth:** Depth is conveyed by the contrast between Surface (White) and Secondary Surface (Light Gray). 
- **Borders:** Cards and containers use a subtle 1px border in Light Gray or Deep Blue (for active states) rather than shadows.
- **Interactive States:** High-priority items may use a sharp, 4px "hard shadow" in the Primary Deep Blue color to simulate a physical lift without using blurs, keeping the design crisp and technical.

## Shapes

The shape language is **Soft (0.25rem)**. This provides a subtle refinement to the otherwise rigid and geometric layout, making the interface feel modern and engineered rather than clinical.

- **Standard Elements:** Buttons, inputs, and small cards use 0.25rem corners.
- **Large Containers:** Hero sections or large feature cards may use 0.5rem (rounded-lg) to frame photography.
- **Strictness:** Do not use pill-shapes or circular buttons; all elements must retain their rectangular integrity to align with the professional brand narrative.

## Components

- **Navigation & Headers:** Always rendered in Primary Deep Blue with White text. Navigation links use Montserrat 600 weight.
- **Buttons:**
    - **Primary:** Bright Red background with White text. Sharp, high-visibility, and used only for the main action on a page.
    - **Outline:** Deep Blue border and text with no fill, used for secondary actions.
- **Input Fields:** Pure white background with a 1px Light Gray border. On focus, the border transitions to 2px Deep Blue.
- **Cards:** White surfaces with a 1px Light Gray border. Content inside uses Charcoal for body text and Deep Blue for internal sub-headers.
- **Dividers:** Horizontal rules should be 1px thick. Use Light Gray for subtle separations and Deep Blue for major section breaks.
- **Notices/Alerts:** Important notices should use the Bright Red color as a top-border accent to immediately draw the eye's attention.