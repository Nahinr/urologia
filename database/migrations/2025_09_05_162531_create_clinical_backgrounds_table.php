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
        Schema::create('clinical_backgrounds', function (Blueprint $table) {
            $table->bigIncrements('id');                        // clinical_background_id (PK)
            $table->unsignedBigInteger('patient_id')->unique(); // 1:1 paciente
            $table->unsignedBigInteger('user_id')->nullable();  // Médico que lo registró
            $table->unsignedBigInteger('updated_by')->nullable(); // Última edición

            // Historia clínica
            $table->text('clinical_history')->nullable();

            // Historia médica (medicaciones y antecedentes)
            $table->text('ocular_meds')->nullable();
            $table->text('systemic_meds')->nullable();
            $table->text('allergies')->nullable();
            $table->text('personal_path_history')->nullable();
            $table->text('trauma_surgical_history')->nullable();
            $table->text('ophthalmologic_surgical_history')->nullable();

            // Historia familiar
            $table->text('fam_glaucoma')->nullable();
            $table->text('fam_retinal_detachment')->nullable();
            $table->text('fam_cataract')->nullable();
            $table->text('fam_blindness')->nullable();
            $table->text('fam_diabetes')->nullable();
            $table->text('fam_hypertension')->nullable();
            $table->text('fam_thyroid')->nullable();
            $table->text('fam_anemia')->nullable();
            $table->text('fam_other')->nullable();

            // Agudeza visual (CC)
            $table->string('av_cc_od', 15)->nullable();
            $table->string('av_cc_os', 15)->nullable();

             // Agudeza visual (SC)
            $table->string('av_sc_od', 15)->nullable();
            $table->string('av_sc_os', 15)->nullable();

            // Eyeglasses (receta de lentes) + ADD
            $table->text('rx_od')->nullable();
            $table->text('rx_os')->nullable();
            $table->string('rx_add', 15)->nullable(); // ADD

            // Lensometría (antes Unfometría)
            $table->string('lensometry_od', 60)->nullable();
            $table->string('lensometry_os', 60)->nullable();

            // AV adicional (si la usas aparte de CC/SC)
            $table->string('av_extra_od', 15)->nullable();
            $table->string('av_extra_os', 15)->nullable();

            // ADD cicloplejía
            $table->string('add_cyclo_od', 15)->nullable();
            $table->string('add_cyclo_os', 15)->nullable();

            // Párpados (después de cicloplejía)
            $table->text('eyelids_od')->nullable();
            $table->text('eyelids_os')->nullable();

            // Examen oftalmológico (UI) — campos existentes
            $table->text('bio_cornea_od')->nullable();
            $table->text('bio_cornea_os')->nullable();
            $table->text('bio_ca_od')->nullable();    // cámara anterior
            $table->text('bio_ca_os')->nullable();
            $table->text('bio_iris_od')->nullable();
            $table->text('bio_iris_os')->nullable();
            $table->text('bio_lens_od')->nullable();  // cristalino
            $table->text('bio_lens_os')->nullable();
            $table->text('bio_vitreous_od')->nullable();
            $table->text('bio_vitreous_os')->nullable();

            // Tensión ocular (AP)
            $table->decimal('iop_ap_od', 4, 1)->nullable();
            $table->decimal('iop_ap_os', 4, 1)->nullable();

            // Fondo ocular
            $table->text('fundus_od')->nullable();
            $table->text('fundus_os')->nullable();

            // Conclusiones
            $table->text('clinical_impression')->nullable();
            $table->text('special_tests')->nullable();
            $table->text('disposition_and_treatment')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('updated_by');
            $table->foreign('patient_id')->references('id')->on('patients')
                  ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinical_backgrounds');
    }
};
