<?php

namespace App\Livewire\Clinic\Tabs;

use Livewire\Component;
use Livewire\WithPagination;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use App\Models\Surgery;
use App\Support\Presenters\SurgeryPresenter;

class SurgeriesTab extends Component implements Forms\Contracts\HasForms
{
    use WithPagination;
    use Forms\Concerns\InteractsWithForms;
    use AuthorizesRequests;

    public int $patientId;
    public bool $showForm = false;
    public ?int $editingId = null;

    /** @var array<string,mixed> */
    public array $data = [];

    protected $listeners = [
        'open-create-surgery' => 'create',
    ];

    public function mount(int $patientId): void
    {
        $this->patientId = $patientId;
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    protected function getFormSchema(): array
    {
        // Cargar plantillas desde JSON
        $plantillasPath = resource_path('data/descripciones_quirurgicas.json');
        $options = [];
        $mapa = [];

        if (File::exists($plantillasPath)) {
            $json = json_decode(File::get($plantillasPath), true) ?? [];
            foreach ($json as $tpl) {
                $titulo = $tpl['titulo'] ?? '';
                $desc   = $tpl['descripcion'] ?? '';
                if ($titulo) {
                    $options[$titulo] = Str::limit($titulo, 120);
                    $mapa[$titulo]    = $desc;
                }
            }
        }

        return [
            Forms\Components\Section::make('Cirugía')
                ->schema([
                    Forms\Components\Hidden::make('patient_id')
                        ->default($this->patientId),

                    Forms\Components\Hidden::make('user_id')
                        ->default(Auth::id()),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('diagnostico_preoperatorio')
                            ->label('Diagnóstico preoperatorio')->required()->columnSpanFull(),

                        Forms\Components\TextInput::make('diagnostico_postoperatorio')
                            ->label('Diagnóstico postoperatorio')->required()->columnSpanFull(),

                        Forms\Components\TextInput::make('anestesia')
                            ->label('Anestesia')->required(),

                        Forms\Components\DatePicker::make('fecha_cirugia')
                            ->label('Fecha de cirugía')->required(),

                        Forms\Components\TextInput::make('lente_intraocular')
                            ->label('Lente intraocular')->required(),

                        Forms\Components\Textarea::make('hallazgos_complicaciones')
                            ->label('Hallazgos/Complicaciones')->rows(3)->columnSpanFull(),

                        Forms\Components\Textarea::make('otros_procedimientos')
                            ->label('Otros procedimientos')->rows(3)->columnSpanFull(),
                    ]),

                    Forms\Components\Section::make('Descripción quirúrgica')->schema([
                        Forms\Components\Select::make('titulo_descripcion')
                            ->label('Plantilla de descripción')
                            ->options($options)
                            ->searchable()
                            ->placeholder('Selecciona una plantilla...')
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) use ($mapa) {
                                if ($state && isset($mapa[$state])) {
                                    $set('descripcion_final', $mapa[$state]);
                                }
                            })
                            ->required(),

                        // Cambia a RichEditor si lo prefieres
                        Forms\Components\Textarea::make('descripcion_final')
                            ->label('Descripción (editable)')
                            ->rows(14)
                            ->required(),
                    ]),
                ]),
        ];
    }

    public function create(): void
    {
        $this->authorize('create', Surgery::class);

        $this->reset('data', 'editingId');
        $this->data = [
            'patient_id' => $this->patientId,
            'user_id'    => Auth::id(),
            'fecha_cirugia' => now()->toDateString(),
        ];
        $this->showForm = true;
        $this->form->fill($this->data);
    }

    public function edit(int $id): void
    {
        $model = Surgery::query()->where('patient_id', $this->patientId)->findOrFail($id);
        $this->authorize('update', $model);

        $this->editingId = $model->id;
        $this->data = $model->toArray();

        $this->showForm = true;
        $this->form->fill($this->data);
    }

    public function save(): void
    {
        if ($this->editingId) {
            $model = Surgery::findOrFail($this->editingId);
            $this->authorize('update', $model);
            $model->update($this->form->getState());
        } else {
            $this->authorize('create', Surgery::class);
            $model = Surgery::create($this->form->getState());
        }

        $this->showForm = false;
        $this->reset('editingId');

        Notification::make()
            ->title('Cirugía guardada')
            ->success()
            ->send();
    }

    public function delete(int $id): void
    {
        $model = Surgery::query()->where('patient_id', $this->patientId)->findOrFail($id);
        $this->authorize('delete', $model);
        $model->delete();

        Notification::make()->title('Cirugía eliminada')->success()->send();
    }

    public function render()
    {
        $items = Surgery::query()
            ->with(['user'])
            ->where('patient_id', $this->patientId)
            ->latest('fecha_cirugia')
            ->paginate(10);

        $tz = config('clinic.city_date_tz', config('app.timezone'));
        $presenters = [];
        foreach ($items as $item) {
            $presenters[$item->id] = SurgeryPresenter::make($item, $tz);
        }

        return view('livewire.clinic.tabs.surgeries-tab', [
            'items' => $items,
            'presenters' => $presenters,
        ]);
    }
}
