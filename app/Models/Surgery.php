<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Surgery extends Model
{
    protected $fillable = [
        'patient_id',
        'user_id',
        'diagnostico_preoperatorio',
        'diagnostico_postoperatorio',
        'anestesia',
        'fecha_cirugia',
        'lente_intraocular',
        'hallazgos_complicaciones',
        'otros_procedimientos',
        'titulo_descripcion',
        'descripcion_final',
    ];

        protected $casts = [
        'fecha_cirugia' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->user_id) && auth()->check()) {
                $model->user_id = auth()->id();
            }
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
