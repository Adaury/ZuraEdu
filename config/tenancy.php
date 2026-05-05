<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dominio base de la plataforma ZuraEdu
    |--------------------------------------------------------------------------
    | Los subdominios se construyen: {dominio}.{base_domain}
    */
    'base_domain' => env('TENANCY_BASE_DOMAIN', 'zuraedu.com'),

    /*
    | En entorno local (localhost / *.test / *.local), resolver siempre
    | al tenant con ID 1 si no se encuentra por subdominio.
    */
    'fallback_tenant_id' => env('TENANCY_FALLBACK_ID', 1),

    /*
    | Rutas excluidas del filtrado por tenant (super admin, healthcheck, etc.)
    */
    'excluded_paths' => [
        'superadmin',
        '_debugbar',
        'telescope',
        'horizon',
        'health',
    ],
];
