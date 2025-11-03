<div class="space-y-4">
    <h3 class="text-base font-semibold">Cirugías</h3>

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
        @forelse($items as $sx)
            @php($p = $presenters[$sx->id] ?? null)
            <x-filament::section>
                <div class="flex items-start justify-between">
                    <div class="space-y-2">
                        <div class="font-medium">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span>
                                    <span class="font-semibold">Fecha:</span>
                                    <span class="text-gray-500">{{ $p?->date() ?? '—' }}</span>
                                </span>
                                <span class="text-gray-500">•</span>
                                <span>
                                    <span class="font-semibold">Dx post-operatorio:</span>
                                    <span class="text-gray-500">{{ $p?->dxPost() ?? '—' }}</span>
                                </span>
                                <span class="text-gray-500">•</span>
                                <span>
                                    <span class="font-semibold">Lente IO:</span>
                                    <span class="text-gray-500">{{ $p?->lens() ?? '—' }}</span>
                                </span>
                            </div>
                        </div>

                        @if($p?->createdAgo())
                            <div class="text-sm">
                                <span class="font-semibold">Creado hace:</span>
                                <span class="text-gray-500">{{ $p->createdAgo() }}</span>
                            </div>
                        @endif

                        <div class="text-sm text-gray-600">
                            <span class="font-semibold">Cirujano:</span> {{ $p?->surgeon() ?? '—' }}
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        @can('print', $sx)
                            <x-filament::button
                                size="sm"
                                color="warning"
                                tag="a"
                                href="{{ URL::signedRoute('filament.admin.surgeries.pdf', ['surgery' => $sx->id]) }}"
                                target="_blank"
                                icon="heroicon-o-printer"
                            >
                                Imprimir
                            </x-filament::button>
                        @endcan

                        @can('update', $sx)
                            <x-filament::button
                                size="sm"
                                color="warning"
                                icon="heroicon-o-pencil-square"
                                wire:click="edit({{ $sx->id }})"
                            >
                                Editar
                            </x-filament::button>
                        @endcan

                        @can('delete', $sx)
                            <x-filament::button
                                size="sm"
                                color="danger"
                                icon="heroicon-o-trash"
                                wire:click="delete({{ $sx->id }})"
                            >
                                Eliminar
                            </x-filament::button>
                        @endcan
                    </div>
                </div>
            </x-filament::section>
        @empty
            <x-filament::section>
                <p class="text-sm text-gray-500">No hay cirugías registradas para este paciente.</p>
            </x-filament::section>
        @endforelse
    </div>

    <div>
        {{ $items->links() }}
    </div>
</div>
