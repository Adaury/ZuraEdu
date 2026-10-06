<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckTenantFeature;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El menú del Administrador se reagrupó (16 secciones → 12, un solo «Dashboard», submenús plegables). Esta prueba fija que NINGÚN enlace
 * se perdió al reagrupar (la lista sale del recorrido del menú anterior) y que no volvieron a aparecer los duplicados.
 */
class MenuAdministradorAgrupadoTest extends TestCase
{
    use RefreshDatabase;

    private function menu(): string
    {
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Menu',
            'dominio'            => 'colegiomenu' . random_int(10000, 99999),
            'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'pro',
        ]);
        foreach (array_keys((new \ReflectionClass(CheckTenantFeature::class))->getConstants()['LABELS']) as $feature) {
            TenantFeature::create(['tenant_id' => $tenant->id, 'feature' => $feature, 'activo' => true]);
        }
        app()->instance('tenant', $tenant);
        $u = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $u->assignRole('Administrador');

        $html = $this->actingAs($u)->get(route('admin.dashboard'))->assertOk()->getContent();
        $ini = strpos($html, '<nav class="sidebar-nav">');

        return substr($html, $ini, strpos($html, '</nav>', $ini) - $ini);
    }

    public function test_el_menu_reagrupado_conserva_todos_los_enlaces_del_administrador(): void
    {
        $menu = $this->menu();
        $perdidos = [];
        foreach ($this->rutasDelMenuAnterior() as $ruta) {
            if (! str_contains($menu, 'href="' . route($ruta) . '"')) {
                $perdidos[] = $ruta;
            }
        }

        $this->assertSame([], $perdidos, "Enlaces que el menú del Administrador ya no tiene:\n  " . implode("\n  ", $perdidos));
    }

    public function test_el_menu_tiene_un_solo_dashboard_y_menos_secciones(): void
    {
        $menu = $this->menu();

        // El panel principal aparece una vez; Ejecutivo, KPIs y Rendimiento están juntos en «Rendimiento y análisis»
        $this->assertSame(1, substr_count($menu, 'href="' . route('admin.dashboard') . '"'));
        $this->assertStringContainsString('Rendimiento y análisis', $menu);
        $this->assertStringNotContainsString('<div class="nav-section-title">Calendario</div>', $menu);
        $this->assertStringNotContainsString('<div class="nav-section-title">Página de Inicio</div>', $menu);
        $this->assertStringNotContainsString('<div class="nav-section-title">Soporte</div>', $menu);
        $this->assertStringContainsString('Administración y soporte', $menu);

        $secciones = substr_count($menu, 'class="nav-section-title"');
        $this->assertLessThanOrEqual(13, $secciones, "El menú del Administrador volvió a crecer: {$secciones} secciones");

        // Cada enlace aparece una sola vez (Inscripciones estaba duplicada)
        preg_match_all('/href="([^"#]+)"/', $menu, $m);
        $repetidos = array_keys(array_filter(array_count_values($m[1]), fn ($n) => $n > 1));
        $this->assertSame([], array_values(array_filter($repetidos, fn ($h) => ! str_contains($h, '?'))), 'Enlaces repetidos en el menú');
    }

    /** @return string[] */
    private function rutasDelMenuAnterior(): array
    {
        return [
            'admin.academico.index',
            'admin.alertas.index',
            'admin.asignaciones.index',
            'admin.asignaturas.index',
            'admin.asistencia.index',
            'admin.asistente.index',
            'admin.avisos-emergencia.index',
            'admin.ayuda',
            'admin.bachillerato-tecnico.index',
            'admin.becas.dashboard',
            'admin.biblioteca.dashboard',
            'admin.biblioteca.prestamos.index',
            'admin.billing.index',
            'admin.boletines.config',
            'admin.boletines.index',
            'admin.cafeteria.dashboard',
            'admin.calendario.index',
            'admin.calificaciones.index',
            'admin.calificaciones.ranking',
            'admin.calificaciones.resumen',
            'admin.carnet.index',
            'admin.centro-administracion',
            'admin.cierre-ano.index',
            'admin.classroom.index',
            'admin.competencias.index',
            'admin.comunicaciones.index',
            'admin.comunicados.dashboard',
            'admin.comunicados.mis',
            'admin.config.calificacion',
            'admin.config.ra',
            'admin.dashboard',
            'admin.disciplina.dashboard',
            'admin.docentes.index',
            'admin.ejecutivo.index',
            'admin.encuestas.dashboard',
            'admin.equipos.dashboard',
            'admin.equipos.prestamos.index',
            'admin.especialidades.index',
            'admin.estudiantes.index',
            'admin.evaluaciones-docentes.dashboard',
            'admin.eventos.dashboard',
            'admin.exportacion-masiva.index',
            'admin.familias.index',
            'admin.galeria.dashboard',
            'admin.gamificacion.index',
            'admin.grupos.index',
            'admin.homepage.edit',
            'admin.horarios.disponibilidad',
            'admin.horarios.index',
            'admin.horarios.suplencias',
            'admin.horarios.vista-maestra',
            'admin.importaciones.index',
            'admin.indicadores.index',
            'admin.inscripciones.index',
            'admin.instrumentos.index',
            'admin.integraciones.index',
            'admin.inventario.index',
            'admin.kpis.index',
            'admin.malla.matriz',
            'admin.matriculas.index',
            'admin.nomina.dashboard',
            'admin.observaciones.index',
            'admin.odoo.index',
            'admin.pagos.conceptos',
            'admin.pagos.config',
            'admin.pagos.dashboard',
            'admin.pagos.deudores',
            'admin.periodos.index',
            'admin.planes-clase.index',
            'admin.planificacion.dashboard',
            'admin.plantillas.index',
            'admin.pre-matriculas.index',
            'admin.proyectos.dashboard',
            'admin.reconocimientos.dashboard',
            'admin.recursos.disponibilidad',
            'admin.recursos.index',
            'admin.registro.index',
            'admin.rendimiento.comparativo',
            'admin.rendimiento.dashboard',
            'admin.rendimiento.porArea',
            'admin.rendimiento.rankingAsignaturas',
            'admin.rendimiento.recuperaciones',
            'admin.rendimiento.rezagados',
            'admin.rendimiento.semaforo',
            'admin.rendimiento.tendencia',
            'admin.reportes.index',
            'admin.reuniones.dashboard',
            'admin.riesgo.index',
            'admin.rubricas.index',
            'admin.salud.dashboard',
            'admin.school-years.index',
            'admin.secciones.index',
            'admin.seguimiento-social.dashboard',
            'admin.sistema.actividad',
            'admin.sistema.demo-trial',
            'admin.sistema.email-notif',
            'admin.sistema.estadisticas',
            'admin.sistema.index',
            'admin.sistema.landing',
            'admin.sistema.login-config',
            'admin.sistema.notificaciones',
            'admin.sistema.whatsapp',
            'admin.solicitudes-docente.index',
            'admin.solicitudes-est.index',
            'admin.solicitudes.index',
            'admin.soporte.chat',
            'admin.soporte.dashboard',
            'admin.transporte.dashboard',
            'admin.tutorias.index',
            'admin.usuarios.index',
            'admin.usuarios.pendientes',
        ];
    }
}
