<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'start_datetime',
        'end_datetime',
        'first_time',
        'observations',
        'google_event_id',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime'   => 'datetime',
        'first_time'     => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
