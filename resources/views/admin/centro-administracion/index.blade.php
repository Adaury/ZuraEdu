@extends('layouts.admin')

@section('page-title', 'Centro de Administración')

@section('content')
<div class="container-fluid px-4">
    <div class="mb-4">
        <h1 class="h3 mb-1">Centro de Administración</h1>
        <p class="text-muted mb-0">Todas las áreas administrables de tu institución, agrupadas por categoría.</p>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item active">Centro de Administración</li>
        </ol></nav>
    </div>

    <div class="mb-4">
        <div class="input-group" style="max-width:420px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
            <input type="text" id="centro-admin-filtro" class="form-control border-start-0"
                   placeholder="Buscar una página de administración...">
        </div>
    </div>

    @if($categorias->isEmpty())
        <div class="alert alert-secondary">No tienes acceso a ninguna sección administrativa todavía.</div>
    @endif

    @foreach($categorias as $categoria => $items)
    <div class="mb-4 centro-admin-categoria">
        <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size:.75rem;letter-spacing:.04em;">{{ $categoria }}</h6>
        <div class="row g-3">
            @foreach($items as $item)
            <div class="col-md-4 col-lg-3 centro-admin-item" data-nombre="{{ Str::lower($item['label']) }}">
                <a href="{{ $item['url'] }}" class="card shadow-sm h-100 text-decoration-none border-0" style="border-radius:12px;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div style="width:42px;height:42px;border-radius:10px;background:#eef2ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi {{ $item['icon'] }}" style="color:#6366f1;"></i>
                        </div>
                        <span class="fw-semibold text-dark" style="font-size:.9rem;">{{ $item['label'] }}</span>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach

    <div class="alert alert-light border small text-muted" id="centro-admin-sin-resultados" style="display:none;">
        Ninguna página coincide con tu búsqueda.
    </div>
</div>

@push('scripts')
<script>
(function () {
    var input = document.getElementById('centro-admin-filtro');
    if (!input) return;

    input.addEventListener('input', function () {
        var q = this.value.trim().toLowerCase();
        var algunaVisible = false;

        document.querySelectorAll('.centro-admin-categoria').forEach(function (categoria) {
            var visiblesEnCategoria = 0;
            categoria.querySelectorAll('.centro-admin-item').forEach(function (item) {
                var coincide = !q || item.dataset.nombre.includes(q);
                item.style.display = coincide ? '' : 'none';
                if (coincide) visiblesEnCategoria++;
            });
            categoria.style.display = visiblesEnCategoria > 0 ? '' : 'none';
            if (visiblesEnCategoria > 0) algunaVisible = true;
        });

        document.getElementById('centro-admin-sin-resultados').style.display = algunaVisible ? 'none' : '';
    });
})();
</script>
@endpush
@endsection
