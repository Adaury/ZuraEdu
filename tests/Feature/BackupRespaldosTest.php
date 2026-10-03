<?php

namespace Tests\Feature;

use App\Models\BackupConfiguracion;
use App\Models\BackupRun;
use App\Models\User;
use App\Services\BackupDestinos;
use App\Services\BackupService;
use App\Services\GoogleDriveService;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Respaldo de la plataforma: hora elegida desde la pantalla, carpeta local de sincronización y Google Drive (API simulada con Http::fake:
 * aquí no se toca el Drive real), y que SOLO el superadministrador pueda verlo.
 */
class BackupRespaldosTest extends TestCase
{
    use RefreshDatabase;

    private array $temporales = [];

    protected function tearDown(): void
    {
        foreach ($this->temporales as $t) {
            is_dir($t) ? $this->borrarCarpeta($t) : @unlink($t);
        }
        parent::tearDown();
    }

    private function borrarCarpeta(string $d): void
    {
        foreach (glob($d . '/*') ?: [] as $f) {
            is_dir($f) ? $this->borrarCarpeta($f) : @unlink($f);
        }
        @rmdir($d);
    }

    private function carpetaTemporal(string $nombre): string
    {
        $d = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'zura_' . $nombre . '_' . uniqid();
        $this->temporales[] = $d;

        return $d;
    }

    private function archivo(string $contenido = 'contenido de respaldo de prueba'): string
    {
        $f = tempnam(sys_get_temp_dir(), 'zbk') ?: '';
        file_put_contents($f, $contenido);
        $this->temporales[] = $f;

        return $f;
    }

    private function superadmin(): User
    {
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        return User::factory()->create()->assignRole('super_admin');
    }

    private function cfg(array $extra = []): BackupConfiguracion
    {
        return BackupConfiguracion::create(array_merge(['activo' => true, 'frecuencia' => 'diaria', 'hora' => '02:30', 'zona_horaria' => 'America/Santo_Domingo', 'retencion_dias' => 7, 'incluir_archivos' => true, 'drive_carpeta_nombre' => 'ZuraEdu Respaldos'], $extra));
    }

    // ── Pantalla y permisos ─────────────────────────────────────────────────

    public function test_la_pantalla_abre_para_el_superadministrador_y_no_expone_el_secreto(): void
    {
        $this->cfg(['drive_client_id' => 'cliente-123.apps.googleusercontent.com', 'drive_client_secret' => 'SECRETO-MUY-PRIVADO']);

        $r = $this->actingAs($this->superadmin())->get(route('superadmin.respaldos.index'))->assertOk();

        $r->assertSee('Respaldos de la plataforma')->assertSee(route('superadmin.respaldos.drive.callback'), false)->assertSee('schedule:run');
        $this->assertStringNotContainsString('SECRETO-MUY-PRIVADO', $r->getContent(), 'el secreto de Google nunca se imprime en la página');
    }

    public function test_las_rutas_antiguas_del_admin_ya_no_existen(): void
    {
        foreach (['admin.sistema.backup', 'admin.sistema.backup.crear', 'admin.sistema.backup.descargar', 'admin.sistema.backup.eliminar'] as $nombre) {
            $this->assertFalse(\Illuminate\Support\Facades\Route::has($nombre), "$nombre debía desaparecer: el respaldo vive solo en el panel del superadministrador");
        }
    }

    public function test_el_administrador_de_un_colegio_no_ve_el_enlace_de_backup(): void
    {
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $admin = User::factory()->create(['activo' => true])->assignRole('Administrador');

        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString('/admin/sistema/backup', $html);
    }

    // ── Hora elegida ────────────────────────────────────────────────────────

