@extends('layouts.admin')

@section('page-title', 'Secciones del Sitio')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="bi bi-grid-1x2 text-indigo-500"></i>
                Secciones del Sitio
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Arma tu sitio público (<a href="{{ route('sitio.show') }}" target="_blank" class="text-indigo-600 hover:underline">verlo</a>)
                agregando bloques, arrastrando para reordenarlos y activando/desactivando cada uno.
            </p>
        </div>
        <form method="POST" action="{{ route('admin.secciones.store') }}" class="flex items-center gap-2">
            @csrf
            <select name="tipo" required
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">-- Elige un bloque --</option>
                @foreach($tipos as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow transition whitespace-nowrap">
                <i class="bi bi-plus-lg"></i>Agregar bloque
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="mb-5 flex items-center gap-3 p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-700 dark:text-green-300">
            <i class="bi bi-check-circle-fill text-green-500 text-lg shrink-0"></i>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        @if($secciones->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 text-gray-400 dark:text-gray-500">
                <i class="bi bi-grid-1x2 text-5xl mb-4 opacity-40"></i>
                <p class="text-base font-medium">Tu sitio todavía no tiene bloques</p>
                <p class="text-sm mt-1">Usa el selector de arriba para agregar el primero</p>
            </div>
        @else
            <ul id="lista-secciones" data-url="{{ route('admin.secciones.reordenar') }}" class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($secciones as $seccion)
                <li class="bloque flex items-center gap-3 px-4 py-3.5" draggable="true" data-id="{{ $seccion->id }}">
                    <i class="bi bi-grip-vertical text-gray-300 dark:text-gray-600 cursor-grab text-lg shrink-0" title="Arrastra para reordenar"></i>

                    <div class="w-9 h-9 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-300 flex items-center justify-center shrink-0">
                        <i class="bi {{ $seccion->icono }}"></i>
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-900 dark:text-white truncate">{{ $seccion->tipo_label }}</p>
                        @if($seccion->resumenCorto())
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $seccion->resumenCorto() }}</p>
                        @endif
                    </div>

                    <div class="flex items-center gap-1 shrink-0">
                        <button type="button" class="btn-mover text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 p-1.5" data-dir="up" title="Subir">
                            <i class="bi bi-chevron-up"></i>
                        </button>
                        <button type="button" class="btn-mover text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 p-1.5" data-dir="down" title="Bajar">
                            <i class="bi bi-chevron-down"></i>
                        </button>
                    </div>

                    <button type="button"
                            class="btn-toggle shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $seccion->activo ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}"
                            data-url="{{ route('admin.secciones.toggle', $seccion) }}">
                        <i class="bi {{ $seccion->activo ? 'bi-eye-fill' : 'bi-eye-slash' }} me-1"></i>
                        {{ $seccion->activo ? 'Activo' : 'Inactivo' }}
                    </button>

                    <a href="{{ route('admin.secciones.edit', $seccion) }}" class="shrink-0 text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 p-1.5" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </a>

                    <form action="{{ route('admin.secciones.destroy', $seccion) }}" method="POST" class="shrink-0"
                          onsubmit="return confirm('¿Eliminar este bloque?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-500 hover:text-red-700 p-1.5" title="Eliminar"><i class="bi bi-trash"></i></button>
                    </form>
                </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

@push('scripts')
<script>
(function () {
    const lista = document.getElementById('lista-secciones');
    if (!lista) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const reordenarUrl = lista.dataset.url;

    function idsActuales() {
        return Array.from(lista.querySelectorAll('.bloque')).map(function (li) { return li.dataset.id; });
    }

    function persistirOrden(snapshotIds) {
        fetch(reordenarUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ orden: idsActuales() }),
        })
        .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
        .then(function (result) {
            if (result.ok) {
                window.SGEToast.show('Orden actualizado.', 'success');
            } else {
                throw new Error('respuesta no ok');
            }
        })
        .catch(function () {
            window.SGEToast.show('No se pudo guardar el orden. Se revirtió.', 'danger');
            restaurarOrden(snapshotIds);
        });
    }

    function restaurarOrden(ids) {
        ids.forEach(function (id) {
            const li = lista.querySelector('.bloque[data-id="' + id + '"]');
            if (li) lista.appendChild(li);
        });
    }

    /* ── Drag & drop nativo ─────────────────────────────── */
    let draggedLi = null;
    let snapshotAlSoltar = null;

    lista.querySelectorAll('.bloque').forEach(function (li) {
        li.addEventListener('dragstart', function () {
            draggedLi = li;
            snapshotAlSoltar = idsActuales();
            li.classList.add('opacity-40');
        });

        li.addEventListener('dragend', function () {
            li.classList.remove('opacity-40');
            if (draggedLi) persistirOrden(snapshotAlSoltar);
            draggedLi = null;
        });

        li.addEventListener('dragover', function (e) {
            if (!draggedLi || draggedLi === li) return;
            e.preventDefault();
            const rect = li.getBoundingClientRect();
            const despuesDelMedio = (e.clientY - rect.top) > (rect.height / 2);
            li.parentNode.insertBefore(draggedLi, despuesDelMedio ? li.nextSibling : li);
        });
    });

    /* ── Botones ↑/↓ (fallback accesible/táctil) ────────── */
    lista.querySelectorAll('.btn-mover').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const li = btn.closest('.bloque');
            const snapshot = idsActuales();
            const sibling = btn.dataset.dir === 'up' ? li.previousElementSibling : li.nextElementSibling;
            if (!sibling) return;
            if (btn.dataset.dir === 'up') {
                li.parentNode.insertBefore(li, sibling);
            } else {
                li.parentNode.insertBefore(sibling, li);
            }
            persistirOrden(snapshot);
        });
    });

    /* ── Toggle activo/inactivo ──────────────────────────── */
    lista.querySelectorAll('.btn-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            fetch(btn.dataset.url, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            })
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
            .then(function (result) {
                if (!result.ok) throw new Error('respuesta no ok');
                const activo = result.data.activo;
                btn.classList.toggle('bg-green-50', activo);
                btn.classList.toggle('text-green-700', activo);
                btn.classList.toggle('dark:bg-green-900/30', activo);
                btn.classList.toggle('dark:text-green-300', activo);
                btn.classList.toggle('bg-gray-100', !activo);
                btn.classList.toggle('text-gray-500', !activo);
                btn.classList.toggle('dark:bg-gray-700', !activo);
                btn.classList.toggle('dark:text-gray-400', !activo);
                btn.innerHTML = '<i class="bi ' + (activo ? 'bi-eye-fill' : 'bi-eye-slash') + ' me-1"></i>' + (activo ? 'Activo' : 'Inactivo');
                window.SGEToast.show(activo ? 'Bloque activado.' : 'Bloque desactivado.', 'success');
            })
            .catch(function () {
                window.SGEToast.show('No se pudo cambiar el estado del bloque.', 'danger');
            });
        });
    });
})();
</script>
@endpush
@endsection
