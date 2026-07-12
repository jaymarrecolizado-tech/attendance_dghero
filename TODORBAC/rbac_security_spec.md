# Role-Based Access Control (RBAC) & Security Hardening Spec

## 1. Executive Summary

Today the system has a single authenticated identity: anyone in `admins` with a valid session (`$_SESSION['admin_id']`) can use every guarded route. Guest registration and scan remain public (or staff-session for scan submit).

This spec introduces **three roles** with least-privilege access, a **role-aware navigation and dashboard**, and **security hardening** that extends existing CSRF, rate limiting, and session cookie controls — without breaking current admin workflows.

### Roles (display names)

| Role key | Display name | Intent |
|----------|--------------|--------|
| `admin` | Admin | Full system control |
| `checker` | Attendance Checker | On-site registration & check-in operations |
| `seo_viewer` | SEO Viewer | Read-only executive / stakeholder monitoring (VIP & KPI focus) |

> **Note on “SEO Viewer”:** Used as specified. Implementation should treat the label as configurable. If this means a specific office title (e.g. Senior Executive Observer), only the display name needs changing — the permission set stays the same.

### Design principles
1. **Do not break existing admins** — migrate all current `admins` rows to `role = 'admin'`.
2. **Enforce on the server** — hide nav links in UI, but **always** authorize in `Router` + controllers.
3. **Least privilege** — checkers and SEO viewers cannot reach settings, logs, import, or user management.
4. **Additive rollout** — ship schema + AuthService first; switch guards route-by-route; keep one login page.
5. **No new frameworks** — extend current PHP/session/`_guards` pattern.

---

## 2. Current State Audit

### Auth model today
- Table: `admins` (`id`, `username`, `password_hash`, `email`, `created_at`) — **no role column**
- Session: `$_SESSION['admin_id']` only
- Router guards: `_guards => ['admin']` or `['staff']` (scan submit)
- Controllers also call `empty($_SESSION['admin_id'])` locally (duplicate checks)
- Nav: `views/partials/admin_nav.php` shows **all** links to every logged-in user

### Gaps
| Gap | Risk |
|-----|------|
| No role field | Cannot differentiate operators vs executives |
| Flat `admin` guard | Over-privileged check-in staff |
| Nav not filtered | UI suggests access even if we later restrict routes |
| No user management UI | Roles can only be set via SQL/scripts |
| VIP not modeled | SEO dashboard cannot reliably highlight VIPs |
| Session fixation / idle timeout | Limited session lifecycle hardening |
| Dual auth checks | Easy to miss when adding routes |

---

## 3. Role Definitions & Permission Matrix

### 3.1 Admin (`admin`)
**Can access everything** the system already offers, plus future user/role management.

| Area | Access |
|------|--------|
| Register (guest form) | Yes |
| Scan & Sign | Yes |
| Registrants (list, QR, email) | Yes |
| Attendance (KPI, roster, mark present/absent, signatures) | Yes |
| Gallery | Yes |
| Events | Yes |
| Import / Import history | Yes |
| Export (CSV/XLSX/PDF) | Yes |
| Report | Yes |
| Logs | Yes |
| Settings | Yes |
| User & role management (new) | Yes |
| SEO / VIP dashboard | Yes (optional link) |

### 3.2 Attendance Checker (`checker`)
**Operational role** for welcome desk / door staff.

| Area | Access |
|------|--------|
| Register | Yes |
| Registrants | Yes (list, search, QR preview; **no** bulk destructive ops if added later) |
| Attendance | Yes (view roster, KPIs, mark attendance, mark absent/in vicinity, signatures) |
| Scan & Sign | Yes (recommended — door workflow) |
| Gallery | **No** (default; optional later) |
| Events / Import / Export / Report / Logs / Settings | **No** |
| User management | **No** |

**Suggested checker defaults after login:** land on `?r=admin_attendance` (or `admin_registrants`).

**Registrants nuance (recommended):**
- Allow: search, view, generate missing QR, send QR email, open register page
- Deny: any future delete/merge participant actions (admin-only)

### 3.3 SEO Viewer (`seo_viewer`) — suggested dashboard scope

