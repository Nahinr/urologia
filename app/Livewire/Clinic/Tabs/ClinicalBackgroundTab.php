<?php

namespace App\Livewire\Clinic\Tabs;

use Illuminate\Support\Arr;
use Livewire\Attributes\On;
use App\Models\ClinicalBackground;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Support\Presenters\ClinicalBackgroundPresenter;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\ToggleButtons;

class ClinicalBackgroundTab extends PatientTab
{
    public ?ClinicalBackground $record = null;

    protected ?ClinicalBackgroundPresenter $presenter = null;

    public ?array $data = [];

    protected function requiredPermission(): ?string
    {
        return 'clinical-background.view';
    }

    protected function bootedPatientTab(): void
    {
        $this->loadRecord();
    }

    protected function loadRecord(): void
    {
        $this->record = ClinicalBackground::query()
            ->with(['user', 'updatedBy'])
            ->firstWhere('patient_id', $this->patientId);

        $this->presenter = $this->record ? ClinicalBackgroundPresenter::make($this->record) : null;

        if ($this->record) {
            $this->resetForm($this->record->toArray());
        } else {
            $this->resetForm();
        }
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Historia Clínica')
                ->description(fn () => $this->recordDescription())
                ->schema([
                    Textarea::make('hea')
                        ->label('HEA (Historia de enfermedad actual)')
                        ->rows(3)
                        ->placeholder('Describir motivo de consulta, inicio, evolución, síntomas, tratamientos previos...')
                        ->columnSpanFull(),
                    Textarea::make('app')
                        ->label('APP (Antecedentes personales patológicos)')
                        ->rows(3)
                        ->placeholder('Ej.: diabetes, HTA, nefropatías, etc.')
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

                Section::make('Condiciones y tratamientos')
                ->schema([
                    // ====== FILA 1: Diabetes ======
                    Grid::make()->columns(10)->schema([
                        ToggleButtons::make('has_diabetes')
                            ->label('Diabetes')
                            ->boolean()
                            ->inline()
                            ->options([true => 'Sí', false => 'No'])
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if (! (bool) $state) $set('diabetes_treatment', null);
                            })
                            ->columnSpan(['default' => 10, 'md' => 4]),    // ancho toggle

                        TextInput::make('diabetes_treatment')
                            ->hiddenLabel()                                // evita salto de línea por label
                            ->label('Tratamiento para Diabetes')
                            ->prefix('Tratamiento')                        // “Tratamiento” dentro del input
                            ->placeholder('Metformina 850mg, insulina…')
                            ->reactive()
                            ->hidden(fn (Get $get) => ! (bool) $get('has_diabetes'))
                            ->columnSpan(['default' => 10, 'md' => 8]),    // ancho input
                    ]),

                    // ====== FILA 2: Hipertensión ======
                    Grid::make()->columns(10)->schema([
                        ToggleButtons::make('has_hypertension')
                            ->label('Hipertensión')
                            ->boolean()
                            ->inline()
                            ->options([true => 'Sí', false => 'No'])
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if (! (bool) $state) $set('hypertension_treatment', null);
                            })
                            ->columnSpan(['default' => 12, 'md' => 4]),

                        TextInput::make('hypertension_treatment')
                            ->hiddenLabel()
                            ->label('Tratamiento para Hipertensión')
                            ->prefix('Tratamiento')
                            ->placeholder('IECA, ARA-II, beta-bloqueadores…')
                            ->reactive()
                            ->hidden(fn (Get $get) => ! (bool) $get('has_hypertension'))
                            ->columnSpan(['default' => 12, 'md' => 8]),
                    ]),

                    // ====== FILA 3: Enfermedad urológica ======
                    Grid::make()->columns(10)->schema([
                        ToggleButtons::make('has_urologic_disease')
                            ->label('Enfermedad urológica')
                            ->boolean()
                            ->inline()
                            ->options([true => 'Sí', false => 'No'])
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if (! (bool) $state) $set('urologic_treatment', null);
                            })
                            ->columnSpan(['default' => 12, 'md' => 4]),

                        TextInput::make('urologic_treatment')
                            ->hiddenLabel()
                            ->label('Tratamiento para Enfermedad urológica')
                            ->prefix('Tratamiento')
                            ->placeholder('Tamsulosina, antibióticos…')
                            ->reactive()
                            ->hidden(fn (Get $get) => ! (bool) $get('has_urologic_disease'))
                            ->columnSpan(['default' => 12, 'md' => 8]),
                    ]),
                ])
                ->columnSpanFull(),

            Section::make('Antecedentes')
                ->schema([
                    Textarea::make('aqx')
                        ->label('AQx (Antecedentes Quirúrgicos)')
                        ->rows(3)
                        ->placeholder('Cirugías previas, fechas, complicaciones…')
                        ->columnSpanFull(),
                    Textarea::make('ago')
                        ->label('AGO (Antecedentes Gineco-Obstétricos)')
                        ->rows(3)
                        ->placeholder('Gestaciones, partos, cesáreas, FUR, etc.')
                        ->columnSpanFull(),
                    Textarea::make('a_aler')
                        ->label('AAler (Alergias)')
                        ->rows(2)
                        ->placeholder('Descripción de alergias y reacciones…')
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

            Section::make('Examen Físico / Diagnóstico / Tratamiento')
                ->schema([
                    Textarea::make('physical_exam')
                        ->label('Examen Físico')
                        ->rows(3)
                        ->placeholder('Signos vitales relevantes, examen regional, etc.')
                        ->columnSpanFull(),
                    Textarea::make('diagnosis')
                        ->label('Diagnóstico')
                        ->rows(3)
                        ->placeholder('Dx principal y diferenciales…')
                        ->columnSpanFull(),
                    Textarea::make('treatment')
                        ->label('Tratamiento')
                        ->rows(3)
                        ->placeholder('Plan terapéutico, indicaciones…')
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ];
    }


    protected function getFormStatePath(): string
    {
        return 'data';
    }

    protected function getFormModel(): ClinicalBackground|string|null
    {
        return $this->record ?? ClinicalBackground::class;
    }

    public function save(): void
    {
        $data = Arr::except($this->form->getState(), ['user_id', 'updated_by']);
        $data['patient_id'] = $this->patientId;

        if ($this->record) {
            $this->record->update($data);
            $this->record->refresh()->load(['user', 'updatedBy']);
            Notification::make()->title('Antecedentes actualizados')->success()->send();
        } else {
            $this->record = ClinicalBackground::create($data);
            $this->record->load(['user', 'updatedBy']);
            Notification::make()->title('Antecedentes creados')->success()->send();
        }

        $this->presenter = $this->record ? ClinicalBackgroundPresenter::make($this->record) : null;
        $this->resetForm($this->record?->toArray() ?? []);
    }

    #[On('save-clinical-background')]
    public function saveFromStickyBar(): void
    {
        $this->save();
    }

    public function render()
    {
        $this->authorizeTab();

        return view('livewire.clinic.tabs.clinical-background-tab');
    }

    private function recordDescription(): HtmlString|string
    {
        if (! $this->record) {
            return 'Aún no registrado.';
        }

        return $this->presenter?->description() ?? new HtmlString('');
    }
}
