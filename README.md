# NativePHP Tasks — Desktop Task Manager ✅

A deliberately small **task manager** that turns a normal Laravel app into a real Windows desktop program with **NativePHP**. About 95% of the code is plain Laravel; the rest is desktop-only APIs (window, menu, notification, persistent settings…).

> **One-line summary:** NativePHP is the layer that wraps your Laravel app in Electron as a real desktop window.

**Status:** Productized — the old `Note` domain is now `Task`, with **status / priority / due date**. All NativePHP desktop features work.

---

## 1. Setup & Run

Prerequisites: PHP 8.3+, Composer, Node.js 18+ (for Electron).

```bash
composer install            # PHP dependencies
php artisan native:install  # Electron/Node deps + PHP runtime
php artisan native:migrate  # NativePHP DB (notes → tasks + new fields)

php artisan native:run      # 🚀 Opens the desktop window
```

`native:run` is not `php artisan serve`: it runs Laravel inside the Electron window. Closing the window quits the app.

To build a packaged installer:

```bash
php artisan native:build    # Windows installer (.exe)
```

---

## 2. Directory Map

```
app/
├── Enums/
│   ├── TaskStatus.php                 ← Pending | In progress | Done
│   └── TaskPriority.php               ← Low | Medium | High
├── Http/Controllers/
│   ├── TaskController.php             ← CRUD + Settings/Notification/Clipboard/Dialog
│   └── UpdaterController.php          ← auto-update check / install
├── Models/Task.php                    ← Eloquent (status, priority, due_date casts)
├── Events/
│   ├── QuickTaskRequested.php         ← Ctrl+Alt+T global shortcut event
│   └── TasksChanged.php               ← PHP → webview live event
└── Providers/
    ├── NativeAppServiceProvider.php   ← window + menu + tray + shortcut + child process
    └── AppServiceProvider.php         ← QuickTaskRequested listener (mini window)
database/migrations/..._convert_notes_to_tasks.php
resources/views/
├── layouts/app.blade.php              ← single Blade layout (plain CSS)
└── tasks/                             ← index / create / edit / _form / _list
routes/web.php                         ← tasks resource + fragment/copy/export/clearFeed
scripts/live-feed.php                  ← background live feed (BTC + USD/TRY)
scripts/release-server.cjs             ← demo auto-update release server
config/nativephp.php                   ← NativePHP settings
```

**Golden rule:** `AppServiceProvider` → general app bootstrap. `NativeAppServiceProvider` → runs **once** on native app start. Define windows/menus there.

---

## 3. Task Model

| Field | Type | Notes |
|---|---|---|
| `title` | string (required) | Max 100 chars |
| `body` | text (optional) | Max 5000 chars |
| `status` | enum | `pending` · `in_progress` · `done` |
| `priority` | enum | `low` · `medium` · `high` |
| `due_date` | date (optional) | Due date |

Status and priority come from `App\Enums\TaskStatus` / `App\Enums\TaskPriority`. The list shows badges and the due date when set. The filter bar can narrow by **status**, **priority**, and **overdue only** (due date in the past and not done). Live list refresh keeps the current filters.

---

## 4. Native Concepts

| Concept | Where | What it does |
|---|---|---|
| **Window** | `NativeAppServiceProvider::boot()` | Main window (960×680) |
| **Menu** | `NativeAppServiceProvider::boot()` | App menu bar; `Menu::route()` binds named routes |
| **Notification** | `TaskController` | Real desktop notifications |
| **Settings** | `TaskController@index/edit` | Remembers the last opened task |
| **MenuBar (tray)** | `NativeAppServiceProvider::boot()` | System tray + context menu |
| **GlobalShortcut** | `NativeAppServiceProvider` + `AppServiceProvider` | **Ctrl+Alt+T** → Quick Task window |
| **Clipboard** | `TaskController@copy` | Copy task text to the clipboard |
| **Dialog** | `TaskController@export` | Native save dialog; PHP writes the file |
| **ChildProcess** | `NativeAppServiceProvider` | `scripts/live-feed.php` background process |
| **AutoUpdater** | `NativeAppServiceProvider` + `UpdaterController` | Self-update in packaged builds |

