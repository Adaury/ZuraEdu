<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sch_profesores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);
            $table->string('apellidos', 80);
            $table->string('email', 120)->nullable()->unique();
            $table->string('especialidad', 100)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('sch_profesores'); }
};
