<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grado;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OnboardingWizardController extends Controller
{
    private const TOTAL_PASOS = 4;

    public function show(int $paso)
    {
        $tenant = app('tenant');

        if ($tenant->onboarding_completado) {
            return redirect()->route('admin.dashboard')
                ->with('info', 'La configuración inicial ya fue completada.');
        }

        $paso = max(1, min($paso, self::TOTAL_PASOS));

        return match ($paso) {
            1 => $this->paso1View($tenant),
            2 => $this->paso2View($tenant),
            3 => $this->paso3View($tenant),
            4 => $this->paso4View($tenant),
        };
    }

    public function store(Request $request, int $paso)
    {
        $tenant = app('tenant');

        if ($tenant->onboarding_completado) {
            return redirect()->route('admin.dashboard');
        }

        return match ($paso) {
            1 => $this->guardarPaso1($request, $tenant),
            2 => $this->guardarPaso2($request, $tenant),
            3 => $this->guardarPaso3($request, $tenant),
            4 => $this->completar($tenant),
            default => redirect()->route('admin.onboarding.show', 1),
        };
    }

    // ── Paso 1: Información de la institución ─────────────────────────────

    private function paso1View($tenant)
    {
        return view('admin.onboarding.paso1', compact('tenant'));
    }

    private function guardarPaso1(Request $request, $tenant)
    {
        $data = $request->validate([
            'nombre_institucion' => ['required', 'string', 'min:3', 'max:120'],
            'tipo'               => ['required', 'in:publico,privado,instituto,tecnico'],
            'telefono_contacto'  => ['nullable', 'string', 'max:30'],
            'email_contacto'     => ['nullable', 'email', 'max:150'],
            'ciudad'             => ['nullable', 'string', 'max:80'],
            'direccion'          => ['nullable', 'string', 'max:250'],
            'color_primario'     => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo'               => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo'] = $path;

            // Eliminar logo anterior si existe
            if ($tenant->logo && Storage::disk('public')->exists($tenant->logo)) {
                Storage::disk('public')->delete($tenant->logo);
            }
        }

        $tenant->update(array_filter($data, fn($v) => $v !== null));
        $tenant->update(['onboarding_paso' => max($tenant->onboarding_paso, 1)]);

        return redirect()->route('admin.onboarding.show', 2);
    }

    // ── Paso 2: Año escolar ───────────────────────────────────────────────

    private function paso2View($tenant)
    {
        $schoolYear = SchoolYear::where('tenant_id', $tenant->id)
            ->latest()
            ->first();

        return view('admin.onboarding.paso2', compact('tenant', 'schoolYear'));
    }

    private function guardarPaso2(Request $request, $tenant)
    {
        $data = $request->validate([
            'nombre'      => ['required', 'string', 'max:30'],
            'fecha_inicio'=> ['required', 'date'],
            'fecha_fin'   => ['required', 'date', 'after:fecha_inicio'],
        ]);

        $schoolYear = SchoolYear::where('tenant_id', $tenant->id)->latest()->first();

        if ($schoolYear) {
            $schoolYear->update($data);
        } else {
            $sy = new SchoolYear($data + ['activo' => true]);
            $sy->tenant_id = $tenant->id;
            $sy->save();
        }

        $tenant->update(['onboarding_paso' => max($tenant->onboarding_paso, 2)]);

        return redirect()->route('admin.onboarding.show', 3);
    }

    // ── Paso 3: Grados activos ────────────────────────────────────────────

    private function paso3View($tenant)
    {
        $grados = Grado::where('tenant_id', $tenant->id)->orderBy('orden')->get();
        return view('admin.onboarding.paso3', compact('tenant', 'grados'));
    }

    private function guardarPaso3(Request $request, $tenant)
    {
        $request->validate([
            'grados_activos'   => ['nullable', 'array'],
            'grados_activos.*' => ['integer', 'exists:grados,id'],
            'nuevo_grado'      => ['nullable', 'string', 'max:80'],
            'nuevo_nivel'      => ['nullable', 'integer', 'min:1', 'max:20'],
            'nuevo_ciclo'      => ['nullable', 'in:primer_ciclo,segundo_ciclo,bachillerato'],
        ]);

        $activos = $request->input('grados_activos', []);

        // Marcar activos/inactivos según selección
        $grados = Grado::where('tenant_id', $tenant->id)->get();
        foreach ($grados as $grado) {
            $grado->update(['activo' => in_array($grado->id, $activos)]);
        }

        // Agregar grado personalizado si se indicó
        if ($request->filled('nuevo_grado')) {
            $nuevoGrado = new Grado([
                'nombre' => $request->nuevo_grado,
                'nivel'  => $request->nuevo_nivel ?? ($grados->max('nivel') + 1),
                'ciclo'  => $request->nuevo_ciclo ?? 'segundo_ciclo',
                'orden'  => $grados->max('orden') + 1,
                'activo' => true,
            ]);
            $nuevoGrado->tenant_id = $tenant->id;
            $nuevoGrado->save();
        }

        $tenant->update(['onboarding_paso' => max($tenant->onboarding_paso, 3)]);

        return redirect()->route('admin.onboarding.show', 4);
    }

    // ── Paso 4: Completado ────────────────────────────────────────────────

    private function paso4View($tenant)
    {
        $gradosActivos = Grado::where('tenant_id', $tenant->id)->where('activo', true)->count();
        $schoolYear    = SchoolYear::where('tenant_id', $tenant->id)->where('activo', true)->latest()->first();

        return view('admin.onboarding.paso4', compact('tenant', 'gradosActivos', 'schoolYear'));
    }

    private function completar($tenant)
    {
        $tenant->update([
            'onboarding_completado' => true,
            'onboarding_paso'       => 4,
        ]);

        return redirect()->route('admin.dashboard')
            ->with('success', '¡Configuración completada! Bienvenido a ZuraEdu.');
    }
}
