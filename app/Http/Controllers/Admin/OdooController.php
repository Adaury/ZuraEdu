<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SincronizarOdooJob;
use App\Models\ActivityLog;
use App\Models\OdooConexion;
use App\Models\OdooVinculo;
use App\Services\Odoo\OdooClient;
use App\Services\Odoo\OdooException;
use App\Services\Odoo\OdooUrlSegura;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Integración con Odoo de UN centro educativo: el administrador pone la URL, la base de datos, el usuario y la clave de API y el sistema
 * vincula los datos. Todo va por el scope de tenant (OdooConexion/OdooVinculo): cada centro ve y toca solo la suya. La clave de API se
 * guarda cifrada y nunca se vuelve a mostrar; dejarla en blanco al guardar conserva la que ya estaba.
 */
class OdooController extends Controller
{
    public function index()
    {
        $conexion = OdooConexion::first();

        $vinculados = OdooVinculo::selectRaw('odoo_modelo, count(*) n')->groupBy('odoo_modelo')->pluck('n', 'odoo_modelo');
        $conErrores = OdooVinculo::whereNotNull('ultimo_error')->latest('updated_at')->limit(10)->get(['entidad_tipo', 'entidad_id', 'ultimo_error', 'updated_at']);

        return view('admin.odoo.index', [
            'conexion'   => $conexion,
            'contactos'  => (int) ($vinculados['res.partner'] ?? 0),
            'facturas'   => (int) ($vinculados['account.move'] ?? 0),
            'conErrores' => $conErrores,
        ]);
    }

    public function guardar(Request $request)
    {
        $actual = OdooConexion::first();

        $data = $request->validate([
            'url'               => 'required|string|max:255',
            'base_datos'        => 'required|string|max:100',
            'usuario'           => 'required|string|max:150',
            'api_key'           => [$actual ? 'nullable' : 'required', 'string', 'max:255'],
            'diario_id'         => 'nullable|integer|min:1',
            'activo'            => 'nullable|boolean',
            'sync_contactos'    => 'nullable|boolean',
            'sync_facturas'     => 'nullable|boolean',
            'publicar_facturas' => 'nullable|boolean',
        ], ['api_key.required' => 'Pega la clave de API de Odoo.']);

        // La URL la llamará el servidor: se valida (https, sin red interna) ANTES de guardarla.
        try {
            $url = OdooUrlSegura::validar($data['url'])['url'];
        } catch (OdooException $e) {
            throw ValidationException::withMessages(['url' => $e->getMessage()]);
        }

        $cambioDestino = $actual && (rtrim($actual->url, '/') !== $url || $actual->base_datos !== $data['base_datos'] || $actual->usuario !== $data['usuario']);

        $campos = [
            'url'               => $url,
            'base_datos'        => $data['base_datos'],
            'usuario'           => $data['usuario'],
            'diario_id'         => $data['diario_id'] ?? null,
            'activo'            => $request->boolean('activo'),
            'sync_contactos'    => $request->boolean('sync_contactos'),
            'sync_facturas'     => $request->boolean('sync_facturas'),
            'publicar_facturas' => $request->boolean('publicar_facturas'),
        ];
        if (! empty($data['api_key'])) {
            $campos['api_key'] = $data['api_key'];   // si va vacía se conserva la guardada
        }
        if ($cambioDestino || ! empty($data['api_key'])) {
            // Credenciales nuevas: lo comprobado antes ya no vale.
            $campos += ['uid_odoo' => null, 'version_odoo' => null, 'ultimo_test_ok' => null, 'ultimo_test_at' => null, 'ultimo_error' => null];
        }

        $conexion = $actual ? tap($actual)->update($campos) : OdooConexion::create($campos);

        $aviso = '';
        if ($cambioDestino) {
            // Otro Odoo (u otra base): los ids guardados no significan nada allí. Se reinician los vínculos para no actualizar registros ajenos.
            OdooVinculo::query()->delete();
            $aviso = ' Cambiaste de Odoo: los vínculos anteriores se reiniciaron y los datos se enviarán de nuevo al nuevo Odoo.';
        }

        // Nunca se registra la clave en la auditoría.
        ActivityLog::registrar('odoo_configuracion', 'OdooConexion', $conexion->id,
            "Integración Odoo " . ($actual ? 'actualizada' : 'creada') . " (URL {$conexion->url}, base {$conexion->base_datos}, " . ($conexion->activo ? 'activa' : 'inactiva') . ')'
            . (! empty($data['api_key']) ? ' · clave de API reemplazada' : ''));

        return redirect()->route('admin.odoo.index')->with('success', 'Configuración de Odoo guardada. Usa «Probar conexión» para comprobarla.' . $aviso);
    }

