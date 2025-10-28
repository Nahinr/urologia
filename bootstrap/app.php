<?php

use App\Console\Commands\SyncGoogleCalendarEvents;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withCommands([
        SyncGoogleCalendarEvents::class,
    ])
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('google-calendar:sync-updates')->everyFiveMinutes();
    })
    ->create();
