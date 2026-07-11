# Guest Registration UI/UX Improvement Spec

## 1. Executive Summary

The guest registration flow (`?r=register`) is the first touchpoint for event participants. Today it is functional but visually flat: a single long form inside a generic Bootstrap card, with minimal hierarchy, no progressive guidance, and limited optimization for kiosk-style tablet use at event entrances.

This spec defines a **mobile-first, tablet-optimized** redesign that makes registration feel modern, trustworthy, and fast — while keeping the existing PHP/Bootstrap stack and all current form fields and validation behavior intact.

### Goals
- Make the page **visually engaging** without sacrificing clarity or government-event professionalism
- Optimize for **phones, iPads, Samsung Galaxy Tab, and landscape tablet kiosks**
- Reduce perceived form length through **grouping and stepped flow**
- Improve **touch ergonomics** for standing users at registration desks
- Align **register**, **register_success**, and **register_error** into one cohesive guest experience

### Out of scope (for this phase)
- Admin UI changes
- Backend validation or database changes
- New registration fields
- Full rebrand of the entire application

---

## 2. Current State Audit

### Files in scope
| File | Role |
|------|------|
| `views/register.php` | Main registration form |
| `views/register_success.php` | QR confirmation screen |
| `views/register_error.php` | Error feedback screen |
| `assets/app.css` | Shared guest + admin styles |

### What works today
- Bootstrap 5.3 grid is already responsive (`col-12 col-md-6`)
- `assets/app.css` defines brand tokens (`--brand-primary`, `--brand-accent`) and Inter font
- CSRF, required fields, and agency/designation expand logic are functional
- Viewport meta tag is present

### Pain points
| Issue | Impact |
|-------|--------|
| **Wall of fields** | 11 inputs in one screen feels overwhelming on phones and tablets |
| **Weak visual hierarchy** | Header, step label, and badge compete; no clear section grouping |
| **Plain card layout** | Single white card on soft gradient — safe but forgettable |
| **Small touch targets** | Navbar buttons (`btn-sm`), expand toggles, and default input height are below ideal kiosk size |
| **No event context** | Page says "Event Registration" but shows no event name, date, or welcome message |
| **Agency/Designation UX** | "Expand" button + hidden select is confusing on touch devices |
| **No progress feedback** | "Step 1 of 2" is static; user cannot see what step 2 is |
| **Inconsistent guest shell** | Success/error pages use `glass-panel`; register uses plain `card` |
| **No loading/submit state** | Register button gives no feedback during POST |
| **Landscape tablet gaps** | Form stays narrow (`col-lg-8`) leaving unused horizontal space on 10–12" tablets |

---

## 3. Design Direction

### Visual personality
**Professional + welcoming + efficient** — suitable for a government networking/launching event, not a consumer app or playful startup landing page.

### Mood board keywords
- Clean civic tech
- Soft glass surfaces
- Confident purple/teal accent (existing palette)
- Generous whitespace
- Subtle motion (fade/slide, not flashy)

### Proposed layout concept

```
┌─────────────────────────────────────────────────────────────┐
│  [Logo/Event mark]  GovNet-Launching          [Scan] [Admin]│
├─────────────────────────────────────────────────────────────┤
│                                                             │
│   ┌──────────────────┐   ┌──────────────────────────────┐ │
│   │  Welcome panel   │   │  Step indicator + form card  │ │
│   │  Event name      │   │  ┌ Personal ─ Work ─ Contact ┐│ │
│   │  Date / venue    │   │  │  [fields for active step]  ││ │
│   │  3-step guide    │   │  │                            ││ │
│   │  illustration    │   │  │  [Back]        [Continue]  ││ │
│   └──────────────────┘   └──────────────────────────────┘ │
│         (tablet+)              (all breakpoints)            │
└─────────────────────────────────────────────────────────────┘
```

On **phones**, the welcome panel collapses into a compact hero banner above the form.

---

## 4. Responsive Strategy

### Target devices

| Category | Examples | Primary use case |
|----------|----------|------------------|
| Phone | iPhone, Galaxy S series | Self-registration on personal device |
| Small tablet | iPad Mini, Tab A | Staff-assisted registration |
| Large tablet | iPad Pro 11/12.9, Galaxy Tab S9 | Kiosk / welcome desk |
| Landscape kiosk | Tablet on stand | Fast walk-up registration |

