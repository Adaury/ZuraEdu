<?php

/*
|--------------------------------------------------------------------------
| Idiomas de la interfaz
|--------------------------------------------------------------------------
| El sistema está escrito en español: los textos de las pantallas se traducen en el servidor con los
| diccionarios lang/{codigo}.json (clave = texto en español). Lo que no esté en el diccionario se
| muestra en español; los datos (nombres, notas, comunicados) nunca se traducen ni salen del servidor.
| Para añadir un idioma: crear lang/{codigo}.json y registrarlo aquí.
*/

return [
    'predeterminado' => 'es',

    // Nombre del cookie donde se recuerda la elección (1 año)
    'cookie' => 'idioma',

    'disponibles' => [
        'es' => ['nombre' => 'Español',   'corto' => 'ES'],
        'en' => ['nombre' => 'English',   'corto' => 'EN'],
        'fr' => ['nombre' => 'Français',  'corto' => 'FR'],
        'pt' => ['nombre' => 'Português', 'corto' => 'PT'],
    ],
];
