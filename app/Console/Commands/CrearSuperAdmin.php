<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CrearSuperAdmin extends Command
{
    protected $signature   = 'superadmin:crear
                                {--name=Super Admin    : Nombre completo}
                                {--email=admin@zuraedu.com : Correo electrónico}
                                {--password=Admin123*  : Contraseña}
                                {--tenant_id=1         : ID del tenant plataforma}';

    protected $description = 'Crea o actualiza el usuario Super Admin de la plataforma ZuraEdu';

    public function handle(): int
    {
        $name      = $this->option('name');
        $email     = $this->option('email');
        $password  = $this->option('password');
        $tenantId  = (int) $this->option('tenant_id');

        $this->info("Creando Super Admin: {$email}");

        // Crear o actualizar usuario
        $user = User::withoutGlobalScopes()->where('email', $email)->first();

        if ($user) {
            $user->name     = $name;
            $user->password = Hash::make($password);
            $user->activo   = true;
            $user->save();
            $this->line("  → Usuario existente actualizado (id={$user->id})");
        } else {
            $user = new User([
                'name'                 => $name,
                'email'                => $email,
                'password'             => Hash::make($password),
                'activo'               => true,
                'pendiente_aprobacion' => false,
                'must_change_password' => false,
            ]);
            $user->tenant_id = $tenantId;
            $user->save();
            $this->line("  → Usuario creado (id={$user->id})");
        }

        // Asignar rol super_admin
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user->syncRoles([$role]);

        $this->newLine();
        $this->info('✅ Super Admin listo.');
        $this->table(
            ['Campo', 'Valor'],
            [
                ['ID',         $user->id],
                ['Nombre',     $user->name],
                ['Email',      $user->email],
                ['Contraseña', $password],
                ['Rol',        'super_admin'],
                ['Tenant ID',  $user->tenant_id],
            ]
        );
        $this->newLine();
        $this->warn('⚠  Cambia la contraseña en producción después del primer login.');

        return 0;
    }
}
