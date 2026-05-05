<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('docentes', function (Blueprint $table) {
            $table->enum('area', ['tecnica', 'administrativa', 'otro'])
                  ->default('otro')
                  ->after('estado');
            $table->string('cargo', 150)->nullable()->after('area');
        });
    }

    public function down(): void
    {
        Schema::table('docentes', function (Blueprint $table) {
            $table->dropColumn(['area', 'cargo']);
        });
    }
};
