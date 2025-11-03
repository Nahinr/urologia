<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinical_backgrounds', function (Blueprint $table) {
            if (!Schema::hasColumn('clinical_backgrounds', 'ophthalmologic_surgical_history')) {
            $table->text('ophthalmologic_surgical_history')->nullable()->after('trauma_surgical_history');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clinical_backgrounds', function (Blueprint $table) {
            if (Schema::hasColumn('clinical_backgrounds', 'ophthalmologic_surgical_history')) {
            $table->dropColumn('ophthalmologic_surgical_history');
            }
        });
    }
};
