<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('surgeries', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('patient_id')
                ->constrained('patients')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Campos clínicos
            $table->string('diagnostico_preoperatorio');
            $table->string('diagnostico_postoperatorio');
            $table->string('anestesia');
            $table->date('fecha_cirugia');
            $table->string('lente_intraocular');
            $table->text('hallazgos_complicaciones')->nullable();
            $table->text('otros_procedimientos')->nullable();

            // Descripción por plantilla (editable)
            $table->string('titulo_descripcion');     // título de plantilla o personalizado
            $table->longText('descripcion_final');    // texto final editable

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surgeries');
    }
};
