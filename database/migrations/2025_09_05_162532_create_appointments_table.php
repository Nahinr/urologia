<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->bigIncrements('id');                 // appointment_id (PK)
            $table->unsignedBigInteger('patient_id');    // FK -> patients.id

            $table->dateTime('start_datetime');          // inicio
            $table->dateTime('end_datetime');            // fin
            $table->boolean('first_time')->default(false); // primera vez (sí/no)
            $table->text('observations')->nullable();    // notas de la cita

            $table->timestamps();

            $table->index(['patient_id', 'start_datetime']);
            $table->foreign('patient_id')->references('id')->on('patients')
                  ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
