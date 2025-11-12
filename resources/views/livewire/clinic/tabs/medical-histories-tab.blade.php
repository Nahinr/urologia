<div class="space-y-4">
    <div class="flex justify-between items-center">
        <h3 class="text-base font-semibold">Consultas</h3>
    </div>

    @if($showForm)
        <form wire:submit.prevent="save" class="space-y-4">
            {{ $this->form }}
            <div class="flex gap-2">
                <x-filament::button type="submit">Guardar</x-filament::button>
                <x-filament::button color="gray" wire:click="$set('showForm', false)" type="button">Cancelar</x-filament::button>
            </div>
        </form>
        <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>
    @endif

    <div class="space-y-2">
        @forelse($items as $mh)
            @php($presenter = $presenters[$mh->id] ?? null)
            <x-filament::section>
                <div class="flex items-start justify-between">
                    <div>
                        <div class="font-medium">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span>
                                    <span class="font-semibold">Fecha de consulta: </span>
                                    <span class="text-gray-500">{{ $presenter?->formattedVisitDate() ?? '—' }}</span>
                                </span>
                                <span class="text-slate-400">•</span>
                                <span>
                                    <span class="font-semibold">Hora: </span>
                                    <span class="text-gray-500">{{ $presenter?->formattedVisitTime() ?? '—' }}</span>
                                </span>
                            </div>

                            @if($presenter?->createdAgo())
                                <div class="mt-1 mb-1">
                                    <span class="font-semibold">Creado hace:</span>
                                    <span class="text-gray-500">{{ $presenter->createdAgo() }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="mb-4">
                            <span class="font-semibold">Médico tratante: </span>
                            <span class="text-gray-500">{{ $presenter?->doctorName() ?? '—' }}</span>
                        </div>

                        <div class="mt-2 space-y-2">
                            <div>
                                <span class="font-semibold">Evolución:</span>
                                <span class="text-gray-500">{{ $presenter?->evolution() ?? '—' }}</span>
                            </div>

                            <div>
                                <span class="font-semibold">Examen Físico:</span>
                                <span class="text-gray-500">{{ $presenter?->physicalExam() ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        @can('update', $mh)
                            <x-filament::button size="sm" wire:click="edit({{ $mh->id }})">Editar</x-filament::button>
                        @endcan
                        @can('delete', $mh)
                            <x-filament::button color="danger" size="sm" wire:click="delete({{ $mh->id }})">Eliminar</x-filament::button>
                        @endcan
                    </div>
                </div>
            </x-filament::section>
        @empty
            <x-filament::section>
                <div class="text-sm text-gray-500">No hay consultas registradas.</div>
            </x-filament::section>
        @endforelse
    </div>

    {{ $items->links() }}
</div>