**Read-only monitoring** for executives / protocol / stakeholders who need situational awareness without editing data.

#### Core permissions
| Capability | Access |
|------------|--------|
| Dedicated SEO dashboard (`admin_seo_dashboard`) | Yes (home after login) |
| Attendance KPIs (JSON read) | Yes |
| VIP / priority guest status list | Yes |
| Live vicinity / present / absent counts | Yes |
| Agency / sector attendance snapshot | Yes (aggregate) |
| Register / Registrants / Attendance write actions | **No** |
| Mark attendance / absent / signatures | **No** |
| Export downloads of PII-heavy full lists | **No** by default (optional limited PDF later) |
| Settings / Logs / Import / Events | **No** |

#### Suggested SEO Dashboard widgets

1. **Event pulse (header)**  
   Active event name, date, last refresh time.

2. **KPI strip**  
   - Signed in (present)  
   - In vicinity (not signed, not absent)  
   - Explicitly absent  
   - In-vicinity rate %  
   - Last-hour sign-ins  
   - Peak hour  

3. **VIP watchlist (primary ask)**  
   Table/cards of VIP guests with status badge:
   - `Present` — signed in  
   - `In Vicinity` — registered, assumed on site  
   - `Absent` — admin/checker marked absent  
   - `Not registered` — optional if invite list exists later  
   Filters: all VIPs / still expected / already present.

4. **Priority agency roll-up**  
   Counts by agency for VIP or all guests (Present / In Vicinity / Absent).

5. **Recent VIP activity feed**  
   Last N VIP check-ins (name, agency, time) — read-only.

6. **Attention list**  
   VIPs still `In Vicinity` after a configurable grace window (e.g. 30+ minutes past event start) without signature — helps protocol team follow up.

7. **Non-PII summary**  
   Prefer designation/agency over email/contact on this screen unless admin enables “show contact”.

#### VIP identification strategy (required data work)

Participants today have `designation`, `sector`, `agency` — **no VIP flag**.

**Recommended (Phase 1):**
- Add `participants.is_vip TINYINT(1) NOT NULL DEFAULT 0`
- Optional: `participants.vip_tier ENUM('vip','vvip','protocol','guest') NULL` for sorting

**Alternative (no schema):** treat designation keywords (`Secretary`, `Undersecretary`, `Director`, `Mayor`, etc.) as VIP — fragile; use only as temporary heuristic.

**Admin UX:** checkbox “VIP / Priority guest” on registrant edit (or bulk mark) — admin-only.

---

## 4. Route → Role Mapping

Legend: **A** = admin, **C** = checker, **S** = seo_viewer, **P** = public, **Staff** = scan staff session

| Route | A | C | S | Notes |
|-------|---|---|---|-------|
| `register`, `register_submit`, `register_success` | P+A+C | P+C | P | Public guest form; logged-in staff may use too |
| `scan` | A+C | C | — | Door scan UI |
| `api_participant` | A+C+Staff | C | — | Needed by scan |
| `attendance_submit` | Staff/A/C | C | — | Keep staff session; allow checker/admin |
| `admin_registrants`, `admin_generate_qr`, `admin_registrant_email`, `admin_qr` | ✓ | ✓ | — | |
| `admin_attendance`, `admin_attendance_kpi`, `admin_attendance_search` | ✓ | ✓ | KPI **read** only for S via dedicated endpoints |
| `admin_attendance_manual`, `mark_absent`, `clear_absent`, signatures | ✓ | ✓ | — | Writes denied for S |
| `admin_attendance_gallery` | ✓ | — | — | |
| `admin_events*` | ✓ | — | — | |
| `admin_import*` | ✓ | — | — | |
| `admin_export`, `export_*`, `sample_csv` | ✓ | — | — | |
| `admin_report*` | ✓ | — | — | |
| `admin_logs` | ✓ | — | — | |
| `admin_settings*` | ✓ | — | — | |
| `admin_seo_dashboard` (new) | ✓ | — | ✓ | S home |
| `admin_seo_vip_json` (new) | ✓ | — | ✓ | Read-only API |
| `admin_users*` (new) | ✓ | — | — | User/role CRUD |
| `admin_login` / `admin_logout` | all roles | | | Shared login |

