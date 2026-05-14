<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('mensaje');
            $table->string('tipo', 20)->default('texto'); // texto | archivo | imagen
            $table->string('archivo_url')->nullable();
            $table->string('archivo_nombre')->nullable();
            $table->boolean('leido_por_todos')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_chat_messages');
    }
};
