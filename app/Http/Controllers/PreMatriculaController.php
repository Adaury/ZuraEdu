<?php

namespace App\Http\Controllers;

use App\Mail\PreMatriculaConfirmacion;
use App\Models\AlertaSistema;
use App\Models\PreMatricula;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PreMatriculaController extends Controller
{
    /**
     * Muestra el formulario público de pre-matrícula.
     */
    public function create()
    {
        $grados = PreMatricula::gradosDisponibles();
        return view('inscripcion', compact('grados'));
    }

    /**
     * Procesa y guarda la solicitud de pre-matrícula.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombres'               => ['required', 'string', 'max:100'],
            'apellidos'             => ['required', 'string', 'max:100'],
            'fecha_nacimiento'      => ['required', 'date', 'before:today'],
            'grado_solicitado'      => ['required', 'string', 'max:80'],
            'nombre_representante'  => ['required', 'string', 'max:150'],
            'cedula_representante'  => ['required', 'string', 'max:20'],
            'telefono'              => ['required', 'string', 'max:30'],
            'email'                 => ['required', 'email', 'max:150'],
            'direccion'             => ['required', 'string', 'max:300'],
        ], [
            'nombres.required'              => 'El nombre del estudiante es obligatorio.',
            'apellidos.required'            => 'El apellido del estudiante es obligatorio.',
            'fecha_nacimiento.required'     => 'La fecha de nacimiento es obligatoria.',
            'fecha_nacimiento.before'       => 'La fecha de nacimiento debe ser anterior a hoy.',
            'grado_solicitado.required'     => 'Debe seleccionar el grado solicitado.',
            'nombre_representante.required' => 'El nombre del representante es obligatorio.',
            'cedula_representante.required' => 'La cédula del representante es obligatoria.',
            'telefono.required'             => 'El teléfono es obligatorio.',
            'email.required'                => 'El correo electrónico es obligatorio.',
            'email.email'                   => 'Ingrese un correo electrónico válido.',
            'direccion.required'            => 'La dirección es obligatoria.',
        ]);

        $preMatricula = PreMatricula::create($validated);

        // ── Notificar a admins/directores ─────────────────────────────────────
        $admins = User::role(['Administrador', 'Director'])->get();
        foreach ($admins as $admin) {
            try {
                AlertaSistema::create([
                    'tipo'            => 'otro',
                    'titulo'          => 'Nueva solicitud de pre-matrícula',
                    'mensaje'         => "Nueva solicitud de pre-matrícula de {$preMatricula->nombre_completo} para {$preMatricula->grado_solicitado}.",
                    'nivel'           => 'info',
                    'destinatario_id' => $admin->id,
                    'referencia_tipo' => 'PreMatricula',
                    'referencia_id'   => $preMatricula->id,
                ]);
            } catch (\Throwable $e) {
                // Alerta no crítica — no bloquear el flujo
            }
        }

        // ── Email de confirmación al solicitante ──────────────────────────────
        try {
            Mail::to($preMatricula->email)->queue(new PreMatriculaConfirmacion($preMatricula));
        } catch (\Throwable $e) {
            // El guardado ya ocurrió; el email falla en silencio
        }

        return redirect()->route('inscripcion.confirmacion')
            ->with('pre_matricula_id', $preMatricula->id);
    }

    /**
     * Página de confirmación post-envío.
     */
    public function confirmacion()
    {
        return view('inscripcion-confirmacion');
    }
}
