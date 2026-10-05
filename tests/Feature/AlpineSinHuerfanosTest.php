<?php

namespace Tests\Feature;

use Tests\Concerns\DetectaAlpineHuerfano;
use Tests\TestCase;

/**
 * El detector de directivas de Alpine fuera de un x-data (ver DetectaAlpineHuerfano). La comprobación sobre las pantallas reales
 * está en RouteSmokeTest, que ya carga cada pantalla del administrador.
 */
class AlpineSinHuerfanosTest extends TestCase
{
    use DetectaAlpineHuerfano;

    public function test_el_detector_encuentra_el_defecto_que_tenia_la_cafeteria(): void
    {
        $roto = '<div x-data="{ modalVenta: false }"><button @click="modalVenta = true">Abrir</button></div>
                 <div x-show="modalVenta" x-cloak><form>x</form></div>';

        $this->assertSame(['<div x-show="modalVenta">'], $this->alpineHuerfanos($roto));
    }

    public function test_no_marca_lo_que_esta_dentro_de_un_x_data_ni_el_x_cloak(): void
    {
        $bien = '<div x-data="{ a: false }"><button @click="a = true">x</button><div x-show="a" x-cloak>y</div></div>
                 <div x-cloak>sin directivas</div>';

        $this->assertSame([], $this->alpineHuerfanos($bien));
    }
}
