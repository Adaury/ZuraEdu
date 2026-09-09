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

    @php($totalTarjetas = $categorias->sum(fn ($items) => $items->count()))

    <div class="mb-4">
        <div class="input-group" style="max-width:420px;">
            <span class="input-group-text border-end-0"><i class="bi bi-search"></i></span>
            <input type="text" id="centro-admin-filtro" class="form-control border-start-0"
                   placeholder="Buscar una página de administración...">
        </div>
        <div class="form-text mt-1" id="centro-admin-contador">{{ $totalTarjetas }} de {{ $totalTarjetas }} páginas</div>
    </div>

    @if($categorias->isEmpty())
        <div class="alert alert-secondary centro-admin-vacio">No tienes acceso a ninguna sección administrativa todavía.</div>
    @endif

    @foreach($categorias as $categoria => $items)
    <div class="mb-4 centro-admin-categoria">
        <h6 class="text-uppercase text-muted fw-bold mb-3 centro-admin-cat-title">{{ $categoria }}</h6>
        <div class="row g-3">
            @foreach($items as $item)
            <div class="col-md-4 col-lg-3 centro-admin-item" data-nombre="{{ $item['busqueda'] }}">
                <a href="{{ $item['url'] }}" class="card shadow-sm h-100 text-decoration-none border-0 centro-admin-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="centro-admin-icon">
                            <i class="bi {{ $item['icon'] }}"></i>
                        </div>
                        <span class="fw-semibold text-dark" style="font-size:.9rem;">{{ $item['label'] }}</span>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach

    <div class="alert alert-light border small text-muted centro-admin-vacio" id="centro-admin-sin-resultados" style="display:none;">
        Ninguna página coincide con tu búsqueda.
    </div>
</div>

@push('styles')
<style>
    .centro-admin-icon {
        width:42px; height:42px; border-radius:10px;
        background:#eef2ff; color:#6366f1;
        display:flex; align-items:center; justify-content:center; flex-shrink:0;
    }
    .centro-admin-card { border-radius:12px; transition:transform .12s ease; }
    .centro-admin-card:hover { transform:translateY(-2px); }
    .centro-admin-cat-title { font-size:.75rem; letter-spacing:.04em; }

    /* El admin conmuta por atributo data-theme en <html>, no por clase.
       #a5b4fc / rgba(99,102,241,.16) = misma pareja que .schoolyear-badge
       en layouts/admin.blade.php. */
    [data-theme="dark"] .centro-admin-icon {
        background: rgba(99,102,241,.16);
        color: #a5b4fc;
    }
    /* .alert-light y .alert-secondary no tienen override dark en el layout. */
    [data-theme="dark"] .centro-admin-vacio {
        background: rgba(15,23,42,.6) !important;
        border-color: #334155 !important;
        color: #94a3b8 !important;
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var input = document.getElementById('centro-admin-filtro');
    if (!input) return;

    var total = document.querySelectorAll('.centro-admin-item').length;
    var contador = document.getElementById('centro-admin-contador');

    function normalizar(str) {
        // U+0300-U+036F = bloque Unicode de marcas diacriticas combinantes
        // (acentos/tildes que quedan sueltos tras normalize('NFD')).
        return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    input.addEventListener('input', function () {
        var q = normalizar(this.value.trim().toLowerCase());
        var algunaVisible = false;
        var visiblesTotal = 0;

        document.querySelectorAll('.centro-admin-categoria').forEach(function (categoria) {
            var visiblesEnCategoria = 0;
            categoria.querySelectorAll('.centro-admin-item').forEach(function (item) {
                var coincide = !q || normalizar(item.dataset.nombre).includes(q);
                item.style.display = coincide ? '' : 'none';
                if (coincide) visiblesEnCategoria++;
            });
            categoria.style.display = visiblesEnCategoria > 0 ? '' : 'none';
            if (visiblesEnCategoria > 0) algunaVisible = true;
            visiblesTotal += visiblesEnCategoria;
        });

        if (contador) contador.textContent = visiblesTotal + ' de ' + total + ' páginas';
        document.getElementById('centro-admin-sin-resultados').style.display = algunaVisible ? 'none' : '';
    });
})();
</script>
@endpush
@endsection
