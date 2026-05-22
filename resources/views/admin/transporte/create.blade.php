@extends('layouts.admin')

@section('page-title', isset($ruta->id) ? 'Editar Ruta' : 'Nueva Ruta')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    {{-- Encabezado --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.transporte.index') }}"
           class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ isset($ruta->id) ? 'Editar Ruta' : 'Nueva Ruta' }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                {{ isset($ruta->id) ? $ruta->nombre : 'Complete los datos de la nueva ruta de transporte' }}
            </p>
        </div>
    </div>

    {{-- Alertas --}}
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Formulario --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <form method="POST"
              action="{{ isset($ruta->id) ? route('admin.transporte.update', $ruta) : route('admin.transporte.store') }}">
            @csrf
            @if(isset($ruta->id)) @method('PUT') @endif

            <div class="p-6 space-y-5">

                {{-- Nombre --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Nombre de la ruta <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="nombre" value="{{ old('nombre', $ruta->nombre) }}"
                           required maxlength="120"
                           placeholder="Ej. Ruta Norte — Sector Los Pinos"
                           class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                  text-sm px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                  dark:text-white placeholder-gray-400">
                </div>

                {{-- Descripción --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Descripción
                        <span class="text-gray-400 font-normal">(opcional)</span>
                    </label>
                    <textarea name="descripcion" rows="3" maxlength="500"
                              placeholder="Recorrido, observaciones, zonas cubiertas…"
                              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                     text-sm px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                     dark:text-white placeholder-gray-400 resize-none">{{ old('descripcion', $ruta->descripcion) }}</textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    {{-- Conductor --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Conductor
                        </label>
                        <input type="text" name="conductor" value="{{ old('conductor', $ruta->conductor) }}"
                               maxlength="120"
                               placeholder="Nombre del conductor"
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                      text-sm px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                      dark:text-white placeholder-gray-400">
                    </div>

                    {{-- Teléfono conductor --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Teléfono del Conductor
                        </label>
                        <input type="text" name="telefono_conductor" value="{{ old('telefono_conductor', $ruta->telefono_conductor) }}"
                               maxlength="30"
                               placeholder="Ej. 809-000-0000"
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                      text-sm px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                      dark:text-white placeholder-gray-400">
                    </div>

                    {{-- Vehículo --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Vehículo / Placa
                        </label>
                        <input type="text" name="vehiculo" value="{{ old('vehiculo', $ruta->vehiculo) }}"
                               maxlength="120"
                               placeholder="Ej. Bus amarillo A-12345"
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                      text-sm px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                      dark:text-white placeholder-gray-400">
                    </div>

                    {{-- Horario salida --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Horario de Salida (ida)
                        </label>
                        <input type="time" name="horario_salida" value="{{ old('horario_salida', $ruta->horario_salida) }}"
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                      text-sm px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                      dark:text-white">
                    </div>

                    {{-- Horario regreso --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Horario de Regreso (vuelta)
                        </label>
                        <input type="time" name="horario_regreso" value="{{ old('horario_regreso', $ruta->horario_regreso) }}"
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                      text-sm px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                      dark:text-white">
                    </div>
                </div>

                {{-- Capacidad --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Capacidad máxima <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="capacidad" value="{{ old('capacidad', $ruta->capacidad ?? 20) }}"
                               required min="1" max="200"
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                      text-sm px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                      dark:text-white">
                    </div>

                    {{-- Estado --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Estado
                        </label>
                        <div class="flex items-center gap-3 pt-2.5">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="activo" value="0">
                                <input type="checkbox" name="activo" value="1"
                                       {{ old('activo', $ruta->activo ?? true) ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2
                                            peer-focus:ring-blue-500 rounded-full peer dark:bg-gray-700
                                            peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full
                                            peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px]
                                            after:start-[2px] after:bg-white after:border-gray-300 after:border
                                            after:rounded-full after:h-5 after:w-5 after:transition-all
                                            dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            </label>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Ruta activa</span>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Acciones --}}
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700
                        rounded-b-xl flex items-center justify-between gap-3">
                <a href="{{ isset($ruta->id) ? route('admin.transporte.show', $ruta) : route('admin.transporte.index') }}"
                   class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200
                          font-medium transition">
                    Cancelar
                </a>
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2.5
                               rounded-lg shadow transition">
                    {{ isset($ruta->id) ? 'Guardar cambios' : 'Crear ruta' }}
                </button>
            </div>

        </form>
    </div>

</div>
@endsection
