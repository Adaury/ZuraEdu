<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->tinyInteger('numero')->unsigned()->notNull();
            $table->string('nombre', 30)->notNull();
            $table->date('fecha_inicio')->notNull();
            $table->date('fecha_fin')->notNull();
            $table->boolean('activo')->default(false);
            $table->boolean('cerrado')->default(false);
            $table->timestamps();

            $table->unique(['school_year_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos');
    }
};
