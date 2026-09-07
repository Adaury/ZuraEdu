<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Publicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Roadmap de producto: "Noticias y Publicaciones" del portal público
 * (docs del portal público, evolución posterior a Fase 2). Deliberadamente
 * separado de Comunicado (que es interno, para staff/padres/estudiantes
 * autenticados) — Publicacion es exclusivamente para el sitio público,
 * sin autenticación.
 */
class PublicacionController extends Controller
{
    public function index(Request $request)
    {
        $publicaciones = Publicacion::query()
            ->when($request->tipo, fn ($q) => $q->where('tipo', $request->tipo))
            ->recientes()
            ->paginate(20)
            ->withQueryString();

        return view('admin.publicaciones.index', compact('publicaciones'));
    }

    public function create()
    {
        return view('admin.publicaciones.create');
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);

        if ($request->hasFile('imagen_destacada')) {
            $data['imagen_destacada'] = $request->file('imagen_destacada')->store('publicaciones', 'public');
        }

        $data['creado_por'] = auth()->id();

        Publicacion::create($data);

        return redirect()->route('admin.publicaciones.index')->with('success', 'Publicación creada correctamente.');
    }

    public function edit(Publicacion $publicacion)
    {
        return view('admin.publicaciones.create', compact('publicacion'));
    }

    public function update(Request $request, Publicacion $publicacion)
    {
        $data = $this->validar($request);

        if ($request->hasFile('imagen_destacada')) {
            if ($publicacion->imagen_destacada) {
                Storage::disk('public')->delete($publicacion->imagen_destacada);
            }
            $data['imagen_destacada'] = $request->file('imagen_destacada')->store('publicaciones', 'public');
        }

        $publicacion->update($data);

        return redirect()->route('admin.publicaciones.index')->with('success', 'Publicación actualizada correctamente.');
    }

    public function destroy(Publicacion $publicacion)
    {
        if ($publicacion->imagen_destacada) {
            Storage::disk('public')->delete($publicacion->imagen_destacada);
        }
        $publicacion->delete();

        return redirect()->route('admin.publicaciones.index')->with('success', 'Publicación eliminada correctamente.');
    }

    private function validar(Request $request): array
    {
        $data = $request->validate([
            'tipo'              => 'required|in:' . implode(',', array_keys(Publicacion::TIPOS)),
            'titulo'            => 'required|string|max:200',
            'contenido'         => 'required|string',
            'fecha'             => 'required|date',
            'estado'            => 'required|in:borrador,publicado',
            'imagen_destacada'  => 'nullable|image|max:2048',
            'imagen_alineacion' => 'nullable|in:' . implode(',', Publicacion::ALINEACIONES),
        ]);

        $data['imagen_alineacion'] = $data['imagen_alineacion'] ?? 'centro';

        // Checkbox desmarcado no se envía en el POST — igual que el bug ya
        // corregido en HomepageController, se guarda explícitamente en cada
        // submit en vez de confiar en un valor por defecto.
        $data['visible'] = $request->boolean('visible');

        return $data;
    }
}
