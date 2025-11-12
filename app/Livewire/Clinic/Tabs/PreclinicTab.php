<?php

namespace App\Livewire\Clinic\Tabs;

use Livewire\WithPagination;
use App\Models\Preclinic;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Livewire\Attributes\On;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PreclinicTab extends PatientTab
{
    use WithPagination, AuthorizesRequests;

    public ?Preclinic $editing = null;
    public bool $showForm = false;
    public ?array $data = [];

    protected function requiredPermission(): ?string { return 'preclinic.view'; }
    protected function getFormStatePath(): string { return 'data'; }
    protected function getFormModel(): Preclinic|string|null { return $this->editing ?? Preclinic::class; }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Preclínica')
                ->schema([
                    DateTimePicker::make('visit_date')
                        ->label('Fecha de registro')
                        ->default(now())
                        ->seconds(false)
                        ->native(false),

                    // Fila 1: P/A y FC
                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        TextInput::make('bp')
                            ->label('P/A (Presión Arterial)')
                            ->placeholder('120/80')
                            ->suffix('mmHg')
                            ->rule('regex:/^\d{2,3}\/\d{2,3}$/') // 120/80
                            ->helperText('Formato: 120/80')
                            ->columnSpan(1),

                        TextInput::make('hr')
                            ->label('FC (Frecuencia cardiaca)')
                            ->placeholder('75')
                            ->numeric()
                            ->minValue(10)
                            ->maxValue(250)
                            ->suffix('lat/min')
                            ->columnSpan(1),
                    ]),

                    // Fila 2: FR y Peso
                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        TextInput::make('rr')
                            ->label('FR (Frecuencia respiratoria)')
                            ->placeholder('17')
                            ->numeric()
                            ->minValue(5)
                            ->maxValue(80)
                            ->suffix('resp/min')
                            ->columnSpan(1),

                        TextInput::make('weight')
                            ->label('Peso')
                            ->placeholder('75')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(500)
                            ->step(0.1)
                            ->suffix('kg')
                            ->columnSpan(1),
                    ]),

                    // Fila 3: SatO2
                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        TextInput::make('sao2')
                            ->label('SatO2')
                            ->placeholder('95')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->columnSpan(1),
                    ]),
                ]),
        ];
    }

    public function create(): void
    {
        $this->authorize('create', Preclinic::class);
        $this->editing = null;
        $this->resetForm(['visit_date' => now()]);
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->editing = Preclinic::where('patient_id', $this->patientId)->findOrFail($id);
        $this->authorize('update', $this->editing);
        $this->resetForm($this->editing->toArray());
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $data['patient_id'] = $this->patientId;

        if ($this->editing) {
            $this->authorize('update', $this->editing);
            unset($data['user_id']);
            $this->editing->update($data);
            Notification::make()->title('Preclínica actualizada')->success()->send();
        } else {
            $this->authorize('create', Preclinic::class);
            $data['user_id'] = auth()->id();
            $this->editing = Preclinic::create($data);
            Notification::make()->title('Preclínica creada')->success()->send();
        }

        $this->reset(['showForm']);
    }

    public function delete(int $id): void
    {
        $item = Preclinic::where('patient_id', $this->patientId)->findOrFail($id);
        $this->authorize('delete', $item);
        $item->delete();
        Notification::make()->title('Preclínica eliminada')->success()->send();
    }

    public function render()
    {
        $this->authorizeTab();

        $items = Preclinic::query()
            ->with('user')
            ->where('patient_id', $this->patientId)
            ->orderByDesc('visit_date')
            ->paginate(5);

        return view('livewire.clinic.tabs.preclinic-tab', compact('items'));
    }

    #[On('open-create-preclinic')]
    public function openCreateFromSticky(): void
    {
        $this->create();
    }
}
