<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fotos_album', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->constrained('albumes')->cascadeOnDelete();
            $table->string('ruta');
            $table->string('titulo')->nullable();
            $table->integer('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fotos_album');
    }
};
