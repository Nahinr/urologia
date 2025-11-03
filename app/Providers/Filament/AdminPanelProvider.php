<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Filament\Pages\Auth\Login as CustomLogin;
use App\Filament\Widgets\CalendarWidget;
use Filament\Http\Middleware\AuthenticateSession;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\PrescriptionPdfController;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->homeUrl(fn () => route('filament.admin.pages.calendario'))
            ->favicon(fn () => asset('images/favicon.png'.'?v=2'))
            ->brandLogo(asset('images/logo-app.png')) 
            ->brandLogoHeight('60px')                   
            ->brandName('Oftalmo-App')
            ->login(CustomLogin::class)
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->pages([
                \App\Filament\Pages\Clinic\Expedientes::class,
            ])
            ->routes(function () {
                Route::middleware(['signed','auth'])->group(function () {
                    Route::get('/attachments/{attachment}/view', [AttachmentController::class, 'inline'])
                        ->name('attachments.view');

                    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])
                        ->name('attachments.download');

                    Route::get('/prescriptions/{prescription}/pdf', [PrescriptionPdfController::class, 'show'])
                         ->middleware(['can:print,prescription'])
                         ->name('prescriptions.pdf');
                });
            })            
            ->plugin(
                FilamentFullCalendarPlugin::make()
                    ->locale('es')                       
                    ->timezone('America/Tegucigalpa')    
                    ->plugins(['dayGrid', 'timeGrid', 'list'])
                    ->selectable()
                    ->editable()
            )
            ->widgets([


            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook('panels::head.end', fn () => view('filament.custom-styles'));


    }
}
