<?php

/*
|--------------------------------------------------------------------------
| Proxies de confianza (X-Forwarded-For / -Proto / -Host)
|--------------------------------------------------------------------------
|
| Laravel solo debe creer en `X-Forwarded-For` cuando la petición llega DESDE un proxy propio. Si confía en cualquiera (`*`), un cliente
| manda `X-Forwarded-For: 1.2.3.4` y la aplicación cree que es esa IP: se esquiva el límite de intentos de login por IP (`throttle`) y se
| falsifica la IP que guarda `ActivityLog` en la auditoría. Se comprobó contra Nginx + FastCGI real.
|
| Con el despliegue documentado (Nginx + PHP-FPM por FastCGI) Nginx ya entrega la IP REAL del cliente en `REMOTE_ADDR`, así que no hace
| falta confiar en nadie para eso. Solo hay que confiar en el servidor web Next.js, que se conecta desde el MISMO servidor (loopback) y
| reenvía la IP del usuario en `X-Forwarded-For` (el login de la web nueva pasa por ahí).
|
| TRUSTED_PROXIES: lista separada por comas de IP o CIDR. Por defecto, solo loopback.
|   - Balanceador o CDN delante de Nginx (Cloudflare, ALB…): pon aquí SUS rangos (y configura el módulo `realip` de Nginx); si no, Laravel
|     verá la IP del balanceador para todos los usuarios y el límite por IP los juntará.
|   - `*` (o `**`) vuelve a confiar en cualquiera: no lo uses en producción.
*/

return [
    'trusted' => array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '127.0.0.1,::1'))), fn ($p) => $p !== '')),
];
