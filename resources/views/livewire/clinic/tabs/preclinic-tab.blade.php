<div class="space-y-4">
    <div class="flex justify-between items-center">
        <h3 class="text-base font-semibold">Preclínica</h3>
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
        @forelse($items as $item)
            <x-filament::section>
                <div class="flex items-start justify-between">
                    <div class="space-y-2">
                        <div class="font-medium">
                            <span class="font-semibold">Fecha:</span>
                            <span class="text-gray-500">
                                {{ optional($item->visit_date)->timezone(config('app.timezone','America/Tegucigalpa'))->format('d/m/Y g:i a') ?? '—' }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-sm">
                            <div><span class="font-semibold">P/A:</span> <span class="text-gray-500">{{ $item->bp ?? '—' }} mmHg</span></div>
                            <div><span class="font-semibold">FC:</span>  <span class="text-gray-500">{{ $item->hr ?? '—' }} lat/min</span></div>
                            <div><span class="font-semibold">FR:</span>  <span class="text-gray-500">{{ $item->rr ?? '—' }} resp/min</span></div>
                            <div><span class="font-semibold">Peso:</span><span class="text-gray-500">{{ $item->weight ?? '—' }} kg</span></div>
                            <div><span class="font-semibold">SatO2:</span><span class="text-gray-500">{{ $item->sao2 ?? '—' }} %</span></div>
                            <div><span class="font-semibold">Médico:</span><span class="text-gray-500">{{ $item->user->display_name ?? $item->user->name ?? '—' }}</span></div>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        @can('update', $item)
                            <x-filament::button size="sm" wire:click="edit({{ $item->id }})">Editar</x-filament::button>
                        @endcan
                        @can('delete', $item)
                            <x-filament::button color="danger" size="sm" wire:click="delete({{ $item->id }})">Eliminar</x-filiment::button>
                        @endcan
                    </div>
                </div>
            </x-filament::section>
        @empty
            <x-filament::section>
                <div class="text-sm text-gray-500">No hay registros de preclínica.</div>
            </x-filiment::section>
        @endforelse
    </div>

    {{ $items->links() }}
</div>
