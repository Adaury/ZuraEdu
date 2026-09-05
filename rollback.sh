#!/usr/bin/env bash
#
# ZuraEdu — rollback.sh
#
# Revierte el código a un tag creado por deploy.sh (deploy-YYYYMMDD-HHMMSS),
# o a cualquier tag existente (ej: pre-l13). La restauración de base de
# datos es un paso EXPLÍCITO y separado: nunca se ejecuta automáticamente,
# porque sobrescribe datos reales.
#
# Uso:
#   ./rollback.sh <tag>
#   ./rollback.sh <tag> --restaurar-bd=/ruta/al/backup.sql
#
# El backup a restaurar es el que generó deploy.sh justo antes de crear ese
# mismo tag (storage/app/backups/backup_*.sql, o descargarlo desde
# /admin/sistema/backup — ver docs/BACKUP_ZURAEDU.md).
#
set -euo pipefail

PROYECTO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$PROYECTO_DIR"

TAG="${1:-}"
RESTAURAR_BD=""
for arg in "$@"; do
    case "$arg" in
        --restaurar-bd=*) RESTAURAR_BD="${arg#--restaurar-bd=}" ;;
    esac
done

log() { echo "[rollback $(date '+%Y-%m-%d %H:%M:%S')] $*"; }

if [ -z "$TAG" ]; then
    echo "Uso: ./rollback.sh <tag> [--restaurar-bd=/ruta/al/backup.sql]" >&2
    echo "" >&2
    echo "Tags de deploy disponibles (más reciente primero):" >&2
    git tag --list 'deploy-*' --sort=-creatordate | head -10 >&2
    exit 1
fi

if [ ! -f artisan ]; then
    echo "Error: este script debe ejecutarse desde la raíz del proyecto (no se encontró 'artisan')." >&2
    exit 1
fi

if ! git rev-parse "$TAG" >/dev/null 2>&1; then
    echo "Error: el tag '$TAG' no existe." >&2
    exit 1
fi

log "[1/6] Modo mantenimiento ON"
php artisan down --retry=60

log "[2/6] Revirtiendo código a $TAG"
git checkout "$TAG"
log "AVISO: quedas en detached HEAD sobre $TAG. Esto es esperado — no sigas commiteando aquí;"
log "una vez resuelto el problema, vuelve a la rama de deploy (ej: git checkout master) para continuar."

log "[3/6] Instalando dependencias PHP (pueden diferir del commit anterior)"
composer install --no-dev --optimize-autoloader

log "[4/6] Reconstruyendo caché"
php artisan optimize:clear
php artisan optimize

if [ -n "$RESTAURAR_BD" ]; then
    if [ ! -f "$RESTAURAR_BD" ]; then
        log "ERROR: no se encontró el archivo de backup '$RESTAURAR_BD'. La base de datos NO se tocó."
    else
        log "[5/6] Restaurar BD desde $RESTAURAR_BD — esto SOBREESCRIBE los datos actuales."
        read -r -p "Escribe CONFIRMAR (en mayúsculas) para continuar, cualquier otra cosa cancela: " CONFIRMACION
        if [ "$CONFIRMACION" = "CONFIRMAR" ]; then
            DB_DATABASE="$(grep '^DB_DATABASE=' .env | cut -d '=' -f2-)"
            DB_USERNAME="$(grep '^DB_USERNAME=' .env | cut -d '=' -f2-)"
            DB_HOST="$(grep '^DB_HOST=' .env | cut -d '=' -f2-)"
            DB_PORT="$(grep '^DB_PORT=' .env | cut -d '=' -f2-)"
            DB_PASSWORD="$(grep '^DB_PASSWORD=' .env | cut -d '=' -f2-)"
            MYSQL_PWD="$DB_PASSWORD" mysql --host="$DB_HOST" --port="$DB_PORT" -u"$DB_USERNAME" "$DB_DATABASE" < "$RESTAURAR_BD"
            log "Base de datos restaurada desde $RESTAURAR_BD."
        else
            log "Restauración de BD cancelada por el operador. La base de datos NO se tocó."
        fi
    fi
else
    log "[5/6] Restauración de BD omitida (no se pasó --restaurar-bd) — solo se revirtió el código."
fi

log "[6/6] Reiniciando workers y saliendo de mantenimiento"
supervisorctl restart sge-horizon sge-reverb 2>/dev/null \
    || log "AVISO: no se pudo reiniciar supervisor automáticamente. Revisar manualmente."
php artisan up

log "Rollback a $TAG completado. Verifica /health manualmente."
