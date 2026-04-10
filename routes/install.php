<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use PTAdmin\Admin\Controllers\InstallController;

Route::prefix('install')->group(function (): void {
    Route::get('/', [InstallController::class, 'welcome'])->name('ptadmin.install.welcome');
    Route::get('/requirements', [InstallController::class, 'requirements'])->name('ptadmin.install.requirements');
    Route::match(['get', 'post'], '/env', [InstallController::class, 'environment'])->name('ptadmin.install.environment');
    Route::post('/stream', [InstallController::class, 'stream'])->name('ptadmin.install.stream');
});
