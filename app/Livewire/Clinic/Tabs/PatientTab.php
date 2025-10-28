<?php

namespace App\Livewire\Clinic\Tabs;

use App\Livewire\Traits\AuthorizesTab;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Attributes\Locked;
use Livewire\Component;
use function data_set;

abstract class PatientTab extends Component implements HasForms
{
    use InteractsWithForms;
    use AuthorizesTab;

    #[Locked]
    public int $patientId;

    public function mount(int $patientId): void
    {
        $this->patientId = $patientId;
        $this->authorizeTab();
        $this->bootedPatientTab();
    }

    protected function bootedPatientTab(): void
    {
        // Hook for child components to run additional setup logic.
    }

    protected function resetForm(array $state = []): void
    {
        $path = $this->getFormStatePath();
        data_set($this, $path, []);

        if ($state !== []) {
            $this->form->fill($state);
        }
    }
}
