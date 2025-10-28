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
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();

            // dueño del documento
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();

            // quién lo subió (usuario autenticado)
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            // almacenamiento y archivo
            $table->string('disk');          // uploads_local | uploads_s3
            $table->string('path');          // ruta relativa dentro del disk
            $table->string('original_name'); // nombre original del archivo
            $table->string('mime', 191)->nullable();
            $table->unsignedBigInteger('size')->nullable();

            // metadatos
            $table->string('description', 500)->nullable();

            $table->timestamps();

            // índices útiles
            $table->index(['patient_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
