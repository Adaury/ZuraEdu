<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entregas_classroom', function (Blueprint $table) {
            // Ampliar estados
            $table->string('estado')->default('pendiente')->change();

            // Campos adicionales
            $table->boolean('devuelta')->default(false)->after('comentario_docente');
            $table->text('retroalimentacion')->nullable()->after('devuelta');
            $table->integer('intentos')->default(1)->after('retroalimentacion');
            $table->dateTime('fecha_revision')->nullable()->after('intentos');
            $table->foreignId('revisado_por')->nullable()->after('fecha_revision')
                  ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('entregas_classroom', function (Blueprint $table) {
            $table->dropForeign(['revisado_por']);
            $table->dropColumn(['devuelta', 'retroalimentacion', 'intentos',
                                'fecha_revision', 'revisado_por']);
        });
    }
};
