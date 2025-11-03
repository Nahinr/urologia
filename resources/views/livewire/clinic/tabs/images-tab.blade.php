<div class="space-y-6">

{{-- MODAL de subida (Filament Form) --}}
    @if ($showUploader)
    <div class="fixed inset-0 bg-black/30 z-40"></div>

    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-base font-semibold">Nueva carpeta de documentos</h3>
            <button type="button" class="text-gray-500 hover:text-gray-700" wire:click="closeUploader">✕</button>
        </div>

        {{-- Aquí se renderiza el formulario de Filament --}}
        {{ $this->form }}

        <div class="mt-4 flex justify-end gap-2">
            <button type="button" wire:click="closeUploader" class="px-3 py-1.5 text-sm rounded-md border">
            Cancelar
            </button>
            <x-filament::button
            wire:click="saveBatch"
            wire:loading.attr="disabled"
            >
            Guardar
            </x-filament::button>
        </div>
        </div>
    </div>
    @endif


    {{-- LISTA de carpetas (una columna) --}}
    <div class="space-y-4">
        @forelse ($batches as $batch)
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-1">
                        <div class="text-base font-semibold">
                            {{ $batch->description ?: 'Carpeta sin título' }}
                        </div>
                        <div class="text-xs text-gray-600">
                            {{ $batch->attachments_count }} {{ \Illuminate\Support\Str::plural('documento', $batch->attachments_count) }}
                        </div>
                        <div class="text-xs text-gray-500">
                            Creada: {{ $batch->created_at->format('d/m/Y H:i') }}
                            · Por: {{ $batch->uploader?->display_name ?? '—' }}
                        </div>
                    </div>

                    <div class="flex-shrink-0 flex items-center gap-2">
                        @can('patient.attachments.view')
                            <x-filament::button color="gray" size="sm" wire:click="openBatch({{ $batch->id }})" icon="heroicon-o-eye">
                                Ver
                            </x-filament::button>
                        @endcan
                        @can('patient.attachments.update')
                            <x-filament::button color="gray" size="sm" wire:click="openEdit({{ $batch->id }})" icon="heroicon-o-pencil-square">
                                Editar
                            </x-filament::button>
                        @endcan
                        @can('patient.attachments.delete')
                            <x-filament::button color="danger" size="sm" wire:click="confirmDelete({{ $batch->id }})" icon="heroicon-o-trash">
                                Eliminar
                            </x-filament::button>
                        @endcan
                    </div>
                </div>
            </div>
        @empty
            <div class="text-sm text-gray-500">Aún no hay carpetas de documentos para este paciente.</div>
        @endforelse
    </div>

    <div class="mt-3">
        {{ $batches->links() }}
    </div>

    {{-- MODAL "Ver" carpeta --}}
    @if ($currentBatch)
    <div class="fixed inset-0 bg-black/30 z-40"></div>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl p-5">
        <div class="flex items-center justify-between mb-3">
            <div>
            <h3 class="text-base font-semibold">{{ $currentBatch->description ?: 'Carpeta sin título' }}</h3>
            <p class="text-xs text-gray-500">
                Creada: {{ $currentBatch->created_at->format('d/m/Y H:i') }} · Por: {{ $currentBatch->uploader?->display_name ?? '—' }}
            </p>
            </div>
            <button class="text-gray-500 hover:text-gray-700" wire:click="closeBatch">✕</button>
        </div>

        {{-- GRID con miniaturas --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            @foreach ($currentBatch->attachments as $att)
                <div class="border rounded-lg p-2 bg-white">
                    {{-- Contenedor cuadrado con posicionamiento relativo --}}
                    <div class="aspect-square relative w-full overflow-hidden rounded">
                        @if (str_starts_with($att->mime, 'image/'))
                            <img
                                src="{{ $att->signedViewUrl(5) }}"
                                alt="{{ $att->original_name }}"
                                class="absolute inset-0 h-full w-full object-cover"
                                loading="lazy"
                            >
                        @elseif ($att->mime === 'application/pdf')
                            {{-- PDF embebido en miniatura --}}
                            <iframe
                                src="{{ $att->signedViewUrl(5) }}#toolbar=0&navpanes=0&scrollbar=0"
                                class="absolute inset-0 h-full w-full"
                                style="border:0"
                                loading="lazy"
                                referrerpolicy="no-referrer"
                            ></iframe>
                            {{-- Fallback por si el navegador bloquea el iframe --}}
                            <noscript>
                                <a href="{{ $att->signedViewUrl(5) }}" target="_blank" class="text-xs underline">Abrir PDF</a>
                            </noscript>
                        @else
                            <div class="absolute inset-0 flex items-center justify-center text-xs text-gray-600">
                                {{ strtoupper(pathinfo($att->original_name, PATHINFO_EXTENSION) ?: 'Archivo') }}
                            </div>
                        @endif
                    </div>

                    <div class="mt-2 text-xs truncate" title="{{ $att->original_name }}">{{ $att->original_name }}</div>

                    <div class="mt-2 flex gap-2">
                        @can('patient.attachments.view')
                            <a href="{{ $att->signedViewUrl(5) }}" target="_blank" class="px-2 py-1 text-xs rounded-md border">Ver</a>
                        @endcan
                        @can('patient.attachments.download')
                            <a href="{{ $att->signedDownloadUrl(5) }}" target="_blank" class="px-2 py-1 text-xs rounded-md border">Descargar</a>
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 flex justify-end">
            <button class="px-3 py-1.5 text-sm rounded-md border" wire:click="closeBatch">Cerrar</button>
        </div>
        </div>
    </div>
    @endif


    {{-- MODAL editar carpeta --}}
    @if ($showEdit)
    <div class="fixed inset-0 bg-black/30 z-40"></div>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-base font-semibold">Editar carpeta</h3>
            <button class="text-gray-500 hover:text-gray-700" wire:click="closeEdit">✕</button>
        </div>

        {{-- Renderiza el formulario nombrado --}}
        {{ $this->getEditForm() }}

        {{-- Miniaturas de archivos EXISTENTES en la carpeta --}}
        @php $batch = \App\Models\AttachmentBatch::with('attachments')->find($editBatchId); @endphp
            @if($batch)
                <div class="mt-4">
                    <div class="text-xs text-gray-600 mb-2">Archivos actuales</div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                            @foreach($batch->attachments as $att)
                            <div class="border rounded-lg p-2 bg-white">
                                <div class="aspect-square w-full overflow-hidden rounded">
                                    @if(str_starts_with($att->mime, 'image/'))
                                        <img src="{{ $att->signedViewUrl(5) }}" alt="{{ $att->original_name }}" class="h-full w-full object-cover">
                                    @elseif($att->mime === 'application/pdf')
                                        <iframe
                                            src="{{ $att->signedViewUrl(5) }}"
                                            class="h-full w-full"
                                            style="border:0"
                                            loading="lazy"
                                        ></iframe>
                                    @else
                                        <div class="h-full w-full flex items-center justify-center text-xs text-gray-600"> {{-- imagen u otro --}}</div>
                                    @endif
                                    </div>
                                    <div class="mt-2 text-[11px] truncate" title="{{ $att->original_name }}">{{ $att->original_name }}</div>
                                </div>
                            @endforeach
                        </div>
                </div>
            @endif

        <div class="mt-4 flex justify-end gap-2">
            <button type="button" wire:click="closeEdit" class="px-3 py-1.5 text-sm rounded-md border">
            Cancelar
            </button>
            <x-filament::button wire:click="saveEdit" wire:loading.attr="disabled">
            Guardar cambios
            </x-filament::button>
        </div>
        </div>
    </div>
    @endif

    {{-- MODAL Confirmar eliminación --}}
    @if ($showDeleteConfirm)
        <div class="fixed inset-0 bg-black/30 z-40"></div>
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-5">
            <h3 class="text-base font-semibold">Eliminar carpeta</h3>
            <p class="mt-2 text-sm text-gray-600">¿Seguro que deseas eliminar esta carpeta y todos sus documentos? Esta acción no se puede deshacer.</p>
            <div class="mt-4 flex justify-end gap-2">
                <button class="px-3 py-1.5 text-sm rounded-md border" wire:click="cancelDelete">Cancelar</button>
                <x-filament::button color="danger" wire:click="doDelete" wire:loading.attr="disabled">Eliminar</x-filament::button>
            </div>
            </div>
        </div>
    @endif


</div>
