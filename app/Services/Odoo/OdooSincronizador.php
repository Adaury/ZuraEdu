<?php

namespace App\Services\Odoo;

use App\Models\OdooConexion;
use App\Models\OdooVinculo;
use App\Models\Pago;
use App\Models\Representante;

/**
 * Envía los datos del centro a SU Odoo (una sola dirección: sistema escolar → Odoo):
 *   · Representantes  → contactos (res.partner), con `ref` = SGE{centro}-R-{id} como clave estable.
 *   · Cuotas (Pagos)  → facturas de cliente (account.move), en borrador por defecto, a nombre del representante principal del estudiante.
 *
 * Idempotente: cada registro enviado queda vinculado (odoo_vinculos) con una huella de lo último enviado; si no cambió, no se vuelve
 * a llamar a Odoo. Si el vínculo se pierde, se reencuentra por la `ref` en vez de duplicar. Todo corre dentro del centro actual (scope de tenant).
 * Un error en un registro se anota y NO detiene a los demás; un error de credenciales sí detiene la ejecución (no tiene sentido insistir).
 *
 * Todavía NO incluye: registrar el cobro de la factura en Odoo ni traer datos desde Odoo.
 */
class OdooSincronizador
{
    private OdooClient $cliente;
    /** @var array<int,string> */
    private array $errores = [];

    public function __construct(private readonly OdooConexion $conexion, ?OdooClient $cliente = null)
    {
        $this->cliente = $cliente ?? new OdooClient($conexion);
    }

    /** @return array{contactos: ?array, facturas: ?array, errores: array<int,string>} */
    public function ejecutar(): array
    {
        $this->errores = [];
        $resumen = ['contactos' => null, 'facturas' => null, 'errores' => []];

        try {
            if ($this->conexion->sync_contactos) {
                $resumen['contactos'] = $this->sincronizarContactos();
            }
            if ($this->conexion->sync_facturas) {
                $resumen['facturas'] = $this->sincronizarFacturas();
            }
        } catch (OdooException $e) {
            // Credenciales / servidor caído: se detiene todo y se deja el motivo visible para el administrador.
            $this->errores[] = $e->getMessage();
        }

        $this->conexion->forceFill([
            'ultima_sync_at' => now(),
            'ultimo_error'   => $this->errores ? mb_substr(implode("\n", array_slice($this->errores, 0, 5)), 0, 1000) : null,
        ])->save();

        $resumen['errores'] = $this->errores;

        return $resumen;
    }

    // ── contactos ───────────────────────────────────────────────────────────

    /** @return array{creados:int, actualizados:int, sin_cambios:int, errores:int} */
    public function sincronizarContactos(): array
    {
        $r = ['creados' => 0, 'actualizados' => 0, 'sin_cambios' => 0, 'errores' => 0];
        $limite = (int) config('odoo.lote_maximo', 300);
        $enviados = 0;

        foreach (Representante::orderBy('id')->cursor() as $rep) {
            if ($enviados >= $limite) {
                break;   // el resto sigue en la próxima ejecución
            }
            try {
                $estado = $this->asegurarContacto($rep);
                $r[$estado]++;
                if ($estado !== 'sin_cambios') {
                    $enviados++;
                }
            } catch (OdooException $e) {
                if ($e->credenciales) {
                    throw $e;
                }
                $r['errores']++;
                $this->errores[] = "Contacto #{$rep->id}: " . $e->getMessage();
            }
        }

        return $r;
    }

