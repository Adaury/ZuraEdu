<?php

/*
|--------------------------------------------------------------------------
| Generador de horarios (HorarioGeneratorService)
|--------------------------------------------------------------------------
| Estos valores se leían con env() dentro del servicio. Con `config:cache`
| (deploy.sh ejecuta `artisan optimize`) env() fuera de config/ devuelve null,
| así que HORARIO_MAX_ITER / HORARIO_MAX_TIME / HORARIO_DEBUG del .env de
| producción se ignoraban. Se leen aquí y el servicio usa config('horarios.*').
*/
return [
    // Tope de iteraciones del backtracking.
    'max_iter' => (int) env('HORARIO_MAX_ITER', 150_000),

    // Tope de tiempo en segundos (seguridad independiente de max_iter).
    'max_time' => (float) env('HORARIO_MAX_TIME', 30),

    // Log detallado del generador.
    'debug' => (bool) env('HORARIO_DEBUG', false),
];
