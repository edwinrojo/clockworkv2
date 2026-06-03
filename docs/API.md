# Clockwork Mobile API (v1)

Base URL: `{APP_URL}/api/v1`

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
  "device_name": "optional-device-label"
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
