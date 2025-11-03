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
        Schema::create('contacts', function (Blueprint $table) {
            $table->bigIncrements('id');                 // contact_id (PK) 
            $table->unsignedBigInteger('patient_id');    // FK -> patients.id
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('relationship', 50);          // Mother, Father, Guardian, etc.
            $table->string('phone', 30)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')
                  ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
