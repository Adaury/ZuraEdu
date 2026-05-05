<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos_cafeteria', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->decimal('precio', 10, 2);
            $table->enum('categoria', ['comida', 'bebida', 'snack', 'otro'])->default('comida');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos_cafeteria');
    }
};