### Breakpoints (Bootstrap-aligned)

| Token | Width | Layout behavior |
|-------|-------|-------------------|
| `xs` | &lt; 576px | Single column, sticky bottom CTA, condensed hero |
| `sm` | ≥ 576px | Two-column fields where appropriate |
| `md` | ≥ 768px | Increased input size, side padding, visible step tabs |
| `lg` | ≥ 992px | Two-panel layout: welcome sidebar + form |
| `xl` | ≥ 1200px | Max content width 1140px, wider field grid |

### Touch & kiosk requirements
- Minimum tap target: **48 × 48 px** (WCAG 2.5.5)
- Input height on tablet: **52–56 px**
- Primary CTA height: **56 px** on `md+`
- Spacing between fields: **16–20 px** on mobile, **20–24 px** on tablet
- Avoid hover-only interactions; all actions must work on touch
- Support **landscape** without horizontal scroll at 1024×768

### Safe areas
- Respect `env(safe-area-inset-*)` for notched phones
- Sticky footer CTA must sit above iOS home indicator

---

## 5. Information Architecture — 3-Step Form

Split the existing single form into three logical steps. **No fields removed.**

### Step 1 — Personal details
- First Name *(required)*
- Middle Name
- Last Name *(required)*
- Nickname
- Sex
- Email Address

### Step 2 — Work information
- Sector *(required)*
- Agency *(required via select/search)*
- Designation
- Office Email

### Step 3 — Contact & submit
- Contact No
- Review summary (read-only chips: name, agency, email)
- Register button

### Stepper UI
- Horizontal step indicator on `md+`: `Personal → Work → Contact`
- On mobile: compact progress bar + "Step 2 of 3" label
- Allow **Back** without losing entered data (client-side only; same form POST at end)
- Validate **per step** before advancing
- Final step submits the full form to `?r=register_submit` (unchanged endpoint)

---

## 6. Component Specifications

### 6.1 Guest page shell (shared across register / success / error)
Create a reusable partial: `views/partials/guest_shell.php`

**Includes:**
- `<head>` meta, fonts, CSS
- Top navbar with event branding
- Optional footer: "Need help?" + support email
- Consistent `guest-page` body class

**Navbar (tablet/phone)**
- Left: event mark + "GovNet-Launching"
- Right: icon buttons on mobile (`Scan`, `Admin`), text buttons on `md+`
- Sticky on scroll with subtle shadow

### 6.2 Welcome hero panel
**Desktop/tablet sidebar content:**
- Event title (configurable later; default: "GovNet-Launching Registration")
- Subtitle: "Register once, check in fast with your QR code"
- 3 illustrated steps:
  1. Fill in your details
  2. Receive your QR code
  3. Scan at the entrance
- Optional decorative SVG pattern or subtle geometric background using brand colors

**Mobile hero (collapsed):**
- 64px icon + one-line welcome
- Thin progress strip under navbar

### 6.3 Form card
- Use `glass-panel` style (already in `app.css`) for consistency with success page
- Rounded corners: **24px** on tablet, **20px** on phone
- Inner padding: `1.25rem` mobile / `2rem` tablet / `2.5rem` desktop
- Soft border + shadow: extend existing `--border-soft` token

### 6.4 Field styling
| Element | Spec |
|---------|------|
| Label | 14px/500, `4d4f65`, 6px margin-bottom |
| Required marker | Teal asterisk or "(required)" screen-reader text |
| Input | 16px font (prevents iOS zoom), 12px radius, focus ring `brand-primary` |
| Select | Custom chevron, full-width on mobile |
| Invalid state | Red border + inline message below field |
| Help text | 12px muted caption under complex fields |

### 6.5 Agency & Designation picker (touch-friendly redesign)
Replace confusing "Expand" toggle with:

**Mobile / tablet pattern:**
- Tap field → opens **bottom sheet** or **full-screen searchable list**
- Search filters datalist options live
- "Other" option reveals text input inline
- Selected value shown as chip inside input area

