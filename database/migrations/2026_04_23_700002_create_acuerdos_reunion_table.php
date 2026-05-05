<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acuerdos_reunion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reunion_id')->constrained('reuniones')->cascadeOnDelete();
            $table->text('descripcion');
            $table->string('responsable')->nullable();
            $table->date('fecha_limite')->nullable();
            $table->boolean('cumplido')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acuerdos_reunion');
    }
};
