<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@sge.test'],
            [
                'name'      => 'Admin',
                'apellidos' => 'Sistema',
                'password'  => Hash::make('Admin2030!'),
                'activo'    => true,
            ]
        );
        $admin->syncRoles('Administrador');
    }
}
