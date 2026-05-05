<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mensajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('remitente_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('destinatario_id')->constrained('users')->cascadeOnDelete();
            $table->string('asunto', 200);
            $table->text('cuerpo');
            $table->boolean('leido')->default(false);
            $table->timestamp('leido_en')->nullable();
            $table->boolean('archivado_remitente')->default(false);
            $table->boolean('archivado_destinatario')->default(false);
            $table->foreignId('mensaje_padre_id')->nullable()->constrained('mensajes')->nullOnDelete();
            $table->timestamps();

            $table->index(['destinatario_id', 'leido']);
            $table->index('remitente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes');
    }
};
