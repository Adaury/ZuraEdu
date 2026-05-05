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

    private function adminUser(): User
    {
        return User::factory()->create()->assignRole('Administrador');
    }

    /**
     * Descargar un archivo fuera del directorio de backups debe ser rechazado.
     */
    public function test_descarga_path_traversal_es_rechazada(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)
            ->get(route('admin.sistema.backup.descargar', ['file' => '../.env']));

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
            ->post(route('admin.sistema.backup.eliminar', ['file' => '../../config/database.php']));

        // El archivo objetivo no debería haber sido eliminado
        $this->assertFileExists(config_path('database.php'));
    }

    /**
     * Un usuario sin rol Administrador no puede acceder al backup.
     */
    public function test_no_admin_no_puede_acceder_a_backup(): void
    {
        $user = User::factory()->create()->assignRole('Docente');

        $response = $this->actingAs($user)->get(route('admin.sistema.backup'));

        $response->assertRedirect();
        $this->assertStringNotContainsString('/sistema/backup', $response->headers->get('Location') ?? '');
    }
}
