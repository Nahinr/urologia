<?php

namespace App\Livewire;

use App\Models\Patient;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;
use App\Filament\Pages\Clinic\Expedientes;

class PatientSearch extends Component implements HasForms
{
    use InteractsWithForms;

    public ?int $selected = null;

    public function mount(?int $selected = null): void
    {
        $this->selected = $selected;
        if ($selected) {
            $this->dispatch('patient-selected', id: $selected)->to(Expedientes::class);
        }
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('selected')
                ->label('Buscar paciente')
                ->placeholder('Nombre, DNI o teléfono')
                ->searchPrompt('Escribe al menos 2 caracteres...')
                ->searchDebounce(350)
                ->searchable()
                ->preload(false)
                ->live()
                ->options(function () {
                    if (! $this->selected) return [];
                    $p = Patient::query()->whereKey($this->selected)->first();
                    return $p ? [$p->id => $p->display_name] : [];
                })
                ->getSearchResultsUsing(function (string $search) {
                    return Patient::query()
                        ->with(['contacts' => fn ($q) => $q->select('id','patient_id','phone')])
                        ->forLookup()
                        ->searchTerm($search)
                        ->limit(10)
                        ->get()
                        ->mapWithKeys(fn ($p) => [$p->id => $p->display_label])
                        ->toArray();
                })
                ->afterStateUpdated(function ($state) {
                    $this->dispatch('patient-selected', id: (int) $state)->to(Expedientes::class);
                })
                ->native(false)
                ->columnSpanFull(),
        ];
    }

    public function render()
    {
        return view('livewire.patient-search');
    }
}