**Fallback:** keep native `<select>` for no-JS environments

### 6.6 Primary actions
| Button | Style | Placement |
|--------|-------|-----------|
| Continue | `btn-primary btn-lg` | Right side; full-width on mobile |
| Back | `btn-outline-secondary` | Left side; hidden on step 1 |
| Register | `btn-primary btn-lg` + checkmark icon | Sticky bottom bar on mobile |

**Submit loading state:**
- Disable button, show spinner + "Registering..."
- Prevent double-submit

### 6.7 Success page enhancements (`register_success.php`)
- Confetti-free celebration: green check animation or success icon pulse
- Participant name: "Welcome, {First Name}!"
- QR inside elevated card with download/share actions
- Add **Add to Wallet** placeholder button (future)
- Prominent instruction: "Show this screen or downloaded QR at check-in"
- Auto brightness hint for outdoor scanning

### 6.8 Error page enhancements (`register_error.php`)
- Friendly icon (warning, not alarming red banner)
- Preserve field-level errors when returning to form (future: session flash)
- Primary CTA: "Try again" with focus on first invalid field

---

## 7. Visual Design Tokens

Extend `assets/app.css` with a `guest-registration` scope. Do not break admin pages.

```css
/* Proposed additions */
:root {
  --guest-hero-gradient: linear-gradient(145deg, #5c6cf2 0%, #7b5cf2 45%, #00c9a7 100%);
  --guest-surface: rgba(255, 255, 255, 0.92);
  --guest-shadow: 0 20px 50px rgba(30, 42, 93, 0.12);
  --guest-input-height: 3.25rem;      /* 52px */
  --guest-input-height-lg: 3.5rem;    /* 56px tablet */
  --guest-radius-xl: 24px;
  --guest-step-active: var(--brand-primary);
  --guest-step-done: var(--brand-accent);
  --guest-step-muted: #c5c9e0;
}
```

### Typography scale
| Use | Mobile | Tablet+ |
|-----|--------|---------|
| Page title | 24px / 700 | 32px / 700 |
| Section title | 18px / 600 | 20px / 600 |
| Body | 16px / 400 | 16px / 400 |
| Caption | 13px / 500 | 14px / 500 |

### Color usage
- Primary actions: purple gradient (existing)
- Success states: teal accent
- Background: layered gradient with optional subtle grid/noise overlay at 3% opacity
- Avoid pure white inputs on pure white card — use `#fafbff` input background for depth

---

## 8. Motion & Micro-interactions

Keep motion subtle and respect `prefers-reduced-motion`.

| Interaction | Animation |
|-------------|-----------|
| Step change | 200ms horizontal slide + fade |
| Field focus | Border color transition 150ms |
| Button press | Scale 0.98 on active |
| Success check | SVG stroke draw 400ms |
| Invalid field | Gentle horizontal shake 300ms |

---

## 9. Accessibility

- Maintain semantic HTML: `fieldset` + `legend` per step
- Associate all labels with inputs (`for` / `id`)
- Step indicator announced via `aria-current="step"`
- Error summary at top of step with `role="alert"`
- Color contrast: minimum **4.5:1** for body text
- Keyboard: Tab order follows visual order; Enter advances step when valid
- Screen reader: "Step 2 of 3, Work information"

---

## 10. Technical Implementation Plan

### Phase 1 — Foundation (low risk)
1. Add `views/partials/guest_shell.php` and `views/partials/guest_nav.php`
2. Create `assets/guest-registration.css` (scoped styles)
3. Refactor `register.php`, `register_success.php`, `register_error.php` to use partials
4. Unify card style to `glass-panel` across all three pages

### Phase 2 — Layout & responsiveness
1. Implement welcome hero + two-column layout at `lg+`
2. Add sticky mobile CTA bar
3. Increase touch targets and input heights at `md+`
4. Add safe-area padding utilities

### Phase 3 — Stepped form UX
1. Add stepper markup and client-side step navigation JS (`assets/guest-registration.js`)
2. Per-step validation using existing HTML5 `required` attributes
3. Review summary on step 3
4. Submit loading state

