<?php

namespace App\Http\Controllers;

use App\Support\GuiasRol;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Guías rápidas («flyers») por rol: públicas (sirven de explicación en la página de bienvenida) y también accesibles dentro de la
 * plataforma. Son contenido genérico del producto: no incluyen datos de ningún colegio ni de ningún usuario.
 */
class GuiaController extends Controller
{
    public function index()
    {
        return view('guias.index', ['guias' => GuiasRol::GUIAS]);
    }

    public function show(string $slug)
    {
        return view('guias.show', ['g' => $this->guia($slug), 'slug' => $slug, 'guias' => GuiasRol::GUIAS]);
    }

    public function pdf(string $slug)
    {
        $pdf = Pdf::loadView('guias.pdf', ['g' => $this->guia($slug), 'slug' => $slug])->setPaper('a4', 'portrait');

        return $pdf->download("guia-rapida-{$slug}-zuraedu.pdf");
    }

    /** Dentro de la plataforma: lleva al usuario a la guía de SU rol. */
    public function mia()
    {
        $slug = GuiasRol::slugPara(auth()->user());

        return $slug ? redirect()->route('guias.show', $slug) : redirect()->route('guias.index');
    }

    private function guia(string $slug): array
    {
        $g = GuiasRol::obtener($slug);
        abort_unless($g, 404);

        return $g;
    }
}
