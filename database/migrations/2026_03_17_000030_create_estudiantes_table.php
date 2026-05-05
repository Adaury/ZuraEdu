<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('numero_matricula', 20)->unique()->notNull();
            $table->string('cedula', 20)->unique()->nullable();
            $table->string('nombres', 100)->notNull();
            $table->string('apellidos', 100)->notNull();
            $table->date('fecha_nacimiento')->notNull();
            $table->enum('sexo', ['M', 'F'])->notNull();
            $table->string('nacionalidad', 50)->default('Dominicana');
            $table->string('lugar_nacimiento', 100)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('direccion')->nullable();
            $table->string('sector', 100)->nullable();
            $table->string('municipio', 100)->nullable();
            $table->string('provincia', 100)->nullable();
            $table->string('foto', 255)->nullable();
            $table->enum('estado', ['activo', 'inactivo', 'egresado', 'transferido'])->default('activo');
            $table->string('tutor_nombre', 150)->nullable();
            $table->string('tutor_parentesco', 50)->nullable();
            $table->string('tutor_telefono', 20)->nullable();
            $table->string('tutor_trabajo', 100)->nullable();
            $table->text('notas_medicas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};
