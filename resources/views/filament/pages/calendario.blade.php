<x-filament-panels::page>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-end gap-3">
            @if ($canViewAllDoctors)
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                    <span>Doctor</span>
                    <select
                        wire:model.live="doctorId"
                        class="fi-input block w-64 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/40 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                    >
                        @if ($doctorOptions->isEmpty())
                            <option value="">No hay doctores disponibles</option>
                        @else
                            <option value="">Todos los doctores</option>
                            @foreach ($doctorOptions as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        @endif
                    </select>
                </label>
            @elseif ($currentDoctorName)
                <div class="flex flex-col gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                    <span>Doctor</span>
                    <span class="fi-input block w-64 rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 text-sm text-gray-800 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        {{ $currentDoctorName }}
                    </span>
                </div>
            @endif
        </div>

        @livewire(\App\Filament\Widgets\CalendarWidget::class, ['doctorId' => $this->doctorId], key('calendar-widget-' . ($this->doctorId ?? 'all')))
    </div>

    <style>
        /* Altura de cada franja de 30 min */
        .fc .fc-timegrid-slot {
            height: 2.6em; /* ajusta a tu gusto: 2.2em, 2.6em, 3em, etc. */
        }
        /* Ajuste visual del label (am/pm) y alineación */
        .fc .fc-timegrid-slot-label {
            padding-top: 6px;
            font-variant-numeric: tabular-nums;
        }
    </style>
</x-filament-panels::page>

