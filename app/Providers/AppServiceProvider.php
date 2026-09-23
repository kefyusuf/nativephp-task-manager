<?php

namespace App\Providers;

use App\Events\QuickTaskRequested;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Native\Desktop\Facades\Window;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Global shortcut bridge: Ctrl+Alt+T → QuickTaskRequested → mini window.
        // id='quicktask' reuses an existing window instead of opening a copy.
        Event::listen(QuickTaskRequested::class, function () {
            Window::open()
                ->id('quicktask')
                ->title('Quick Task')
                ->width(420)
                ->height(560)
                ->url(route('tasks.create', ['quick' => 1]))
                ->hideMenu()
                ->resizable(false)
                ->alwaysOnTop();
        });
    }
}
