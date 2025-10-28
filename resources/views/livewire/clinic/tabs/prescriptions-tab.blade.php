<div class="space-y-4">
    <h3 class="text-base font-semibold">Recetas</h3>

    @if($showForm)
        <form wire:submit.prevent="save" class="space-y-4">
            {{ $this->form }}

            <div class="flex gap-2">
                <x-filament::button type="submit">Guardar</x-filament::button>
                <x-filament::button color="gray" type="button" wire:click="$set('showForm', false)">Cancelar</x-filament::button>
            </div>
        </form>

        <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>
    @endif

    <div class="space-y-2">
        @forelse($items as $rx)
            @php($presenter = $presenters[$rx->id] ?? null)
            <x-filament::section>
                <div class="flex items-start justify-between">
                    <div class="space-y-4">
                        <div class="font-medium">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span>
                                    <span class="font-semibold">Fecha:</span>
                                    <span class="text-gray-500">{{ $presenter?->issuedDate() ?? '—' }}</span>
                                </span>
                                <span class="text-gray-500">•</span>
                                <span>
                                    <span class="font-semibold">Hora:</span>
                                    <span class="text-gray-500">{{ $presenter?->issuedTime() ?? '—' }}</span>
                                </span>
                            </div>

                            @if($presenter?->createdAgo())
                                <div class="mt-1 font-medium">
                                    <span class="font-semibold">Creado hace:</span>
                                    <span class="text-gray-500">{{ $presenter->createdAgo() }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="font-medium">
                            <span class="font-semibold">Médico tratante:</span>
                            <span class="text-gray-500">{{ $presenter?->doctorName() ?? '—' }}</span>
                        </div>

                        <div>
                            <div class="font-semibold uppercase tracking-wide mb-2 mt-5">Datos de receta</div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <div class="font-semibold mb-1">Diagnóstico</div>
                                    <div class="prose prose-sm max-w-none">{!! $presenter?->diagnosis() ?? '—' !!}</div>
                                </div>

                                <div>
                                    <div class="font-semibold mb-1">Medicamentos e indicaciones</div>
                                    <div class="prose prose-sm max-w-none">{!! $presenter?->medications() ?? '—' !!}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        @can('print', $rx)
                            <x-filament::button size="sm" tag="a"
                                href="{{ URL::signedRoute('filament.admin.prescriptions.pdf', ['prescription' => $rx->id]) }}"
                                target="_blank"
                                icon="heroicon-o-printer">
                                Imprimir
                            </x-filament::button>
                        @endcan

                        @can('update', $rx)
                            <x-filament::button size="sm" wire:click="edit({{ $rx->id }})">Editar</x-filament::button>
                        @endcan

                        @can('delete', $rx)
                            <x-filament::button color="danger" size="sm" wire:click="delete({{ $rx->id }})">Eliminar</x-filament::button>
                        @endcan
                    </div>
                </div>
            </x-filament::section>
        @empty
            <x-filament::section>
                <div class="text-sm text-gray-500">No hay recetas registradas.</div>
            </x-filament::section>
        @endforelse
    </div>

    {{ $items->links() }}
</div>
