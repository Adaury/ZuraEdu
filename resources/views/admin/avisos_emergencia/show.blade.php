@extends('layouts.admin')

@section('page-title', 'Aviso — '.$aviso->titulo)

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- Cabecera --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.avisos-emergencia.index') }}"
           class="p-2 rounded-lg text-gray-500 hover:text-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <div class="flex-1 min-w-0">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2 truncate">
                <i class="bi {{ $aviso->icono }} shrink-0" style="color: {{ ['emergencia'=>'#EF4444','suspension'=>'#F97316','actividad'=>'#3B82F6','informativo'=>'#6B7280'][$aviso->tipo] ?? '#6B7280' }};"></i>
                {{ $aviso->titulo }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                Enviado el {{ $aviso->created_at->format('d/m/Y') }} a las {{ $aviso->created_at->format('H:i') }}
            </p>
        </div>
        <form method="POST" action="{{ route('admin.avisos-emergencia.destroy', $aviso) }}"
              onsubmit="return confirm('¿Eliminar este aviso del historial? Esta acción no se puede deshacer.')">
            @csrf @method('DELETE')
            <button type="submit"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 rounded-xl border border-red-200 dark:border-red-700 transition">
                <i class="bi bi-trash"></i>
                Eliminar
            </button>
        </form>
    </div>

    <div class="space-y-4">

        {{-- Badge tipo + estadísticas --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex flex-wrap items-center gap-4">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold {{ $aviso->badge_clase }}">
                    <i class="bi {{ $aviso->icono }}"></i>
                    {{ $aviso->tipo_label }}
                </span>

                <div class="flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-300">
                    <i class="bi bi-people-fill text-indigo-500"></i>
                    <span>{{ $aviso->destinatarios_label }}</span>
                </div>

                <div class="flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-300 ml-auto">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-bold text-sm">
                        {{ $aviso->total_enviados }}
                    </span>
                    <span class="text-gray-500 dark:text-gray-400">notificacion{{ $aviso->total_enviados !== 1 ? 'es' : '' }} enviada{{ $aviso->total_enviados !== 1 ? 's' : '' }}</span>
                </div>
            </div>
        </div>

        {{-- Mensaje --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <h2 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                Mensaje
            </h2>
            <p class="text-gray-800 dark:text-gray-200 text-sm leading-relaxed whitespace-pre-line">{{ $aviso->mensaje }}</p>
        </div>

        {{-- Metadatos --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <h2 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">
                Detalles del envío
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400 font-medium mb-0.5">Enviado por</dt>
                    <dd class="text-gray-900 dark:text-white">{{ $aviso->enviadoPor?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400 font-medium mb-0.5">Fecha y hora</dt>
                    <dd class="text-gray-900 dark:text-white">{{ $aviso->created_at->format('d/m/Y H:i:s') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400 font-medium mb-0.5">Destinatarios</dt>
                    <dd class="text-gray-900 dark:text-white">{{ $aviso->destinatarios_label }}</dd>
                </div>
                @if($aviso->grupo)
                <div>
                    <dt class="text-gray-500 dark:text-gray-400 font-medium mb-0.5">Grupo</dt>
                    <dd class="text-gray-900 dark:text-white">{{ $aviso->grupo->nombre_completo }}</dd>
                </div>
                @endif
                <div>
                    <dt class="text-gray-500 dark:text-gray-400 font-medium mb-0.5">Total notificados</dt>
                    <dd class="text-gray-900 dark:text-white font-semibold">{{ $aviso->total_enviados }} usuario{{ $aviso->total_enviados !== 1 ? 's' : '' }}</dd>
                </div>
                @if(in_array($aviso->tipo, ['emergencia', 'suspension']))
                <div>
                    <dt class="text-gray-500 dark:text-gray-400 font-medium mb-0.5">WhatsApp</dt>
                    <dd class="flex items-center gap-1.5 text-green-600 dark:text-green-400">
                        <i class="bi bi-whatsapp"></i>
                        Enviado a representantes con teléfono
                    </dd>
                </div>
                @endif
            </dl>
        </div>

    </div>

</div>
@endsection
