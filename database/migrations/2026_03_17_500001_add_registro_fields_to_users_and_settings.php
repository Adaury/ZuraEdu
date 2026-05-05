<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add registration fields to users
        Schema::table('users', function (Blueprint $table) {
            $table->string('cedula', 15)->nullable()->after('apellidos');
            $table->string('area_trabajo', 30)->nullable()->after('telefono');
            $table->boolean('pendiente_aprobacion')->default(false)->after('activo');
            $table->text('motivo_rechazo')->nullable()->after('pendiente_aprobacion');
        });

        // Add codigo_registro to system_settings
        DB::table('system_settings')->insertOrIgnore([
            'key'        => 'codigo_registro',
            'value'      => 'PSAC2026',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['cedula', 'area_trabajo', 'pendiente_aprobacion', 'motivo_rechazo']);
        });

        DB::table('system_settings')->where('key', 'codigo_registro')->delete();
    }
};