    public function probar()
    {
        $conexion = OdooConexion::first();
        if (! $conexion) {
            return redirect()->route('admin.odoo.index')->with('error', 'Primero guarda las credenciales de Odoo.');
        }

        $cliente = new OdooClient($conexion);

        try {
            $version = $cliente->version();
            $uid     = $cliente->autenticar();

            // Permisos reales del usuario sobre lo que vamos a escribir (sin crear nada).
            $faltan = [];
            foreach (array_filter([
                'res.partner'  => $conexion->sync_contactos || $conexion->sync_facturas,
                'account.move' => $conexion->sync_facturas,
            ]) as $modelo => $_) {
                $puede = $cliente->ejecutar($modelo, 'check_access_rights', ['create'], ['raise_exception' => false]);
                if (! $puede) {
                    $faltan[] = $modelo;
                }
            }

            $conexion->forceFill([
                'uid_odoo' => $uid, 'version_odoo' => mb_substr((string) ($version['server_version'] ?? ''), 0, 40),
                'ultimo_test_ok' => ! $faltan, 'ultimo_test_at' => now(),
                'ultimo_error' => $faltan ? 'El usuario de Odoo no tiene permiso para crear en: ' . implode(', ', $faltan) . '. Dale el acceso en Odoo (Contactos / Facturación).' : null,
            ])->save();

            return redirect()->route('admin.odoo.index')->with(
                $faltan ? 'warning' : 'success',
                $faltan ? "Conectó con Odoo {$conexion->version_odoo}, pero al usuario le faltan permisos: " . implode(', ', $faltan) . '.'
                        : "Conexión correcta con Odoo {$conexion->version_odoo}."
            );
        } catch (OdooException $e) {
            $conexion->forceFill(['ultimo_test_ok' => false, 'ultimo_test_at' => now(), 'ultimo_error' => $e->getMessage()])->save();

            return redirect()->route('admin.odoo.index')->with('error', $e->getMessage());
        }
    }

    public function sincronizar()
    {
        $conexion = OdooConexion::first();

        if (! $conexion || ! $conexion->activo) {
            return redirect()->route('admin.odoo.index')->with('error', 'Activa la integración y guarda antes de sincronizar.');
        }
        if (! $conexion->sync_contactos && ! $conexion->sync_facturas) {
            return redirect()->route('admin.odoo.index')->with('error', 'Marca al menos una cosa para enviar (contactos o facturas).');
        }

        SincronizarOdooJob::dispatch();

        return redirect()->route('admin.odoo.index')->with('success', 'Sincronización en cola. Se envía en segundo plano; actualiza esta página en un momento para ver el resultado.');
    }

    public function desconectar()
    {
        $conexion = OdooConexion::first();

        if ($conexion) {
            ActivityLog::registrar('odoo_desconectado', 'OdooConexion', $conexion->id, 'Integración Odoo desconectada y credenciales borradas');
            OdooVinculo::query()->delete();
            $conexion->delete();
        }

        return redirect()->route('admin.odoo.index')->with('success', 'Odoo desconectado: se borraron las credenciales y los vínculos. Los datos que ya estaban en Odoo no se tocan.');
    }
}
