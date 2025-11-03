<?php

namespace App\Filament\Widgets;

use Filament\Forms;
use App\Models\Contact;
use App\Models\Patient;
use App\Models\User;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Appointment;
use Carbon\CarbonImmutable;
use Filament\Support\RawJs;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Saade\FilamentFullCalendar\Actions;
use Filament\Notifications\Notification;
use App\Filament\Forms\Fields\PhoneField;
use Filament\Forms\Components\DatePicker;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;


class CalendarWidget extends FullCalendarWidget
{
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 1;

    public Model|string|null $model = Appointment::class;

    public ?int $doctorId = null;

    private string $calendarTz = 'America/Tegucigalpa';

    private function fromDbLocal(?string $value): ?CarbonImmutable
    {
        if (!$value) return null;
        return CarbonImmutable::createFromFormat('Y-m-d H:i:s', $value, $this->calendarTz);
    }

    private function toDbLocal(CarbonImmutable $dt): string
    {
        return $dt->setTimezone($this->calendarTz)->toDateTimeString();
    }

    // ★ Nueva: normaliza fecha a Y-m-d desde DateTimeInterface o string variado
    private function normalizeDate(mixed $input): string
    {
        if ($input instanceof \DateTimeInterface) {
            return CarbonImmutable::instance(\Carbon\Carbon::instance($input))
                ->setTimezone($this->calendarTz)
                ->format('Y-m-d');
        }
        $s = trim((string) $input);
        if ($s === '') {
            return now($this->calendarTz)->format('Y-m-d');
        }
        // Cae a parse (tolerante) y retorna Y-m-d
        return CarbonImmutable::parse($s, $this->calendarTz)->format('Y-m-d');
    }

