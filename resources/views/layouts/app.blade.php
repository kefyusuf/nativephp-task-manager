<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'NativePHP Tasks')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Teaching project: no npm/Vite build, plain CSS only. --}}
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #f4f5f7; color: #1f2430; padding: 24px;
        }
        header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 24px; padding-bottom: 16px;
            border-bottom: 2px solid #e2e4ea;
        }
        header h1 { font-size: 20px; }
        nav a {
            margin-left: 12px; color: #4f46e5; text-decoration: none; font-weight: 600;
        }
        nav a:hover { text-decoration: underline; }
        main { max-width: 760px; margin: 0 auto; }
        .card {
            background: #fff; border: 1px solid #e2e4ea; border-radius: 10px;
            padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgb(0 0 0 / 5%);
        }
        label { display: block; font-weight: 600; margin-bottom: 12px; font-size: 14px; }
        input[type="text"], input[type="date"], textarea, select {
            width: 100%; margin-top: 6px; padding: 10px;
            border: 1px solid #d3d6dd; border-radius: 6px; font: inherit;
        }
        .field-row {
            display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;
        }
        @media (max-width: 640px) {
            .field-row { grid-template-columns: 1fr; }
        }
        button {
            background: #4f46e5; color: #fff; border: 0; border-radius: 6px;
            padding: 10px 20px; font: inherit; font-weight: 600; cursor: pointer;
        }
        button:hover { background: #4338ca; }
        .hint {
            background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 8px;
            padding: 10px 14px; font-size: 13px; margin-bottom: 16px;
        }
        .error { color: #dc2626; font-size: 13px; margin: -6px 0 12px; }
        article .meta { color: #6b7280; font-size: 12px; margin: 8px 0; }
        article .actions { display: flex; gap: 12px; align-items: center; }
        article .actions a { color: #4f46e5; text-decoration: none; font-size: 14px; }
        article .actions button { background: #fee2e2; color: #b91c1c; padding: 6px 12px; font-size: 13px; }
        .task-head {
            display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;
        }
        .badges { display: flex; gap: 6px; flex-shrink: 0; }
        .badge {
            display: inline-block; padding: 2px 8px; border-radius: 999px;
            font-size: 11px; font-weight: 600; background: #e5e7eb; color: #374151;
        }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-in_progress { background: #dbeafe; color: #1e40af; }
        .badge-done { background: #dcfce7; color: #166534; }
        .badge-priority-low { background: #f3f4f6; color: #4b5563; }
        .badge-priority-medium { background: #ffedd5; color: #9a3412; }
        .badge-priority-high { background: #fee2e2; color: #b91c1c; }
        code { background: #e5e7eb; padding: 1px 5px; border-radius: 4px; font-size: 12px; }
        .empty { color: #6b7280; text-align: center; padding: 32px 0; }
        .filter-bar {
            display: grid; grid-template-columns: 1fr 1fr auto auto; gap: 12px;
            align-items: end; padding: 14px 16px;
        }
        .filter-bar label { margin-bottom: 0; }
        .filter-check {
            display: flex; align-items: center; gap: 8px; white-space: nowrap;
            padding-bottom: 10px; font-size: 13px;
        }
        .filter-check input { width: auto; margin: 0; }
        .filter-actions {
            display: flex; gap: 10px; align-items: center; padding-bottom: 2px;
        }
        .filter-actions a { color: #4f46e5; font-size: 13px; }
        @media (max-width: 720px) {
            .filter-bar { grid-template-columns: 1fr 1fr; }
        }
        #toast {
            position: fixed; left: 50%; bottom: 24px; transform: translateX(-50%) translateY(80px);
            background: #1f2430; color: #fff; padding: 10px 18px; border-radius: 8px;
            font-size: 14px; opacity: 0; transition: all .3s ease; pointer-events: none;
            box-shadow: 0 4px 16px rgb(0 0 0 / 25%);
        }
        #toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
        footer {
            max-width: 760px; margin: 24px auto 0; color: #6b7280; font-size: 12px;
            display: flex; justify-content: space-between;
        }
        footer a { color: #4f46e5; text-decoration: none; }
        #update-bar {
            position: fixed; top: 0; left: 0; right: 0; display: none;
            justify-content: space-between; align-items: center; gap: 12px;
            background: #1f2430; color: #fff; padding: 10px 20px; font-size: 14px;
        }
        #update-bar.show { display: flex; }
        #update-bar button { background: #22c55e; }
        #update-bar button:hover { background: #16a34a; }
    </style>
</head>
<body>
    <header>
        <h1>✅ NativePHP Tasks</h1>
        <nav>
            <a href="{{ route('tasks.index') }}">All Tasks</a>
            <a href="{{ route('tasks.create') }}">+ New Task</a>
        </nav>
    </header>
    <main>
        @yield('content')
    </main>

    <footer>
        <span>v{{ config('nativephp.version') }}</span>
        <a href="#" id="check-updates">🔄 check for updates</a>
    </footer>

    <div id="update-bar">
        <span>⬇ New version downloaded — ready to install</span>
        <button id="update-install">Restart and install</button>
    </div>

    <div id="toast"></div>

    <script>
        // PHP → webview live bridge. window.Native is injected only in the
        // Electron window; this block is a no-op in a normal browser.
        const wireLiveBridge = () => {
            const refresh = () => fetch('/tasks/fragment' + window.location.search)
                .then((r) => r.text())
                .then((html) => {
                    const el = document.getElementById('tasks-list');
                    if (el) el.innerHTML = html;
                });

            const toast = (msg) => {
                const t = document.getElementById('toast');
                t.textContent = msg;
                t.classList.add('show');
                setTimeout(() => t.classList.remove('show'), 3500);
            };

            Native.on('App\\Events\\TasksChanged', (payload) => {
                refresh();
                toast(payload.action === 'deleted'
                    ? '🗑 Deleted: ' + payload.title
                    : '✏️ Updated: ' + payload.title);
            });

            Native.on('Native\\Desktop\\Events\\ChildProcess\\MessageReceived', (event) => {
                if (event.alias !== 'feed') return;
                refresh();
                try { toast('📡 ' + JSON.parse(event.data).title); } catch (e) { /* bad line */ }
            });

            const AU = 'Native\\Desktop\\Events\\AutoUpdater\\';
            Native.on(AU + 'CheckingForUpdate', () => toast('🔄 Checking for updates…'));
            Native.on(AU + 'UpdateNotAvailable', () => toast('✅ App is up to date'));
            Native.on(AU + 'UpdateAvailable', (p) => toast('⬇ New version: ' + p.version + ' — downloading…'));
            Native.on(AU + 'Error', () => toast('⚠️ Update error'));
            Native.on(AU + 'UpdateDownloaded', () => {
                document.getElementById('update-bar').classList.add('show');
            });

            const post = (url) => fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            document.getElementById('check-updates').addEventListener('click', (e) => {
                e.preventDefault();
                post('/updater/check');
            });
            document.getElementById('update-install').addEventListener('click', (e) => {
                e.preventDefault();
                toast('⏳ Installing, the app will restart…');
                post('/updater/install');
            });
        };

        if (window.Native) {
            wireLiveBridge();
        } else {
            window.addEventListener('native:init', wireLiveBridge, { once: true });
        }
    </script>
</body>
</html>