    public function test_se_guarda_la_hora_elegida_y_se_calcula_la_proxima_ejecucion(): void
    {
        $this->actingAs($this->superadmin())->post(route('superadmin.respaldos.guardar'), [
            'activo' => 1, 'frecuencia' => 'diaria', 'dia_semana' => 0, 'hora' => '03:15', 'zona_horaria' => 'America/Santo_Domingo',
            'retencion_dias' => 10, 'incluir_archivos' => 1, 'drive_carpeta_nombre' => 'ZuraEdu Respaldos',
        ])->assertSessionHas('success');

        $c = BackupConfiguracion::first();
        $this->assertSame('03:15', $c->hora);
        $this->assertSame(10, $c->retencion_dias);
        $this->assertTrue($c->activo);
    }

    public function test_la_hora_y_la_zona_invalidas_se_rechazan(): void
    {
        $base = ['frecuencia' => 'diaria', 'dia_semana' => 0, 'retencion_dias' => 7, 'drive_carpeta_nombre' => 'X'];
        $su = $this->superadmin();

        $this->actingAs($su)->post(route('superadmin.respaldos.guardar'), array_merge($base, ['hora' => '25:99', 'zona_horaria' => 'UTC']))->assertSessionHasErrors('hora');
        $this->actingAs($su)->post(route('superadmin.respaldos.guardar'), array_merge($base, ['hora' => '03:00', 'zona_horaria' => 'Marte/Olimpo']))->assertSessionHasErrors('zona_horaria');
        $this->actingAs($su)->post(route('superadmin.respaldos.guardar'), array_merge($base, ['hora' => '03:00', 'zona_horaria' => 'UTC', 'retencion_dias' => 0]))->assertSessionHasErrors('retencion_dias');
        $this->assertSame(0, BackupConfiguracion::count());
    }

    public function test_la_proxima_ejecucion_diaria_cae_hoy_o_manana_segun_la_hora(): void
    {
        $c = $this->cfg(['hora' => '02:30']);

        // 05:00 UTC = 01:00 en Santo Domingo → todavía es hoy a las 02:30
        $this->assertSame('2026-10-04 02:30', $c->proximaEjecucion(Carbon::parse('2026-10-04 05:00', 'UTC'))->format('Y-m-d H:i'));
        // 10:00 UTC = 06:00 en Santo Domingo → ya pasó: mañana
        $this->assertSame('2026-10-05 02:30', $c->proximaEjecucion(Carbon::parse('2026-10-04 10:00', 'UTC'))->format('Y-m-d H:i'));
    }

    public function test_la_proxima_ejecucion_semanal_cae_en_el_dia_elegido(): void
    {
        $c = $this->cfg(['frecuencia' => 'semanal', 'dia_semana' => 1, 'hora' => '04:00']);   // lunes

        $p = $c->proximaEjecucion(Carbon::parse('2026-10-03 12:00', 'UTC'));   // sábado
        $this->assertSame('2026-10-05 04:00', $p->format('Y-m-d H:i'));
        $this->assertSame(1, $p->dayOfWeek);
    }

    public function test_desactivado_no_hay_proxima_ejecucion(): void
    {
        $this->assertNull($this->cfg(['activo' => false])->proximaEjecucion());
    }

    public function test_el_programador_usa_la_hora_y_la_zona_elegidas(): void
    {
        $this->cfg(['hora' => '03:15', 'zona_horaria' => 'America/Santo_Domingo']);

        $kernel = $this->app->make(\Illuminate\Contracts\Console\Kernel::class);
        $schedule = new Schedule();
        (new \ReflectionMethod($kernel, 'schedule'))->invoke($kernel, $schedule);

        $evento = collect($schedule->events())->first(fn ($e) => str_contains($e->command ?? '', 'sge:backup'));
        $this->assertNotNull($evento);
        $this->assertSame('15 3 * * *', $evento->expression);
        $this->assertSame('America/Santo_Domingo', (string) $evento->timezone);
    }

