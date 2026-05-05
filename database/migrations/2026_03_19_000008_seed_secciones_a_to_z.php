<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $letras = range('A', 'Z');

        foreach ($letras as $i => $letra) {
            DB::table('secciones')->updateOrInsert(
                ['nombre' => $letra],
                ['nombre' => $letra, 'orden' => $i + 1, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        // Remove D-Z (keep A, B, C which were the originals)
        DB::table('secciones')->whereNotIn('nombre', ['A', 'B', 'C'])->delete();
    }
};
