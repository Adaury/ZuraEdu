<?php

return [
    // Segundos máximos de espera por cada llamada a Odoo (una llamada lenta no debe colgar la petición ni el worker).
    'timeout' => (int) env('ODOO_TIMEOUT', 20),

    // Seguridad (SSRF): la URL de Odoo la escribe el administrador y el SERVIDOR la llama. Por defecto solo se permite https y se rechazan
    // direcciones privadas/locales (127.0.0.1, 10.x, 192.168.x, 169.254.x...). Para probar contra un Odoo local de desarrollo, poner
    // ODOO_PERMITIR_RED_LOCAL=true en el .env (NO activarlo en producción).
    'permitir_red_local' => (bool) env('ODOO_PERMITIR_RED_LOCAL', false),

    // Resolver el DNS de la URL para comprobar a qué IP apunta (anti-SSRF). Solo se desactiva en tests.
    'verificar_dns' => true,

    // Cuántos registros se envían por ejecución como máximo (el resto sigue en la siguiente; evita ejecuciones eternas).
    'lote_maximo' => (int) env('ODOO_LOTE_MAXIMO', 300),
];
