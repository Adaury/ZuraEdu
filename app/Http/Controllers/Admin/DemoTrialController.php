<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class DemoTrialController extends Controller
{
    private function setSetting(string $key, $value): void
    {
        DB::table('system_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now()]
        );
    }

    private function getSetting(string $key, $default = null)
    {
        return DB::table('system_settings')->where('key', $key)->value('value') ?? $default;
    }

    // ── Vista principal ────────────────────────────────────────────────────
    public function index()
    {
        $settings = DB::table('system_settings')->pluck('value', 'key');

        // Calcular estado del período de prueba
        $trialActivo = ($settings['trial_activo'] ?? '0') === '1';
        $trialInicio = $settings['trial_inicio'] ?? null;
        $trialDias   = (int) ($settings['trial_dias'] ?? 30);
        $trialExpira = null;
        $trialDiasRestantes = null;
        $trialExpirado = false;

        if ($trialActivo && $trialInicio) {
            $inicio = \Carbon\Carbon::parse($trialInicio);
            $trialExpira = $inicio->copy()->addDays($trialDias);
            $trialDiasRestantes = max(0, (int) now()->diffInDays($trialExpira, false));
            $trialExpirado = now()->gt($trialExpira);
        }

        // Verificar usuarios demo
        $usuariosDemo = [
            'docente'    => \App\Models\User::where('email', 'docente@demo.com')->exists(),
            'estudiante' => \App\Models\User::where('email', 'estudiante@demo.com')->exists(),
            'padre'      => \App\Models\User::where('email', 'padre@demo.com')->exists(),
        ];

        return view('admin.sistema.demo-trial', compact(
            'settings', 'trialActivo', 'trialInicio', 'trialDias',
            'trialExpira', 'trialDiasRestantes', 'trialExpirado', 'usuariosDemo'
        ));
    }

    // ── Activar / desactivar demo ──────────────────────────────────────────
    public function toggleDemo(Request $request)
    {
        $activo = $request->boolean('demo_activo') ? '1' : '0';
        $this->setSetting('demo_activo', $activo);
        \App\Helpers\Setting::flush();

        return back()->with('success', $activo === '1'
            ? 'Modo demo activado. Los usuarios pueden acceder con sus perfiles demo.'
            : 'Modo demo desactivado. Los accesos demo están bloqueados.');
    }

    // ── Crear usuarios demo ────────────────────────────────────────────────
    public function crearUsuariosDemo()
    {
        try {
            Artisan::call('db:seed', ['--class' => 'DemoUsersSeeder', '--force' => true]);
            return back()->with('success', '✅ Usuarios demo creados: docente@demo.com, estudiante@demo.com, padre@demo.com (contraseña: 123456)');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al crear usuarios demo: ' . $e->getMessage());
        }
    }

    // ── Guardar configuración período de prueba ────────────────────────────
    public function saveTrial(Request $request)
    {
        $request->validate([
            'trial_activo'  => 'nullable|in:1',
            'trial_inicio'  => 'required|date',
            'trial_dias'    => 'required|integer|min:1|max:365',
            'trial_mensaje' => 'nullable|string|max:200',
        ]);

        $this->setSetting('trial_activo',  $request->has('trial_activo') ? '1' : '0');
        $this->setSetting('trial_inicio',  $request->trial_inicio);
        $this->setSetting('trial_dias',    $request->trial_dias);
        $this->setSetting('trial_mensaje', $request->trial_mensaje ?? '');
        \App\Helpers\Setting::flush();

        return back()->with('success', 'Período de prueba actualizado correctamente.');
    }

    // ── Desactivar período de prueba ───────────────────────────────────────
    public function desactivarTrial()
    {
        $this->setSetting('trial_activo', '0');
        \App\Helpers\Setting::flush();
        return back()->with('success', 'Período de prueba desactivado.');
    }
}
