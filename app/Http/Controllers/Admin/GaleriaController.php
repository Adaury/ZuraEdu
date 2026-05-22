<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\FotoAlbum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GaleriaController extends Controller
{
    // ── Dashboard ─────────────────────────────────────────────────────────
    public function dashboard()
    {
        $totalAlbumes  = Album::count();
        $activos       = Album::activos()->count();
        $totalFotos    = FotoAlbum::count();
        $albumesRecien = Album::withCount('fotos')
            ->latest()
            ->limit(6)
            ->get();

        // Álbumes con más fotos
        $albumesMasFotos = Album::withCount('fotos')
            ->orderByDesc('fotos_count')
            ->limit(5)
            ->get();

        // Fotos subidas este mes
        $fotosEsteMes = FotoAlbum::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Fotos subidas este año
        $fotosAnio = FotoAlbum::whereYear('created_at', now()->year)->count();

        return view('admin.galeria.dashboard', compact(
            'totalAlbumes', 'activos', 'totalFotos',
            'albumesRecien', 'albumesMasFotos',
            'fotosEsteMes', 'fotosAnio'
        ));
    }

    // ── Index ─────────────────────────────────────────────────────────────
    public function index()
    {
        $albumes = Album::withCount('fotos')
            ->ordenados()
            ->paginate(18);

        return view('admin.galeria.index', compact('albumes'));
    }

    // ── Create ────────────────────────────────────────────────────────────
    public function create()
    {
        return view('admin.galeria.create');
    }

    // ── Store ─────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo'      => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'orden'       => 'nullable|integer|min:0',
            'activo'      => 'boolean',
            'portada'     => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('portada')) {
            $data['portada'] = $request->file('portada')->store('galeria/portadas', 'public');
        }

        $data['activo'] = $request->boolean('activo', true);
        $data['orden']  = $data['orden'] ?? 0;

        $album = Album::create($data);

        return redirect()->route('admin.galeria.show', $album)
                         ->with('success', 'Álbum creado correctamente.');
    }

    // ── Show ──────────────────────────────────────────────────────────────
    public function show(Album $galeria)
    {
        $galeria->load(['fotos' => fn($q) => $q->orderBy('orden')->orderBy('created_at')]);

        return view('admin.galeria.show', ['album' => $galeria]);
    }

    // ── Edit ──────────────────────────────────────────────────────────────
    public function edit(Album $galeria)
    {
        return view('admin.galeria.create', ['album' => $galeria]);
    }

    // ── Update ────────────────────────────────────────────────────────────
    public function update(Request $request, Album $galeria)
    {
        $data = $request->validate([
            'titulo'      => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'orden'       => 'nullable|integer|min:0',
            'activo'      => 'boolean',
            'portada'     => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('portada')) {
            // Eliminar portada anterior si existe
            if ($galeria->portada && Storage::disk('public')->exists($galeria->portada)) {
                Storage::disk('public')->delete($galeria->portada);
            }
            $data['portada'] = $request->file('portada')->store('galeria/portadas', 'public');
        } else {
            unset($data['portada']);
        }

        $data['activo'] = $request->boolean('activo', true);
        $data['orden']  = $data['orden'] ?? 0;

        $galeria->update($data);

        return redirect()->route('admin.galeria.show', $galeria)
                         ->with('success', 'Álbum actualizado correctamente.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────
    public function destroy(Album $galeria)
    {
        // Las fotos se eliminan en cascada por el modelo FotoAlbum::booted()
        foreach ($galeria->fotos as $foto) {
            $foto->delete();
        }

        // Eliminar portada si existe
        if ($galeria->portada && Storage::disk('public')->exists($galeria->portada)) {
            Storage::disk('public')->delete($galeria->portada);
        }

        $galeria->delete();

        return redirect()->route('admin.galeria.index')
                         ->with('success', 'Álbum eliminado correctamente.');
    }

    // ── Subir fotos (múltiple) ────────────────────────────────────────────
    public function subirFotos(Request $request, Album $galeria)
    {
        $request->validate([
            'fotos'         => 'required|array|min:1',
            'fotos.*'       => 'required|image|max:2048',
            'titulos'       => 'nullable|array',
            'titulos.*'     => 'nullable|string|max:255',
        ]);

        $archivos = $request->file('fotos');
        $titulos  = $request->input('titulos', []);
        $orden    = $galeria->fotos()->max('orden') ?? 0;

        foreach ($archivos as $i => $archivo) {
            $ruta = $archivo->store('galeria/' . $galeria->id, 'public');

            FotoAlbum::create([
                'album_id' => $galeria->id,
                'ruta'     => $ruta,
                'titulo'   => $titulos[$i] ?? null,
                'orden'    => ++$orden,
            ]);
        }

        return redirect()->route('admin.galeria.show', $galeria)
                         ->with('success', count($archivos) . ' foto(s) subida(s) correctamente.');
    }

    // ── Eliminar foto individual ──────────────────────────────────────────
    public function eliminarFoto(Album $galeria, FotoAlbum $foto)
    {
        abort_if($foto->album_id !== $galeria->id, 403);

        $foto->delete(); // El modelo elimina el archivo en disco

        return back()->with('success', 'Foto eliminada.');
    }

    // ── Galería pública ───────────────────────────────────────────────────
    public function galeriaPublica()
    {
        $albumes = Album::with(['fotos' => fn($q) => $q->orderBy('orden')->limit(12)])
            ->activos()
            ->ordenados()
            ->get();

        return view('galeria', compact('albumes'));
    }
}
