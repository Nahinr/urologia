<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicalBackground extends Model
{
        protected $fillable = [
        'patient_id',
            // === NUEVOS CAMPOS EXPEDIENTES V2 ===
        'hea','app',
        'has_diabetes','diabetes_treatment',
        'has_hypertension','hypertension_treatment',
        'has_urologic_disease','urologic_treatment',
        'aqx','ago','a_aler',
        'physical_exam','diagnosis','treatment',
            // ====================================
        
        'clinical_history','ocular_meds','systemic_meds','allergies',
        'personal_path_history','trauma_surgical_history','ophthalmologic_surgical_history',
        'fam_glaucoma','fam_retinal_detachment','fam_cataract','fam_blindness',
        'fam_diabetes','fam_hypertension','fam_thyroid','fam_anemia','fam_other',
        'av_cc_od','av_cc_os','av_sc_od','av_sc_os',
        'rx_od','rx_os','rx_add',
        'lensometry_od','lensometry_os',
        'av_extra_od','av_extra_os',
        'add_cyclo_od','add_cyclo_os',
        'eyelids_od','eyelids_os',
        'bio_cornea_od','bio_cornea_os','bio_ca_od','bio_ca_os','bio_iris_od','bio_iris_os',
        'bio_lens_od','bio_lens_os','bio_vitreous_od','bio_vitreous_os',
        'iop_ap_od','iop_ap_os',
        'fundus_od','fundus_os',
        'clinical_impression','special_tests','disposition_and_treatment',
    ];

    protected $casts = [
        'has_diabetes'         => 'boolean',
        'has_hypertension'     => 'boolean',
        'has_urologic_disease' => 'boolean',
    ];
    public function patient() { return $this->belongsTo(Patient::class); }

    public function user() { return $this->belongsTo(\App\Models\User::class, 'user_id'); }

    /** Último editor */
    public function updatedBy() { return $this->belongsTo(\App\Models\User::class, 'updated_by'); }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->user_id)) {
                $model->user_id = auth()->id();
            }
        });

        static::updating(function (self $model) {            
           $model->updated_by = auth()->id();
       });
  }

}
