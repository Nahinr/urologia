<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\GoogleCalendarAuthorizationController;


Route::get('optimize', function () {
    Artisan::call('optimize:clear');
    return 'Application cache cleared';
});
Route::get('/', function () {
    return redirect()->route('filament.admin.pages.calendario');
});

Route::prefix('admin')->middleware('guest')->group(function () {
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('google-calendar/connect', [GoogleCalendarAuthorizationController::class, 'redirect'])
        ->name('google-calendar.connect');

    Route::get('google-calendar/callback', [GoogleCalendarAuthorizationController::class, 'callback'])
        ->name('google-calendar.callback');
});
