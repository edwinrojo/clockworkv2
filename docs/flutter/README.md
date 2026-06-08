# Clockwork Flutter — Device Binding

Integration guide for **one employee, one device** with admin approval on device changes.

The Laravel backend is implemented. Use these phases in order in the Flutter repo.

---

## Phases

| Phase | Doc | Summary |
|-------|-----|---------|
| **1** | [phase-1-device-identity.md](phase-1-device-identity.md) | Persistent UUID + device metadata service |
| **2** | [phase-2-login-api.md](phase-2-login-api.md) | Send `device_id` on login; parse error codes |
| **3** | [phase-3-device-change-ui.md](phase-3-device-change-ui.md) | Required / pending screens and login routing |
| **4** | [phase-4-device-binding-hardening.md](phase-4-device-binding-hardening.md) | Edge cases, polish, tests, docs — **final phase** |

---

## Quick reference

### Login (required fields)

```
POST /api/v1/auth/login
```

- `device_id` — **required** (UUID from secure storage)
- `device_name`, `device_model`, `platform`, `os_version`, `reason` — optional

### Device error codes

| Code | Meaning |
|------|---------|
| `DEVICE_CHANGE_REQUIRED` | New phone; admin must approve |
| `DEVICE_CHANGE_PENDING` | Request submitted; waiting for admin |

### Admin approval (web only)

Laravel admin: **Device requests** (`/device-change-requests`) or **Users → Edit**.

### Golden rules

1. Generate `device_id` once; store in `flutter_secure_storage`
2. Never clear `device_id` on logout — only the auth token
3. App reinstall = new device = approval required

---

## Related backend docs

- [API.md](../API.md) — full mobile API (update pending for `device_id` requirement)
- [PROJECT_CONTEXT.md](../../PROJECT_CONTEXT.md) — product context

---

## After Phase 4

Device binding is **feature-complete** on mobile. Optional later work:

- Push notification when admin approves/rejects
- Auto-poll on pending screen
- `X-Device-Id` header if backend adds per-request enforcement
