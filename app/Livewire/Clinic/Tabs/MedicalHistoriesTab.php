<?php

namespace App\Livewire\Clinic\Tabs;

use Livewire\WithPagination;
use App\Models\MedicalHistory;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DateTimePicker;
use Livewire\Attributes\On;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Support\Presenters\MedicalHistoryPresenter;



class MedicalHistoriesTab extends PatientTab
{
    use WithPagination;
    use AuthorizesRequests;
    public ?MedicalHistory $editing = null;
    public bool $showForm = false;

    public ?array $data = [];

    protected function requiredPermission(): ?string
    {
        return 'history.view';
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Consulta')
                ->schema([
                    DateTimePicker::make('visit_date')
                        ->label('Fecha de consulta')
                        ->default(now())
                        ->seconds(false)
                        ->native(false),

                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        Textarea::make('evolution')
                            ->label('Evolución')
                            ->rows(6)
                            ->placeholder('Describir evolución desde la última consulta, respuesta a tratamiento, síntomas actuales…')
                            ->columnSpan(1),

                        Textarea::make('physical_exam')
                            ->label('Examen Físico')
                            ->rows(6)
                            ->placeholder('Signos vitales relevantes y hallazgos al examen físico…')
                            ->columnSpan(1),
                    ]),
                ]),
        ];
    }


    protected function getFormModel(): MedicalHistory|string|null
    {
        return $this->editing ?? MedicalHistory::class;
    }

    public function create(): void
    {
        $this->authorize('create', MedicalHistory::class);
        $this->editing = null;
        $this->resetForm(['visit_date' => now()]);
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->editing = MedicalHistory::where('patient_id', $this->patientId)->findOrFail($id);
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
            Notification::make()->title('Consulta actualizada')->success()->send();
        } else {
            $this->authorize('create', MedicalHistory::class);
            $data['user_id'] = auth()->id();
            $this->editing = MedicalHistory::create($data);
            Notification::make()->title('Consulta creada')->success()->send();
        }

        $this->reset(['showForm']);
    }

    public function delete(int $id): void
    {
        $item = MedicalHistory::where('patient_id', $this->patientId)->findOrFail($id);
        $this->authorize('delete', $item);
        // Por ahora eliminación dura; luego cambiamos a "anular" con un campo status.
        $item->delete();
        Notification::make()->title('Consulta eliminada')->success()->send();
    }

    public function render()
    {
        $this->authorizeTab();
        $items = MedicalHistory::query()
            ->with('user')
            ->where('patient_id', $this->patientId)
            ->orderByDesc('visit_date')
            ->paginate(5);

        $presenters = $items->getCollection()
            ->mapWithKeys(fn (MedicalHistory $history) => [
                $history->id => MedicalHistoryPresenter::make($history),
            ])
            ->all();

        return view('livewire.clinic.tabs.medical-histories-tab', [
            'items' => $items,
            'presenters' => $presenters,
        ]);
    }

    #[On('open-create-consulta')]
    public function openCreateFromSticky(): void
    {
        $this->create(); // reutiliza tu método existente
    }
}
