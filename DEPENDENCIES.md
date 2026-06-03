# Clockwork — Dependencies

This document describes **direct** project dependencies declared in `composer.json` and `package.json`, and how each supports the Clockwork attendance system (admin web app, mobile API backend, QR display, geofencing).

Indirect packages (pulled in by Laravel, Symfony, etc.) are not listed here; see `composer.lock` and `package-lock.json` for the full tree.

---

## PHP runtime

| Requirement | Purpose |
|-------------|---------|
| **PHP ^8.3** | Application runtime. Clockwork uses modern PHP features (enums, constructor promotion, typed properties) across services, API, and admin. |

---

## PHP — production (`composer.json` → `require`)

| Package | Version constraint | Purpose in Clockwork |
|---------|-------------------|-------------------|
| **laravel/framework** | ^13.7 | Core application: routing, Eloquent ORM, migrations, queues, scheduler, validation, caching, and HTTP layer for admin + API. |
| **inertiajs/inertia-laravel** | ^3.0 | Bridges Laravel controllers to the Vue admin SPA (events, venues, users, live ops, attendances) without a separate REST API for the dashboard. |
| **laravel/fortify** | ^1.37.2 | Authentication backend: login, registration, password reset, email verification, two-factor authentication, and passkey support for admin users. |
| **laravel/sanctum** | ^4.3 | Bearer token authentication for the **mobile API** (`/api/v1/*`) used by the Flutter employee app. |
| **laravel/wayfinder** | ^0.1.14 | Generates type-safe TypeScript route/action helpers (`@/routes/*`, `@/actions/*`) so the Vue admin calls Laravel endpoints without hardcoded URLs. |
| **laravel/tinker** | ^3.0 | REPL for local debugging and ad-hoc inspection of models (users, events, tokens) in development. |
| **laravel/chisel** | ^0.1.0 | Laravel starter-kit tooling (project scaffolding conventions). |

### Transitive highlights (not in `composer.json`, installed via Composer)

| Package | Purpose in Clockwork |
|---------|-------------------|
| **laravel/passkeys** | WebAuthn passkey registration and login for admin accounts (via Fortify). |
| **laravel/prompts** | CLI prompts used by Artisan commands. |

---

## PHP — development (`composer.json` → `require-dev`)

| Package | Version constraint | Purpose in Clockwork |
|---------|-------------------|-------------------|
| **phpunit/phpunit** | ^12.5.23 | Automated tests: admin CRUD, mobile API, sessions, QR display, geofence validation, auth. |
| **laravel/pint** | ^1.27 | PHP code style formatter (Laravel preset). |
| **laravel/sail** | ^1.53 | Docker-based local development environment (optional). |
| **laravel/pail** | ^1.2.5 | Tails application logs in the terminal during `composer run dev`. |
| **laravel/boost** | ^2.4 | MCP tooling and Laravel-specific AI/agent helpers for this repo. |
| **laravel/pao** | ^1.0.6 | Performance / analysis tooling for Laravel (dev-only). |
| **fakerphp/faker** | ^1.24 | Generates fake data for factories (users, events, venues, attendances) in tests and seeders. |
| **mockery/mockery** | ^1.6 | Test doubles for mocking collaborators in unit tests. |
| **nunomaduro/collision** | ^8.9.3 | Readable error output when running Artisan and PHPUnit in the terminal. |

---

## JavaScript / TypeScript — production (`package.json` → `dependencies`)

| Package | Version constraint | Purpose in Clockwork |
|---------|-------------------|-------------------|
| **vue** | ^3.5.13 | Admin UI framework (pages, forms, live dashboard, QR display page). |
| **@inertiajs/vue3** | ^3.0.0 | Vue adapter for Inertia: SPA navigation, forms, shared props, polling on live ops. |
| **@inertiajs/vite** | ^3.0.0 | Vite integration for Inertia v3 (dev/build pipeline). |
| **laravel-vite-plugin** | ^3.0.0 | Connects Vite to Laravel (`@vite` in Blade, asset hashing, HMR). |
| **@laravel/passkeys** | ^0.2.0 | Client-side WebAuthn helpers for admin passkey registration and login. |
| **leaflet** | ^1.9.4 | Interactive maps on venue create/edit for pin placement, radius geofence, and polygon drawing (OpenStreetMap tiles). |
| **qrcode** | ^1.5.4 | Renders the rotating check-in QR code on the public **display** page (`/display/{secret}`). |
| **tailwindcss** | ^4.1.1 | Utility-first CSS for admin and display UIs. |
| **tw-animate-css** | ^1.2.5 | Animation utilities used with Tailwind. |
| **reka-ui** | ^2.9.8 | Accessible headless UI primitives (dialogs, dropdowns, etc.) for admin components. |
| **@lucide/vue** | ^1.17.0 | Icon set for navigation and actions in the admin UI. |
| **@vueuse/core** | ^12.8.2 | Vue composition utilities (reactivity helpers, browser APIs). |
| **class-variance-authority** | ^0.7.1 | Variant-based styling for shared UI components (buttons, badges). |
| **clsx** | ^2.1.1 | Conditional CSS class names. |
| **tailwind-merge** | ^3.2.0 | Merges Tailwind classes without conflicts in UI components. |
| **vue-sonner** | ^2.0.0 | Toast notifications for admin success/error feedback. |
| **vue-input-otp** | ^0.3.2 | OTP input UI for two-factor authentication screens. |

