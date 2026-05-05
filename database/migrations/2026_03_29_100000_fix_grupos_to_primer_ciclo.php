<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Todos los grupos deben ser 1ro de Secundaria (primer ciclo)
        // grado_id=1 = 1ro de Secundaria
        DB::table('grupos')->whereIn('id', [1, 2, 3, 4, 5, 6])
            ->update(['grado_id' => 1]);
    }

    public function down(): void
    {
        DB::table('grupos')->where('id', 1)->orWhere('id', 2)->update(['grado_id' => 4]);
        DB::table('grupos')->where('id', 3)->orWhere('id', 4)->update(['grado_id' => 5]);
        DB::table('grupos')->where('id', 5)->orWhere('id', 6)->update(['grado_id' => 6]);
    }
};