### Guard evolution
Replace boolean `admin` with role-aware guards, e.g.:

```php
'_guards' => ['auth', 'role:admin'],
'_guards' => ['auth', 'role:admin|checker'],
'_guards' => ['auth', 'role:admin|seo_viewer'],
'_guards' => ['auth', 'role:admin|checker|seo_viewer'],
```

Keep legacy `admin` guard as alias for `auth + any logged-in account` **only during migration**, then remove.

---

## 5. Data Model Changes

### 5.1 Migration `007_rbac_roles.sql` (proposed)

```sql
ALTER TABLE admins
  ADD COLUMN role ENUM('admin','checker','seo_viewer') NOT NULL DEFAULT 'admin' AFTER email,
  ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role,
  ADD COLUMN last_login_at DATETIME NULL AFTER is_active,
  ADD COLUMN display_name VARCHAR(120) NULL AFTER username;

-- Existing accounts remain full admins
UPDATE admins SET role = 'admin' WHERE role IS NULL OR role = '';
```

### 5.2 Migration `008_participant_vip.sql` (proposed)

```sql
ALTER TABLE participants
  ADD COLUMN is_vip TINYINT(1) NOT NULL DEFAULT 0 AFTER designation,
  ADD INDEX idx_participants_vip (is_vip);
```

### 5.3 Session payload (after login)

```php
$_SESSION['admin_id'] = (int)$admin['id'];
$_SESSION['admin_role'] = $admin['role'];           // admin|checker|seo_viewer
$_SESSION['admin_username'] = $admin['username'];
$_SESSION['admin_display_name'] = $admin['display_name'] ?? $admin['username'];
```

Always re-validate `is_active` and role from DB on sensitive actions (or once per request via lightweight AuthService).

---

## 6. Application Architecture

### 6.1 New service: `App\Services\AuthService`
Centralize checks used by Router and controllers:

- `user(): ?array` — current account from session + optional DB refresh  
- `check(): bool` — logged in and active  
- `role(): ?string`  
- `hasRole(string ...$roles): bool`  
- `requireRoles(string ...$roles): void` — 403 or redirect  
- `can(string $permission): bool` — optional permission map  
- `loginHomeRoute(): string` — role-based landing page  

### 6.2 Permission map (optional clarity layer)

```text
admin.*                    → admin
registrants.read/write     → admin, checker
attendance.read            → admin, checker, seo_viewer
attendance.write           → admin, checker
seo.dashboard              → admin, seo_viewer
users.manage               → admin
settings.manage            → admin
exports.run                → admin
logs.read                  → admin
```

Start with **role checks on routes**; add named permissions only if the matrix grows.

### 6.3 Router changes (`src/Core/Router.php`)
- Parse `role:a|b` guards  
- On failure: redirect to login (GET) or JSON 403 (API)  
- Call `session_write_close()` is **not** needed for normal pages; do **not** reintroduce long-lived SSE  

### 6.4 Remove duplicate `requireAdmin()` drift
Gradually replace controller `empty($_SESSION['admin_id'])` with `AuthService` so a forgotten check cannot reopen a hole. Phase this file-by-file; keep temporary dual-check during rollout.

### 6.5 Navigation
Update `views/partials/admin_nav.php`:

```php
$adminNavLinks = [ /* each item includes 'roles' => ['admin','checker'] */ ];
// Filter by AuthService::hasRole(...)
```

SEO viewer gets a slim nav: **Dashboard | Logout** (and maybe Register as public link only).

### 6.6 Post-login redirects
| Role | Landing route |
|------|----------------|
| `admin` | `admin_registrants` (current behavior) or `admin_attendance` |
| `checker` | `admin_attendance` |
| `seo_viewer` | `admin_seo_dashboard` |

---

## 7. Security Hardening (paired with RBAC)

Implement carefully; prefer config flags so prod can tighten without breaking local WAMP.

