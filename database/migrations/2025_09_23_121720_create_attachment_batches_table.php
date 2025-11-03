<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attachment_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('description', 500)->nullable(); // descripción de la “carpeta”
            $table->timestamps();

            $table->index(['patient_id', 'created_at']);
        });

        Schema::table('attachments', function (Blueprint $table) {
            $table->foreignId('batch_id')
                ->after('patient_id')
                ->nullable()
                ->constrained('attachment_batches')
                ->nullOnDelete();

            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batch_id');
            $table->dropIndex(['batch_id']);
        });
        Schema::dropIfExists('attachment_batches');
    }
};
