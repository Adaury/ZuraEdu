<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        // Varios flujos del sistema (boletines, exportación masiva, respaldo, SIGERD) llaman a set_time_limit(300|600). Todas las
        // pruebas corren en un solo proceso, así que ese límite seguía corriendo entre pruebas y, si la suite pasaba de 600 s
        // desde la última llamada (máquina lenta), PHP abortaba con "Maximum execution time of 600 seconds exceeded" en la prueba
        // que estuviera en curso. Se reinicia en cada prueba; una prueba puede volver a fijarlo y se levanta en la siguiente.
        @set_time_limit(0);

        parent::setUp();
    }
}
