<?php

namespace App\Providers;

use App\Events\QuickTaskRequested;
use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\AutoUpdater;
use Native\Desktop\Facades\ChildProcess;
use Native\Desktop\Facades\GlobalShortcut;
use Native\Desktop\Facades\Menu;
use Native\Desktop\Facades\MenuBar;
use Native\Desktop\Facades\Window;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * NativePHP service provider: runs once when the app starts in native mode.
     * All desktop behavior (window, menu, tray, shortcuts) lives here.
     */
    public function boot(): void
    {
        Window::open()
            ->title('NativePHP Tasks')
            ->width(960)
            ->height(680);

        Menu::create(
            Menu::file(),
            Menu::edit(),
            Menu::view(),
            Menu::window(),
            Menu::make(
                Menu::route('tasks.index', 'All Tasks'),
                Menu::route('tasks.create', 'New Task'),
                Menu::link('https://nativephp.com/docs', 'NativePHP Docs')
                    ->openInBrowser(),
            )->label('Tasks'),
        );

        MenuBar::create()
            ->route('tasks.index')
            ->withContextMenu(
                Menu::make(
                    Menu::label('NativePHP Tasks'),
                    Menu::separator(),
                    Menu::route('tasks.index', 'Show App'),
                    Menu::separator(),
                    Menu::quit(),
                )
            );

        // Global shortcut: Ctrl+Alt+T → QuickTaskRequested (handled in AppServiceProvider).
        GlobalShortcut::key('CmdOrCtrl+Alt+T')
            ->event(QuickTaskRequested::class)
            ->register();

        // Background live feed. The DB path is passed explicitly because packaged
        // apps move the NativePHP database into the user-data folder.
        ChildProcess::php(
            [base_path('scripts/live-feed.php'), (string) config('database.connections.'.config('database.default').'.database')],
            alias: 'feed',
        );

        // Auto-update: only checks when app-update.yml exists (packaged builds).
        if (file_exists(dirname(base_path(), 2).'/app-update.yml')) {
            AutoUpdater::checkForUpdates();
        }
    }

    /**
     * php.ini directives for the bundled PHP runtime.
     *
     * @return array<string, mixed>
     */
    public function phpIni(): array
    {
        return [
        ];
    }
}