### Phase 4 — Agency/Designation picker
1. Bottom-sheet or modal picker component
2. Debounced search over existing datalist options
3. Preserve current POST field names (`agency_select`, `agency_other`, etc.)

### Phase 5 — Polish
1. Success/error page visual upgrades
2. Motion + reduced-motion fallbacks
3. Cross-device QA (see checklist below)

### Files to create
```
views/partials/guest_shell.php
views/partials/guest_nav.php
views/partials/guest_hero.php
assets/guest-registration.css
assets/guest-registration.js
```

### Files to modify
```
views/register.php
views/register_success.php
views/register_error.php
assets/app.css (optional shared tokens only)
```

### JS constraints
- Vanilla JS only (match existing project pattern)
- No build step required
- Progressive enhancement: full form still works if JS fails (single-page fallback)

---

## 11. Wireframe Notes by Device

### Smartphone (portrait, 390×844)
- Navbar: brand left, icon actions right
- Hero: 1 compact banner (~96px)
- Form: full width, one column
- Step tabs → dropdown or dots
- Sticky bottom: `[Back] [Continue]`

### iPad / Galaxy Tab (portrait, 768×1024)
- Navbar: full text buttons
- Hero + form stacked OR hero as top band
- Two-column fields in steps 1–2
- Step tabs visible horizontally
- CTA inline at card bottom (not sticky)

### Tablet kiosk (landscape, 1024×768 / 1280×800)
- Split layout: 35% welcome panel / 65% form
- Large typography and inputs
- Step tabs with icons + labels
- Register desk tip in hero: "Staff: tap Continue to help the guest"

---

## 12. Acceptance Criteria

### Visual
- [ ] Registration page no longer appears as a single plain white form block
- [ ] All three guest pages share the same shell, navbar, and typography
- [ ] Brand colors and Inter font used consistently

### Responsive
- [ ] No horizontal scrolling on iPhone SE, iPhone 15, Galaxy S23, iPad 10th gen, Tab S9
- [ ] Tap targets ≥ 48px on tablet mode
- [ ] Landscape tablet shows two-panel layout without cramping

### UX
- [ ] Form split into 3 steps with back/continue navigation
- [ ] User can complete registration without reading instructions twice
- [ ] Agency/designation selection works with touch only (no precision hover)
- [ ] Submit shows loading state; double-submit prevented

### Functional regression
- [ ] All existing fields still POST to `register_submit` with same names
- [ ] CSRF validation still passes
- [ ] Server-side validation errors still render on `register_error.php`
- [ ] QR success flow unchanged

### Performance
- [ ] No additional JS framework bundles
- [ ] CSS additions &lt; 15KB gzipped
- [ ] First paint acceptable on 4G mobile (no large image assets required)

---

## 13. QA Device Checklist

| Device | Orientation | Test |
|--------|-------------|------|
| iPhone 13/14/15 | Portrait | Full registration flow |
| Samsung Galaxy S22/S23 | Portrait | Agency picker + keyboard overlap |
| iPad 10.9" | Portrait + Landscape | Kiosk layout, two-column fields |
| iPad Pro 12.9" | Landscape | Welcome + form split |
| Galaxy Tab S8/S9 | Landscape | Staff-assisted registration |
| Desktop 1366×768 | Landscape | Regression, no broken grid |

### Browser matrix
- Safari iOS (latest)
- Chrome Android (latest)
- Samsung Internet
- Chrome desktop (admin fallback check)

---

## 14. Future Enhancements (post-MVP)

- Pull active event name/date from `events` table into hero panel
- Dark mode for evening events
- Locale switch (EN / Filipino)
- Save draft in `sessionStorage` if guest navigates away
- Haptic feedback on successful registration (supported devices)
- On-site QR display mode after registration (fullscreen brightness boost)

---

## 15. Implementation Status

| Item | Status |
|------|--------|
| Spec document | Done |
| Guest shell partial | Done |
| Stepped form UI | Done |
| Tablet/kiosk layout | Done |
| Agency picker redesign | Done |
| Success/error page refresh | Done |

---

*Document created: 2026-07-11*  
*Primary route: `?r=register`*  
*Related: `TODOMORE/future_improvements_spec.md` (backend/performance track)*
