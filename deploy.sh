#!/usr/bin/env bash
#
# ZuraEdu — deploy.sh
#
# Procedimiento mínimo de deploy con backup previo (Gate de Producción,
# blocker #3, parte 2/3 — ver docs/GATE_PRODUCCION_ZURAEDU.md). Sigue el
# checklist ya documentado en DEPLOY.md §9, agregando: backup automático
# antes de tocar nada, y un tag de git para poder revertir con rollback.sh.
#
# Uso:
#   ./deploy.sh [--sin-migrar] [--sin-assets]
#
#   --sin-migrar   No corre `php artisan migrate --force` (deploy solo de código).
#   --sin-assets   No recompila assets con npm/Vite (usa los ya compilados).
#
# Requiere: estar en la raíz del proyecto, con permisos ya configurados
# sobre storage/ y bootstrap/cache/ (ver DEPLOY.md §8).
#
set -Eeuo pipefail

PROYECTO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$PROYECTO_DIR"

SIN_MIGRAR=false
SIN_ASSETS=false
for arg in "$@"; do
    case "$arg" in
        --sin-migrar) SIN_MIGRAR=true ;;
        --sin-assets) SIN_ASSETS=true ;;
        *)
            echo "Argumento desconocido: $arg" >&2
            exit 1
            ;;
    esac
done

PASO_ACTUAL="inicio"
MANTENIMIENTO=false   # true entre `artisan down` y `artisan up`
TAG_PUBLICADO=""      # se llena cuando el tag de rollback ya está en origin

log() {
    case "$*" in "["[0-9]*"/9]"*) PASO_ACTUAL="$*" ;; esac
    echo "[deploy $(date '+%Y-%m-%d %H:%M:%S')] $*"
}

# Si cualquier paso falla (set -e), NO se sale en silencio: se dice en qué paso fue y qué hacer. La aplicación se deja en
# mantenimiento A PROPÓSITO (no se publica código a medias: p. ej. con las migraciones a medio aplicar).
al_fallar() {
    local codigo=$?
    trap - ERR
    echo "" >&2
    log "ERROR (código $codigo) en: $PASO_ACTUAL" >&2
    if [ "$MANTENIMIENTO" = true ]; then
        log "La aplicación SIGUE EN MANTENIMIENTO (a propósito)." >&2
        log "Qué hacer: 1) lee el error de arriba y corrige la causa; 2) vuelve a ejecutar ./deploy.sh (las migraciones ya aplicadas no se repiten)." >&2
        log "Ojo: si falló una MIGRACIÓN, MySQL no deshace los cambios de esquema a medias: antes de repetir revisa 'php artisan migrate:status'," >&2
        log "     o restaura el backup de este deploy con ./rollback.sh <tag> --restaurar-bd=<archivo> (DEPLOY.md §14)." >&2
        if [ -n "$TAG_PUBLICADO" ]; then
            log "Para volver a la versión anterior: ./rollback.sh $TAG_PUBLICADO  (y luego: php artisan up)." >&2
        fi
        log "Solo si estás SEGURO de que la aplicación está en un estado consistente: php artisan up" >&2
    fi
    exit "$codigo"
}
trap al_fallar ERR

if [ ! -f artisan ]; then
    echo "Error: este script debe ejecutarse desde la raíz del proyecto (no se encontró 'artisan')." >&2
    exit 1
fi

if [ -n "$(git status --porcelain)" ]; then
    echo "Error: hay cambios sin commitear en el servidor. Resuélvelos antes de desplegar (nunca se descartan automáticamente)." >&2
    git status --short
    exit 1
fi

RAMA_ACTUAL="$(git rev-parse --abbrev-ref HEAD)"
TAG="deploy-$(date '+%Y%m%d-%H%M%S')"

log "Rama: $RAMA_ACTUAL — tag de rollback que se creará: $TAG"

log "[1/9] Modo mantenimiento ON"
php artisan down --retry=60
MANTENIMIENTO=true

log "[2/9] Backup de BD + archivos antes de desplegar"
if ! php artisan sge:backup; then
    log "ERROR: el backup pre-deploy falló. Abortando ANTES de tocar código."
    php artisan up
    MANTENIMIENTO=false
    exit 1
fi

log "[3/9] Creando y publicando tag de rollback: $TAG"
git tag "$TAG"
git push origin "$TAG"
TAG_PUBLICADO="$TAG"

log "[4/9] Actualizando código (git pull origin $RAMA_ACTUAL)"
git pull origin "$RAMA_ACTUAL"

log "[5/9] Instalando dependencias PHP"
composer install --no-dev --optimize-autoloader

if [ "$SIN_ASSETS" = false ]; then
    log "[6/9] Compilando assets (npm ci && npm run build)"
    npm ci
    npm run build
else
    log "[6/9] Assets omitidos (--sin-assets)"
fi

if [ "$SIN_MIGRAR" = false ]; then
    log "[7/9] Ejecutando migraciones"
    php artisan migrate --force
else
    log "[7/9] Migraciones omitidas (--sin-migrar)"
fi

log "[8/9] Reconstruyendo caché"
php artisan optimize:clear
php artisan optimize

log "[9/9] Recargando PHP-FPM, reiniciando workers y saliendo de mantenimiento"
# Con opcache.validate_timestamps=0 (DEPLOY.md §8) PHP-FPM NO vuelve a leer los .php: sin recargarlo, la aplicación saldría de
# mantenimiento ejecutando el código ANTERIOR sobre las dependencias y la base de datos ya nuevas. Se hace ANTES de `artisan up`.
FPM_SERVICIO="${PHP_FPM_SERVICE:-php8.3-fpm}"
if sudo -n systemctl reload "$FPM_SERVICIO" 2>/dev/null; then
    log "PHP-FPM ($FPM_SERVICIO) recargado: OPcache ya sirve el código nuevo."
else
    log "AVISO: no se pudo recargar $FPM_SERVICIO (¿otro nombre de servicio, o sudo pide contraseña?)."
    log "       Con opcache.validate_timestamps=0 el servidor SIGUE sirviendo el código anterior hasta que lo hagas."
    log "       Ejecuta AHORA: sudo systemctl reload $FPM_SERVICIO   (y define PHP_FPM_SERVICE=<servicio> en el próximo deploy)."
fi
supervisorctl restart sge-horizon sge-reverb 2>/dev/null \
    || log "AVISO: no se pudo reiniciar supervisor automáticamente (¿nombres de proceso distintos? ver DEPLOY.md §6). Revisar manualmente."
php artisan up
MANTENIMIENTO=false

if [ -n "${DEPLOY_HEALTH_URL:-}" ]; then
    log "Verificando $DEPLOY_HEALTH_URL ..."
    if command -v curl >/dev/null 2>&1; then
        RESPUESTA="$(curl -s -o /dev/null -w '%{http_code}' "$DEPLOY_HEALTH_URL" || echo "000")"
        if [ "$RESPUESTA" = "200" ]; then
            log "/health respondió 200 — deploy verificado."
        else
            log "AVISO: /health respondió $RESPUESTA (no 200). Revisar antes de dar el deploy por bueno."
            log "Si es necesario revertir: ./rollback.sh $TAG"
        fi
    fi
else
    log "DEPLOY_HEALTH_URL no está definida — verifica /health manualmente."
fi

log "Deploy completado. Tag de rollback disponible: $TAG"
log "Para revertir: ./rollback.sh $TAG"
