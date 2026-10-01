<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Migración 2026_09_30_000002_optimizar_indices_redundantes: quita índices que son
 * prefijo de otro y agrega el índice cubriente de asistencias. Fija el resultado para
 * que nadie vuelva a crear un índice (tenant_id) suelto sobre un compuesto que ya lo cubre.
 */
class IndicesOptimizadosTest extends TestCase
{
    use RefreshDatabase;

    public function test_asistencias_tiene_el_indice_cubriente_para_los_kpis_del_dia(): void
    {
        $this->assertTrue(Schema::hasIndex('asistencias', 'asistencias_tenant_fecha_estado_index'));
        // El (tenant_id) suelto quedó cubierto por el compuesto.
        $this->assertFalse(Schema::hasIndex('asistencias', 'idx_asistencias_tenant'));
    }

    public function test_se_eliminaron_los_indices_redundantes_de_las_tablas_calientes(): void
    {
        foreach ([
            ['calificaciones_academicas', 'idx_calac_mat_id'],
            ['calificaciones_academicas', 'calificaciones_academicas_matricula_id_index'],
            ['calificaciones_academicas', 'calificaciones_academicas_asignacion_id_index'],
            ['matriculas', 'idx_matriculas_tenant'],
            ['matriculas', 'matriculas_grupo_id_index'],
            ['pagos', 'idx_pagos_tenant'],
        ] as [$tabla, $indice]) {
            $this->assertFalse(Schema::hasIndex($tabla, $indice), "{$tabla}.{$indice} debía eliminarse");
        }
    }

    public function test_estudiantes_tiene_el_indice_del_listado_paginado_con_el_orden_correcto(): void
    {
        // Cubre filtro (tenant + borrado lógico) Y orden del listado: sin él MySQL hace filesort en cada página.
        $indice = collect(Schema::getIndexes('estudiantes'))->firstWhere('name', 'est_tenant_listado_idx');

        $this->assertNotNull($indice, 'Falta est_tenant_listado_idx en estudiantes');
        $this->assertSame(['tenant_id', 'deleted_at', 'apellidos', 'nombres'], $indice['columns']);
        $this->assertFalse($indice['unique']);
    }

    public function test_los_indices_unicos_de_estudiantes_que_protegen_la_integridad_no_se_tocan(): void
    {
        foreach (['est_tenant_cedula_unique', 'est_tenant_matricula_unique'] as $unico) {
            $this->assertTrue(Schema::hasIndex('estudiantes', $unico), "{$unico} debe conservarse");
        }
    }

    public function test_los_indices_que_cubren_siguen_existiendo(): void
    {
        // Los que sirven como prefijo/cobertura y las claves únicas de negocio no se tocan.
        foreach ([
            ['calificaciones_academicas', 'cal_ac_unique'],
            ['calificaciones_academicas', 'cal_acad_asig_pub_idx'],
            ['matriculas', 'matriculas_tenant_unique'],
            ['matriculas', 'mat_grupo_sy_estado_idx'],
            ['pagos', 'pagos_tenant_estado_idx'],
            ['pagos', 'pagos_matricula_id_estado_index'],
        ] as [$tabla, $indice]) {
            $this->assertTrue(Schema::hasIndex($tabla, $indice), "{$tabla}.{$indice} debe conservarse");
        }
    }
}