    /** Crea o actualiza el contacto del representante en Odoo. @return 'creados'|'actualizados'|'sin_cambios' */
    private function asegurarContacto(Representante $rep): string
    {
        $valores = [
            'name'         => trim($rep->nombre_completo) ?: "Representante #{$rep->id}",
            'ref'          => $this->ref('R', $rep->id),
            'company_type' => 'person',
            'email'        => $rep->email ?: false,
            'phone'        => $rep->telefono ?: false,
            'street'       => $rep->direccion ?: false,
            'function'     => $rep->ocupacion ?: false,
            // La cédula va en las notas, NO en `vat`: Odoo valida el formato del VAT/RNC y rechazaría el contacto entero.
            'comment'      => $rep->cedula ? "Cédula: {$rep->cedula}" : false,
        ];
        $huella = hash('sha256', json_encode($valores));

        $vinculo = OdooVinculo::where(['entidad_tipo' => 'representante', 'entidad_id' => $rep->id, 'odoo_modelo' => 'res.partner'])->first();

        if ($vinculo && $vinculo->huella === $huella) {
            return 'sin_cambios';
        }

        if ($vinculo) {
            try {
                $this->cliente->escribir('res.partner', [(int) $vinculo->odoo_id], $valores);
                $this->guardarVinculo('representante', $rep->id, 'res.partner', (int) $vinculo->odoo_id, $huella);

                return 'actualizados';
            } catch (OdooException $e) {
                if ($e->credenciales || stripos($e->getMessage(), 'does not exist') === false) {
                    throw $e;
                }
                // El contacto se borró en Odoo: se vuelve a crear (abajo).
            }
        }

        // Sin vínculo (o roto): buscar por la ref estable antes de crear, para no duplicar.
        $existente = $this->cliente->buscarLeer('res.partner', [['ref', '=', $valores['ref']]], ['id'], 1);
        if ($existente) {
            $id = (int) $existente[0]['id'];
            $this->cliente->escribir('res.partner', [$id], $valores);
            $this->guardarVinculo('representante', $rep->id, 'res.partner', $id, $huella);

            return 'actualizados';
        }

        $id = $this->cliente->crear('res.partner', $valores);
        $this->guardarVinculo('representante', $rep->id, 'res.partner', $id, $huella);

        return 'creados';
    }

    // ── facturas ────────────────────────────────────────────────────────────

    /** @return array{creados:int, sin_cambios:int, cambiados:int, sin_representante:int, errores:int} */
    public function sincronizarFacturas(): array
    {
        $r = ['creados' => 0, 'sin_cambios' => 0, 'cambiados' => 0, 'sin_representante' => 0, 'errores' => 0];
        $limite = (int) config('odoo.lote_maximo', 300);
        $enviados = 0;

        $pagos = Pago::with(['matricula.estudiante.representantes'])
            ->whereIn('estado', ['pendiente', 'vencido', 'pagado'])
            ->orderBy('id')->cursor();

        foreach ($pagos as $pago) {
            if ($enviados >= $limite) {
                break;
            }
            try {
                $estado = $this->enviarFactura($pago);
                $r[$estado]++;
                if ($estado === 'creados') {
                    $enviados++;
                }
            } catch (OdooException $e) {
                if ($e->credenciales) {
                    throw $e;
                }
                $r['errores']++;
                $this->errores[] = "Factura del pago #{$pago->id}: " . $e->getMessage();
            }
        }

        return $r;
    }

