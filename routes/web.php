<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use PTAdmin\Admin\Controllers\FrontendController;

Route::prefix(admin_web_prefix())->group(function (): void {
    Route::get('', [FrontendController::class, 'index'])->name('ptadmin.web.index');
});
