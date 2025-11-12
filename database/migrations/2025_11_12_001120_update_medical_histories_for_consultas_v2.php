<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_histories', function (Blueprint $table) {
            // Nuevos campos (texto largo)
            if (! Schema::hasColumn('medical_histories', 'evolution')) {
                $table->longText('evolution')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('medical_histories', 'physical_exam')) {
                $table->longText('physical_exam')->nullable()->after('evolution');
            }

            // Eliminar campos viejos que ya no se usarán
            foreach (['findings','refraction_od','refraction_os','refraction_add','tx'] as $col) {
                if (Schema::hasColumn('medical_histories', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        // Solo quitamos los nuevos; NO recreamos los antiguos.
        Schema::table('medical_histories', function (Blueprint $table) {
            foreach (['evolution','physical_exam'] as $col) {
                if (Schema::hasColumn('medical_histories', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