---

## JavaScript / TypeScript — development (`package.json` → `devDependencies`)

| Package | Version constraint | Purpose in Clockwork |
|---------|-------------------|-------------------|
| **vite** | ^8.0.0 | Frontend dev server, bundling, and production builds. |
| **@vitejs/plugin-vue** | ^6.0.0 | Vue SFC support in Vite. |
| **@laravel/vite-plugin-wayfinder** | ^0.1.3 | Generates Wayfinder TypeScript routes/actions during Vite builds. |
| **@tailwindcss/vite** | ^4.1.11 | Tailwind v4 integration for Vite. |
| **typescript** | ^5.2.2 | Static typing for Vue pages and shared types (`resources/js/types`). |
| **vue-tsc** | ^2.2.4 | Type-checking Vue SFCs (`npm run types:check`). |
| **@types/node** | ^22.13.5 | TypeScript definitions for Node (Vite config). |
| **@types/leaflet** | ^1.9.21 | TypeScript definitions for Leaflet map component. |
| **@types/qrcode** | ^1.5.6 | TypeScript definitions for QR code generation on display page. |
| **eslint** | ^9.17.0 | Linting for Vue/TypeScript source. |
| **@eslint/js** | ^9.19.0 | ESLint flat config base rules. |
| **typescript-eslint** | ^8.23.0 | TypeScript-aware ESLint rules. |
| **@vue/eslint-config-typescript** | ^14.3.0 | Vue + TypeScript ESLint presets. |
| **eslint-plugin-vue** | ^9.32.0 | Vue-specific lint rules. |
| **eslint-plugin-import** | ^2.32.0 | Import ordering and resolution linting. |
| **eslint-import-resolver-typescript** | ^4.4.4 | Resolves `@/` path aliases for ESLint import plugin. |
| **@stylistic/eslint-plugin** | ^5.10.0 | Code style rules for ESLint. |
| **eslint-config-prettier** | ^10.0.1 | Disables ESLint rules that conflict with Prettier. |
| **prettier** | ^3.4.2 | Formats Vue, TypeScript, and JSON in `resources/`. |
| **prettier-plugin-tailwindcss** | ^0.6.11 | Sorts Tailwind classes in Prettier. |
| **concurrently** | ^9.0.1 | Runs PHP server, queue, logs, and Vite together via `composer run dev`. |

---

## Optional npm platform binaries (`package.json` → `optionalDependencies`)

Prebuilt native binaries for **Linux** and **Windows** so `npm install` works on those platforms without compiling Tailwind/Rollup locally. Not used at runtime in the browser; build tooling only.

| Package | Purpose |
|---------|---------|
| **@rollup/rollup-linux-x64-gnu**, **@rollup/rollup-win32-x64-msvc** | Rollup bundler binaries for Vite on specific OS/architectures. |
| **@tailwindcss/oxide-linux-x64-gnu**, **@tailwindcss/oxide-win32-x64-msvc** | Tailwind v4 Oxide engine binaries. |
| **lightningcss-linux-x64-gnu**, **lightningcss-win32-x64-msvc** | CSS transformer binaries used by the toolchain. |

---

## How stacks map to Clockwork features

| Feature area | Primary dependencies |
|--------------|-------------------|
| Admin dashboard (Inertia + Vue) | `laravel/framework`, `inertiajs/inertia-laravel`, `vue`, `@inertiajs/vue3`, `tailwindcss`, `reka-ui` |
| Admin authentication (2FA, passkeys) | `laravel/fortify`, `@laravel/passkeys`, `vue-input-otp` |
| Mobile employee API | `laravel/sanctum`, `laravel/framework` |
| Event sessions & QR rotation | `laravel/framework` (scheduler, cache, Eloquent), `qrcode` (display) |
| Geofencing (admin map) | `leaflet`, venue storage in Laravel |
| Type-safe admin routes | `laravel/wayfinder`, `@laravel/vite-plugin-wayfinder` |
| Quality & CI | `phpunit/phpunit`, `laravel/pint`, `eslint`, `prettier`, `vue-tsc` |

---

## Updating this file

When adding or removing a direct dependency, update the matching section above and run:

```bash
composer update   # after composer.json changes
npm install       # after package.json changes
```

*Last updated: 2026-06-03*
