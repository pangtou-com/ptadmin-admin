<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use PTAdmin\Admin\Controllers\PublicAuthController;

Route::prefix((string) config('ptadmin.public_api_prefix', 'api'))
    ->group(function (): void {
        Route::get('captcha/challenge', [PublicAuthController::class, 'captchaChallenge'])
            ->name('ptadmin.public.captcha.challenge');
        Route::post('captcha/challenge/refresh', [PublicAuthController::class, 'captchaRefresh'])
            ->name('ptadmin.public.captcha.refresh');
        Route::post('auth/register', [PublicAuthController::class, 'register'])
            ->name('ptadmin.public.auth.register');
    });
