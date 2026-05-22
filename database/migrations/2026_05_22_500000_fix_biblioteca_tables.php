<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('libros', 'tenant_id')) {
            Schema::table('libros', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index()->after('id');
            });
        }

        if (! Schema::hasColumn('prestamos_biblioteca', 'tenant_id')) {
            Schema::table('prestamos_biblioteca', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index()->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('libros', 'tenant_id')) {
            Schema::table('libros', function (Blueprint $table) {
                $table->dropColumn('tenant_id');
            });
        }

        if (Schema::hasColumn('prestamos_biblioteca', 'tenant_id')) {
            Schema::table('prestamos_biblioteca', function (Blueprint $table) {
                $table->dropColumn('tenant_id');
            });
        }
    }
};
