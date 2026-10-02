#!/usr/bin/env bash
#
# Prueba automática de deploy.sh en un servidor SIMULADO (no toca ningún servidor real ni la base de datos).
#
# Crea en un directorio temporal un remoto git "bare", un clon con `artisan` y el deploy.sh actual, y sustituye php, composer, npm,
# supervisorctl, systemctl, sudo y curl por programas falsos que registran cada llamada (y fallan a demanda). Así se comprueba el ORDEN
# de los pasos y qué pasa cuando algo falla, sin necesitar un servidor: deploy.sh es un script que nunca se ejecuta "de prueba" en producción.
#
# Uso:  bash scripts/probar-deploy.sh        (sale con código 1 si falla alguna comprobación)
set -uo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
T="$(mktemp -d)"
trap 'rm -rf "$T"' EXIT

fallos=0
pruebas=0
ok()  { pruebas=$((pruebas + 1)); echo "  ✓ $1"; }
mal() { pruebas=$((pruebas + 1)); fallos=$((fallos + 1)); echo "  ✗ $1"; [ -n "${2:-}" ] && echo "      $2"; }
afirmar() { if eval "$2"; then ok "$1"; else mal "$1" "${3:-}"; fi; }

# ── servidor simulado ───────────────────────────────────────────────────────────────────────────────────────────────────
git init -q --bare "$T/remoto.git"
git clone -q "$T/remoto.git" "$T/servidor" 2>/dev/null
(
    cd "$T/servidor" || exit 1
    git config user.email prueba@example.test && git config user.name prueba
    git checkout -q -b master
    cp "$RAIZ/deploy.sh" deploy.sh && chmod +x deploy.sh
    cp "$RAIZ/rollback.sh" rollback.sh && chmod +x rollback.sh
    printf '.env
' > .gitignore
    touch artisan
    git add -A && git commit -q -m inicial && git push -q origin master 2>/dev/null
)

# .env del servidor simulado (ignorado por git: no ensucia el árbol de trabajo); rollback.sh lo lee para restaurar la BD.
printf 'DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sge_prueba
DB_USERNAME=usuario
DB_PASSWORD=clave
' > "$T/servidor/.env"

mkdir -p "$T/stubs"
for c in php composer npm supervisorctl systemctl curl mysql; do
    cat > "$T/stubs/$c" <<'STUB'
#!/usr/bin/env bash
n=$(basename "$0"); echo "$n $*" >> "$DEPLOY_LOG"
case "$n $*" in
  "php artisan sge:backup")        [ "${FAIL_BACKUP:-0}" = 1 ]   && exit 1 ;;
  "php artisan migrate --force")   [ "${FAIL_MIGRATE:-0}" = 1 ]  && exit 1 ;;
  "composer install"*)             [ "${FAIL_COMPOSER:-0}" = 1 ] && exit 1 ;;
  "systemctl reload"*)             [ "${FAIL_RELOAD:-0}" = 1 ]   && exit 1 ;;
  curl*)                           echo -n "${HEALTH_CODE:-200}" ;;
esac
exit 0
STUB
    chmod +x "$T/stubs/$c"
done
cat > "$T/stubs/sudo" <<'STUB'
#!/usr/bin/env bash
echo "sudo $*" >> "$DEPLOY_LOG"; [ "$1" = "-n" ] && shift; exec "$@"
STUB
chmod +x "$T/stubs/sudo"

LOG="$T/llamadas.log"
SALIDA="$T/salida.txt"

