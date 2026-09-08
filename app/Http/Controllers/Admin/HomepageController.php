<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfigInstitucional;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Branding e identidad del sitio público (logo, colores, nombre, anuncios).
 * El contenido de las secciones (hero/carrusel/about/etc.) se administra
 * desde el constructor visual -- ver Admin\PaginaSeccionController.
 */
class HomepageController extends Controller
{
    public function edit()
    {
        $config = ConfigInstitucional::all()->pluck('valor', 'clave')->toArray();
        return view('admin.homepage.edit', compact('config'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'hp_color_primario'   => 'nullable|string|max:7',
            'hp_color_secundario' => 'nullable|string|max:7',
            'logo'                => 'nullable|image|max:2048',
            'system_name'         => 'nullable|string|max:200',
            'system_abbr'         => 'nullable|string|max:10',
            'system_sub'          => 'nullable|string|max:80',
            'hp_ads_izquierda'    => 'nullable|string|max:5000',
            'hp_ads_derecha'      => 'nullable|string|max:5000',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $old = ConfigInstitucional::get('hp_logo_path');
            if ($old) Storage::disk('public')->delete($old);
            $path = $request->file('logo')->store('branding', 'public');
            ConfigInstitucional::set('hp_logo_path', $path);
            // Sincronizado con el logo del sidebar/portal (Setting.system_logo,
            // subido desde /admin/sistema) — eran dos logos independientes sin
            // relación; subirlo desde cualquiera de los dos lugares actualiza
            // ambos para que no queden desincronizados.
            \App\Helpers\Setting::set('system_logo', $path);
        }

        $keys = [
            'hp_color_primario', 'hp_color_secundario',
            'nombre_institucion',
            'hp_ads_izquierda', 'hp_ads_derecha',
        ];

        foreach ($keys as $key) {
            if ($request->has($key)) {
                ConfigInstitucional::set($key, $request->input($key) ?? '');
            }
        }

        // Guardar system settings (sidebar branding)
        foreach (['system_name', 'system_abbr', 'system_sub'] as $key) {
            if ($request->filled($key)) {
                \App\Helpers\Setting::set($key, $request->input($key));
            }
        }

        return redirect()->route('admin.homepage.edit')
            ->with('success', 'Branding actualizado correctamente.');
    }
}
