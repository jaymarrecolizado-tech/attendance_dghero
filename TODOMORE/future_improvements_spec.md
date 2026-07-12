# GovNet-Launching Attendance System - Future Improvements Spec

## 1. Executive Summary
This document outlines the architectural, security, and user experience (UX) improvements recommended to elevate the attendance system from a functional MVP to an enterprise-grade, high-availability application capable of handling high-throughput check-ins without bottlenecks.

## 2. Performance & Scalability

### 2.1 Refactor Rate Limiter (Critical)
**Problem:** The current implementation in `src/Services/RateLimiter.php` relies on a flat `ratelimit.json` file utilizing `flock()` (file locking) for concurrency. During burst traffic (e.g., event opening doors), this causes severe disk I/O bottlenecks and race conditions.
**Solution:**
- **Primary:** Implement a Redis-backed rate limiter using the `INCR` and `EXPIRE` commands.
- **Fallback:** If Redis is unavailable, migrate the rate limiter state to an optimized SQLite in-memory database or a dedicated MySQL memory table with indexing on the IP/Identifier column.

### 2.2 Database Query Optimization
**Problem:** Manual search in the admin modal and full-table scans can degrade performance as participant numbers scale.
**Solution:**
- Ensure `uuid`, `email`, and `agency` columns have dedicated `B-Tree` indexes.
- Analyze the `admin_attendance_search` query to ensure it utilizes compound indexes effectively, especially when performing `LIKE` queries.

## 3. Security Perimeter Enhancements

### 3.1 Strict Session & Cookie Handling
**Problem:** Session cookies might be vulnerable to interception or XSS if not configured strictly.
**Solution:**
- Enforce `session.cookie_httponly = 1`.
- Enforce `session.cookie_secure = 1` (requires HTTPS).
- Set `session.cookie_samesite = "Strict"`.

### 3.2 Admin Authentication Hardening
**Problem:** The admin portal is susceptible to brute-force attacks.
**Solution:**
- Implement a progressive lockout mechanism: 5 failed attempts result in a 15-minute IP/account lockout.
- Log all failed admin login attempts to a secure audit table.

### 3.3 CSRF Token Lifecycle
**Problem:** CSRF tokens need strict lifecycles to prevent replay attacks.
**Solution:**
- Rotate the CSRF token upon successful authentication and periodically during active sessions.
- Invalidate the token strictly after use on state-changing endpoints (like `register_submit`).

## 4. Premium User Experience (UX)

### 4.1 Real-Time KPI Dashboard (WebSockets / SSE)
**Problem:** The admin KPI dashboard currently polls the server every 30 seconds using `setInterval`. This is resource-intensive and delays updates.
**Solution:**
- Replace HTTP polling with **Server-Sent Events (SSE)** or WebSockets.
- The dashboard will maintain a persistent connection, allowing the server to push check-in events instantly. The UI numbers will tick up in real-time as users are scanned at the door.

### 4.2 Offline-Resilient Scanner (Progressive Web App - PWA)
**Problem:** Event venues often suffer from spotty or congested Wi-Fi. If the scanner device loses connection, the line stops.
**Solution:**
- Convert the scanner interface (`scan.php`) into a PWA using a Service Worker.
- Cache the scanner assets (HTML, CSS, JS, HTML5-QRCode library) for offline load.
- Use `IndexedDB` to queue successful QR scans when offline.
- Implement a background sync mechanism that automatically flushes the queue to the server when the internet connection is restored.

---

## 5. Implementation Status (2026-07-11)

| Item | Status | Notes |
|------|--------|-------|
| 2.1 Rate limiter refactor | Done | MySQL `rate_limits` table with row locking; file fallback via `RATE_LIMITER_DRIVER=file` |
| 2.2 DB indexes | Done | `idx_participants_agency`, `idx_attendance_date` in migration `005_performance_security.sql` |
| 3.1 Session cookies | Already done | `config/bootstrap.php` sets httponly, secure (HTTPS), SameSite Strict |
| 3.2 Admin lockout | Done | 5 failed logins → 15 min IP lockout; failures logged to `action_logs` |
| 3.3 CSRF rotation | Partial | Token rotates on admin login and successful registration; single-use invalidation not yet applied |
| 4.1 Real-time KPI (SSE) | Reverted to polling | SSE blocked PHP session locks / WAMP workers; attendance page now polls every 15s |
| 4.2 Offline scanner PWA | Pending | Not started |

### Deploy notes
- Migration `005` runs automatically on next DB connection (or run `php scripts/run_migrations.php`).
- Redis-backed rate limiting remains a future upgrade if traffic warrants it.

