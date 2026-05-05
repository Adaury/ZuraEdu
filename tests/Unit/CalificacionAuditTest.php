<?php

namespace Tests\Unit;

use App\Models\CalificacionAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalificacionAuditTest extends TestCase
{
    use RefreshDatabase;

    /**
     * registrarCambios NO crea registro si el valor no cambió.
     */
    public function test_no_crea_audit_si_valor_no_cambio(): void
    {
        $anterior = new \App\Models\Calificacion();
        $anterior->nota_final = 85.0;

        CalificacionAudit::registrarCambios(
            'Calificacion',
            $anterior,
            ['nota_final' => 85.0],
            1,
            1,
            ['nota_final']
        );

        $this->assertDatabaseCount('calificacion_audits', 0);
    }

    /**
     * registrarCambios crea registro cuando el valor cambia.
     */
    public function test_crea_audit_cuando_valor_cambia(): void
    {
        $anterior = new \App\Models\Calificacion();
        $anterior->nota_final = 75.0;

        CalificacionAudit::registrarCambios(
            'Calificacion',
            $anterior,
            ['nota_final' => 90.0],
            1,
            1,
            ['nota_final']
        );

        $this->assertDatabaseHas('calificacion_audits', [
            'modelo'         => 'Calificacion',
            'campo'          => 'nota_final',
            'valor_anterior' => 75.0,
            'valor_nuevo'    => 90.0,
            'matricula_id'   => 1,
            'asignacion_id'  => 1,
        ]);
    }

    /**
     * registrarCambios con $anterior null (registro nuevo) guarda valor_anterior = null.
     */
    public function test_crea_audit_para_registro_nuevo(): void
    {
        CalificacionAudit::registrarCambios(
            'Calificacion',
            null,
            ['nota_final' => 80.0],
            2,
            3,
            ['nota_final']
        );

        $this->assertDatabaseHas('calificacion_audits', [
            'modelo'         => 'Calificacion',
            'campo'          => 'nota_final',
            'valor_anterior' => null,
            'valor_nuevo'    => 80.0,
        ]);
    }

    /**
     * Solo los campos vigilados se auditan.
     */
    public function test_solo_audita_campos_vigilados(): void
    {
        $anterior = new \App\Models\Calificacion();
        $anterior->nota_final = 70.0;
        $anterior->tareas     = 60.0;

        CalificacionAudit::registrarCambios(
            'Calificacion',
            $anterior,
            ['nota_final' => 90.0, 'tareas' => 99.0],
            1,
            1,
            ['nota_final'] // solo vigilamos nota_final
        );

        $this->assertDatabaseCount('calificacion_audits', 1);
        $this->assertDatabaseHas('calificacion_audits', ['campo' => 'nota_final']);
        $this->assertDatabaseMissing('calificacion_audits', ['campo' => 'tareas']);
    }
}
