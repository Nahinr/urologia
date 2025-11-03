<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

class Attachment extends Model
{
    protected $fillable = [
        'patient_id',
        'batch_id',   
        'uploaded_by',
        'disk',
        'path',
        'original_name',
        'mime',
        'size',
        'description',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Conveniencia: devuelve el disk a usar para este registro.
     * (si por alguna razón no viene, usa el configurado globalmente)
     */
    public function storageDisk(): string
    {
        return $this->disk ?: config('uploads.disk');
    }

    public function signedViewUrl(int $minutes = 10): string
    {
        return URL::temporarySignedRoute(
            // OJO: nombre con prefijo del panel:
            'filament.admin.attachments.view',
            now()->addMinutes($minutes),
            ['attachment' => $this->id]
        );
    }    

    public function signedDownloadUrl(int $minutes = 10): string
    {
        return URL::temporarySignedRoute(
            // OJO: nombre con prefijo del panel:
            'filament.admin.attachments.download',
            now()->addMinutes($minutes),
            ['attachment' => $this->id]
        );
    }

    protected static function booted(): void
    {
        static::deleting(function (self $attachment) {
            try {
                $disk = $attachment->storageDisk();
                if ($attachment->path && Storage::disk($disk)->exists($attachment->path)) {
                    Storage::disk($disk)->delete($attachment->path);
                }
            } catch (\Throwable $e) {
                // Loguea si quieres; no impedimos la eliminación del registro
                \Log::warning('Error deleting attachment file: '.$e->getMessage(), ['id' => $attachment->id]);
            }
        });
    }

    public function batch()
    {
        return $this->belongsTo(\App\Models\AttachmentBatch::class, 'batch_id');
    }

}
