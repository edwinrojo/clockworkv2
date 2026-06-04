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

## Changelog

| Date | Notes |
|------|-------|
| 2026-06-03 | Initial v1 documentation; rate limiting added |
