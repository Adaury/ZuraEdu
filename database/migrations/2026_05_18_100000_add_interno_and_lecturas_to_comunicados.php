<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comunicados', function (Blueprint $table) {
            $table->boolean('es_interno')->default(false)->after('activo');
            $table->index('es_interno');
        });

        Schema::create('comunicado_lecturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comunicado_id')->constrained('comunicados')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('leido_at');
            $table->unsignedBigInteger('tenant_id')->default(0);
            $table->timestamps();

            $table->unique(['comunicado_id', 'user_id']);
            $table->index(['user_id', 'leido_at']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comunicado_lecturas');
        Schema::table('comunicados', function (Blueprint $table) {
            $table->dropIndex(['es_interno']);
            $table->dropColumn('es_interno');
        });
    }
};
