<?php

return [
    // Zona horaria en la que se interpretan las horas de los eventos del calendario escolar (la "hora de reloj" del colegio).
    // `app.timezone` está en UTC (marcas de tiempo del sistema) y NO sirve para esto: un evento a las 18:30 saldría a las 14:30 en
    // Google Calendar. República Dominicana no tiene horario de verano (UTC-4 todo el año).
    'zona_horaria' => env('CALENDARIO_TZ', 'America/Santo_Domingo'),
];
