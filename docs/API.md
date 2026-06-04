# Clockwork Mobile API (v1)

Base URL: `{APP_URL}/api/v1`

## Deployment assumption

The Flutter app is **one employee per device** (assigned phone, no shared login or kiosk mode on mobile). Integrators should:

- Log in once and store the Bearer token in platform secure storage.
- Send a stable `device_name` on login (e.g. `"PG-DDS-2841"` or `"Samsung A14"`) for support and Sanctum token labels.
- Not implement multi-account switching on the same installation.

Venue QR codes are shown on the **web display** (`/display/{secret}`), not inside the employee app.

---

All authenticated endpoints require:

```
Authorization: Bearer {token_id}|{plain_text_token}
Accept: application/json
Content-Type: application/json
```

## Rate limits

| Endpoint group | Limit | Key |
|----------------|-------|-----|
| `POST /auth/login` | 10 / minute | IP |
| `POST /auth/forgot-password`, `POST /auth/reset-password` | 5 / minute | IP |
| `POST /check-in` | 30 / minute | User ID |
| Other authenticated routes | 120 / minute | User ID |

When limited, the API returns `429 Too Many Requests`.

---

## Authentication

### POST `/auth/login`

Employee login only (admin roles are rejected).

**Body**

```json
{
  "email": "employee@example.com",
  "password": "password",
  "device_name": "PG-DDS-2841"
}
```

`device_name` is optional but recommended: it labels the Sanctum token (useful when revoking tokens per device). It does not bind check-ins to hardware in v1.

**Body (minimal)**

```json
{
  "email": "employee@example.com",
  "password": "password"
}
```

**Success `200`**

```json
{
  "data": {
    "token": "1|plainTextToken",
    "token_type": "Bearer",
    "user": { "id": "...", "name": "...", "email": "..." }
  }
}
```

**Errors**

- `422` — Invalid credentials
- `403` — `ACCOUNT_INACTIVE` or `UNAUTHORIZED` (non-employee)

### POST `/auth/forgot-password`

Sends a password reset email to active employees only. Always returns the same message (does not reveal whether the email exists).

**Body**

```json
{
  "email": "employee@example.com"
}
```

**Success `200`**

```json
{
  "data": {
    "message": "If that email is registered, a password reset link has been sent."
  }
}
```

The email link uses `CLOCKWORK_MOBILE_PASSWORD_RESET_URL` (default `clockwork://reset-password`) with `token` and `email` query parameters for the Flutter app.

### POST `/auth/reset-password`

Resets the password and revokes all Sanctum tokens for that employee.

**Body**

```json
{
  "email": "employee@example.com",
  "token": "from-email-link",
  "password": "new-secure-password",
  "password_confirmation": "new-secure-password"
}
```

**Success `200`**

```json
{
  "data": {
    "message": "Your password has been reset. Please sign in on your device."
  }
}
```

**Errors**

- `422` — Invalid or expired token, or non-employee account

On login, all previous API tokens for that employee are revoked (one active session per employee device).

### POST `/auth/logout`

Revokes the current access token. Requires authentication.

**Success `200`**

```json
{
  "data": { "message": "Logged out." }
}
```

---

## Profile

### GET `/profile`

**Success `200`**

```json
{
  "data": {
    "user": {
      "id": "...",
      "first_name": "...",
      "last_name": "...",
      "name": "...",
      "email": "...",
      "employee_number": "EMP-001",
      "department_id": "...",
      "is_active": true
    }
  }
}
```

---

## Events

### GET `/events`

Lists events eligible for check-in (live status, active session, within check-in window).

**Success `200`**

```json
{
  "data": {
    "events": [
      {
        "id": "...",
        "title": "...",
        "venue": { "id": "...", "name": "...", "latitude": 6.75, "longitude": 125.35 }
      }
    ]
  }
}
```

---

## Check-in

### POST `/check-in`

**Body**

```json
{
  "qr_token": "string-from-qr-display",
  "latitude": 6.75,
  "longitude": 125.35,
  "accuracy": 12.5,
  "captured_at": "2026-06-03T14:00:00+08:00",
  "idempotency_key": "optional-unique-key"
}
```

