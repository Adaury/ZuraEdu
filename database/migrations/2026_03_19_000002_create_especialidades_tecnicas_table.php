<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('especialidades_tecnicas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('codigo', 20)->unique();
            $table->text('descripcion')->nullable();
            $table->string('color', 7)->default('#1e3a6e');
            $table->string('icono', 50)->default('bi-mortarboard');
            $table->foreignId('coordinador_id')->nullable()->constrained('docentes')->nullOnDelete();
            $table->boolean('activo')->default(true);
            $table->tinyInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('especialidades_tecnicas');
    }
};
