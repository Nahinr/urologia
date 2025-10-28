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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->bigIncrements('id');                         // prescription_id (PK)
            $table->unsignedBigInteger('patient_id');            // FK -> patients.id
            $table->unsignedBigInteger('medical_history_id')->nullable(); // FK -> medical_histories.id
            $table->unsignedBigInteger('user_id')->nullable();   // FK -> users.id (quien emitió)

            $table->text('medications_description')->nullable(); // texto libre de medicamentos
            $table->text('diagnosis')->nullable();               // diagnóstico impreso
            $table->dateTime('issued_at')->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->timestamps();

            $table->index(['patient_id', 'issued_at']);
            $table->index(['medical_history_id', 'issued_at']);
            $table->index('user_id');
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('medical_history_id')->references('id')->on('medical_histories')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