| Field | Required | Notes |
|-------|----------|-------|
| `qr_token` | Yes | Plain token from rotating venue QR |
| `latitude` | Yes | WGS84 |
| `longitude` | Yes | WGS84 |
| `accuracy` | No | GPS accuracy in meters |
| `captured_at` | No | ISO 8601 client capture time |
| `idempotency_key` | No | Replays return existing attendance |

**Success `201`** (new) / **`200`** (idempotent replay)

```json
{
  "data": {
    "attendance": {
      "id": "...",
      "event_id": "...",
      "event_title": "...",
      "venue_name": "...",
      "checked_in_at": "...",
      "status": "present",
      "source": "mobile"
    },
    "replayed": false
  }
}
```

**Status values:** `present`, `late` (after grace period from check-in open time; see `CLOCKWORK_LATE_GRACE_MINUTES`, default 15).

**Error `422`**

```json
{
  "message": "Human-readable message",
  "code": "QR_EXPIRED",
  "errors": {}
}
```

| Code | Meaning |
|------|---------|
| `QR_EXPIRED` | Token rotated or expired |
| `INVALID_QR` | Unknown token |
| `OUTSIDE_GEOFENCE` | GPS outside venue boundary |
| `ALREADY_CHECKED_IN` | Duplicate for this event |
| `EVENT_NOT_ACTIVE` | Session/window not open |
| `UNAUTHORIZED` | Not an active employee |
| `ACCOUNT_INACTIVE` | Employee account disabled |

---

## Attendance history

### GET `/attendances`

**Query:** `per_page` (max 50, default 20)

**Success `200`**

```json
{
  "data": {
    "attendances": [
      {
        "id": "...",
        "event_id": "...",
        "event_title": "...",
        "venue_name": "...",
        "checked_in_at": "...",
        "status": "present",
        "source": "mobile"
      }
    ]
  },
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 5
  }
}
```

---

## Flutter integration checklist

Use this when building the employee app in a **separate Flutter repository**. The Laravel admin app and venue QR display stay in this project.

### Backend prerequisites (before testing on a device)

- [ ] Migrations applied: `php artisan migrate`
- [ ] Sample data (optional): `php artisan db:seed`
- [ ] QR rotation running: `php artisan schedule:work` (or cron in production)
- [ ] At least one **live** event with an **active session** (admin → Events → Live ops → Start session)
- [ ] Venue has a geofence (radius or polygon) near where you will test GPS
- [ ] Venue display URL open in a browser (`/display/{secret}`) for the rotating QR — not inside Flutter

### Flutter configuration

| Item | Recommendation |
|------|----------------|
| Base URL | `{APP_URL}/api/v1` — no trailing slash |
| Config | `.env` / `--dart-define=API_BASE_URL=...` per flavor (dev, staging, prod) |
| HTTP client | `dio` or `http`; always set `Accept` and `Content-Type` to `application/json` |
| Token storage | `flutter_secure_storage` (Keychain / Keystore) — never `SharedPreferences` for the Bearer token |
| QR scanning | `mobile_scanner` or `qr_code_scanner` — scan the **plain string** embedded in the venue QR |
| Location | `geolocator` (or equivalent) — request permission before check-in |
| Deep links | `app_links` / `uni_links` for password reset (see below) |

**Local development URL notes**

| Environment | Typical `API_BASE_URL` |
|-------------|------------------------|
| iOS Simulator → Herd/Valet on Mac | `https://clockwork.m.test/api/v1` (trust local TLS) |
| Android Emulator → host machine | `http://10.0.2.2/api/v1` or your Herd proxy URL |
| Physical phone → dev machine | LAN IP or tunnel (e.g. `https://xxx.ngrok-free.app/api/v1`) — `localhost` will not work |

Ensure `APP_URL` in Laravel `.env` matches what the phone can reach.

### Authentication flow

1. **Cold start** — read token from secure storage; if present, call `GET /profile`.
2. **401 on any request** — clear token and show login (token revoked, password reset, or expired).
3. **Login** — `POST /auth/login` with `email`, `password`, and stable `device_name` (asset tag or device model).
4. **Store token** — save the full string from `data.token` (format `id|plainTextToken`). Send as:
   ```
   Authorization: Bearer {data.token}
   ```
5. **Logout** — `POST /auth/logout` then delete local token.

