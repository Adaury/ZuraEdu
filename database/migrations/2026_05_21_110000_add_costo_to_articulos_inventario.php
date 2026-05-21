<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articulos_inventario', function (Blueprint $table) {
            $table->decimal('costo_unitario', 10, 2)->nullable()->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('articulos_inventario', function (Blueprint $table) {
            $table->dropColumn('costo_unitario');
        });
    }
};