### 7.1 Authentication
| Control | Spec |
|---------|------|
| Role + `is_active` on login | Reject inactive accounts |
| Lockout (already) | Keep 5 fails / 15 min; log role attempts |
| Generic login errors | Do not reveal whether username exists |
| Password policy (new users) | Min 10 chars; block known weak defaults in prod |
| Force password change flag (optional) | `must_change_password` column — Phase 2 |
| Session regenerate | `session_regenerate_id(true)` on successful login |
| Idle timeout | Absolute + idle (e.g. 8h absolute, 60–120 min idle) via `last_activity` in session |

### 7.2 Authorization
| Control | Spec |
|---------|------|
| Deny by default | Unknown routes / missing guards stay public only if intentionally public |
| Server-side role checks | Every mutating admin endpoint |
| IDOR review | Signature/export/report IDs must confirm caller role |
| SEO APIs | No write methods; strip emails/phones unless permitted |

### 7.3 CSRF & sessions (extend existing)
| Control | Spec |
|---------|------|
| CSRF (existing) | Keep on all POST/JSON state changes |
| Rotate CSRF on login (existing) | Keep |
| Cookie flags (existing) | httponly, SameSite=Strict, secure when HTTPS |
| Session storage | Prefer strict mode; regenerate on privilege change |

### 7.4 Audit logging
Extend `action_logs` (or detail JSON) to include:
- `role` at time of action  
- `login_success` / `login_failed` / `logout`  
- `role_denied` when guard blocks  
- User create/update/deactivate  

### 7.5 Account management (admin-only)
- Create checker / SEO viewer accounts  
- Activate / deactivate (soft disable)  
- Reset password  
- Change role (cannot demote last remaining admin)  
- Never store plaintext passwords  

### 7.6 Hardening checklist (non-breaking defaults)
- [ ] `APP_DEBUG` false in production (already pattern)  
- [ ] Disable `diagnose.php` / `create_admin.php` in production or gate behind local-only  
- [ ] Ensure `.env` denied via `.htaccess` (already)  
- [ ] Rate limit login + attendance submit (already)  
- [ ] Do not re-enable infinite SSE streams  

---

## 8. UI / UX Specs

### 8.1 Login page
- Same form for all roles  
- Optional subtitle: “Staff & Admin Portal”  
- After login, redirect by role (Section 6.6)  
- Show role badge in nav: `Admin` / `Checker` / `SEO Viewer`

### 8.2 Checker dashboard experience
- Nav: Register | Registrants | Attendance | Scan | Logout  
- Attendance page unchanged functionally (write allowed)  
- Hide Import/Export/Settings/etc.

### 8.3 SEO Viewer dashboard (new view)
- Route: `?r=admin_seo_dashboard`  
- View: `views/admin_seo_dashboard.php`  
- Assets: optional `assets/seo-dashboard.css` (scoped)  
- Auto-refresh KPIs every 15–30s via existing `admin_attendance_kpi` **or** dedicated `admin_seo_summary` that includes VIP counts  
- **No** mark attendance buttons  
- VIP table with status chips matching attendance roster semantics (`Present` / `In Vicinity` / `Absent`)

### 8.4 Forbidden access UX
- HTML: friendly “You don’t have access to this area” + link to role home  
- JSON APIs: `{ "error": "forbidden", "code": 403 }`  

---

## 9. Implementation Plan (safe rollout)

### Phase 0 — Spec & backups
- This document  
- DB backup before migrations  

### Phase 1 — Schema (non-breaking)
1. Add `admins.role`, `is_active`, `last_login_at`  
2. Default all existing users to `admin`  
3. Add `participants.is_vip`  
4. Run via existing `Database::migrate()` auto path  

**Acceptance:** App behaves exactly as today for current admins.

### Phase 2 — AuthService + Router guards
1. Add `AuthService`  
2. Store `admin_role` in session on login  
3. Implement `role:*` guards  
4. Map routes per matrix  
5. Filter `admin_nav.php`  

**Acceptance:** Creating a test `checker` account cannot open Settings/Logs; admin still can.

### Phase 3 — Role landing + SEO dashboard (read-only)
1. Login redirects by role  
2. Build SEO dashboard + VIP list API  
3. Admin can toggle `is_vip` on registrants  

