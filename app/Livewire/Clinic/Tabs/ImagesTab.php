<?php

namespace App\Livewire\Clinic\Tabs;

use Livewire\WithPagination;

use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Attachment;
use App\Models\AttachmentBatch;

class ImagesTab extends PatientTab
{
    use WithPagination;

    /** Modal de subida (usaremos Filament Form) */
    public bool $showUploader = false;
    public bool $showEdit = false;
    public ?int $editBatchId = null;
    public ?string $editDescription = null;
    public ?array $editData = ['description' => null, 'files' => []];

    protected function requiredPermission(): ?string
    {
        return 'patient.attachments.view';
    }

    /** Estado del form de Filament */
    public ?array $data = [
        'files' => [],
        'description' => null,
    ];

    /** Drawer/Modal de una carpeta */
    public ?int $openBatchId = null;

    public int $perPage = 9;

    protected $listeners = [
        'open-create-image' => 'openUploader',
    ];

    protected function bootedPatientTab(): void
    {
        // Inicializa el form
        $this->form->fill($this->data);
    }

    /** ---------- Filament Form (FileUpload) ---------- */
    public function form(Form $form): Form
    {
        $disk = config('uploads.disk');
        $base = config('uploads.base_path', 'patients');
        $ym   = now()->format('Y/m');
        $dir  = "{$base}/{$this->patientId}/{$ym}";

        return $form
            ->statePath('data')
            ->schema([
                FileUpload::make('files')
                    ->label('Archivos')
                    ->multiple()
                    ->required()
                    ->disk($disk)
                    ->directory($dir)
                    ->visibility('private')
                    ->preserveFilenames() // para poder usar el nombre original desde el path
                    ->acceptedFileTypes(['image/jpeg','image/png','image/webp','application/pdf'])
                    ->maxSize(10240), // 10MB por archivo

                Textarea::make('description')
                    ->label('Descripción (carpeta)')
                    ->rows(2)
                    ->maxLength(500),
            ]);
    }



    /** Abre/cierra modal */
    public function openUploader(): void
    {
        abort_unless(auth()->user()?->can('patient.attachments.create'), 403);

        $this->reset('data');
        $this->data = ['files' => [], 'description' => null];
        $this->form->fill($this->data);

        $this->showUploader = true;
    }

    public function closeUploader(): void
    {
        $this->showUploader = false;
    }

    /** Guardar “carpeta” y adjuntos desde FileUpload */
    public function saveBatch(): void
    {
        abort_unless(auth()->user()?->can('patient.attachments.create'), 403);

        $state = $this->form->getState(); // ['files' => [ 'patients/1/2025/09/archivo.pdf', ... ], 'description' => '...']

        $paths = $state['files'] ?? [];
        if (empty($paths)) {
            $this->dispatch('toast', type: 'warning', message: 'Seleccione al menos un archivo.');
            return;
        }

        // 1) Crear carpeta
        $batch = AttachmentBatch::create([
            'patient_id'  => $this->patientId,
            'uploaded_by' => auth()->id(),
            'description' => $state['description'] ?? null,
        ]);

        // 2) Crear registros de attachments a partir de los paths
        $disk = config('uploads.disk');
        foreach ($paths as $path) {
            // nombre original (como preservamos nombres, el basename es el original)
            $original = basename($path);
            $size     = Storage::disk($disk)->size($path) ?: null;
            $mime     = Storage::disk($disk)->mimeType($path) ?: null;

            Attachment::create([
                'patient_id'    => $this->patientId,
                'batch_id'      => $batch->id,
                'uploaded_by'   => auth()->id(),
                'disk'          => $disk,
                'path'          => $path,
                'original_name' => $original,
                'mime'          => $mime,
                'size'          => $size,
                'description'   => $state['description'] ?? null,
            ]);
        }

        // reset UI
        $this->showUploader = false;
        $this->resetPage();

        $this->dispatch('toast', type: 'success', message: 'Carpeta creada y documentos subidos.');
    }

    /** Abrir/cerrar/eliminar carpeta */
    public function openBatch(int $batchId): void
    {
        abort_unless(auth()->user()?->can('patient.attachments.view'), 403);
        $this->openBatchId = $batchId;
    }

    public function closeBatch(): void
    {
        $this->openBatchId = null;
    }

    public function deleteBatch(int $batchId): void
    {
        abort_unless(auth()->user()?->can('patient.attachments.delete'), 403);

        $batch = AttachmentBatch::where('patient_id', $this->patientId)->findOrFail($batchId);
        foreach ($batch->attachments as $att) {
            $att->delete(); // el hook del modelo borra el archivo físico
        }
        $batch->delete();

        if ($this->openBatchId === $batchId) {
            $this->openBatchId = null;
        }

        $this->dispatch('toast', type: 'success', message: 'Carpeta eliminada.');
        $this->resetPage();
    }