    public function test_el_programador_semanal_usa_el_dia_elegido(): void
    {
        $this->cfg(['frecuencia' => 'semanal', 'dia_semana' => 5, 'hora' => '23:45']);

        $kernel = $this->app->make(\Illuminate\Contracts\Console\Kernel::class);
        $schedule = new Schedule();
        (new \ReflectionMethod($kernel, 'schedule'))->invoke($kernel, $schedule);

        $this->assertSame('45 23 * * 5', collect($schedule->events())->first(fn ($e) => str_contains($e->command ?? '', 'sge:backup'))->expression);
    }

    // ── Carpeta local de sincronización ─────────────────────────────────────

    public function test_la_carpeta_se_crea_y_valida(): void
    {
        $ruta = $this->carpetaTemporal('sync') . DIRECTORY_SEPARATOR . 'anidada' . DIRECTORY_SEPARATOR . 'ZuraEdu';
        $this->temporales[] = dirname($ruta);

        $this->assertNull(BackupDestinos::validarCarpeta($ruta));
        $this->assertDirectoryExists($ruta);
    }

    public function test_se_rechazan_rutas_relativas_con_puntos_y_carpetas_publicas(): void
    {
        $this->assertNotNull(BackupDestinos::validarCarpeta(''));
        $this->assertNotNull(BackupDestinos::validarCarpeta('respaldos/relativa'));
        $this->assertNotNull(BackupDestinos::validarCarpeta(sys_get_temp_dir() . '/a/../b'));
        $this->assertNotNull(BackupDestinos::validarCarpeta(public_path('respaldos')), 'dentro de public/ cualquiera podría descargarlo por URL');
        $this->assertNotNull(BackupDestinos::validarCarpeta(storage_path('app/public/respaldos')));
    }

    public function test_guardar_con_carpeta_publica_se_rechaza_y_no_guarda(): void
    {
        $this->actingAs($this->superadmin())->post(route('superadmin.respaldos.guardar'), [
            'frecuencia' => 'diaria', 'dia_semana' => 0, 'hora' => '03:00', 'zona_horaria' => 'UTC', 'retencion_dias' => 7, 'drive_carpeta_nombre' => 'X',
            'carpeta_local_activa' => 1, 'carpeta_local_ruta' => public_path('respaldos'),
        ])->assertSessionHas('error');

        $this->assertSame(0, BackupConfiguracion::count());
    }

    public function test_copia_el_respaldo_a_la_carpeta_y_aplica_la_retencion(): void
    {
        $carpeta = $this->carpetaTemporal('copia');
        $cfg = $this->cfg(['carpeta_local_activa' => true, 'carpeta_local_ruta' => $carpeta]);
        $destinos = new BackupDestinos($cfg);

        $origen = $this->archivo('SQL de prueba');
        $r = $destinos->distribuir($origen, 'backup_2026-10-03_02-30-00.sql');

        $this->assertTrue($r['carpeta_local']['ok']);
        $this->assertFileExists($carpeta . '/backup_2026-10-03_02-30-00.sql');
        $this->assertSame('SQL de prueba', file_get_contents($carpeta . '/backup_2026-10-03_02-30-00.sql'));

        // Un respaldo viejo y un archivo ajeno: solo se borra el viejo con patrón de respaldo
        file_put_contents($carpeta . '/backup_viejo.sql', 'x');
        touch($carpeta . '/backup_viejo.sql', now()->subDays(30)->getTimestamp());
        file_put_contents($carpeta . '/notas_personales.txt', 'no tocar');
        touch($carpeta . '/notas_personales.txt', now()->subDays(30)->getTimestamp());

        $this->assertSame(1, $destinos->aplicarRetencion(7)['carpeta_local']);
        $this->assertFileDoesNotExist($carpeta . '/backup_viejo.sql');
        $this->assertFileExists($carpeta . '/notas_personales.txt');
        $this->assertFileExists($carpeta . '/backup_2026-10-03_02-30-00.sql');
    }

