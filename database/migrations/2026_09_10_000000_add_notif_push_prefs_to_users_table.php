<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notificaciones Configurables (Fase 5, pieza 3) -- preferencia de push por
 * categoría, por usuario. Columna JSON en vez de tabla aparte: es un solo
 * booleano por categoría (máx. 7 claves), sin campos adicionales como
 * asunto/cuerpo/activa (que sí justificarían una tabla, ver
 * plantillas_comunicacion). Ausente = activo (default-on), ver
 * User::pushActivo().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('notif_push_prefs')->nullable()->after('profile_photo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notif_push_prefs');
        });
    }
};