    /** Data */
    public function getBatchesProperty()
    {
        return AttachmentBatch::query()
            ->where('patient_id', $this->patientId)
            ->withCount('attachments')
            ->with(['uploader:id,name'])
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
    }

    public function getCurrentBatchProperty(): ?AttachmentBatch
    {
        if (! $this->openBatchId) return null;

        return AttachmentBatch::with(['attachments' => function ($q) {
            $q->orderBy('created_at', 'asc');
        }, 'uploader:id,name'])
            ->where('patient_id', $this->patientId)
            ->find($this->openBatchId);
    }

    public function render()
    {
        $this->authorizeTab();
        return view('livewire.clinic.tabs.images-tab', [
            'batches' => $this->batches,
            'currentBatch' => $this->currentBatch,
        ]);
    }

    public function getEditForm(): Form
    {
        $disk = config('uploads.disk');
        $base = config('uploads.base_path', 'patients');
        $dir  = "{$base}/{$this->patientId}/" . now()->format('Y/m');

        return $this->makeForm()                // <- clave
            ->statePath('editData')
            ->schema([
                \Filament\Forms\Components\Textarea::make('description')
                    ->label('Descripción (carpeta)')
                    ->rows(2)
                    ->maxLength(500),

                \Filament\Forms\Components\FileUpload::make('files')
                    ->label('Agregar archivos (opcional)')
                    ->multiple()
                    ->disk($disk)
                    ->directory($dir)
                    ->visibility('private')
                    ->preserveFilenames()
                    ->acceptedFileTypes(['image/jpeg','image/png','image/webp','application/pdf'])
                    ->maxSize(10240),
            ]);
    }

    public function openEdit(int $batchId): void
    {
        abort_unless(auth()->user()?->can('patient.attachments.update'), 403);

        $batch = AttachmentBatch::where('patient_id', $this->patientId)->findOrFail($batchId);
        $this->editBatchId = $batch->id;
        $this->editData = ['description' => $batch->description, 'files' => []];
        $this->getEditForm()->fill($this->editData);

        $this->showEdit = true;
    }

    public function closeEdit(): void
    {
        $this->showEdit = false;
        $this->editBatchId = null;
        $this->editData = ['description' => null, 'files' => []];
    }

    public function saveEdit(): void
    {
        abort_unless(auth()->user()?->can('patient.attachments.update'), 403);

        $state = $this->getEditForm()->getState(); // ['description' => ..., 'files' => [paths...]]

        $batch = AttachmentBatch::where('patient_id', $this->patientId)->findOrFail($this->editBatchId);
        $batch->update(['description' => $state['description'] ?? null]);

        $paths = $state['files'] ?? [];
        if (! empty($paths)) {
            $disk = config('uploads.disk');
            foreach ($paths as $path) {
                Attachment::create([
                    'patient_id'    => $this->patientId,
                    'batch_id'      => $batch->id,
                    'uploaded_by'   => auth()->id(),
                    'disk'          => $disk,
                    'path'          => $path,
                    'original_name' => basename($path),
                    'mime'          => Storage::disk($disk)->mimeType($path) ?: null,
                    'size'          => Storage::disk($disk)->size($path) ?: null,
                    'description'   => $state['description'] ?? null,
                ]);
            }
        }

        $this->closeEdit();
        $this->dispatch('toast', type: 'success', message: 'Carpeta actualizada.');
    }

      /** ---------- Confirmación de eliminación (modal) ---------- */
    public bool $showDeleteConfirm = false;
    public ?int $deleteBatchId = null;

    public function confirmDelete(int $batchId): void
    {
        abort_unless(auth()->user()?->can('patient.attachments.delete'), 403);
        $this->deleteBatchId = $batchId;
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteBatchId = null;
    }

    public function doDelete(): void
    {
        abort_unless(auth()->user()?->can('patient.attachments.delete'), 403);

        DB::transaction(function () {
            $batch = AttachmentBatch::where('patient_id', $this->patientId)
                ->findOrFail($this->deleteBatchId);

            // El hook del modelo se encarga de borrar attachments + archivos
            $batch->delete();
        });

        $this->showDeleteConfirm = false;
        $this->deleteBatchId = null;
        $this->openBatchId = null;

        $this->dispatch('toast', type: 'success', message: 'Carpeta eliminada.');
        $this->resetPage();
    }
}
