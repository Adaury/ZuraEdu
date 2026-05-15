<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE grados MODIFY COLUMN ciclo
             ENUM('inicial','primer_ciclo','segundo_ciclo','bachillerato')
             NOT NULL DEFAULT 'primer_ciclo'"
        );
    }

    public function down(): void
    {
        // Remove any 'inicial' rows first to avoid data-truncation errors
        DB::table('grados')->where('ciclo', 'inicial')->delete();

        DB::statement(
            "ALTER TABLE grados MODIFY COLUMN ciclo
             ENUM('primer_ciclo','segundo_ciclo','bachillerato')
             NOT NULL DEFAULT 'primer_ciclo'"
        );
    }
};
