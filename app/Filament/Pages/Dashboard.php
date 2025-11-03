<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    // (Opcional) cómo aparece en el menú
    // protected static ?string $navigationLabel = 'Escritorio';
    // protected static ?string $navigationIcon  = 'heroicon-o-home';
    // No definas $view ni $slug. El slug por defecto ya es 'dashboard'

    protected static bool $shouldRegisterNavigation = false;

    // Quita la franja superior de widgets
    protected function getHeaderWidgets(): array
    {
        return [];
    }

    // Quita los widgets del cuerpo
    public function getWidgets(): array
    {
        return [];
    }
}