    // ── Google Drive (API simulada) ─────────────────────────────────────────

    private function cfgDrive(array $extra = []): BackupConfiguracion
    {
        return $this->cfg(array_merge(['drive_client_id' => 'cid', 'drive_client_secret' => 'csecret', 'drive_refresh_token' => 'rtoken', 'drive_cuenta' => 'yo@gmail.com', 'drive_activo' => true], $extra));
    }

    public function test_el_secreto_y_el_token_se_guardan_cifrados(): void
    {
        $c = $this->cfgDrive();
        $crudo = \DB::table('backup_configuracion')->where('id', $c->id)->first();

        $this->assertNotSame('csecret', $crudo->drive_client_secret);
        $this->assertNotSame('rtoken', $crudo->drive_refresh_token);
        $this->assertSame('rtoken', $c->fresh()->drive_refresh_token);
        $this->assertArrayNotHasKey('drive_refresh_token', $c->toArray(), 'no sale en JSON/array');
    }

    public function test_conectar_con_el_codigo_guarda_el_permiso_y_la_cuenta(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token'                  => Http::response(['access_token' => 'at1', 'refresh_token' => 'rt1', 'expires_in' => 3600]),
            'openidconnect.googleapis.com/v1/userinfo'      => Http::response(['email' => 'colegio@gmail.com']),
        ]);
        $c = $this->cfg(['drive_client_id' => 'cid', 'drive_client_secret' => 'csecret']);

        (new GoogleDriveService($c))->conectarConCodigo('codigo-de-google', 'https://zura.test/cb');

        $c->refresh();
        $this->assertSame('rt1', $c->drive_refresh_token);
        $this->assertSame('colegio@gmail.com', $c->drive_cuenta);
        $this->assertTrue($c->driveConectado());
    }

    public function test_si_google_no_entrega_el_permiso_permanente_se_explica(): void
    {
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['access_token' => 'at1'])]);
        $c = $this->cfg(['drive_client_id' => 'cid', 'drive_client_secret' => 'csecret']);

        $this->expectExceptionMessage('permiso permanente');
        (new GoogleDriveService($c))->conectarConCodigo('x', 'https://zura.test/cb');
    }

    public function test_el_callback_rechaza_un_estado_distinto_y_acepta_el_correcto(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token'              => Http::response(['access_token' => 'at1', 'refresh_token' => 'rt1']),
            'openidconnect.googleapis.com/v1/userinfo'  => Http::response(['email' => 'colegio@gmail.com']),
        ]);
        $this->cfg(['drive_client_id' => 'cid', 'drive_client_secret' => 'csecret']);
        $su = $this->superadmin();

        $this->actingAs($su)->withSession(['drive_oauth_state' => 'correcto'])->get(route('superadmin.respaldos.drive.callback', ['state' => 'falso', 'code' => 'c']))->assertSessionHas('error');
        $this->assertFalse(BackupConfiguracion::first()->driveConectado(), 'un estado falso (CSRF) no conecta nada');

        $this->actingAs($su)->withSession(['drive_oauth_state' => 'correcto'])->get(route('superadmin.respaldos.drive.callback', ['state' => 'correcto', 'code' => 'c']))->assertSessionHas('success');
        $this->assertTrue(BackupConfiguracion::first()->driveConectado());
    }

    public function test_subir_crea_la_carpeta_una_vez_y_sube_el_archivo(): void
    {
        Cache::flush();
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'at1']),
            'www.googleapis.com/drive/v3/files?q=*' => Http::response(['files' => []]),
            'www.googleapis.com/drive/v3/files?fields=id' => Http::response(['id' => 'CARPETA1']),
            'www.googleapis.com/upload/drive/v3/files*' => Http::response('', 200, ['Location' => 'https://upload.test/sesion']),
            'upload.test/sesion' => Http::response(['id' => 'ARCH1', 'name' => 'backup_x.sql', 'size' => '30']),
        ]);
        $c = $this->cfgDrive();

        $r = (new GoogleDriveService($c))->subir($this->archivo(str_repeat('a', 30)), 'backup_x.sql');

        $this->assertSame('ARCH1', $r['id']);
        $this->assertSame('CARPETA1', $c->fresh()->drive_carpeta_id, 'la carpeta queda guardada para las siguientes corridas');
        Http::assertSent(fn ($req) => str_contains($req->url(), 'upload/drive/v3/files') && ($req->data()['parents'] ?? null) === ['CARPETA1']);
    }

    public function test_la_subida_por_tramos_sigue_el_rango_que_confirma_google(): void
    {
        Cache::flush();
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'at1']),
            'www.googleapis.com/drive/v3/files/CARP*' => Http::response(['id' => 'CARP', 'trashed' => false]),
            'www.googleapis.com/upload/drive/v3/files*' => Http::response('', 200, ['Location' => 'https://upload.test/sesion']),
            'upload.test/sesion' => Http::sequence()
                ->push('', 308, ['Range' => 'bytes=0-3'])
                ->push('', 308, ['Range' => 'bytes=0-7'])
                ->push(['id' => 'ARCH2', 'name' => 'files.zip', 'size' => '10']),
        ]);
        $svc = new GoogleDriveService($this->cfgDrive(['drive_carpeta_id' => 'CARP']));
        $svc->tramo = 4;

        $r = $svc->subir($this->archivo('0123456789'), 'files.zip');

        $this->assertSame('ARCH2', $r['id']);
        $rangos = [];
        Http::assertSent(function ($req) use (&$rangos) {
            if ($req->url() === 'https://upload.test/sesion') {
                $rangos[] = $req->header('Content-Range')[0] ?? '';
            }

            return true;
        });
        $this->assertSame(['bytes 0-3/10', 'bytes 4-7/10', 'bytes 8-9/10'], $rangos);
    }

    public function test_un_permiso_revocado_da_un_mensaje_claro(): void
    {
        Cache::flush();
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant'], 400)]);

        $this->expectExceptionMessage('vuelve a conectar');
        (new GoogleDriveService($this->cfgDrive()))->accessToken();
    }

    public function test_la_retencion_de_drive_solo_borra_respaldos_viejos(): void
    {
        Cache::flush();
        $viejo = now()->subDays(40)->toIso8601String();
        $nuevo = now()->subDay()->toIso8601String();
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'at1']),
            'www.googleapis.com/drive/v3/files/CARP?*' => Http::response(['id' => 'CARP', 'trashed' => false]),
            'www.googleapis.com/drive/v3/files?q=*' => Http::response(['files' => [
                ['id' => 'a', 'name' => 'backup_viejo.sql', 'createdTime' => $viejo],
                ['id' => 'b', 'name' => 'backup_nuevo.sql', 'createdTime' => $nuevo],
                ['id' => 'c', 'name' => 'foto_personal.jpg', 'createdTime' => $viejo],
            ]]),
            'www.googleapis.com/drive/v3/files/a' => Http::response('', 204),
        ]);

        $n = (new GoogleDriveService($this->cfgDrive(['drive_carpeta_id' => 'CARP'])))->aplicarRetencion(7);

        $this->assertSame(1, $n);
        Http::assertSent(fn ($req) => $req->method() === 'DELETE' && str_ends_with($req->url(), '/files/a'));
        Http::assertNotSent(fn ($req) => $req->method() === 'DELETE' && (str_ends_with($req->url(), '/files/b') || str_ends_with($req->url(), '/files/c')));
    }

    public function test_desconectar_borra_el_permiso_y_desactiva_drive(): void
    {
        Http::fake(['oauth2.googleapis.com/revoke' => Http::response('', 200)]);
        $c = $this->cfgDrive();

        (new GoogleDriveService($c))->desconectar();

        $c->refresh();
        $this->assertFalse($c->driveConectado());
        $this->assertFalse($c->drive_activo);
        $this->assertNull($c->drive_cuenta);
        Http::assertSent(fn ($req) => str_contains($req->url(), 'oauth2.googleapis.com/revoke'));
    }

    // ── El comando completo reparte a los destinos ──────────────────────────

    private function simularRespaldo(): array
    {
        $sql = $this->archivo('-- sql');
        $zip = $this->archivo('PK zip');
        $this->mock(BackupService::class, function ($mock) use ($sql, $zip) {
            $mock->shouldReceive('respaldarBaseDatos')->once()->andReturn(['ok' => true, 'path' => $sql, 'filename' => 'backup_t.sql', 'size' => 500, 'error' => null]);
            $mock->shouldReceive('respaldarArchivos')->once()->andReturn(['ok' => true, 'path' => $zip, 'filename' => 'files_t.zip', 'size' => 300, 'error' => null]);
            $mock->shouldReceive('aplicarRetencion')->once()->andReturn(0);
        });

        return [$sql, $zip];
    }

    public function test_el_comando_copia_ambos_archivos_a_la_carpeta_y_lo_registra(): void
    {
        $carpeta = $this->carpetaTemporal('cmd');
        $this->cfg(['carpeta_local_activa' => true, 'carpeta_local_ruta' => $carpeta]);
        $this->simularRespaldo();

        $this->artisan('sge:backup')->assertExitCode(0);

        $this->assertFileExists($carpeta . '/backup_t.sql');
        $this->assertFileExists($carpeta . '/files_t.zip');
        $run = BackupRun::latest('id')->first();
        $this->assertSame('exitoso', $run->estado);
        $this->assertTrue($run->destinos['carpeta_local']['backup_t.sql']['ok']);
        $this->assertTrue($run->destinos['carpeta_local']['files_t.zip']['ok']);
    }

    public function test_el_comando_sube_a_drive_y_registra_el_resultado(): void
    {
        Cache::flush();
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'at1']),
            'www.googleapis.com/drive/v3/files/CARP?*' => Http::response(['id' => 'CARP', 'trashed' => false]),
            'www.googleapis.com/drive/v3/files?q=*' => Http::response(['files' => []]),
            'www.googleapis.com/upload/drive/v3/files*' => Http::response('', 200, ['Location' => 'https://upload.test/s']),
            'upload.test/s' => Http::response(['id' => 'X', 'name' => 'archivo', 'size' => '5']),
        ]);
        $this->cfgDrive(['drive_carpeta_id' => 'CARP']);
        $this->simularRespaldo();

        $this->artisan('sge:backup')->assertExitCode(0);

        $run = BackupRun::latest('id')->first();
        $this->assertTrue($run->destinos['drive']['backup_t.sql']['ok']);
        $this->assertTrue($run->destinos['drive']['files_t.zip']['ok']);
        $this->assertArrayHasKey('drive', $run->destinos['retencion']);
    }

    public function test_si_drive_falla_el_respaldo_sigue_siendo_correcto_pero_se_registra_el_error(): void
    {
        Cache::flush();
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant'], 400)]);
        $this->cfgDrive();
        $this->simularRespaldo();

        $this->artisan('sge:backup')->assertExitCode(0);

        $run = BackupRun::latest('id')->first();
        $this->assertSame('exitoso', $run->estado, 'el respaldo local ya es válido');
        $this->assertFalse($run->destinos['drive']['backup_t.sql']['ok']);
        $this->assertStringContainsString('vuelve a conectar', $run->destinos['drive']['backup_t.sql']['error']);
    }

    public function test_sin_destinos_activos_el_comando_no_cambia(): void
    {
        $this->simularRespaldo();

        $this->artisan('sge:backup')->assertExitCode(0);

        $this->assertNull(BackupRun::latest('id')->first()->destinos);
    }
}
