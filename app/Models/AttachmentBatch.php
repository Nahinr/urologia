<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttachmentBatch extends Model
{
    protected $fillable = ['patient_id', 'uploaded_by', 'description'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'batch_id');
    }

    protected static function booted(): void
    {
        static::deleting(function (self $batch) {
            foreach ($batch->attachments()->cursor() as $att) {
                $att->delete();
            }
        });
    }
}
