<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalHistory extends Model
{
    protected $fillable = [
        'patient_id','user_id','findings','refraction_od','refraction_os','refraction_add','tx','visit_date',
    ];

    protected $casts = [
        'visit_date' => 'datetime',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function prescriptions() { return $this->hasMany(Prescription::class); }
    public function user() { return $this->belongsTo(User::class); }

    // helpers
    public function getLabelAttribute(): string
    {
        $date = optional($this->visit_date)->format('d/m/Y H:i') ?: 's/f';
        return "#{$this->id} · {$date}";
    }
}
