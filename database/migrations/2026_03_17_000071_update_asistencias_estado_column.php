<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE asistencias MODIFY estado ENUM('presente','ausente','tarde','excusa','retiro') NOT NULL DEFAULT 'presente'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE asistencias MODIFY estado ENUM('presente','ausente','tardanza','justificado') NOT NULL DEFAULT 'presente'");
    }
};