    private function normalizeTime(?string $input): string
    {
        if ($input === null) return '08:00';
        $s = strtolower(trim($input));
        if ($s === '') return '08:00';

        if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $s)) {
            $s = substr($s, 0, 5);
        }
        $s = preg_replace('/\s+/', '', $s);

        if (preg_match('/^(\d{1,2}):(\d{2})(am|pm)?$/', $s, $m)) {
            $h = (int) $m[1];
            $i = (int) $m[2];
            $ap = $m[3] ?? null;
            if ($ap === 'pm' && $h < 12) $h += 12;
            if ($ap === 'am' && $h === 12) $h = 0;
            if ($h === 24) $h = 0;
            return sprintf('%02d:%02d', $h, $i);
        }

        if (preg_match('/^\d{1,2}:\d{2}$/', $s)) {
            [$h, $i] = array_map('intval', explode(':', $s));
            if ($h === 24) $h = 0;
            return sprintf('%02d:%02d', $h, $i);
        }

        return CarbonImmutable::parse($input, $this->calendarTz)->format('H:i');
    }

    /** Opciones 30 min (valor H:i, etiqueta 12h am/pm) */
    private function timeOptions(): array
    {
        $opts = [];
        for ($h = 0; $h < 24; $h++) {
            foreach ([0, 30] as $m) {
                $value = sprintf('%02d:%02d', $h, $m);
                $label = strtolower(CarbonImmutable::createFromFormat('H:i', $value, $this->calendarTz)->format('g:ia'));
                $opts[$value] = $label;
            }
        }
        return $opts;
    }

    private function getDoctorOptions(): array
    {
        return User::query()
            ->select(['id', 'name', 'last_name', 'email'])
            ->whereHas('roles', fn ($q) => $q->where('name', 'Doctor'))
            ->orderBy('name')
            ->orderBy('last_name')
            ->get()
            ->mapWithKeys(fn (User $user) => [$user->id => $user->display_name])
            ->toArray();
    }

    private function resolveDoctorId(int|string|null $value = null): ?int
    {
        $currentDoctorId = $this->currentDoctorId();

        if (! $this->canViewAllDoctors()) {
            return $currentDoctorId;
        }

        if ($value !== null && $value !== '') {
            return (int) $value;
        }

        return $this->doctorId !== null && $this->doctorId !== ''
            ? (int) $this->doctorId
            : null;
    }

    private function currentDoctorId(): ?int
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        return $user->hasRole('Doctor') ? (int) $user->id : null;
    }

    private function canViewAllDoctors(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('Administrator') || $user->hasRole('Receptionist');
    }

    private function resolveDoctorColor(?int $doctorId): array
    {
        $default = [
            'background' => '#64748b',
            'border' => '#64748b',
            'text' => '#f8fafc',
        ];

        if ($doctorId === null) {
            return $default;
        }

        $palette = [
            '#e63946', // rojo coral
            '#f3722c', // naranja intenso
            '#f9c74f', // amarillo brillante
            '#43aa8b', // verde esmeralda
            '#277da1', // azul cielo profundo
            '#9b5de5', // violeta vibrante
            '#f72585', // fucsia fuerte
            '#4d908e', // verde azulado grisáceo (neutral balanceado)
        ];

        $index = abs(crc32((string) $doctorId)) % count($palette);
        $background = $palette[$index];

        return [
            'background' => $background,
            'border' => $background,
            'text' => $this->getAccessibleTextColor($background),
        ];
    }

    private function getAccessibleTextColor(string $hexColor): string
    {
        $hex = ltrim($hexColor, '#');

        if (strlen($hex) === 3) {
            $hex = preg_replace('/(.)/u', '$1$1', $hex);
        }

        if (strlen($hex) !== 6) {
            return '#0f172a';
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance > 0.6 ? '#0f172a' : '#f8fafc';
    }

    private function getBestPhoneForPatientId(null|int|string $id): array
    {
        // Siempre devolvemos este formato
        $result = ['phone' => null, 'source' => null];
        if (!$id) return $result;

        // Cargar los datos necesarios del paciente
        $p = \App\Models\Patient::query()
            ->select(['id','phone','birth_date'])
            ->find($id);

        if (!$p) return $result;

        // ¿Es menor de edad?
        $isMinor = false;
        if ($p->birth_date) {
            try {
                $dob = \Carbon\CarbonImmutable::parse($p->birth_date, $this->calendarTz);
                $isMinor = $dob->age < 18;
            } catch (\Throwable $e) {
                // Si hay un problema con la fecha, asumimos no menor
                $isMinor = false;
            }
        }

        // Si NO es menor y SÍ tiene teléfono del paciente => usar ese y salir
        if (!$isMinor && $p->phone) {
            return ['phone' => $p->phone, 'source' => 'patient'];
        }

        // Caso 1: menor de edad (use contacto)
        // Caso 2: mayor de edad sin teléfono (use contacto)
        // Buscar cualquier contacto con teléfono
        $q = \App\Models\Contact::query()
            ->where('patient_id', $id)
            ->whereNotNull('phone')
            ->where('phone', '!=', '');

        // Si la tabla tiene created_at, prioriza más reciente
        if (\Illuminate\Support\Facades\Schema::hasColumn('contacts', 'created_at')) {
            $q->orderByDesc('created_at');
        }

        $contact = $q->first(['phone']); // Sólo necesitamos el teléfono

        if ($contact?->phone) {
            return ['phone' => $contact->phone, 'source' => 'contact'];
        }

        // No hay contacto con teléfono
        return $result;
    }

    /* ---------------- Crear ---------------- */
    protected function headerActions(): array
    {
        return [
            Actions\CreateAction::make('create')
                ->extraAttributes(['style' => 'display:none'])
                ->label('Nueva cita')
                ->modalHeading('Nueva cita')
                ->modalWidth('xl')
                ->createAnother(false)
                ->model(Appointment::class)
                ->form(fn () => $this->getFormSchema())
                ->mountUsing(function (Forms\Form $form, array $arguments) {
                    $start = CarbonImmutable::parse($arguments['start'] ?? now())->setTimezone($this->calendarTz);
                    $end   = isset($arguments['end'])
                        ? CarbonImmutable::parse($arguments['end'])->setTimezone($this->calendarTz)
                        : $start->addMinutes(30);
                    if ($end->lessThanOrEqualTo($start)) $end = $start->addMinutes(30);

                    $doctorOptions = $this->getDoctorOptions();
                    $initialDoctorId = $this->resolveDoctorId($this->doctorId);

                    if (! $initialDoctorId) {
                        $initialDoctorId = $this->resolveDoctorId(array_key_first($doctorOptions) ?: null);
                    }

                    $form->fill([
                        'doctor_id'     => $initialDoctorId,
                        'date'           => $start->format('Y-m-d'),
                        'start_time'     => $start->format('H:i'),
                        'end_time'       => $end->format('H:i'),
                        'start_datetime' => $start->format('Y-m-d H:i'),
                        'end_datetime'   => $end->format('Y-m-d H:i'),
                        'observations'   => null,
                        'patient_phone'  => null,
                        'patient_phone_source'  => null,
                    ]);
                })
                ->mutateFormDataUsing(function (array $data): array {
                    // ★ Usar normalizadores y parse en vez de createFromFormat
                    $date  = $this->normalizeDate($data['date'] ?? null);
                    $sTime = $this->normalizeTime($data['start_time'] ?? null);
                    $eTime = $this->normalizeTime($data['end_time'] ?? null);

                    $s = CarbonImmutable::parse("{$date} {$sTime}", $this->calendarTz);
                    $e = CarbonImmutable::parse("{$date} {$eTime}", $this->calendarTz);
                    if ($e->lessThanOrEqualTo($s)) $e = $s->addMinutes(30);

                    $data['doctor_id']      = $this->resolveDoctorId($data['doctor_id'] ?? null);
                    $data['start_datetime'] = $s->format('Y-m-d H:i:s');
                    $data['end_datetime']   = $e->format('Y-m-d H:i:s');

                    unset($data['date'], $data['start_time'], $data['end_time']);
                    return $data;
                })
                ->after(fn () => $this->refreshRecords()),
        ];
    }

    /* ---------------- Editar al click ---------------- */
    protected function viewAction(): Action
    {
        return Actions\EditAction::make()
            ->modalWidth('xl')
            ->form(fn () => $this->getFormSchema())
            ->mountUsing(function (Appointment $record, Forms\Form $form, array $arguments) {
                $startArg = data_get($arguments, 'event.start');
                $endArg   = data_get($arguments, 'event.end');

                $start = $startArg
                    ? CarbonImmutable::parse($startArg)->setTimezone($this->calendarTz)
                    : $this->fromDbLocal($record->getRawOriginal('start_datetime'));

                $end = $endArg
                    ? CarbonImmutable::parse($endArg)->setTimezone($this->calendarTz)
                    : $this->fromDbLocal($record->getRawOriginal('end_datetime'));
                $best = $this->getBestPhoneForPatientId($record->patient_id);
                $form->fill([
                    'doctor_id'     => $record->doctor_id,
                    'patient_id'     => $record->patient_id,
                    'date'           => $start?->format('Y-m-d'),
                    'start_time'     => $start?->format('H:i'),
                    'end_time'       => $end?->format('H:i'),
                    'start_datetime' => $start?->format('Y-m-d H:i'),
                    'end_datetime'   => $end?->format('Y-m-d H:i'),
                    'observations'   => $record->observations,
                    'patient_phone'         => $best['phone'], 
                    'patient_phone_source'  => $best['source'],
                ]);
            })
            ->mutateFormDataUsing(function (array $data): array {
                // ★ Usar normalizadores y parse en vez de createFromFormat
                $date  = $this->normalizeDate($data['date'] ?? null);
                $sTime = $this->normalizeTime($data['start_time'] ?? null);
                $eTime = $this->normalizeTime($data['end_time'] ?? null);

                $s = CarbonImmutable::parse("{$date} {$sTime}", $this->calendarTz);
                $e = CarbonImmutable::parse("{$date} {$eTime}", $this->calendarTz);
                if ($e->lessThanOrEqualTo($s)) $e = $s->addMinutes(30);

                $data['doctor_id']      = $this->resolveDoctorId($data['doctor_id'] ?? null);
                $data['start_datetime'] = $s->format('Y-m-d H:i:s');
                $data['end_datetime']   = $e->format('Y-m-d H:i:s');

                unset($data['date'], $data['start_time'], $data['end_time']);
                return $data;
            })
            ->extraModalFooterActions([
                Actions\DeleteAction::make('deleteAppointment')
                    ->label('Eliminar')
                    ->icon('heroicon-m-trash')
                    ->color('gray')          // menos intenso que danger
                    ->outlined()             // aún más suave
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar cita')
                    ->modalDescription('Esta acción no se puede deshacer.')
                    ->successNotificationTitle('Cita eliminada')
                    ->after(fn () => $this->refreshRecords()),
            ])
            ->after(fn () => $this->refreshRecords());
    }

    /* ---------------- Form (fecha editable + selects 30 min) ---------------- */
    public function getFormSchema(): array
    {
        return [
            Select::make('doctor_id')
                ->label('Doctor')
                ->options($this->getDoctorOptions())
                ->searchable()
                ->preload()
                ->native(false)
                ->required()
                ->placeholder('Selecciona un doctor')
                ->validationMessages([
                    'required' => 'Selecciona un doctor.',
                ])
                ->columnSpanFull(),

            Select::make('patient_id')
                ->label('Paciente')
                ->placeholder('Buscar por nombre, teléfono o DNI')
                ->required()
                ->validationMessages([
                    'required' => 'Selecciona o crea un paciente.'
                ])
                ->rules(['exists:patients,id'])
                ->searchable()
                ->preload(false)
                ->live()          
                ->reactive() 
                ->options(fn () => [])
                ->getSearchResultsUsing(function (string $search) {
                    $s = trim($search);
                    $digits = preg_replace('/\D+/', '', $s);

                    return Patient::query()
                        ->select(['id', 'first_name', 'last_name', 'phone', 'dni'])
                        ->when($digits !== '', function ($q) use ($digits) {
                            $q->where(function ($qq) use ($digits) {
                                $qq->whereRaw("REPLACE(REPLACE(phone,'-',''),' ','') LIKE ?", ["%{$digits}%"])
                                   ->orWhereRaw("REPLACE(REPLACE(dni,'-',''),' ','') LIKE ?",   ["%{$digits}%"]);
                            });
                        })
                        ->when($digits === '', function ($q) use ($s) {
                            $q->where(function ($qq) use ($s) {
                                $qq->where('first_name', 'like', "%{$s}%")
                                   ->orWhere('last_name',  'like', "%{$s}%")
                                   ->orWhereRaw("CONCAT(first_name,' ',last_name) LIKE ?", ["%{$s}%"]);
                            });
                        })
                        ->orderBy('first_name')->orderBy('last_name')
                        ->limit(20)
                        ->get()
                        ->mapWithKeys(function (Patient $p) {
                            $label = $p->full_name;
                            $meta  = collect([$p->dni])->filter()->implode(' · ');
                            if ($meta) $label .= " — {$meta}";
                            return [$p->id => $label];
                        })
                        ->toArray();
                })
                ->getOptionLabelUsing(function ($value) {
                    if (!$value) return null;
                    $p = Patient::query()->select(['id','first_name','last_name'])->find($value);
                    if (!$p) return null;
                    $label = $p->full_name;
                    $meta  = collect([$p->dni])->filter()->implode(' · ');
                    return $meta ? "{$label} — {$meta}" : $label;
                })

                 ->createOptionForm([
                    Forms\Components\Grid::make(12)->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(80)
                            ->columnSpan(6),

                        Forms\Components\TextInput::make('last_name')
                            ->label('Apellido')
                            ->maxLength(80)
                            ->columnSpan(6),

                        
                        ...PhoneField::schema(
                            countryField: 'express_phone_country',   // nombres únicos en el modal
                            nationalField: 'express_phone_national',
                            e164Field: 'phone',                     // se guarda en $data['phone'] en E.164 (+504...)
                            countrySpan: 4,
                            numberSpan: 8,
                        ),

                    ])->columns(12),
                ])
                ->createOptionUsing(function (array $data) {
                    $e164 = $data['phone'] ?? null;

                    $existing = Patient::query()
                        ->when($e164, fn ($q) => $q->where('phone', $e164))
                        ->first();

                    if ($existing) {
                        $best = $this->getBestPhoneForPatientId($existing->id);
                        $this->form->fill([
                            'patient_phone'        => $best['phone'],
                            'patient_phone_source' => $best['source'],
                        ]);
                        return $existing->id;
                    }

                    $p = Patient::create([
                        'first_name' => $data['first_name'],
                        'last_name'  => $data['last_name'] ?? null,
                        'phone'      => $e164,
                        'dni'        => null,
                    ]);

                    $best = $this->getBestPhoneForPatientId($p->id);
                    $this->form->fill([
                        'patient_phone'        => $best['phone'],
                        'patient_phone_source' => $best['source'],
                    ]);

                    return $p->id;
                })

                ->afterStateUpdated(function ($state, Set $set) {
                    if (!$state) {
                    $set('patient_phone', null);
                    $set('patient_phone_source', null);
                    return;
                    }
                    $best = $this->getBestPhoneForPatientId($state);
                    $set('patient_phone', $best['phone']);
                    $set('patient_phone_source', $best['source']);
                })
                ->columnSpanFull(),

            Forms\Components\TextInput::make('patient_phone')
            ->label('Teléfono')
            ->readOnly() 
            ->reactive()        // solo lectura (recomendado)
            ->dehydrated(false)   // no se guarda en la cita; lo trae de Patient
            ->helperText(function (Get $get) {
                $src = $get('patient_phone_source');
                    if (!$src) return null;

                    return 'Origen: ' . ($src === 'patient'
                        ? 'Teléfono del paciente'
                        : 'Teléfono de encargado'
                    );
                })
            ->placeholder('')    // por si no tiene
            ->columnSpanFull(),

            Forms\Components\Hidden::make('patient_phone_source')->dehydrated(false),


            Textarea::make('observations')
            ->label('Notas')
            ->placeholder('Motivo de la cita, indicaciones, Observaciones, etc.')
            ->rows(2)
            ->columnSpanFull()
            ->maxLength(2000), // ajusta si tu columna es TEXT sin límite práctico

            Hidden::make('start_datetime'),
            Hidden::make('end_datetime'),


            Grid::make(12)->schema([
                DatePicker::make('date')
                    ->label('Fecha')
                    ->native(false)
                    ->format('Y-m-d')
                    ->displayFormat('l, j F, Y')
                    ->closeOnDateSelection(true)
                    ->reactive()
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        if (!$state) return;

                        // ★ normalizar fecha SIEMPRE
                        $date = $this->normalizeDate($state);

                        $sTime = $this->normalizeTime($get('start_time') ?? '08:00');
                        $eTime = $this->normalizeTime($get('end_time')   ?? '08:30');

                        // ★ usar parse tolerante
                        $s = CarbonImmutable::parse("{$date} {$sTime}", $this->calendarTz);
                        $e = CarbonImmutable::parse("{$date} {$eTime}", $this->calendarTz);
                        if ($e->lessThanOrEqualTo($s)) $e = $s->addMinutes(30);

                        $set('start_datetime', $s->format('Y-m-d H:i'));
                        $set('end_datetime',   $e->format('Y-m-d H:i'));
                    })
                    ->columnSpan(6)
                    ->extraAttributes(['class' => 'text-base font-medium text-gray-700 capitalize']),

                Select::make('start_time')
                    ->label('Inicio')
                    ->options($this->timeOptions())
                    ->native(true)
                    ->reactive()
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        $date = $this->normalizeDate($get('date') ?? null);
                        if (!$state || !$date) return;

                        $sTime = $this->normalizeTime($state);
                        $set('start_time', $sTime);

                        $s = CarbonImmutable::parse("{$date} {$sTime}", $this->calendarTz);
                        $set('start_datetime', $s->format('Y-m-d H:i'));

                        $eTime = $this->normalizeTime($get('end_time') ?? $sTime);
                        $e = CarbonImmutable::parse("{$date} {$eTime}", $this->calendarTz);
                        if ($e->lessThanOrEqualTo($s)) {
                            $e = $s->addMinutes(30);
                            $set('end_time', $e->format('H:i'));
                            $set('end_datetime', $e->format('Y-m-d H:i'));
                        }
                    })
                    ->columnSpan(3)
                    ->extraAttributes(['class' => 'w-28']),

                Select::make('end_time')
                    ->label('Fin')
                    ->options($this->timeOptions())
                    ->native(true)
                    ->reactive()
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        $date = $this->normalizeDate($get('date') ?? null);
                        if (!$state || !$date) return;

                        $eTime = $this->normalizeTime($state);
                        $set('end_time', $eTime);

                        $e = CarbonImmutable::parse("{$date} {$eTime}", $this->calendarTz);
                        $set('end_datetime', $e->format('Y-m-d H:i'));

                        $sTime = $this->normalizeTime($get('start_time') ?? $eTime);
                        $s = CarbonImmutable::parse("{$date} {$sTime}", $this->calendarTz);
                        if ($e->lessThanOrEqualTo($s)) {
                            $e = $s->addMinutes(30);
                            $set('end_time', $e->format('H:i'));
                            $set('end_datetime', $e->format('Y-m-d H:i'));
                        }
                    })
                    ->columnSpan(3)
                    ->extraAttributes(['class' => 'w-28']),
            ])->columns(12),
        ];
    }

    /* ---------------- Eventos ---------------- */
    public function fetchEvents(array $fetchInfo): array
    {
        $rangeStart = CarbonImmutable::parse($fetchInfo['start'])->setTimezone($this->calendarTz);
        $rangeEnd   = CarbonImmutable::parse($fetchInfo['end'])->setTimezone($this->calendarTz);

        $startDb = $this->toDbLocal($rangeStart);
        $endDb   = $this->toDbLocal($rangeEnd);

        return Appointment::query()
            ->with(['patient', 'doctor'])
            ->when($this->resolveDoctorId(), fn ($q, $doctorId) => $q->where('doctor_id', $doctorId))
            ->where(function ($q) use ($startDb, $endDb) {
                $q->whereBetween('start_datetime', [$startDb, $endDb])
                  ->orWhereBetween('end_datetime',   [$startDb, $endDb])
                  ->orWhere(function ($q) use ($startDb, $endDb) {
                      $q->where('start_datetime', '<=', $startDb)
                        ->where('end_datetime',   '>=', $endDb);
                  });
            })
            ->get()
            ->map(function (Appointment $a) {
                $startLocal = $this->fromDbLocal($a->getRawOriginal('start_datetime'));
                $endLocal   = $this->fromDbLocal($a->getRawOriginal('end_datetime'));
                $color      = $this->resolveDoctorColor($a->doctor_id ? (int) $a->doctor_id : null);

                return [
                    'id'    => (string) $a->id,
                    'title' => $a->patient?->full_name ?? 'Cita',
                    'start' => $startLocal?->toIso8601String(),
                    'end'   => $endLocal?->toIso8601String(),
                    'editable' => true,
                    'backgroundColor' => $color['background'],
                    'borderColor'     => $color['border'],
                    'textColor'       => $color['text'],
                    'extendedProps' => [
                        'patient_id' => $a->patient_id,
                        'doctor_id'  => $a->doctor_id,
                        'doctor_name' => $a->doctor?->display_name,
                        'colors'      => $color,
                    ],
                ];
            })
            ->all();
    }

    public function config(): array
    {
        return [
            'initialView'  => 'timeGridWeek',
            'allDaySlot'    => false,
            'slotDuration' => '00:30:00',
            'snapDuration' => '00:30:00',
            'nowIndicator' => true,
            'selectable'   => true,
            'editable'     => true,
            'timeZone'     => $this->calendarTz,

            'slotLabelFormat' => [
                'hour'   => 'numeric',
                'minute' => '2-digit',
                'hour12' => true,
            ],

            'eventTimeFormat' => [
                'hour'   => 'numeric',
                'minute' => '2-digit',
                'meridiem' => 'short', // am/pm
                'hour12' => true,
            ],

            'headerToolbar' => [
                'left'   => 'dayGridMonth,timeGridWeek,timeGridDay',
                'center' => 'title',
                'right'  => 'prev,next',
            ],

            'titleFormat' => [
                'year' => 'numeric',
                'month' => 'long',
                'day' => 'numeric',
            ],

            'datesSet' => RawJs::make(<<<'JS'
                function(info) {
                    const isMonthView = info.view.type === 'dayGridMonth';
                    info.view.calendar.setOption('selectable', !isMonthView);
                }
            JS),

            'dateClick' => RawJs::make(<<<'JS'
                function(info) {
                    if (info.view.type === 'dayGridMonth') {
                        info.view.calendar.changeView('timeGridDay', info.date);
                    }
                }
            JS),
        ];
    }

    public function onEventDrop(array $event, array $oldEvent, array $relatedEvents, array $delta, ?array $oldResource, ?array $newResource): bool
    {
        try {
            $appt = Appointment::find($event['id']);
            if (! $appt) return false;

            $start = CarbonImmutable::parse($event['start'])->setTimezone($this->calendarTz);
            $end   = isset($event['end']) ? CarbonImmutable::parse($event['end'])->setTimezone($this->calendarTz) : $start->addMinutes(30);

            $appt->update([
                'start_datetime' => $this->toDbLocal($start),
                'end_datetime'   => $this->toDbLocal($end),
            ]);

            $this->refreshRecords();
            return true;
        } catch (\Throwable $e) {
            Notification::make()->title('No se pudo reprogramar')->body('Error: '.$e->getMessage())->danger()->send();
            return false;
        }
    }

    public function onEventResize(array $event, array $oldEvent, array $relatedEvents = [], array $delta = [], $oldResource = null, $newResource = null): bool
    {
        try {
            $appt = Appointment::find($event['id']);
            if (! $appt) return false;

            $start = CarbonImmutable::parse($event['start'])->setTimezone($this->calendarTz);
            $end   = CarbonImmutable::parse($event['end'])->setTimezone($this->calendarTz);

            $appt->update([
                'start_datetime' => $this->toDbLocal($start),
                'end_datetime'   => $this->toDbLocal($end),
            ]);

            $this->refreshRecords();
            return true;
        } catch (\Throwable $e) {
            Notification::make()->title('No se pudo actualizar la duración')->body('Error: '.$e->getMessage())->danger()->send();
            return false;
        }
    }

    public function onSelect(array $info): void
    {
        $this->mountAction('create', arguments: [
            'start' => $info['start'] ?? null,
            'end'   => $info['end'] ?? null,
        ]);
    }
}
