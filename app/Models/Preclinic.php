<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Preclinic extends Model
{
    protected $fillable = [
        'patient_id','user_id','visit_date',
        'bp','hr','rr','weight','sao2',
    ];

    protected $casts = [
        'visit_date' => 'datetime',
        'hr'    => 'integer',
        'rr'    => 'integer',
        'weight'=> 'decimal:2',
        'sao2'  => 'integer',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function user()    { return $this->belongsTo(\App\Models\User::class, 'user_id'); }
}