New login on the same account **revokes all previous tokens** on the server (one employee, one device).

### Password reset (optional v1)

1. Configure Laravel `.env`:
   ```env
   CLOCKWORK_MOBILE_PASSWORD_RESET_URL=clockwork://reset-password
   ```
2. Register the same scheme in Flutter (`AndroidManifest` intent filter, iOS URL types).
3. Email link shape: `clockwork://reset-password?token=...&email=...`
4. App screen: read query params → `POST /auth/reset-password` → redirect to login.

Mail must work in the environment where you test (`MAIL_*` in `.env`).

### Recommended build order

| Phase | Screen / feature | API |
|-------|------------------|-----|
| 1 | Login + secure token | `POST /auth/login` |
| 2 | Splash / session restore | `GET /profile` |
| 3 | Event list (empty state OK) | `GET /events` |
| 4 | QR scanner → check-in | `POST /check-in` |
| 5 | Success / error UX from `code` | — |
| 6 | Attendance history | `GET /attendances` |
| 7 | Forgot / reset password | `POST /auth/forgot-password`, `POST /auth/reset-password` |
| 8 | Logout | `POST /auth/logout` |

### Check-in request (implementation details)

- Send **current** GPS when the user taps confirm (not a cached fix from app launch).
- `qr_token` is the raw decoded QR payload (opaque string), not a URL path.
- Use `idempotency_key` (UUID per attempt) if you retry after network failure — replays return `200` with `replayed: true`.
- Handle `422` by reading `code` (not only `message`) for localized UI.

**User-facing messages for `code`**

| `code` | Suggested app action |
|--------|----------------------|
| `QR_EXPIRED` | Ask user to scan the **current** code on the display |
| `INVALID_QR` | Same — wrong or non-Clockwork QR |
| `OUTSIDE_GEOFENCE` | Show map hint / move closer to venue |
| `ALREADY_CHECKED_IN` | Show success state (already recorded) |
| `EVENT_NOT_ACTIVE` | Explain session not started or window closed |
| `ACCOUNT_INACTIVE` | Contact HR; block app use |
| `UNAUTHORIZED` | Force logout |

Login errors use `403` with `code` `UNAUTHORIZED` or `ACCOUNT_INACTIVE`.

### End-to-end test script (manual)

Use seeded accounts after `php artisan db:seed`:

| Role | Email | Password |
|------|-------|----------|
| Employee (mobile) | `employee@clockwork.test` | `password` |
| Coordinator (admin web) | `coordinator@clockwork.test` | `password` |

**Steps**

1. [ ] Coordinator: set event to **Live**, **Start session**, copy display URL.
2. [ ] Open display URL on laptop/tablet; confirm QR refreshes every N seconds.
3. [ ] Flutter: login as `employee@clockwork.test`.
4. [ ] `GET /events` returns at least one event (if empty, session/window/status is wrong).
5. [ ] Stand within venue geofence (seeded coliseum ~6.7495, 125.3557, 200 m radius).
6. [ ] Scan QR from display → `POST /check-in` → `201` / `present` or `late`.
7. [ ] Second scan → `422` / `ALREADY_CHECKED_IN`.
8. [ ] Coordinator: Live ops shows employee in recent check-ins.
9. [ ] Flutter: `GET /attendances` lists the record.
10. [ ] Optional: revoke mobile sessions on user edit → next API call returns `401`.

### Production checklist

- [ ] HTTPS only; pin or trust system CAs as per org policy
- [ ] `APP_URL` and Sanctum stateful domains configured for API host
- [ ] Scheduler/cron for `clockwork:rotate-qr-tokens` every 10 seconds
- [ ] Queue worker if using queued mail
- [ ] `CLOCKWORK_MOBILE_PASSWORD_RESET_URL` points to production app deep link
- [ ] Location permission rationale strings (App Store / Play Store)

### Related docs

- [DATABASE.md](./DATABASE.md) — tables and check-in data flow
- [PROJECT_CONTEXT.md](../PROJECT_CONTEXT.md) — product scope and mobile deployment model

---

## Changelog

| Date | Notes |
|------|-------|
| 2026-06-04 | Flutter integration checklist added |
| 2026-06-03 | Initial v1 documentation; rate limiting added |
