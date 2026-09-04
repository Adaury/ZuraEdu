<?php

namespace Tests\Feature;

use App\Console\Commands\BackupSistema;
use App\Models\BackupRun;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas del sistema de backup automático (Production Gate blocker #1).
 *
 * IMPORTANTE: estas pruebas NUNCA ejecutan mysqldump real ni tocan la BD de
 * backups real — BackupService se reemplaza por un mock en el contenedor.
 * El único acceso a disco real es a un subdirectorio de prueba, dentro de
 * storage/app/backups, con archivos con nombres únicos que se limpian en
 * tearDown() sin importar si el test pasa o falla.
 */
class BackupAutomaticoTest extends TestCase
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

    public function test_comando_sge_backup_esta_registrado(): void
    {
        $this->assertArrayHasKey('sge:backup', \Illuminate\Support\Facades\Artisan::all());
    }

    public function test_el_comando_esta_programado_diariamente_en_el_scheduler(): void
    {
        $schedule = $this->app->make(Schedule::class);

        $eventos = collect($schedule->events())
            ->filter(fn($e) => str_contains($e->command ?? '', 'sge:backup'));

        $this->assertTrue($eventos->isNotEmpty(), 'sge:backup no está registrado en el scheduler.');
    }

    public function test_backup_exitoso_registra_un_backup_run_exitoso(): void
    {
        $this->mock(BackupService::class, function ($mock) {
            $mock->shouldReceive('respaldarBaseDatos')->once()->andReturn([
                'ok' => true, 'path' => '/tmp/x.sql', 'filename' => 'backup_test.sql', 'size' => 500, 'error' => null,
            ]);
            $mock->shouldReceive('respaldarArchivos')->once()->andReturn([
                'ok' => true, 'path' => '/tmp/x.zip', 'filename' => 'files_test.zip', 'size' => 300, 'error' => null,
            ]);
            $mock->shouldReceive('aplicarRetencion')->once()->andReturn(2);
        });

        $this->artisan('sge:backup')->assertExitCode(0);

        $run = BackupRun::latest('id')->first();
        $this->assertNotNull($run);
        $this->assertSame('exitoso', $run->estado);
        $this->assertSame('backup_test.sql', $run->bd_archivo);
        $this->assertSame('files_test.zip', $run->archivos_archivo);
        $this->assertSame(2, $run->eliminados_retencion);
        $this->assertNotNull(BackupRun::ultimoExitoso());
    }

    public function test_backup_con_falla_de_bd_registra_backup_run_fallido_y_exit_code_no_cero(): void
    {
        $this->mock(BackupService::class, function ($mock) {
            $mock->shouldReceive('respaldarBaseDatos')->once()->andReturn([
                'ok' => false, 'path' => null, 'filename' => null, 'size' => null,
                'error' => 'mysqldump no disponible en el PATH',
            ]);
            $mock->shouldNotReceive('respaldarArchivos');
        });

        $this->artisan('sge:backup')->assertExitCode(1);

        $run = BackupRun::latest('id')->first();
        $this->assertNotNull($run);
        $this->assertSame('fallido', $run->estado);
        $this->assertSame('backup_bd', $run->etapa_fallo);
        $this->assertStringContainsString('mysqldump', $run->error_mensaje);
        $this->assertNull(BackupRun::ultimoExitoso());
    }

    public function test_backup_con_falla_de_archivos_registra_etapa_correcta(): void
    {
        $this->mock(BackupService::class, function ($mock) {
            $mock->shouldReceive('respaldarBaseDatos')->once()->andReturn([
                'ok' => true, 'path' => '/tmp/x.sql', 'filename' => 'backup_test.sql', 'size' => 500, 'error' => null,
            ]);
            $mock->shouldReceive('respaldarArchivos')->once()->andReturn([
                'ok' => false, 'path' => null, 'filename' => null, 'size' => null,
                'error' => 'No se pudo crear el zip',
            ]);
        });

        $this->artisan('sge:backup')->assertExitCode(1);

        $run = BackupRun::latest('id')->first();
        $this->assertSame('fallido', $run->estado);
        $this->assertSame('backup_archivos', $run->etapa_fallo);
        // El backup de BD ya generado antes de la falla de archivos se conserva en el registro.
        $this->assertSame('backup_test.sql', $run->bd_archivo);
    }

    public function test_opcion_sin_archivos_omite_el_backup_de_archivos(): void
    {
        $this->mock(BackupService::class, function ($mock) {
            $mock->shouldReceive('respaldarBaseDatos')->once()->andReturn([
                'ok' => true, 'path' => '/tmp/x.sql', 'filename' => 'backup_test.sql', 'size' => 500, 'error' => null,
            ]);
            $mock->shouldNotReceive('respaldarArchivos');
            $mock->shouldReceive('aplicarRetencion')->once()->andReturn(0);
        });

        $this->artisan('sge:backup --sin-archivos')->assertExitCode(0);
    }

    public function test_retencion_elimina_solo_archivos_mas_viejos_que_el_limite(): void
    {
        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $viejo   = $dir . DIRECTORY_SEPARATOR . 'backup_test-retencion-viejo.sql';
        $reciente = $dir . DIRECTORY_SEPARATOR . 'backup_test-retencion-reciente.sql';

        file_put_contents($viejo, str_repeat('x', 200));
        file_put_contents($reciente, str_repeat('x', 200));
        touch($viejo, time() - (10 * 86400));
        touch($reciente, time() - 3600);

        $this->archivosDePrueba = [$viejo, $reciente];

        $eliminados = (new BackupService())->aplicarRetencion(7);

        $this->assertFileDoesNotExist($viejo);
        $this->assertFileExists($reciente);
        $this->assertGreaterThanOrEqual(1, $eliminados);
    }

    public function test_zip_de_archivos_incluye_los_archivos_del_directorio_origen(): void
    {
        $origen = storage_path('app/backups_test_origen_' . uniqid());
        mkdir($origen, 0755, true);
        file_put_contents($origen . '/foto1.jpg', 'contenido-de-prueba');
        mkdir($origen . '/subcarpeta');
        file_put_contents($origen . '/subcarpeta/doc.pdf', 'otro-contenido');

        $resultado = (new BackupService())->respaldarArchivos($origen);

        $this->assertTrue($resultado['ok']);
        $this->assertFileExists($resultado['path']);

        $zip = new \ZipArchive();
        $zip->open($resultado['path']);
        $this->assertNotFalse($zip->locateName('foto1.jpg'));
        $this->assertNotFalse($zip->locateName('subcarpeta/doc.pdf'));
        $zip->close();

        $this->archivosDePrueba[] = $resultado['path'];
        @unlink($origen . '/foto1.jpg');
        @unlink($origen . '/subcarpeta/doc.pdf');
        @rmdir($origen . '/subcarpeta');
        @rmdir($origen);
    }

    public function test_disco_de_backups_no_es_el_disco_publico(): void
    {
        // Regla de seguridad: los backups nunca deben quedar accesibles vía
        // /storage (disco público). storage/app/backups vive fuera de
        // storage/app/public, que es lo único servido por storage:link.
        $backupsDir = realpath(storage_path('app/backups')) ?: storage_path('app/backups');
        $publicDir  = realpath(storage_path('app/public'));

        $this->assertStringStartsNotWith($publicDir, $backupsDir);
    }

    public function test_panel_admin_muestra_ultimo_backup_exitoso(): void
    {
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $admin = User::factory()->create()->assignRole('Administrador');

        BackupRun::create([
            'iniciado_en'       => now()->subDay(),
            'finalizado_en'     => now()->subDay(),
            'duracion_segundos' => 12,
            'estado'            => 'exitoso',
            'bd_archivo'        => 'backup_ayer.sql',
            'bd_tamano_bytes'   => 1000,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.sistema.backup'));

        $response->assertOk();
        $response->assertSee('backup_ayer.sql');
    }

    public function test_configuracion_de_backup_tiene_valores_por_defecto_seguros(): void
    {
        $this->assertTrue(config('backup.enabled'));
        $this->assertSame(7, config('backup.retencion_dias'));
        $this->assertSame('local', config('backup.disco'));
        $this->assertNotEquals('public', config('backup.disco'));
    }
}
