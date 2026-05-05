<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfigInstitucional;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class HomepageController extends Controller
{
    private array $sections = [
        'hero'     => ['titulo' => 'Sección Hero (Portada)'],
        'about'    => ['titulo' => 'Sobre la Institución'],
        'stats'    => ['titulo' => 'Estadísticas'],
        'features' => ['titulo' => 'Características'],
        'contacto' => ['titulo' => 'Contacto y Redes'],
        'branding' => ['titulo' => 'Logo y Colores'],
    ];

    public function edit()
    {
        $config = ConfigInstitucional::all()->pluck('valor', 'clave')->toArray();
        return view('admin.homepage.edit', compact('config'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'hp_hero_titulo'       => 'nullable|string|max:200',
            'hp_hero_subtitulo'    => 'nullable|string|max:500',
            'hp_hero_btn_texto'    => 'nullable|string|max:80',
            'hp_hero_btn2_texto'   => 'nullable|string|max:80',
            'hp_about_titulo'      => 'nullable|string|max:200',
            'hp_about_texto'       => 'nullable|string|max:1000',
            'hp_contacto_direccion'=> 'nullable|string|max:200',
            'hp_contacto_telefono' => 'nullable|string|max:50',
            'hp_contacto_email'    => 'nullable|email|max:100',
            'hp_color_primario'    => 'nullable|string|max:7',
            'hp_color_secundario'  => 'nullable|string|max:7',
            'logo'                 => 'nullable|image|max:2048',
            'system_name'          => 'nullable|string|max:200',
            'system_abbr'          => 'nullable|string|max:10',
            'system_sub'           => 'nullable|string|max:80',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $old = ConfigInstitucional::get('hp_logo_path');
            if ($old) Storage::disk('public')->delete($old);
            $path = $request->file('logo')->store('branding', 'public');
            ConfigInstitucional::set('hp_logo_path', $path);
        }

        // Save all text fields
        $keys = [
            'hp_hero_titulo', 'hp_hero_subtitulo', 'hp_hero_btn_texto', 'hp_hero_btn2_texto',
            'hp_hero_visible',
            'hp_about_titulo', 'hp_about_texto', 'hp_about_visible',
            'hp_stat1_numero', 'hp_stat1_label',
            'hp_stat2_numero', 'hp_stat2_label',
            'hp_stat3_numero', 'hp_stat3_label',
            'hp_stat4_numero', 'hp_stat4_label',
            'hp_stats_visible',
            'hp_features_titulo', 'hp_features_visible',
            'hp_contacto_direccion', 'hp_contacto_telefono', 'hp_contacto_email', 'hp_contacto_visible',
            'hp_social_facebook', 'hp_social_instagram', 'hp_social_twitter',
            'hp_color_primario', 'hp_color_secundario',
            'nombre_institucion',
        ];

        foreach ($keys as $key) {
            if ($request->has($key)) {
                ConfigInstitucional::set($key, $request->input($key) ?? '');
            }
        }

        // Guardar system settings (sidebar branding)
        foreach (['system_name', 'system_abbr', 'system_sub'] as $key) {
            if ($request->filled($key)) {
                \Illuminate\Support\Facades\DB::table('system_settings')->updateOrInsert(
                    ['key' => $key],
                    ['value' => $request->input($key), 'updated_at' => now()]
                );
            }
        }

        // Clear all config cache
        Cache::flush();

        return redirect()->route('admin.homepage.edit')
            ->with('success', 'Página principal actualizada correctamente.');
    }
}
