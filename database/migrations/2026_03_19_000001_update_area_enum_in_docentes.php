<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE docentes MODIFY COLUMN area
            ENUM('academica','tecnica','ambas','administrativa','otro')
            DEFAULT 'otro'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE docentes MODIFY COLUMN area
            ENUM('tecnica','administrativa','otro')
            DEFAULT 'otro'");
    }
};
