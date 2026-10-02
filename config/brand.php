<?php

/**
 * Marca de la PLATAFORMA (ZuraEdu). No es la marca de cada colegio: cada centro educativo conserva su propio logo y nombre
 * (Tenant::logo_url / system_logo) en las partes que son suyas; ZuraEdu aparece como la plataforma que lo respalda
 * (pie de página, acceso, panel de superadministrador, correos, PDF, app móvil).
 *
 * Los archivos del logo viven en public/brand/ y se generan con `node scripts/marca/generar-logos.cjs`.
 */
return [
    'nombre' => 'ZuraEdu',
    'lema'   => 'Plataforma de Gestión Escolar',

    // Año de publicación: el pie muestra «© 2026» este año y «© 2026–2028» en los siguientes.
    'anio_inicio' => 2026,

    // Texto legal de TODOS los pies de página (pantallas, PDF y correos).
    'derechos' => 'Todos los derechos reservados',

    // Rutas dentro de public/
    'logos' => [
        'color'        => 'brand/zuraedu-logo.svg',
        'blanco'       => 'brand/zuraedu-logo-blanco.svg',
        'icono'        => 'brand/zuraedu-icono.svg',
        'icono-blanco' => 'brand/zuraedu-icono-blanco.svg',
        // PNG para correos y PDF (no todos los clientes aceptan SVG)
        'png'          => 'brand/zuraedu-logo-300.png',
    ],
];
