<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preclinics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('patient_id');         // FK -> patients.id
            $table->unsignedBigInteger('user_id')->nullable(); // quien registró
            $table->dateTime('visit_date')->default(DB::raw('CURRENT_TIMESTAMP'));

            // Campos de Preclínica
            $table->string('bp', 20)->nullable();           // P/A  ej: 120/80
            $table->unsignedSmallInteger('hr')->nullable(); // FC   ej: 75 (lat/min)
            $table->unsignedSmallInteger('rr')->nullable(); // FR   ej: 17 (resp/min)
            $table->decimal('weight', 6, 2)->nullable();    // Peso ej: 75.00 kg
            $table->unsignedTinyInteger('sao2')->nullable();// SatO2 ej: 95 (%)

            $table->timestamps();

            $table->index(['patient_id','visit_date']);
            $table->index('user_id');

            $table->foreign('patient_id')->references('id')->on('patients')
                  ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preclinics');
    }
};
