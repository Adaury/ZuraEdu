<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use ZipArchive;

/**
 * Exportación de los datos de UN colegio (tenant): un ZIP con un CSV por tabla, solo con las filas de su `tenant_id`.
 *
 * Es una copia para consulta/archivo del propio colegio. NO es el respaldo de la plataforma (ese es global y solo lo ve el
 * superadministrador, ver BackupSistema) ni sirve para restaurar: para eso existe el respaldo global.
 *
 * Reglas de seguridad:
 *  - El tenant sale de quien llama (nunca del navegador) y cada consulta lleva `where tenant_id = ?`.
 *  - Nunca salen columnas con contraseñas, tokens ni secretos (regex), ni las tablas con credenciales de terceros.
 *  - Mensajes privados y notificaciones quedan fuera.
 *  - Las tablas de salud, disciplina, trabajo social y nómina solo salen si el llamador lo pide (permiso aparte).
 */
class RespaldoColegioService
{
    /** Columnas que jamás se exportan (contraseñas, tokens, claves). */
    public const COLUMNAS_SECRETAS = '/(password|remember_token|token|secret|api_key|apikey|credential|two_factor|recovery_code)/i';

    /** Tablas con credenciales de terceros, suscripción o sesiones: nunca se exportan. */
    public const TABLAS_EXCLUIDAS = [
        'system_settings', 'odoo_conexiones', 'sigerd_config', 'subscriptions', 'support_sessions', 'qr_asistencia_tokens',
        // Comunicación privada y cachés
        'mensajes', 'tenant_chat_messages', 'classroom_messages', 'comentarios_classroom', 'notificaciones', 'rendimiento_cache',
    ];

    /** Datos delicados: solo con `incluirSensibles`. */
    public const TABLAS_SENSIBLES = [
        'fichas_salud', 'incidentes_medicos', 'faltas_disciplinarias', 'conducta_registros', 'casos_seguimiento',
        'intervenciones_caso', 'nomina_empleados', 'pagos_nomina', 'evaluaciones_docentes',
    ];

    /** @return array<string, string[]> tabla => columnas, de todas las tablas que tienen `tenant_id` */
    public function esquema(): array
    {
        $db = DB::connection();
        $esquema = [];

        if ($db->getDriverName() === 'mysql') {
            $filas = $db->select(
                'select c.table_name as t, c.column_name as c from information_schema.columns c
                 join information_schema.tables x on x.table_schema = c.table_schema and x.table_name = c.table_name and x.table_type = ?
                 where c.table_schema = ? and c.table_name in (
                     select table_name from information_schema.columns where table_schema = ? and column_name = ?
                 ) order by c.table_name, c.ordinal_position',
                ['BASE TABLE', $db->getDatabaseName(), $db->getDatabaseName(), 'tenant_id']
            );
            foreach ($filas as $f) {
                $esquema[$f->t][] = $f->c;
            }
        } else {
            foreach (Schema::getTableListing() as $tabla) {
                $columnas = Schema::getColumnListing($tabla);
                if (in_array('tenant_id', $columnas, true)) {
                    $esquema[$tabla] = $columnas;
                }
            }
        }

        ksort($esquema);

        return $esquema;
    }

    /** Tablas y columnas que se exportarían: ['tabla' => ['columnas' => [...], 'omitidas' => [...], 'sensible' => bool]] */
    public function plan(bool $incluirSensibles): array
    {
        $plan = [];
        foreach ($this->esquema() as $tabla => $columnas) {
            if (in_array($tabla, self::TABLAS_EXCLUIDAS, true)) {
                continue;
            }
            $sensible = in_array($tabla, self::TABLAS_SENSIBLES, true);
            if ($sensible && ! $incluirSensibles) {
                continue;
            }
            $validas = array_values(array_filter($columnas, fn ($c) => ! preg_match(self::COLUMNAS_SECRETAS, $c)));
            $plan[$tabla] = [
                'columnas' => $validas,
                'omitidas' => array_values(array_diff($columnas, $validas)),
                'sensible' => $sensible,
            ];
        }

        return $plan;
    }

