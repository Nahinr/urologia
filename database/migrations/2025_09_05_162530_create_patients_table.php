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
        Schema::create('patients', function (Blueprint $table) {
            $table->bigIncrements('id');                       // patient_id (PK)
            $table->string('first_name', 100);                 // Nombre
            $table->string('last_name', 100);                  // Apellido
            $table->string('dni', 15)->nullable()->unique();   // 0000-0000-00000
            $table->enum('sex', ['M', 'F', 'Other'])->nullable(); // Sexo
            $table->date('birth_date')->nullable();            // Fecha de nacimiento
            $table->string('phone', 30)->nullable();           // Teléfono
            $table->string('address', 255)->nullable();        // Dirección
            $table->string('occupation', 255)->nullable();     // Ocupación
            $table->softDeletes();                              // deleted_at para archivados
            $table->timestamps();                              // created_at / updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
