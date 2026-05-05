<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ampliar de ENUM a VARCHAR(50) para soportar cualquier pasarela futura
        DB::statement("ALTER TABLE pagos MODIFY COLUMN metodo_pago VARCHAR(50) NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE pagos MODIFY COLUMN metodo_pago ENUM('efectivo','transferencia','tarjeta','stripe','cardnet','otro') NULL");
    }
};
