<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Mensajes de chat por aula ──────────────────────────────────
        Schema::create('classroom_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('clase_virtual_id')->constrained('clases_virtuales')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receptor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('mensaje');
            $table->enum('tipo', ['general', 'privado'])->default('general');
            $table->boolean('fijado')->default(false);
            $table->timestamps();

            $table->index(['clase_virtual_id', 'tipo', 'created_at']);
            $table->index(['clase_virtual_id', 'receptor_id']);
        });

        // ── 2. Campos de videoconferencia en clases_virtuales ─────────────
        Schema::table('clases_virtuales', function (Blueprint $table) {
            $table->string('meeting_url', 300)->nullable()->after('permite_comentarios');
            $table->enum('meeting_status', ['idle', 'active'])->default('idle')->after('meeting_url');
            $table->dateTime('meeting_started_at')->nullable()->after('meeting_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classroom_messages');
        Schema::table('clases_virtuales', function (Blueprint $table) {
            $table->dropColumn(['meeting_url', 'meeting_status', 'meeting_started_at']);
        });
    }
};
