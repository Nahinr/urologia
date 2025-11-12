<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clinical_backgrounds', function (Blueprint $table) {
            // === NUEVOS CAMPOS EXPEDIENTES V2 ===
            // Título "Historia Clínica" es presentacional (no se guarda)
            $table->text('hea')->nullable()->after('clinical_history'); // Historia de enfermedad actual
            $table->text('app')->nullable()->after('hea');              // Antecedentes personales patológicos

            // Diabetes / Hipertensión / Enfermedad Urológica + tratamiento
            $table->boolean('has_diabetes')->nullable()->after('app');
            $table->string('diabetes_treatment', 255)->nullable()->after('has_diabetes');

            $table->boolean('has_hypertension')->nullable()->after('diabetes_treatment');
            $table->string('hypertension_treatment', 255)->nullable()->after('has_hypertension');

            $table->boolean('has_urologic_disease')->nullable()->after('hypertension_treatment');
            $table->string('urologic_treatment', 255)->nullable()->after('has_urologic_disease');

            // AQx / AGO / AAler
            $table->text('aqx')->nullable()->after('urologic_treatment');   // Antecedentes Quirúrgicos
            $table->text('ago')->nullable()->after('aqx');                   // Antecedentes Gineco-Obstétricos
            $table->text('a_aler')->nullable()->after('ago');                // Alergias (descripción)

            // Examen Físico / Diagnóstico / Tratamiento
            $table->text('physical_exam')->nullable()->after('a_aler');
            $table->text('diagnosis')->nullable()->after('physical_exam');
            $table->text('treatment')->nullable()->after('diagnosis');
        });
    }

    public function down(): void
    {
        Schema::table('clinical_backgrounds', function (Blueprint $table) {
            $table->dropColumn([
                'hea','app',
                'has_diabetes','diabetes_treatment',
                'has_hypertension','hypertension_treatment',
                'has_urologic_disease','urologic_treatment',
                'aqx','ago','a_aler',
                'physical_exam','diagnosis','treatment',
            ]);
        });
    }
};
