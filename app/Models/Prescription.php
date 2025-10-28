<?php

namespace App\Models;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Mews\Purifier\Facades\Purifier;

class Prescription extends Model
{
     protected $fillable = [
        'patient_id','medical_history_id','medications_description','diagnosis','issued_at',
    ];

    protected $casts = ['issued_at' => 'datetime'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

   public function user()
    {
        return $this->belongsTo(User::class);
    }


    // Nombre legible del médico (si quieres usar $prescription->doctor_name)
    public function getDoctorNameAttribute(): ?string
    {
        if (! $this->relationLoaded('user')) {
            $this->load('user');
        }
        $u = $this->user;
        if (! $u) return null;

        // Si tienes name y last_name, úsalo; si no, cae a name
        $full = trim(($u->name ?? '') . ' ' . ($u->last_name ?? ''));
        return $full !== '' ? $full : ($u->name ?? null);
    }

    public function getDoctorSpecialtyAttribute(): ?string
    {
        if (! $this->relationLoaded('user')) {
            $this->load('user');
        }

        return $this->user?->specialty;
    }

    public function getDiagnosisHtmlAttribute(): string
    {
        return Purifier::clean($this->diagnosis, 'basic'); // usa perfil 'basic'
    }

    public function getMedicationsHtmlAttribute(): string
    {
        return Purifier::clean($this->medications_description, 'basic');
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->user_id)) {
                $model->user_id = auth()->id();
            }
        });
    }
}
