<?php

namespace App\Livewire\Clinic\Tabs;

use App\Models\ClinicalBackground;
use App\Support\Presenters\ClinicalBackgroundPresenter;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;

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
            $this->patientBackgroundSection(),
            $this->medicalHistoryGrid(),
            $this->acuityAndLensesSection(),
            $this->lensometrySection(),
            $this->externalExamSection(),
            $this->tensionAndFundusSection(),
            $this->treatmentSection(),
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

    private function patientBackgroundSection(): Section
    {
        return Section::make('Antecedentes clínicos del paciente')
            ->description(fn () => $this->recordDescription())
            ->schema([
                Textarea::make('clinical_history')
                    ->label('Historia clínica')
                    ->rows(2)
                    ->placeholder('Motivo de consulta, síntomas (duración, intensidad), evolución, tratamientos previos…')
                    ->columnSpanFull(),
            ]);
    }

    private function medicalHistoryGrid(): Grid
    {
        return Grid::make([
            'default' => 1,
            'md' => 2,
        ])->schema([
            Section::make('Historia Médica / Quirúrgica / Traumática')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('ocular_meds')->label('Medicinas oculares')->placeholder('N/A'),
                        TextInput::make('systemic_meds')->label('Medicaciones sistémicas')->placeholder('N/A'),
                        TextInput::make('allergies')->label('Alergias')->placeholder('N/A'),
                        TextInput::make('personal_path_history')->label('A.P.P.')->placeholder('N/A'),
                        TextInput::make('trauma_surgical_history')->label('A.T.Q.')->placeholder('N/A'),
                        Textarea::make('ophthalmologic_surgical_history')
                            ->label('Antecedentes Oftalmológicos Quirúrgicos (AOQ)')
                            ->placeholder('N/A')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                ])
                ->columnSpan(1),
            Card::make('Antecedentes familiares')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('fam_glaucoma')->label('Glaucoma')->placeholder('N/A'),
                        TextInput::make('fam_cataract')->label('Desprendimiento de retina')->placeholder('N/A'),
                        TextInput::make('fam_blindness')->label('Cataratas')->placeholder('N/A'),
                        TextInput::make('fam_retinal_detachment')->label('Ceguera')->placeholder('N/A'),
                        TextInput::make('fam_diabetes')->label('Diabetes')->placeholder('N/A'),
                        TextInput::make('fam_hypertension')->label('Hipertensión')->placeholder('N/A'),
                        TextInput::make('fam_thyroid')->label('Tiroides')->placeholder('N/A'),
                        TextInput::make('fam_anemia')->label('Anemia')->placeholder('N/A'),
                        TextInput::make('fam_other')->label('Otros')->placeholder('N/A'),
                    ]),
                ])
                ->columnSpan(1),
        ])->columnSpanFull();
    }

    private function acuityAndLensesSection(): Section
    {
        return Section::make('Agudeza visual / Lentes')
            ->schema([
                Grid::make([
                    'default' => 1,
                    'md' => 3,
                ])->schema([
                    Section::make('Agudeza visual CC')
                        ->schema([
                            TextInput::make('av_cc_od')->label('OD')->maxLength(15)->placeholder('N/A'),
                            TextInput::make('av_cc_os')->label('OS')->maxLength(15)->placeholder('N/A'),
                        ])
                        ->columnSpan(1),
                    Section::make('Agudeza visual SC')
                        ->schema([
                            TextInput::make('av_sc_od')->label('OD')->maxLength(15)->placeholder('N/A'),
                            TextInput::make('av_sc_os')->label('OS')->maxLength(15)->placeholder('N/A'),
                        ])
                        ->columnSpan(1),
                    Section::make('Receta de lentes')
                        ->schema([
                            TextInput::make('rx_od')->label('RX OD')->placeholder('N/A'),
                            TextInput::make('rx_os')->label('RX OS')->placeholder('N/A'),
                            TextInput::make('rx_add')->label('ADD')->placeholder('N/A')->suffix('D'),
                        ])
                        ->columnSpan(1),
                ]),
            ])
            ->columnSpanFull();
    }

    private function lensometrySection(): Section
    {
        return Section::make('Lensometría / AV Extra / Cicloplejía')
            ->schema([
                Grid::make([
                    'default' => 1,
                    'md' => 3,
                ])->schema([
                    Section::make('Lensometría')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('lensometry_od')
                                    ->label('OD')
                                    ->maxLength(60)
                                    ->placeholder('N/A'),
                                TextInput::make('lensometry_os')
                                    ->label('OS')
                                    ->maxLength(60)
                                    ->placeholder('N/A'),
                            ]),
                        ])
                        ->columnSpan(1),
                    Section::make('AV CC')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('av_extra_od')
                                    ->label('OD')
                                    ->maxLength(15)
                                    ->placeholder('N/A'),
                                TextInput::make('av_extra_os')
                                    ->label('OS')
                                    ->maxLength(15)
                                    ->placeholder('N/A'),
                            ]),
                        ])
                        ->columnSpan(1),
                    Section::make('ADD')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('add_cyclo_od')
                                    ->label('OD')
                                    ->maxLength(15)
                                    ->placeholder('N/A'),
                                TextInput::make('add_cyclo_os')
                                    ->label('OS')
                                    ->maxLength(15)
                                    ->placeholder('N/A'),
                            ]),
                        ])
                        ->columnSpan(1),
                ]),
            ])
            ->columnSpanFull();
    }

    private function externalExamSection(): Section
    {
        return Section::make('Examen Externo')
            ->schema([
                $this->bilateralTextareaSection('Párpados', 'eyelids'),
                $this->bilateralTextareaSection('Córnea', 'bio_cornea'),
                $this->bilateralTextareaSection('Cámara anterior (C/A)', 'bio_ca'),
                $this->bilateralTextareaSection('Iris', 'bio_iris'),
                $this->bilateralTextareaSection('Cristalino', 'bio_lens'),
                $this->bilateralTextareaSection('Vítreo', 'bio_vitreous'),
            ])
            ->columnSpanFull();
    }

    private function tensionAndFundusSection(): Section
    {
        return Section::make('Tensión ocular / Fondo')
            ->schema([
                Grid::make([
                    'default' => 1,
                    'md' => 2,
                ])->schema([
                    Section::make('Tensión ocular (AP)')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('iop_ap_od')
                                    ->label('OD')
                                    ->placeholder('N/A')
                                    ->suffix('mmHg')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(999.9)
                                    ->step(0.1)
                                    ->rule('decimal:0,1'),
                                TextInput::make('iop_ap_os')
                                    ->label('OS')
                                    ->placeholder('N/A')
                                    ->suffix('mmHg')
                                    ->minValue(0)
                                    ->maxValue(999.9)
                                    ->step(0.1)
                                    ->numeric()
                                    ->rule('decimal:0,1'),
                            ]),
                        ])
                        ->columnSpan(1),
                    Section::make('Fondo ocular')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('fundus_od')->label('OD')->placeholder('N/A'),
                                TextInput::make('fundus_os')->label('OS')->placeholder('N/A'),
                            ]),
                        ])
                        ->columnSpan(1),
                ]),
            ])
            ->columnSpanFull();
    }

    private function treatmentSection(): Section
    {
        return Section::make('Conclusiones y Plan de Tratamiento')
            ->schema([
                TextInput::make('clinical_impression')->label('Impresión clínica'),
                TextInput::make('special_tests')->label('Pruebas especiales'),
                Textarea::make('disposition_and_treatment')->label('Disposición y tratamiento')->autosize(),
            ]);
    }

    private function bilateralTextareaSection(string $title, string $fieldPrefix): Section
    {
        return Section::make($title)->schema([
            Textarea::make("{$fieldPrefix}_od")
                ->label('OD')
                ->autosize()
                ->columnSpanFull(),
            Textarea::make("{$fieldPrefix}_os")
                ->label('OS')
                ->autosize()
                ->columnSpanFull(),
        ])->columnSpanFull();
    }

    private function recordDescription(): HtmlString|string
    {
        if (! $this->record) {
            return 'Aún no registrado.';
        }

        return $this->presenter?->description() ?? new HtmlString('');
    }
}
