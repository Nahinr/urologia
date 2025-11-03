<?php

namespace App\Livewire\Clinic\Tabs;

use Livewire\WithPagination;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Filament\Forms\Components\RichEditor;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use App\Models\Prescription;
use App\Support\Presenters\PrescriptionPresenter;

class PrescriptionsTab extends PatientTab
{
    use WithPagination;
    use AuthorizesRequests;

    public ?Prescription $editing = null;
    public bool $showForm = false;

    public ?array $data = [];

    protected function requiredPermission(): ?string
    {
        return 'prescription.view';
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Receta')
                ->schema([
                    DateTimePicker::make('issued_at')
                        ->label('Fecha de emisión')
                        ->seconds(false)
                        ->default(now())               // usa tz del app
                        ->native(false)
                        ->required(),

                    Grid::make([
                        'default' => 1,
                        'md'      => 2,
                    ])->schema([
                        RichEditor::make('diagnosis')
                            ->label('Diagnóstico')
                            ->columnSpan(1)
                            ->toolbarButtons([
                                'bold', 'italic', 'underline', 'strike',
                                'bulletList', 'orderedList',
                                'link',
                                'undo', 'redo',
                            ])
                            ->required(),

                        RichEditor::make('medications_description')
                            ->label('Medicamentos e indicaciones')
                            ->columnSpan(1)
                            ->toolbarButtons([
                                'bold', 'italic', 'underline', 'strike',
                                'bulletList', 'orderedList',
                                'link',
                                'undo', 'redo',
                            ])
                            ->required(),
                    ]),
                ]),
        ];
    }

    protected function getFormModel(): Prescription|string|null
    {
        return $this->editing ?? Prescription::class;
    }

    public function create(): void
    {
        $this->authorize('create', \App\Models\Prescription::class);
        $this->editing = null;
        $this->resetForm([
            'issued_at' => now(),
        ]);
        $this->showForm = true;

        // si estás paginado, evita quedarte en páginas vacías
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $this->editing = Prescription::where('patient_id', $this->patientId)->findOrFail($id);
        $this->authorize('update', $this->editing);
        $this->resetForm($this->editing->toArray());
        $this->showForm = true;
    }

    public function save(): void
    {
        $state = $this->form->getState();

        // Validaciones clave (además de required en schema)
        $this->validate([
            'data.diagnosis'               => 'required|string|max:2000',
            'data.medications_description' => 'required|string|max:5000',
            'data.issued_at'               => 'required|date',
        ], [], [
            'data.diagnosis'               => 'diagnóstico',
            'data.medications_description' => 'medicamentos e indicaciones',
            'data.issued_at'               => 'fecha de emisión',
        ]);

        $state['patient_id'] = $this->patientId; // OBLIGATORIO
        


        if ($this->editing) {
            $this->authorize('update', $this->editing);
            unset($state['user_id']); 
            $this->editing->update($state);
            Notification::make()->title('Receta actualizada')->success()->send();
        } else {
            $this->authorize('create', \App\Models\Prescription::class);
            // unset($state['user_id']); // No permitir asignar user_id desde el form
            $this->editing = Prescription::create($state);
            Notification::make()->title('Receta creada')->success()->send();
        }

        $this->reset(['showForm']);
    }

    public function delete(int $id): void
    {
        $rx = Prescription::where('patient_id', $this->patientId)->findOrFail($id);
        $this->authorize('delete', $rx);
        $rx->delete();
        Notification::make()->title('Receta eliminada')->success()->send();
    }

    public function render()
    {
        $this->authorizeTab();
        $items = Prescription::query()
            ->with(['user', 'patient'])
            ->where('patient_id', $this->patientId)
            ->orderByDesc('issued_at')
            ->paginate(5);

        $presenters = $items->getCollection()
            ->mapWithKeys(fn (Prescription $prescription) => [
                $prescription->id => PrescriptionPresenter::make($prescription),
            ])
            ->all();

        return view('livewire.clinic.tabs.prescriptions-tab', [
            'items' => $items,
            'presenters' => $presenters,
        ]);
    }

    // Abrir el form desde la barra sticky
    #[On('open-create-prescription')]
    public function openCreateFromSticky(): void
    {
        $this->create();
    }
}
