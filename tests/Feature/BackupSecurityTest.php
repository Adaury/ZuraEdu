<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pruebas de seguridad del BackupController.
 * Verifica que la descarga y eliminación de backups
 * no permitan path traversal.
 */
class BackupSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** Solo el superadministrador accede a los respaldos (el volcado contiene los datos de TODOS los colegios). */
    private function adminUser(): User
    {
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        return User::factory()->create()->assignRole('super_admin');
    }

    /** El Administrador de UN colegio no puede ver, descargar, crear ni borrar el respaldo global. */
    public function test_el_administrador_de_un_colegio_no_accede_al_respaldo_global(): void
    {
        $admin = User::factory()->create()->assignRole('Administrador');

        foreach ([
            ['get', route('superadmin.respaldos.descargar', ['file' => 'backup_x.sql'])],
            ['post', route('superadmin.respaldos.eliminar', ['file' => 'backup_x.sql'])],
            ['get', route('superadmin.respaldos.drive.conectar')],
            ['post', route('superadmin.respaldos.drive.probar')],
            ['post', route('superadmin.respaldos.carpeta.probar')],
            ['get', route('superadmin.respaldos.index')],
            ['post', route('superadmin.respaldos.ejecutar')],
            ['post', route('superadmin.respaldos.guardar'), ['frecuencia' => 'diaria', 'dia_semana' => 0, 'hora' => '03:00', 'zona_horaria' => 'UTC', 'retencion_dias' => 7, 'drive_carpeta_nombre' => 'X']],
        ] as $caso) {
            $r = $this->actingAs($admin)->{$caso[0]}($caso[1], $caso[2] ?? []);
            $this->assertContains($r->getStatusCode(), [302, 403, 404], $caso[1]);
            $this->assertStringNotContainsString('respaldos', (string) $r->headers->get('Location'), 'no debe llevarlo a la pantalla de respaldos');
        }
    }

    /**
     * Descargar un archivo fuera del directorio de backups debe ser rechazado.
     */
    public function test_descarga_path_traversal_es_rechazada(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)
            ->get(route('superadmin.respaldos.descargar', ['file' => '../.env']));

        // Debe redirigir con error o 404, nunca servir el archivo
        $this->assertTrue(
            $response->isRedirect() || $response->status() === 404,
            "Path traversal no fue bloqueado. Status: " . $response->status()
        );
    }

    /**
     * Intentar eliminar un archivo fuera del directorio de backups debe ser rechazado.
     */
    public function test_eliminacion_path_traversal_es_rechazada(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)
            ->post(route('superadmin.respaldos.eliminar', ['file' => '../../config/database.php']));

        // El archivo objetivo no debería haber sido eliminado
        $this->assertFileExists(config_path('database.php'));
    }

    /**
     * Un usuario sin rol Administrador no puede acceder al backup.
     */
    public function test_no_admin_no_puede_acceder_a_backup(): void
    {
        $user = User::factory()->create()->assignRole('Docente');

        $response = $this->actingAs($user)->get(route('superadmin.respaldos.index'));

        $this->assertContains($response->getStatusCode(), [302, 403], 'un usuario sin el rol no entra a los respaldos');
        $this->assertStringNotContainsString('/respaldos', $response->headers->get('Location') ?? '');
    }
}
