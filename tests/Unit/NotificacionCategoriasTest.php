<?php

namespace Tests\Unit;

use App\Models\Notificacion;
use App\Services\NotificacionPreferenciaService;
use Tests\TestCase;

/**
 * Notificaciones Configurables (Fase 5, pieza 3) -- integridad del catálogo
 * de categorías. Sin BD: solo verifica que las constantes del modelo estén
 * bien formadas entre sí.
 */
class NotificacionCategoriasTest extends TestCase
{
    public function test_todos_los_tipos_con_icono_tienen_categoria(): void
    {
        foreach (array_keys(Notificacion::ICONOS) as $tipo) {
            $this->assertArrayHasKey(
                $tipo,
                Notificacion::TIPO_CATEGORIA,
                "El tipo '{$tipo}' tiene icono pero no categoría asignada."
            );
        }
    }

    public function test_iconos_y_colores_cubren_exactamente_los_mismos_tipos(): void
    {
        // Regresión del bug encontrado en la auditoría: 'carnet_acceso'
        // tenía icono pero no color (caía al gris #6b7280 por defecto).
        $iconos  = collect(array_keys(Notificacion::ICONOS))->sort()->values()->all();
        $colores = collect(array_keys(Notificacion::COLORES))->sort()->values()->all();

        $this->assertSame($iconos, $colores);
    }

    public function test_toda_categoria_referenciada_existe_en_el_catalogo(): void
    {
        foreach (Notificacion::TIPO_CATEGORIA as $tipo => $categoria) {
            $this->assertArrayHasKey(
                $categoria,
                Notificacion::CATEGORIAS,
                "El tipo '{$tipo}' apunta a la categoría '{$categoria}', que no existe en CATEGORIAS."
            );
        }

        foreach (array_keys(Notificacion::CATEGORIAS) as $categoria) {
            $this->assertContains(
                $categoria,
                Notificacion::TIPO_CATEGORIA,
                "La categoría '{$categoria}' no tiene ningún tipo asignado."
            );
        }
    }

    public function test_tipo_desconocido_cae_en_sistema(): void
    {
        $this->assertSame('sistema', NotificacionPreferenciaService::categoria('tipo_completamente_inventado'));
    }
}
