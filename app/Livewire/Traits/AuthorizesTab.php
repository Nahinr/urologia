<?php

namespace App\Livewire\Traits;

use Filament\Notifications\Notification;

trait AuthorizesTab
{
    /** Define esto en cada Tab que use el trait */
    protected function requiredPermission(): ?string
    {
        return null;
    }


    protected function authorizeTab(): void
    {
        $perm = $this->requiredPermission();
        if (!$perm) {
            return;
        }

        if (! auth()->check() || ! auth()->user()->can($perm)) {
            Notification::make()
                ->title('No tienes permiso para ver esta sección')
                ->danger()
                ->send();

            // Pedir a la Page que cambie a la primera pestaña permitida
            $this->dispatch('request-tab-fallback');

            // Evitar procesamiento/render innecesario en este ciclo
            $this->skipRender();
        }
    }
}
