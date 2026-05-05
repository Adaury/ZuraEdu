<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Seccion;

class SeccionesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range('A', 'Z') as $i => $letra) {
            Seccion::firstOrCreate(
                ['nombre' => $letra],
                ['orden'  => $i + 1]
            );
        }
    }
}
