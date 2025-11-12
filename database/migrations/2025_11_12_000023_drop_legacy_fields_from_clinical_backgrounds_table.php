<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
            Schema::table('clinical_backgrounds', function (Blueprint $table) {
                $table->dropColumn([
                'ocular_meds','systemic_meds','allergies',
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
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clinical_backgrounds', function (Blueprint $table) {
            //
        });
    }
};
