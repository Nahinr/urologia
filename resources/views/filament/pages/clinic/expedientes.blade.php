<x-filament-panels::page>

    {{-- BLOQUE STICKY (fondo sólido) --}}
    <div
        x-data
        wire:keydown.window.ctrl.s.prevent="$dispatch('save-clinical-background')"
        wire:keydown.window.meta.s.prevent="$dispatch('save-clinical-background')"

        class="sticky top-16 z-10 bg-white dark:bg-gray-900
               border-b border-gray-200 dark:border-gray-800"
    >
        <div class="px-4 py-3 space-y-3">
            {{-- 1) Buscador: SIEMPRE visible --}}
            @canany(['clinical-background.view', 'history.view', 'prescription.view', 'patient.attachments.view'])
                <div class="w-full max-w-3xl">
                    <livewire:patient-search :selected="$patient?->id" />
                </div>
            @endcanany

            {{-- 2) Mini head + pestañas + acciones: SOLO si hay paciente --}}
            @if ($patient)
                <x-filament::section class="mb-1">
                    <style>
                        .patient-grid{ display:grid; gap:.5rem 2rem; }
                        @media (max-width: 767.98px){ .patient-grid{ grid-template-columns: 1fr !important; } }
                        @media (min-width: 768px){ .patient-grid{ grid-template-columns: repeat(5,minmax(0,1fr)) !important; } }
                        .truncate-ellipsis{ overflow:hidden; text-overflow:ellipsis; white-space:nowrap; min-width:0; }
                    </style>

                    <div class="w-full">
                        <h2 class="text-lg font-semibold mb-2">
                            {{ $patient->display_name }}
                        </h2>

                        <div class="patient-grid text-sm text-gray-800 w-full">
                            <div class="space-y-1">
                                <div><span class="font-semibold">DNI:</span> {{ $patient->dni ?? '—' }}</div>
                                <div class="truncate-ellipsis"><span class="font-semibold">Teléfono:</span> {{ $patient->primary_phone ?? '—' }}</div>
                                <div><span class="font-semibold">Género:</span> {{ $patient->gender_label ?? '—' }}</div>
                            </div>

                            <div class="space-y-1">
                                <div>
                                    <span class="font-semibold">Nacimiento:</span>
                                    {{ $patient->birth_date ? \Carbon\Carbon::parse($patient->birth_date)->format('d/m/Y') : '—' }}
                                </div>
                                <div><span class="font-semibold">Edad:</span> {{ $patient->age_full ?? '—' }}</div>
                            </div>

                            <div class="space-y-1">
                                <div class="truncate-ellipsis"><span class="font-semibold">Ocupación:</span> {{ $patient->occupation ?? '—' }}</div>
                                <div class="truncate-ellipsis"><span class="font-semibold">Dirección:</span> {{ $patient->address ?? '—' }}</div>
                            </div>

                            <div class="space-y-1">
                                <div class="truncate-ellipsis"><span class="font-semibold">Encargado:</span> {{ $patient->primary_contact_name ?? '—' }}</div>
                                <div class="truncate-ellipsis"><span class="font-semibold">Teléfono Enc:</span> {{ $patient->primary_contact_phone ?? '—' }}</div>
                            </div>

                            <div class="space-y-1">
                                <div class="truncate-ellipsis"><span class="font-semibold">Parentesco:</span> {{ $patient->primary_contact_relation ?? '—' }}</div>
                            </div>
                        </div>
                    </div>
                </x-filament::section>

                {{-- Pestañas + botón a la derecha --}}
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1">
                        <x-filament::tabs class="!gap-1 !justify-start w-full">

                            @can('clinical-background.view')
                            <x-filament::tabs.item
                                :active="$tab === 'antecedentes'"
                                wire:click="setTab('antecedentes')"
                                class="!px-4 !py-2 !rounded-md"
                                :class="$tab === 'antecedentes'
                                    ? '!text-white !shadow-sm'
                                    : '!text-slate-700'">
                                Antecedentes
                            </x-filament::tabs.item>
                            @endcan

                            @can('history.view')
                            <x-filament::tabs.item
                                :active="$tab === 'consultas'"
                                wire:click="setTab('consultas')"
                                class="!px-4 !py-2 !rounded-md"
                                :class="$tab === 'consultas'
                                    ? '!text-white !shadow-sm'
                                    : '!text-slate-700'">
                                Consultas
                            </x-filament::tabs.item>
                            @endcan

                            @can('prescription.view')
                            <x-filament::tabs.item
                                :active="$tab === 'recetas'"
                                wire:click="setTab('recetas')"
                                class="!px-4 !py-2 !rounded-md"
                                :class="$tab === 'recetas'
                                    ? '!text-white !shadow-sm'
                                    : '!text-slate-700'">
                                Recetas
                            </x-filament::tabs.item>
                            @endcan

                            @can('patient.attachments.viewAny')
                            <x-filament::tabs.item
                                :active="$tab === 'imagenes'"
                                wire:click="setTab('imagenes')"
                                class="!px-4 !py-2 !rounded-md"
                                :class="$tab === 'imagenes'
                                    ? '!text-white !shadow-sm'
                                    : '!text-slate-700'">
                                Imágenes
                            </x-filament::tabs.item>
                            @endcan

                        </x-filament::tabs>

                    </div>

                    <div class="flex items-center gap-2">
                        @if ($tab === 'antecedentes')
                            @can('clinical-background.update')
                            <x-filament::button
                                icon="heroicon-o-cloud-arrow-up"
                                wire:click="$dispatch('save-clinical-background')"
                            >
                                Guardar cambios
                            </x-filament::button>
                            @endcan

                        @elseif ($tab === 'consultas')
                            @can('history.create')
                            <x-filament::button
                                icon="heroicon-o-plus"
                                wire:click="$dispatch('open-create-consulta')"
                            >
                                Nueva consulta
                            </x-filament::button>
                            @endcan

                        @elseif ($tab === 'recetas')
                            @can('prescription.create')
                            <x-filament::button
                                icon="heroicon-o-plus"
                                wire:click="$dispatch('open-create-prescription')"
                            >
                                Nueva receta
                            </x-filament::button>
                            @endcan

                        @elseif ($tab === 'imagenes')
                            @can('patient.attachments.create')
                            <x-filament::button
                                icon="heroicon-o-plus"
                                wire:click="$dispatch('open-create-image')"
                            >
                                Agregar
                            </x-filament::button>
                            @endcan
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- CONTENIDO debajo del sticky --}}
    <div class="mt-4">
        @if ($patient)
            @if ($tab === 'antecedentes')
                @can('clinical-background.view')
                    <livewire:clinic.tabs.clinical-background-tab :patientId="$patient->id" />
                @endcan
            @elseif ($tab === 'consultas')
                @can('history.view')
                    <livewire:clinic.tabs.medical-histories-tab :patientId="$patient->id" />
                @endcan
            @elseif ($tab === 'recetas')
                @can('prescription.view')
                    <livewire:clinic.tabs.prescriptions-tab :patientId="$patient->id" />
                @endcan
            @elseif ($tab === 'imagenes')
                @can('patient.attachments.viewAny')
                    <livewire:clinic.tabs.images-tab :patientId="$patient->id" />
                @endcan
            @endif
        @else
            <x-filament::section>
                <p class="text-sm text-gray-500">Busca un paciente para cargar su expediente.</p>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
