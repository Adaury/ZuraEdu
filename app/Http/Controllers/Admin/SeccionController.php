<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seccion;
use Illuminate\Http\Request;

class SeccionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:10|unique:secciones,nombre',
        ]);

        $maxOrden = Seccion::max('orden') ?? 0;

        Seccion::create([
            'nombre' => strtoupper(trim($request->nombre)),
            'orden'  => $maxOrden + 1,
        ]);

        return back()->with('success', 'Sección "' . strtoupper(trim($request->nombre)) . '" creada correctamente.');
    }

    public function update(Request $request, Seccion $seccion)
    {
        $request->validate([
            'nombre' => 'required|string|max:10|unique:secciones,nombre,' . $seccion->id,
        ]);

        $seccion->update(['nombre' => strtoupper(trim($request->nombre))]);

        return back()->with('success', 'Sección actualizada correctamente.');
    }

    public function destroy(Seccion $seccion)
    {
        if ($seccion->grupos()->exists()) {
            return back()->with('error', 'No se puede eliminar la sección "' . $seccion->nombre . '" porque tiene grupos asociados.');
        }

        $seccion->delete();

        return back()->with('success', 'Sección "' . $seccion->nombre . '" eliminada.');
    }
}
