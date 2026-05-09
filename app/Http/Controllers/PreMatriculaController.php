<?php

namespace App\Http\Controllers;

use App\Mail\PreMatriculaConfirmacion;
use App\Models\AlertaSistema;
use App\Models\ConfigInstitucional;
use App\Models\PreMatricula;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PreMatriculaController extends Controller
{
    private function instData(): array
    {
        $nombre = ConfigInstitucional::withoutGlobalScopes()
            ->where('clave', 'nombre_institucion')->value('valor');

        $logo = ConfigInstitucional::withoutGlobalScopes()
            ->where('clave', 'logo_url')->value('valor');

        $settings = \Illuminate\Support\Facades\DB::table('system_settings')
            ->whereIn('key', ['system_name', 'system_abbr', 'system_logo'])
            ->pluck('value', 'key');

        // Logo: prioridad logo de system_settings, luego ConfigInstitucional
        $logoFinal = $settings['system_logo'] ?? $logo ?? null;

        return [
            'inst'        => $nombre ?: config('app.name'),
            'logo'        => $logoFinal,
            'system_name' => $settings['system_name'] ?? 'ZuraEdu',
            'system_abbr' => $settings['system_abbr'] ?? 'SGE',
        ];
    }

    public function create()
    {
        $grados = PreMatricula::gradosDisponibles();
        return view('inscripcion', array_merge(compact('grados'), $this->instData()));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombres'                => ['required', 'string', 'max:100'],
            'apellidos'              => ['required', 'string', 'max:100'],
            'fecha_nacimiento'       => ['required', 'date', 'before:today'],
            'genero'                 => ['required', 'in:Masculino,Femenino,Otro'],
            'lugar_nacimiento'       => ['nullable', 'string', 'max:150'],
            'cedula_estudiante'      => ['nullable', 'string', 'max:20'],
            'grado_solicitado'       => ['required', 'string', 'max:80'],
            'nombre_representante'   => ['required', 'string', 'max:150'],
            'cedula_representante'   => ['required', 'string', 'max:20'],
            'relacion_representante' => ['required', 'in:Padre,Madre,Tutor/a,Otro'],
            'telefono'               => ['required', 'string', 'max:30'],
            'email'                  => ['required', 'email', 'max:150'],
            'direccion'              => ['required', 'string', 'max:300'],
            'cedula_rep_doc'         => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:3072'],
            'acta_nacimiento_doc'    => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:3072'],
            'foto_doc'               => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $codigo = PreMatricula::generateCodigo();

        $documentos = [];
        foreach ([
            'cedula_rep_doc'      => 'cedula_representante',
            'acta_nacimiento_doc' => 'acta_nacimiento',
            'foto_doc'            => 'foto_estudiante',
        ] as $field => $key) {
            if ($request->hasFile($field)) {
                $documentos[$key] = $request->file($field)->store("pre_matriculas/{$codigo}", 'public');
            }
        }

        $pm = PreMatricula::withoutGlobalScopes()->create([
            'codigo'                 => $codigo,
            'nombres'                => $validated['nombres'],
            'apellidos'              => $validated['apellidos'],
            'fecha_nacimiento'       => $validated['fecha_nacimiento'],
            'genero'                 => $validated['genero'],
            'lugar_nacimiento'       => $validated['lugar_nacimiento'] ?? null,
            'cedula_estudiante'      => $validated['cedula_estudiante'] ?? null,
            'grado_solicitado'       => $validated['grado_solicitado'],
            'nombre_representante'   => $validated['nombre_representante'],
            'cedula_representante'   => $validated['cedula_representante'],
            'relacion_representante' => $validated['relacion_representante'],
            'telefono'               => $validated['telefono'],
            'email'                  => $validated['email'],
            'direccion'              => $validated['direccion'],
            'estado'                 => 'pendiente',
            'documentos'             => $documentos ?: null,
        ]);

        try { Mail::to($pm->email)->queue(new PreMatriculaConfirmacion($pm)); } catch (\Throwable $e) {}

        try {
            foreach (User::role(['Administrador', 'Director'])->get() as $admin) {
                AlertaSistema::create([
                    'tipo'            => 'otro',
                    'titulo'          => 'Nueva solicitud de pre-matricula',
                    'mensaje'         => "Nueva pre-matricula ({$pm->codigo}): {$pm->nombre_completo} para {$pm->grado_solicitado}.",
                    'nivel'           => 'info',
                    'destinatario_id' => $admin->id,
                    'referencia_tipo' => 'PreMatricula',
                    'referencia_id'   => $pm->id,
                ]);
            }
        } catch (\Throwable $e) {}

        return redirect()->route('inscripcion.confirmacion', $codigo);
    }

    public function confirmacion(string $codigo)
    {
        $pm = PreMatricula::withoutGlobalScopes()
            ->where('codigo', strtoupper($codigo))
            ->firstOrFail();

        return view('inscripcion-confirmacion', array_merge(compact('pm'), $this->instData()));
    }

    public function consulta(Request $request)
    {
        $pm       = null;
        $error    = null;
        $busqueda = $request->input('codigo', '');

        if ($request->filled('codigo')) {
            $codigo = strtoupper(trim($request->codigo));
            $pm = PreMatricula::withoutGlobalScopes()->where('codigo', $codigo)->first();
            if (! $pm) {
                $error = 'No se encontro ninguna solicitud con ese codigo. Verifique e intente nuevamente.';
            }
        }

        return view('inscripcion-consulta', array_merge(compact('pm', 'error', 'busqueda'), $this->instData()));
    }
}