**Acceptance:** SEO viewer sees VIP vicinity/present/absent; cannot POST attendance mutations.

### Phase 4 — User management UI (admin-only)
1. List accounts  
2. Create / deactivate / reset password / set role  
3. Protect last admin  

### Phase 5 — Security polish
1. Session regenerate on login  
2. Idle timeout  
3. Richer audit events  
4. Production gate for diagnostic scripts  
5. Tests for each role’s allow/deny matrix  

---

## 10. Testing Plan

### Automated (extend `scripts/`)
- Login as each role; assert session role  
- HTTP allow/deny matrix for representative routes  
- Checker denied `admin_settings`  
- SEO denied `admin_attendance_manual`  
- SEO allowed `admin_seo_dashboard` + VIP JSON  
- Inactive user cannot login  
- Existing admin regression: full access  

### Manual QA
| Actor | Test |
|-------|------|
| Admin | Full nav; create checker + SEO users; mark VIP |
| Checker | Only Register/Registrants/Attendance/Scan; mark present/absent |
| SEO Viewer | Dashboard KPIs + VIP statuses; no write controls |
| Guest | Public register still works without login |

### Regression must-pass
- Guest registration + QR success  
- Scan & sign attendance  
- Attendance KPI cards / roster statuses  
- CSRF on POSTs  
- Rate limiter still functions  

---

## 11. Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Existing admins locked out | Default role `admin`; migration UPDATE all rows |
| Missed route without new guard | Inventory `routes.php`; deny-by-default for new admin routes |
| SEO sees too much PII | VIP payload whitelist fields; hide contact by default |
| Checker over-reach via direct URL | Server guards, not only hidden nav |
| Session hang regression | Do not restore long-lived SSE |
| VIP heuristic false positives | Prefer explicit `is_vip` flag |

---

## 12. Out of Scope (this RBAC track)

- OAuth / SSO / LDAP  
- Fine-grained per-agency tenancy  
- Mobile app auth  
- Replacing password auth with passkeys (future)  
- Public VIP display screens without login  

---

## 13. Proposed Files (implementation later)

```
migrations/007_rbac_roles.sql
migrations/008_participant_vip.sql
src/Services/AuthService.php
src/Controllers/AdminUsersController.php      (Phase 4)
src/Controllers/AdminSeoController.php        (Phase 3)
views/admin_seo_dashboard.php
views/admin_users.php
views/partials/admin_nav.php                  (filter by role)
src/Core/Router.php                           (role guards)
src/Controllers/AuthController.php            (session role + landing)
config/routes.php                             (guard updates)
scripts/seed_role_users.php                   (dev helpers)
scripts/test_rbac_matrix.php
```

---

## 14. Acceptance Criteria (definition of done)

- [x] Three roles exist in DB and session  
- [x] Existing admins retain full access after migration  
- [x] Checker nav + routes limited to Register, Registrants, Attendance (+ Scan)  
- [x] SEO Viewer has read-only dashboard with VIP Present / In Vicinity / Absent  
- [x] SEO Viewer cannot mutate attendance or open settings/import/export/logs  
- [x] Admin can manage users and VIP flags  
- [x] Unauthorized access returns 403 / safe redirect  
- [x] Security extras: session regenerate on login, inactive users blocked, audit on denials  
- [x] Guest registration and scan flows unchanged for the public  

---

## 15. Implementation Status

| Item | Status |
|------|--------|
| Spec document | Done |
| Schema migrations | Done (`007`, `008`, auto via `Database::migrate`) |
| AuthService + Router role guards | Done |
| Nav filtering | Done |
| Checker enforcement | Done |
| SEO dashboard + VIP | Done |
| User management UI | Done |
| Session/idle hardening | Done (regenerate + idle/absolute timeout) |
| RBAC automated tests | Done (`scripts/test_rbac_matrix.php`) |

---

*Document created: 2026-07-12*  
*Related: `TODOMORE/future_improvements_spec.md`, `TODOUI/guest_registration_ui_spec.md`*  
*Primary goal: least-privilege staff roles + executive visibility without breaking current admin operations*