**Native ↔ PHP bridge:** Ctrl+Alt+T → `QuickTaskRequested` → listener in `AppServiceProvider` → `Window::open()->id('quicktask')`. The `id` focuses an existing window instead of opening a copy.

**Live data (PHP → webview):** `event(new TasksChanged(...))` (`ShouldBroadcastNow`, `nativephp` channel) → NativePHP delivers to open windows → JS `Native.on('App\\Events\\TasksChanged', ...)` refreshes `tasks/fragment`.

**Live feed:** `scripts/live-feed.php` polls Coinbase BTC spot + USD/TRY. On price change it inserts a 📡-prefixed row into `tasks` and writes to STDOUT. The DB path is passed via `argv[1]` / `NATIVEPHP_DB_PATH` (user-data path in packaged apps). **🧹 clear live feed** deletes all 📡 tasks.

**Quick task:** Ctrl+Alt+T works anywhere and opens a 420×560 always-on-top mini window. Saving with `quick=1` keeps the form open; the main list updates via `TasksChanged`.

---

## 5. Where Is the Data?

- **Development** (`native:run`): `database/nativephp.sqlite`. Independent of `.env`'s `database.sqlite`; `native:run` migrates this file.
- **Packaged app:** user app-data folder (Windows: `%APPDATA%`). `Settings` live there too.
- **Old `notes` data:** the `convert_notes_to_tasks` migration renames the table and adds `status` / `priority` / `due_date`. If the app was used before, run `php artisan native:migrate` against `nativephp.sqlite` (`php artisan migrate` only touches `database.sqlite`).

---

## 6. Tests

```bash
php artisan test
```

`TaskControllerTest` covers CRUD, validation, filters, fragment, clearFeed, copy, and export. Native desktop APIs (Settings/Notification/Clipboard/Dialog) talk to Electron over HTTP, so tests stub that bridge with `Http::fake`.

---

## 7. Known Warnings

- Laragon `php_imagick.dll` / `php_memcached.dll` warnings are harmless.
- First `native:run` may download Electron and the PHP runtime.
- Windows notifications may be hidden if notifications are turned off.
- **nativephp/desktop 2.3.0 packaging bug:** `pdfPageSize.js` may be missing from the published build. If `native:run` fails with "Could not resolve ../pdfPageSize.js":
  ```bash
  cd vendor/nativephp/desktop/resources/electron/electron-plugin
  npx esbuild src/server/pdfPageSize.ts --format=esm --target=es2022 --outfile=dist/server/pdfPageSize.js
  ```
- **Port clash:** the installed app and `native:run` both want 127.0.0.1:8100.

---

## 8. Auto-Update

Flow: `AutoUpdater::checkForUpdates()` → reads `latest.yml` → new version downloads automatically → `UpdateDownloaded` → bar in the webview → **"Restart and install"** → `AutoUpdater::quitAndInstall()`.

Demo releases are served from a local HTTP server:

```bash
node scripts/release-server.cjs storage/app/releases
```

In production use a github/s3/spaces provider + `native:build --publish` (no hand-written `latest.yml` / `app-update.yml`).

**Ship a new version (this demo layout):**
1. Bump `NATIVEPHP_APP_VERSION` in `.env` → `php artisan native:build`
2. Copy `setup.exe` + `.blockmap` into `storage/app/releases/`; update `latest.yml`
3. After install, rewrite `resources/app-update.yml` (NSIS resets the resources folder)

---

## 9. Packaging

```bash
php artisan native:build
```

Output: `nativephp/electron/dist/<AppName>-<version>-setup.exe` (~500MB — Chromium + PHP binary + Laravel app). Unsigned builds show a SmartScreen warning; production needs code signing.

---

## 10. Next Steps

- Real distribution: `native:build --publish` + github/s3/spaces + code signing
- Window events: `WindowFocused` / `WindowClosed` / `WindowBlurred`
- Full packaged-path support in `scripts/live-feed.php` (currently passed via argv)

Docs: <https://nativephp.com/docs>
