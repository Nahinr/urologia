<?php

namespace App\Filament\Pages\Clinic;

use App\Models\Patient;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;

class Expedientes extends Page
{
    protected static ?string $navigationLabel = 'Expedientes';
    protected static ?string $navigationGroup = 'Clínica';
    protected static ?string $navigationIcon  = 'heroicon-o-folder-open';
    protected static ?int $navigationSort     = 10;
    protected static string $view = 'filament.pages.clinic.expedientes';

    public ?int $patientId = null;
    public ?Patient $patient = null;
    
    public ?string $tab = null;

    public function mount(): void
    {
        $this->tab = $this->firstAllowedTab();
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, $this->allowedTabs(), true)) {
        $this->tab = $tab;
        } else {
            $this->tab = $this->firstAllowedTab();
        }
    }


    public static function canAccess(): bool
    {
        $u = auth()->user();

        if (! $u) {
            return false;
        }

        return $u->can('clinical-background.view')
            || $u->can('history.view')
            || $u->can('prescription.view')
            || $u->can('patient.attachments.view');
    }

    public function getHeading(): string
    {
        return 'Expedientes';
    }


    public function getBreadcrumbs(): array
    {
        return [
            '#'                                     => 'Clínica',  
            url()->current()                        => 'Expedientes',
        ];
    }


    #[On('patient-selected')]
    public function loadPatient(int $id): void
    {
        $with = ['contacts'];
        if (method_exists(\App\Models\Patient::class, 'guardian')) $with[] = 'guardian';

        $this->patientId = $id;
        $this->patient   = \App\Models\Patient::with($with)->find($id);
        $this->tab = $this->firstAllowedTab();
    }

    protected function allowedTabs(): array
    {
        $u = auth()->user();

        $tabs = [];

        // Antecedentes (permiso separado)
        if ($u?->can('clinical-background.view')) {
            $tabs[] = 'antecedentes';
        }

        // Historias (evoluciones)
        if ($u?->can('history.view')) {
            $tabs[] = 'consultas';
        }

        // Recetas (solo doctor)
        if ($u?->can('prescription.view')) {
            $tabs[] = 'recetas';
        }

        // Imágenes/adjuntos clínicos
        if ($u?->can('patient.attachments.view')) { 
            $tabs[] = 'imagenes';
        }

        return $tabs;
    }

    protected function firstAllowedTab(): ?string
    {
        $allowed = $this->allowedTabs();
        return $allowed[0] ?? null;
    }

    #[On('request-tab-fallback')]
    public function fallbackTab(): void
    {
        $this->tab = $this->firstAllowedTab();
    }

    public static function shouldRegisterNavigation(): bool
    {
        $u = auth()->user();

        return $u
            && (
                $u->can('clinical-background.view')
                || $u->can('history.view')
                || $u->can('prescription.view')
                || $u->can('patient.attachments.view')
            );
    }

}