# corrida [VAR=valor ...] -- [argumentos de deploy.sh]
corrida() {
    : > "$LOG"
    local vars=() args=()
    while [ $# -gt 0 ] && [ "$1" != "--" ]; do vars+=("$1"); shift; done
    [ "${1:-}" = "--" ] && shift
    args=("$@")
    (
        cd "$T/servidor" || exit 1
        export PATH="$T/stubs:$PATH" DEPLOY_LOG="$LOG"
        env "${vars[@]}" bash ./deploy.sh "${args[@]}" > "$SALIDA" 2>&1
        echo "salida=$?" >> "$LOG"
    )
    CODIGO="$(sed -n 's/^salida=//p' "$LOG")"
}
llamo()   { grep -qE "^$1" "$LOG"; }
posicion() { grep -nE "^$1" "$LOG" | head -1 | cut -d: -f1; }
cuantas()  { grep -cE "^$1" "$LOG"; }
dijo()     { grep -qF -- "$1" "$SALIDA"; }

echo "A) Despliegue correcto"
corrida X=1 -- --sin-assets
afirmar "termina con código 0" '[ "$CODIGO" = 0 ]'
afirmar "pone y quita el mantenimiento una sola vez" '[ "$(cuantas "php artisan down")" = 1 ] && [ "$(cuantas "php artisan up")" = 1 ]'
orden_ok=true
prev=0
for paso in "php artisan down" "php artisan sge:backup" "composer install" "php artisan migrate --force" "php artisan optimize" "sudo -n systemctl reload" "supervisorctl restart" "php artisan up"; do
    p="$(posicion "$paso")"
    if [ -z "$p" ] || [ "$p" -le "$prev" ]; then orden_ok=false; fi
    prev="${p:-0}"
done
afirmar "los pasos ocurren en el orden documentado (mantenimiento, backup, dependencias, migrar, caché, recargar FPM, workers, subir)" '$orden_ok'
afirmar "recarga PHP-FPM ANTES de salir de mantenimiento" '[ "$(posicion "systemctl reload")" -lt "$(posicion "php artisan up")" ]'
afirmar "el backup ocurre ANTES de tocar el código (composer/migrar)" '[ "$(posicion "php artisan sge:backup")" -lt "$(posicion "composer install")" ]'
afirmar "publica un tag de rollback en el remoto" '[ "$(git -C "$T/remoto.git" tag --list "deploy-*" | wc -l)" -ge 1 ]'
afirmar "con --sin-assets no llama a npm" '! llamo "npm"'
afirmar "reinicia horizon y reverb" 'grep -q "supervisorctl restart sge-horizon sge-reverb" "$LOG"'
afirmar "el servicio por defecto es php8.3-fpm" 'grep -q "systemctl reload php8.3-fpm" "$LOG"'

echo "B) El backup falla"
corrida FAIL_BACKUP=1 -- --sin-assets
afirmar "sale con error" '[ "$CODIGO" = 1 ]'
afirmar "sale de mantenimiento él solo (la web no se queda caída)" 'llamo "php artisan up"'
afirmar "NO toca el código: ni composer, ni migrar, ni pull" '! llamo "composer" && ! llamo "php artisan migrate"'
afirmar "lo explica" 'dijo "backup pre-deploy falló"'

echo "C) Falla la migración"
corrida FAIL_MIGRATE=1 -- --sin-assets
afirmar "sale con error" '[ "$CODIGO" = 1 ]'
afirmar "deja la aplicación en MANTENIMIENTO a propósito (no sale con código a medias)" '! llamo "php artisan up"'
afirmar "dice en qué paso falló" 'dijo "ERROR (código 1) en: [7/9]"'
afirmar "explica que sigue en mantenimiento y qué hacer" 'dijo "SIGUE EN MANTENIMIENTO" && dijo "Qué hacer"'
afirmar "ofrece el rollback con el tag de ESTA corrida" 'dijo "./rollback.sh deploy-"'
afirmar "avisa de que MySQL no deshace el esquema a medias" 'dijo "no deshace los cambios de esquema"'
afirmar "no sigue con caché, workers ni subir" '! llamo "php artisan optimize" && ! llamo "supervisorctl"'

echo "D) Falla composer"
corrida FAIL_COMPOSER=1 -- --sin-assets
afirmar "sale con error en el paso 5, en mantenimiento" '[ "$CODIGO" = 1 ] && dijo "en: [5/9]" && ! llamo "php artisan up"'
afirmar "no llega a migrar" '! llamo "php artisan migrate"'

echo "E) No se puede recargar PHP-FPM"
corrida FAIL_RELOAD=1 -- --sin-assets
afirmar "el despliegue termina (código 0)" '[ "$CODIGO" = 0 ]'
afirmar "AVISA con el comando exacto a ejecutar" 'dijo "AVISO: no se pudo recargar php8.3-fpm" && dijo "sudo systemctl reload php8.3-fpm"'
afirmar "explica el riesgo del código viejo en OPcache" 'dijo "SIGUE sirviendo el código anterior"'

echo "F) Otro nombre de servicio PHP-FPM"
corrida PHP_FPM_SERVICE=php8.4-fpm -- --sin-assets
afirmar "usa el servicio configurado" 'grep -q "systemctl reload php8.4-fpm" "$LOG" && ! grep -q "systemctl reload php8.3-fpm" "$LOG"'

echo "G) Opciones"
corrida X=1 -- --sin-assets --sin-migrar
afirmar "--sin-migrar no migra" '! llamo "php artisan migrate" && [ "$CODIGO" = 0 ]'
corrida X=1 --
afirmar "sin --sin-assets compila los assets con npm ci y npm run build" 'llamo "npm ci" && llamo "npm run build"'
corrida X=1 -- --opcion-rara
afirmar "un argumento desconocido se rechaza ANTES de tocar nada" '[ "$CODIGO" = 1 ] && ! llamo "php artisan down" && dijo "Argumento desconocido"'

echo "H) Verificación de /health"
corrida DEPLOY_HEALTH_URL=https://ejemplo.test/health HEALTH_CODE=200 -- --sin-assets
afirmar "con 200 lo da por verificado" 'dijo "deploy verificado"'
corrida DEPLOY_HEALTH_URL=https://ejemplo.test/health HEALTH_CODE=503 -- --sin-assets
afirmar "con 503 avisa y recuerda el rollback (sin fallar el script)" '[ "$CODIGO" = 0 ] && dijo "respondió 503" && dijo "./rollback.sh deploy-"'

echo "I) Árbol de trabajo sucio"
echo "cambio sin commitear" >> "$T/servidor/artisan"
corrida X=1 -- --sin-assets
afirmar "se niega a desplegar y NO activa el mantenimiento" '[ "$CODIGO" = 1 ] && ! llamo "php artisan down" && dijo "cambios sin commitear"'
git -C "$T/servidor" checkout -q -- artisan

# ── rollback.sh (va el último: deja el repositorio en detached HEAD sobre el tag) ────────────────────────────────────────
# rollback [VAR=valor ...] -- <argumentos de rollback.sh> ; la respuesta a read se pasa por RESPUESTA
rollback() {
    : > "$LOG"
    local vars=() args=()
    while [ $# -gt 0 ] && [ "$1" != "--" ]; do vars+=("$1"); shift; done
    [ "${1:-}" = "--" ] && shift
    args=("$@")
    (
        cd "$T/servidor" || exit 1
        export PATH="$T/stubs:$PATH" DEPLOY_LOG="$LOG"
        printf '%s
' "${RESPUESTA:-}" | env "${vars[@]}" bash ./rollback.sh "${args[@]}" > "$SALIDA" 2>&1
        echo "salida=$?" >> "$LOG"
    )
    CODIGO="$(sed -n 's/^salida=//p' "$LOG")"
}
TAG_REAL="$(git -C "$T/remoto.git" tag --list 'deploy-*' | head -1)"

echo "J) rollback.sh"
rollback X=1 --
afirmar "sin tag: muestra el uso y NO activa el mantenimiento" '[ "$CODIGO" = 1 ] && dijo "Uso: ./rollback.sh" && ! llamo "php artisan down"'
rollback X=1 -- deploy-no-existe
afirmar "con un tag inexistente: se niega ANTES de tocar nada" '[ "$CODIGO" = 1 ] && dijo "no existe" && ! llamo "php artisan down"'
rollback X=1 -- "$TAG_REAL"
afirmar "con un tag real termina con código 0" '[ "$CODIGO" = 0 ]'
afirmar "revierte el código dejando el repositorio en detached HEAD sobre el tag" '! git -C "$T/servidor" symbolic-ref -q HEAD >/dev/null && [ "$(git -C "$T/servidor" describe --tags --exact-match)" = "$TAG_REAL" ]'
afirmar "recarga PHP-FPM ANTES de salir de mantenimiento (si no, seguiría sirviendo el código que se quiere deshacer)" '[ "$(posicion "systemctl reload")" -lt "$(posicion "php artisan up")" ]'
afirmar "no toca la base de datos si no se pidió" '! llamo "mysql"'
afirmar "no revierte migraciones (solo el código)" '! llamo "php artisan migrate"'
rollback FAIL_RELOAD=1 -- "$TAG_REAL"
afirmar "si no puede recargar PHP-FPM, avisa con el comando exacto y sigue" '[ "$CODIGO" = 0 ] && dijo "sudo systemctl reload php8.3-fpm" && dijo "SIGUE sirviendo el código que querías revertir"'
rollback X=1 -- "$TAG_REAL" --restaurar-bd=/no/existe.sql
afirmar "--restaurar-bd con un archivo que no existe: no toca la BD y lo dice" '! llamo "mysql" && dijo "La base de datos NO se tocó"'
echo "-- SELECT 1;" > "$T/backup.sql"
RESPUESTA="no" rollback X=1 -- "$TAG_REAL" --restaurar-bd="$T/backup.sql"
afirmar "restaurar la BD sin escribir CONFIRMAR cancela y NO toca la BD" '! llamo "mysql" && dijo "cancelada por el operador"'
RESPUESTA="confirmar" rollback X=1 -- "$TAG_REAL" --restaurar-bd="$T/backup.sql"
afirmar "'confirmar' en minúsculas tampoco vale (pide MAYÚSCULAS)" '! llamo "mysql"'
RESPUESTA="CONFIRMAR" rollback X=1 -- "$TAG_REAL" --restaurar-bd="$T/backup.sql"
afirmar "con CONFIRMAR restaura sobre la base de datos del .env" 'grep -q "mysql --host=127.0.0.1 --port=3306 -uusuario sge_prueba" "$LOG"'

echo ""
echo "$((pruebas - fallos))/$pruebas comprobaciones correctas$([ "$fallos" -gt 0 ] && echo " — $fallos FALLARON")."
[ "$fallos" -eq 0 ]