    /**
     * Genera el ZIP y devuelve su ruta (el llamador debe borrarlo tras enviarlo).
     *
     * @return array{ruta: string, tablas: int, filas: int}
     */
    public function generar(int $tenantId, string $nombreInstitucion, string $solicitadoPor, bool $incluirSensibles): array
    {
        set_time_limit(600);

        $plan = $this->plan($incluirSensibles);
        $zipRuta = tempnam(sys_get_temp_dir(), 'respaldo_colegio_');
        $zip = new ZipArchive();
        if ($zip->open($zipRuta, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el archivo ZIP.');
        }

        $temporales = [];
        $conteos = [];
        $totalFilas = 0;

        foreach ($plan as $tabla => $info) {
            if (! $info['columnas']) {
                continue;
            }
            $tmp = tempnam(sys_get_temp_dir(), 'rc_csv_');
            $temporales[] = $tmp;
            $fh = fopen($tmp, 'w');
            fwrite($fh, "\xEF\xBB\xBF");   // BOM: Excel abre los acentos bien
            fputcsv($fh, $info['columnas']);

            $q = DB::table($tabla)->where('tenant_id', $tenantId)->select($info['columnas']);
            $filas = in_array('id', $info['columnas'], true) ? $q->lazyById(2000, 'id') : $q->cursor();

            $n = 0;
            foreach ($filas as $fila) {
                fputcsv($fh, array_map(fn ($v) => $this->celdaSegura($v), array_values((array) $fila)));
                $n++;
            }
            fclose($fh);

            $conteos[$tabla] = $n;
            $totalFilas += $n;
            $zip->addFile($tmp, 'datos/' . $tabla . '.csv');
        }

        $zip->addFromString('LEEME.txt', $this->leeme($nombreInstitucion, $solicitadoPor, $incluirSensibles, $plan, $conteos));
        $zip->addFromString('manifest.json', json_encode([
            'institucion' => $nombreInstitucion,
            'generado_en' => now()->toIso8601String(),
            'solicitado_por' => $solicitadoPor,
            'incluye_datos_sensibles' => $incluirSensibles,
            'tablas' => $conteos,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $zip->close();
        foreach ($temporales as $tmp) {
            @unlink($tmp);
        }

        return ['ruta' => $zipRuta, 'tablas' => count($conteos), 'filas' => $totalFilas];
    }

    /** Neutraliza fórmulas de Excel (=, +, -, @) con comilla simple (convención OWASP). */
    private function celdaSegura(mixed $valor): mixed
    {
        if (is_string($valor) && $valor !== '' && in_array($valor[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $valor;
        }

        return $valor;
    }

    private function leeme(string $institucion, string $por, bool $sensibles, array $plan, array $conteos): string
    {
        $l = [];
        $l[] = 'COPIA DE LOS DATOS DE ' . mb_strtoupper($institucion);
        $l[] = 'Generada: ' . now()->format('d/m/Y H:i') . ' · Solicitada por: ' . $por;
        $l[] = '';
        $l[] = 'Cada archivo de la carpeta "datos" es una tabla en formato CSV (UTF-8, se abre con Excel).';
        $l[] = 'Contiene SOLO los datos de ' . $institucion . '.';
        $l[] = '';
        $l[] = 'NO incluye: contraseñas, tokens ni claves; credenciales de integraciones (Odoo, SIGERD, pagos);';
        $l[] = 'mensajes privados ni notificaciones; archivos subidos (fotos, entregas, documentos).';
        $l[] = $sensibles
            ? 'SÍ incluye datos sensibles (salud, disciplina, trabajo social, nómina, evaluaciones docentes): trátelos con reserva.'
            : 'NO incluye datos sensibles (salud, disciplina, trabajo social, nómina, evaluaciones docentes): los exporta la Dirección.';
        $l[] = '';
        $l[] = 'Esta copia sirve para consulta y archivo. No permite restaurar el sistema por sí sola.';
        $l[] = '';
        $l[] = 'TABLAS (filas):';
        foreach ($conteos as $tabla => $n) {
            $l[] = sprintf('  %-34s %s%s', $tabla, number_format($n), ! empty($plan[$tabla]['omitidas']) ? '   (sin: ' . implode(', ', $plan[$tabla]['omitidas']) . ')' : '');
        }

        return implode("\r\n", $l) . "\r\n";
    }
}
