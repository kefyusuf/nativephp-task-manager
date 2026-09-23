<?php

use App\Http\Controllers\TaskController;
use App\Http\Controllers\UpdaterController;
use Illuminate\Support\Facades\Route;

// Standard resource routing works the same in a desktop app.
// Custom routes are registered before the resource to avoid conflicts.
Route::get('tasks/fragment', [TaskController::class, 'fragment'])->name('tasks.fragment');
Route::get('tasks/{task}/copy', [TaskController::class, 'copy'])->name('tasks.copy');
Route::get('tasks/{task}/export', [TaskController::class, 'export'])->name('tasks.export');
Route::delete('tasks/feed', [TaskController::class, 'clearFeed'])->name('tasks.clearFeed');

Route::resource('tasks', TaskController::class)->except(['show']);

// Auto-update triggers
Route::post('updater/check', [UpdaterController::class, 'check'])->name('updater.check');
Route::post('updater/install', [UpdaterController::class, 'install'])->name('updater.install');

Route::get('/', fn () => redirect()->route('tasks.index'));
