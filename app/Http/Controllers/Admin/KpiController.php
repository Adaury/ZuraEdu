<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\KpiDashboardService;
use Illuminate\Http\JsonResponse;

class KpiController extends Controller
{
    public function __construct(private readonly KpiDashboardService $kpis)
    {
        $this->middleware(['auth']);
    }

    // ── Vista principal ─────────────────────────────────────────────────

    public function index()
    {
        $kpis = $this->kpis->calcularKpis();

        return view('admin.kpis.index', compact('kpis'));
    }

    // ── Endpoint JSON para actualización sin reload (Alpine.js fetch) ───

    public function data(): JsonResponse
    {
        return response()->json($this->kpis->calcularKpis());
    }
}
