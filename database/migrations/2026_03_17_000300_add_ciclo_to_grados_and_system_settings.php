<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add ciclo to grados
        Schema::table('grados', function (Blueprint $table) {
            $table->enum('ciclo', ['primer_ciclo', 'segundo_ciclo'])
                  ->default('primer_ciclo')
                  ->after('nivel');
        });

        // Assign ciclos: grados 1-3 = primer_ciclo, 4-6 = segundo_ciclo
        DB::table('grados')->whereIn('nivel', [1, 2, 3])->update(['ciclo' => 'primer_ciclo']);
        DB::table('grados')->whereIn('nivel', [4, 5, 6])->update(['ciclo' => 'segundo_ciclo']);

        // System settings table
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Seed default settings
        DB::table('system_settings')->insert([
            ['key' => 'system_logo',     'value' => null,                                            'created_at' => now(), 'updated_at' => now()],
            ['key' => 'system_name',     'value' => 'ZuraEdu',                                       'created_at' => now(), 'updated_at' => now()],
            ['key' => 'system_abbr',     'value' => 'ZE',                                            'created_at' => now(), 'updated_at' => now()],
            ['key' => 'session_timeout', 'value' => '120',                                            'created_at' => now(), 'updated_at' => now()],
            ['key' => 'max_login_attempts','value'=> '5',                                             'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::table('grados', function (Blueprint $table) {
            $table->dropColumn('ciclo');
        });
        Schema::dropIfExists('system_settings');
    }
};
