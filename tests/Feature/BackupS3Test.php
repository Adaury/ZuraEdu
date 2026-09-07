<?php

namespace Tests\Feature;

use App\Models\BackupRun;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Capa extra de durabilidad del backup: subir una copia al disco remoto
 * configurado (config('backup.disco'), típicamente 's3') además de la copia
 * local. El backup local ya es válido por sí solo — una falla al subir al
 * disco remoto se reporta pero nunca hace fallar la corrida completa.
 */
class BackupS3Test extends TestCase
{
    use RefreshDatabase;

    private array $archivosDePrueba = [];

    protected function tearDown(): void
    {
        foreach ($this->archivosDePrueba as $archivo) {
            @unlink($archivo);
        }

        parent::tearDown();
    }

    private function crearArchivoLocalDePrueba(string $contenido = 'contenido de prueba'): string
    {
        $path = storage_path('app/backups_test_s3_' . uniqid() . '.sql');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        file_put_contents($path, $contenido);
        $this->archivosDePrueba[] = $path;

        return $path;
    }

    public function test_con_disco_local_no_intenta_subir_nada(): void
    {
        config(['backup.disco' => 'local']);
        $path = $this->crearArchivoLocalDePrueba();

        $resultado = (new BackupService())->subirDestinoRemoto($path, basename($path));

        $this->assertTrue($resultado['ok']);
        $this->assertNull($resultado['error']);
    }

    public function test_con_disco_s3_sube_el_archivo(): void
    {
        config(['backup.disco' => 's3']);
        Storage::fake('s3');
        $path = $this->crearArchivoLocalDePrueba('contenido del sql de prueba');

        $resultado = (new BackupService())->subirDestinoRemoto($path, 'backup_prueba.sql');

        $this->assertTrue($resultado['ok']);
        Storage::disk('s3')->assertExists('backups/backup_prueba.sql');
    }

    public function test_si_falla_la_subida_reporta_error_sin_lanzar_excepcion(): void
    {
        config(['backup.disco' => 'un-disco-que-no-existe']);
        $path = $this->crearArchivoLocalDePrueba();

        $resultado = (new BackupService())->subirDestinoRemoto($path, basename($path));

        $this->assertFalse($resultado['ok']);
        $this->assertNotNull($resultado['error']);
    }

    public function test_el_comando_sge_backup_sube_bd_y_archivos_al_disco_configurado(): void
    {
        config(['backup.disco' => 's3']);
        Storage::fake('s3');

        $sqlPath = $this->crearArchivoLocalDePrueba('dump sql');
        $zipPath = $this->crearArchivoLocalDePrueba('zip falso');

        $this->mock(BackupService::class, function ($mock) use ($sqlPath, $zipPath) {
            $mock->shouldReceive('respaldarBaseDatos')->once()->andReturn([
                'ok' => true, 'path' => $sqlPath, 'filename' => 'backup_test.sql', 'size' => 500, 'error' => null,
            ]);
            $mock->shouldReceive('respaldarArchivos')->once()->andReturn([
                'ok' => true, 'path' => $zipPath, 'filename' => 'files_test.zip', 'size' => 300, 'error' => null,
            ]);
            $mock->shouldReceive('aplicarRetencion')->once()->andReturn(0);
            $mock->shouldReceive('subirDestinoRemoto')->with($sqlPath, 'backup_test.sql')->once()->andReturn(['ok' => true, 'error' => null]);
            $mock->shouldReceive('subirDestinoRemoto')->with($zipPath, 'files_test.zip')->once()->andReturn(['ok' => true, 'error' => null]);
        });

        $this->artisan('sge:backup')->assertExitCode(0);

        $run = BackupRun::latest('id')->first();
        $this->assertSame('exitoso', $run->estado);
    }

    public function test_una_subida_remota_fallida_no_hace_fallar_el_backup(): void
    {
        config(['backup.disco' => 's3']);
        $sqlPath = $this->crearArchivoLocalDePrueba();

        $this->mock(BackupService::class, function ($mock) use ($sqlPath) {
            $mock->shouldReceive('respaldarBaseDatos')->once()->andReturn([
                'ok' => true, 'path' => $sqlPath, 'filename' => 'backup_test.sql', 'size' => 500, 'error' => null,
            ]);
            $mock->shouldReceive('aplicarRetencion')->once()->andReturn(0);
            $mock->shouldReceive('subirDestinoRemoto')->once()->andReturn(['ok' => false, 'error' => 'Error simulado subiendo a S3']);
        });

        $this->artisan('sge:backup --sin-archivos')->assertExitCode(0);

        $run = BackupRun::latest('id')->first();
        $this->assertSame('exitoso', $run->estado, 'Una falla en la subida remota no debe marcar el backup como fallido.');
    }

    public function test_el_boton_manual_del_panel_tambien_intenta_subir_al_disco_remoto(): void
    {
        config(['backup.disco' => 's3']);
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $admin = User::factory()->create()->assignRole('Administrador');

        $sqlPath = $this->crearArchivoLocalDePrueba();

        $this->mock(BackupService::class, function ($mock) use ($sqlPath) {
            $mock->shouldReceive('respaldarBaseDatos')->once()->andReturn([
                'ok' => true, 'path' => $sqlPath, 'filename' => 'backup_manual.sql', 'size' => 500, 'error' => null,
            ]);
            $mock->shouldReceive('subirDestinoRemoto')->once()->andReturn(['ok' => false, 'error' => 'No se pudo subir al disco remoto de prueba.']);
        });

        $response = $this->actingAs($admin)->post(route('admin.sistema.backup.crear'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertStringContainsString('No se pudo subir', session('success'));
    }
}