    /** @return 'creados'|'sin_cambios'|'cambiados'|'sin_representante' */
    private function enviarFactura(Pago $pago): string
    {
        $huella  = hash('sha256', implode('|', [$pago->concepto, number_format((float) $pago->monto, 2, '.', ''), $pago->fecha_vencimiento?->toDateString()]));
        $vinculo = OdooVinculo::where(['entidad_tipo' => 'pago', 'entidad_id' => $pago->id, 'odoo_modelo' => 'account.move'])->first();

        if ($vinculo) {
            if ($vinculo->huella === $huella) {
                return 'sin_cambios';
            }
            // Cambió el concepto/monto/vencimiento DESPUÉS de crear la factura. No se reescribe una factura (puede estar publicada o cobrada):
            // se deja constancia visible para que contabilidad la ajuste en Odoo.
            $vinculo->update(['ultimo_error' => 'El pago cambió después de enviarse a Odoo; ajusta la factura en Odoo.']);

            return 'cambiados';
        }

        $estudiante = $pago->matricula?->estudiante;
        $rep = $estudiante?->representantes->sortByDesc(fn ($x) => (int) ($x->pivot->es_principal ?? 0))->first();
        if (! $rep) {
            return 'sin_representante';
        }

        $this->asegurarContacto($rep);
        $partnerId = (int) OdooVinculo::where(['entidad_tipo' => 'representante', 'entidad_id' => $rep->id, 'odoo_modelo' => 'res.partner'])->value('odoo_id');

        $ref = $this->ref('P', $pago->id);

        // ¿Ya existe en Odoo (vínculo perdido)? Se adopta en vez de duplicar.
        $existente = $this->cliente->buscarLeer('account.move', [['ref', '=', $ref], ['move_type', '=', 'out_invoice']], ['id'], 1);
        if ($existente) {
            $this->guardarVinculo('pago', $pago->id, 'account.move', (int) $existente[0]['id'], $huella);

            return 'sin_cambios';
        }

        $nota = 'Estudiante: ' . ($estudiante?->nombre_completo ?? '—');
        if ($pago->estado === 'pagado') {
            $nota .= ' · Pagado en el sistema escolar el ' . ($pago->fecha_pago?->format('d/m/Y') ?? '—') . ($pago->metodo_pago ? " ({$pago->metodo_pago})" : '')
                . '. El cobro aún no se registra automáticamente en Odoo.';
        }

        $valores = [
            'move_type'         => 'out_invoice',
            'partner_id'        => $partnerId,
            'ref'               => $ref,
            'invoice_date'      => now()->toDateString(),
            'invoice_date_due'  => $pago->fecha_vencimiento?->toDateString(),
            'narration'         => $nota,
            'invoice_line_ids'  => [[0, 0, [
                'name'       => $pago->concepto . ' — ' . ($estudiante?->nombre_completo ?? ''),
                'quantity'   => 1,
                'price_unit' => (float) $pago->monto,
                'tax_ids'    => [[6, 0, []]],   // sin impuestos: el total en Odoo = el monto de la cuota (educación exenta; no dejar que Odoo agregue ITBIS por defecto)
            ]]],
        ];
        if ($this->conexion->diario_id) {
            $valores['journal_id'] = (int) $this->conexion->diario_id;
        }

        $id = $this->cliente->crear('account.move', $valores);
        $this->guardarVinculo('pago', $pago->id, 'account.move', $id, $huella);

        if ($this->conexion->publicar_facturas) {
            try {
                $this->cliente->ejecutar('account.move', 'action_post', [[$id]]);
            } catch (OdooException $e) {
                if ($e->credenciales) {
                    throw $e;
                }
                // La factura quedó creada en borrador: se anota el motivo para que se publique a mano.
                OdooVinculo::where(['entidad_tipo' => 'pago', 'entidad_id' => $pago->id, 'odoo_modelo' => 'account.move'])
                    ->update(['ultimo_error' => 'Creada en borrador; no se pudo publicar: ' . mb_substr($e->getMessage(), 0, 300)]);
            }
        }

        return 'creados';
    }

    // ── utilidades ──────────────────────────────────────────────────────────

    /** Clave estable e independiente por centro (varios colegios podrían compartir una misma instancia de Odoo). */
    private function ref(string $tipo, int $id): string
    {
        return "SGE{$this->conexion->tenant_id}-{$tipo}-{$id}";
    }

    private function guardarVinculo(string $tipo, int $id, string $modelo, int $odooId, string $huella): void
    {
        OdooVinculo::updateOrCreate(
            ['entidad_tipo' => $tipo, 'entidad_id' => $id, 'odoo_modelo' => $modelo],
            ['odoo_id' => $odooId, 'huella' => $huella, 'sincronizado_at' => now(), 'ultimo_error' => null],
        );
    }
}
