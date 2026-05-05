<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 30)->notNull();
            $table->tinyInteger('nivel')->unsigned()->unique()->notNull();
            $table->tinyInteger('orden')->unsigned()->notNull();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grados');
    }
};
