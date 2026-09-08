<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\PaginaSeccion;
use Illuminate\Http\Request;

class PaginaSeccionController extends Controller
{
    /**
     * SubstituteBindings resuelve {seccion} ANTES de que termine de correr
     * ResolveTenant en algunos escenarios (orden de middleware ya corregido
     * a nivel global, pero esto es la segunda capa de defensa documentada en
     * PublicSiteController::noticiaShow() -- no confiar solo en el scope
     * automático de BelongsToTenant para un modelo bindeado por ruta).
     */
    private function autorizar(PaginaSeccion $seccion): void
    {
        abort_unless($seccion->tenant_id === app('tenant')->id, 404);
    }

    public function index()
    {
        $secciones = PaginaSeccion::tiposValidos()->ordenadas()->get();

        return view('admin.secciones.index', [
            'secciones' => $secciones,
            'tipos'     => PaginaSeccion::TIPOS,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:' . implode(',', array_keys(PaginaSeccion::TIPOS)),
        ]);

        $orden = (int) (PaginaSeccion::max('orden') ?? 0) + 1;

        $seccion = PaginaSeccion::create([
            'tipo'      => $request->tipo,
            'orden'     => $orden,
            'activo'    => true,
            'contenido' => PaginaSeccion::CONTENIDO_INICIAL[$request->tipo] ?? [],
        ]);

        return redirect()->route('admin.secciones.edit', $seccion)
            ->with('success', 'Bloque "' . $seccion->tipo_label . '" agregado. Completa su contenido.');
    }

    public function edit(PaginaSeccion $seccion)
    {
        $this->autorizar($seccion);

        $albumes = $seccion->tipo === 'carrusel'
            ? Album::activos()->ordenados()->get()
            : collect();

        return view('admin.secciones.edit', compact('seccion', 'albumes'));
    }

    public function update(Request $request, PaginaSeccion $seccion)
    {
        $this->autorizar($seccion);

        $rules = match ($seccion->tipo) {
            'hero' => [
                'contenido.titulo'     => 'nullable|string|max:200',
                'contenido.subtitulo'  => 'nullable|string|max:500',
                'contenido.btn_texto'  => 'nullable|string|max:80',
                'contenido.btn_url'    => 'nullable|string|max:255',
                'contenido.btn2_texto' => 'nullable|string|max:80',
                'contenido.btn2_url'   => 'nullable|string|max:255',
            ],
            'about' => [
                'contenido.titulo' => 'nullable|string|max:200',
                'contenido.texto'  => 'nullable|string|max:5000',
            ],
            'stats' => [
                'contenido.items'          => 'nullable|array',
                'contenido.items.*.numero' => 'nullable|string|max:20',
                'contenido.items.*.label'  => 'nullable|string|max:100',
            ],
            'features' => [
                'contenido.titulo'          => 'nullable|string|max:200',
                'contenido.items'           => 'nullable|array',
                'contenido.items.*.icono'   => 'nullable|string|max:50',
                'contenido.items.*.titulo'  => 'nullable|string|max:100',
                'contenido.items.*.texto'   => 'nullable|string|max:500',
            ],
            'carrusel' => [
                'contenido.album_id' => 'nullable|integer',
            ],
            'noticias' => [
                'contenido.titulo'  => 'nullable|string|max:200',
                'contenido.limite'  => 'nullable|integer|min:1|max:' . PaginaSeccion::LIMITE_NOTICIAS_MAX,
            ],
            'contacto' => [
                'contenido.titulo'    => 'nullable|string|max:200',
                'contenido.direccion' => 'nullable|string|max:200',
                'contenido.telefono'  => 'nullable|string|max:50',
                'contenido.email'     => 'nullable|email|max:100',
                'contenido.facebook'  => 'nullable|string|max:255',
                'contenido.instagram' => 'nullable|string|max:255',
                'contenido.twitter'   => 'nullable|string|max:255',
            ],
            default => [],
        };

        $request->validate($rules);

        $contenido = $request->input('contenido', []);

        if ($seccion->tipo === 'carrusel' && filled($contenido['album_id'] ?? null)) {
            $valido = Album::where('id', $contenido['album_id'])
                ->where('tenant_id', app('tenant')->id)
                ->exists();
            if (! $valido) {
                $contenido['album_id'] = null;
            }
        }

        if (in_array($seccion->tipo, ['stats', 'features'], true)) {
            $contenido['items'] = array_values(array_filter($contenido['items'] ?? [], function ($item) {
                return filled($item['titulo'] ?? null) || filled($item['numero'] ?? null) || filled($item['texto'] ?? null) || filled($item['label'] ?? null);
            }));
        }

        $seccion->update([
            'activo'    => $request->boolean('activo'),
            'contenido' => $contenido,
        ]);

        return redirect()->route('admin.secciones.index')
            ->with('success', 'Bloque "' . $seccion->tipo_label . '" actualizado.');
    }

    public function toggleActivo(PaginaSeccion $seccion)
    {
        $this->autorizar($seccion);

        $seccion->update(['activo' => ! $seccion->activo]);

        return response()->json(['ok' => true, 'activo' => $seccion->activo]);
    }

    public function reordenar(Request $request)
    {
        $request->validate(['orden' => 'required|array', 'orden.*' => 'integer']);

        foreach ($request->orden as $pos => $id) {
            PaginaSeccion::where('id', $id)
                ->where('tenant_id', app('tenant')->id)
                ->update(['orden' => $pos + 1]);
        }

        return response()->json(['ok' => true]);
    }

    public function destroy(PaginaSeccion $seccion)
    {
        $this->autorizar($seccion);

        $seccion->delete();

        return redirect()->route('admin.secciones.index')
            ->with('success', 'Bloque eliminado.');
    }
}
