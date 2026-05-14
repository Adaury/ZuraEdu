<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('token', 64)->unique();
            $table->string('visitor_nombre', 120);
            $table->string('visitor_email', 180)->nullable();
            $table->string('visitor_telefono', 30)->nullable();
            $table->enum('status', ['open', 'closed', 'resolved'])->default('open');
            $table->unsignedBigInteger('atendido_por')->nullable(); // admin user_id
            $table->timestamp('ultimo_mensaje_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'ultimo_mensaje_at']);
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('support_sessions')->cascadeOnDelete();
            $table->text('mensaje');
            $table->enum('origen', ['visitor', 'admin'])->default('visitor');
            $table->unsignedBigInteger('user_id')->nullable(); // admin que respondió
            $table->boolean('leido')->default(false);
            $table->timestamps();

            $table->index(['session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_sessions');
    }
};
