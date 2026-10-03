@extends('layouts.superadmin')
@section('title', 'Respaldos')
@section('content')

@php
    $v = fn (string $campo, $defecto = null) => old($campo, $cfg->{$campo} ?? $defecto);
    $estadoDestino = function ($run, $destino) {
        $archivos = collect(($run->destinos[$destino] ?? []));
        if ($archivos->isEmpty()) return null;
        return $archivos->every(fn ($r) => is_array($r) && ($r['ok'] ?? false));
    };
@endphp

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-cloud-arrow-up-fill me-2" style="color:#6366f1;"></i>Respaldos de la plataforma</h4>
        <p class="text-muted small mb-0">Copia automática de la base de datos y los archivos de <strong>todos los colegios</strong>. Solo tú puedes ver esta pantalla.</p>
    </div>
    <form method="POST" action="{{ route('superadmin.respaldos.ejecutar') }}" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').innerHTML='<span class=&quot;spinner-border spinner-border-sm me-1&quot;></span>Respaldando… no cierres la página';">
        @csrf
        <button class="btn btn-primary"><i class="bi bi-play-circle-fill me-1"></i>Respaldar ahora</button>
    </form>
</div>

{{-- Estado de un vistazo --}}
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px;"><div class="card-body">
            <div class="text-muted small">Último respaldo correcto</div>
            @if($ultimoExitoso)
                <div class="fw-bold fs-5">{{ $ultimoExitoso->iniciado_en->timezone($cfg->zona_horaria ?: 'UTC')->format('d/m/Y H:i') }}</div>
                <div class="small text-muted">{{ $ultimoExitoso->bd_archivo }}</div>
            @else
                <div class="fw-bold fs-5 text-danger">Ninguno todavía</div>
                <div class="small text-muted">Pulsa «Respaldar ahora» para hacer el primero.</div>
            @endif
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px;"><div class="card-body">
            <div class="text-muted small">Próximo respaldo automático</div>
            @if($proxima)
                <div class="fw-bold fs-5">{{ $proxima->format('d/m/Y H:i') }}</div>
                <div class="small text-muted">{{ $cfg->frecuencia === 'semanal' ? 'Cada ' . strtolower($dias[$cfg->dia_semana]) : 'Todos los días' }} · zona {{ $cfg->zona_horaria }}</div>
            @else
                <div class="fw-bold fs-5 text-warning">Desactivado</div>
                <div class="small text-muted">Activa la programación abajo.</div>
            @endif
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px;"><div class="card-body">
            <div class="text-muted small">Programador de tareas del servidor</div>
            @if($programadorActivo)
                <div class="fw-bold fs-5 text-success"><i class="bi bi-check-circle-fill me-1"></i>Funcionando</div>
                <div class="small text-muted">Se ejecutó hace menos de 5 minutos.</div>
            @else
                <div class="fw-bold fs-5 text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>Sin señal</div>
                <div class="small text-muted">Sin esto el respaldo automático <strong>no se ejecuta</strong>. Mira «Cómo activarlo» abajo.</div>
            @endif
        </div></div>
    </div>
</div>

<form id="cfg" method="POST" action="{{ route('superadmin.respaldos.guardar') }}">
@csrf

<div class="row g-3">
    {{-- ① Programación --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px;"><div class="card-body">
            <h6 class="fw-bold mb-3"><span class="badge bg-primary me-2">1</span>Cuándo respaldar</h6>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1" @checked($v('activo', true))>
                <label class="form-check-label fw-semibold" for="activo">Respaldo automático activado</label>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-sm-6">
                    <label class="form-label small fw-semibold mb-1">Frecuencia</label>
                    <select name="frecuencia" id="frecuencia" class="form-select form-select-sm">
                        <option value="diaria" @selected($v('frecuencia', 'diaria') === 'diaria')>Todos los días</option>
                        <option value="semanal" @selected($v('frecuencia') === 'semanal')>Una vez por semana</option>
                    </select>
                </div>
                <div class="col-sm-6" id="bloque-dia">
                    <label class="form-label small fw-semibold mb-1">Día de la semana</label>
                    <select name="dia_semana" class="form-select form-select-sm">
                        @foreach($dias as $n => $d)<option value="{{ $n }}" @selected((int) $v('dia_semana', 0) === $n)>{{ $d }}</option>@endforeach
                    </select>
                </div>
                <div class="col-sm-6">
                    <label class="form-label small fw-semibold mb-1">Hora</label>
                    <input type="time" name="hora" class="form-control form-control-sm" value="{{ $v('hora', '02:30') }}" required>
                </div>
                <div class="col-sm-6">
                    <label class="form-label small fw-semibold mb-1">Zona horaria</label>
                    <select name="zona_horaria" class="form-select form-select-sm">
                        @foreach($zonas as $z => $nombre)<option value="{{ $z }}" @selected($v('zona_horaria', 'America/Santo_Domingo') === $z)>{{ $nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="col-sm-6">
                    <label class="form-label small fw-semibold mb-1">Conservar los últimos</label>
                    <div class="input-group input-group-sm"><input type="number" min="1" max="365" name="retencion_dias" class="form-control" value="{{ $v('retencion_dias', 7) }}" required><span class="input-group-text">días</span></div>
                </div>
                <div class="col-sm-6 d-flex align-items-end">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="incluir_archivos" name="incluir_archivos" value="1" @checked($v('incluir_archivos', true))>
                        <label class="form-check-label small" for="incluir_archivos">Incluir fotos y documentos</label>
                    </div>
                </div>
            </div>
            <div class="form-text">Recomendado: de madrugada (2:30), cuando casi nadie usa el sistema. Los respaldos más viejos se borran solos.</div>
        </div></div>
    </div>

    {{-- ② Carpeta local de sincronización --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px;"><div class="card-body">
            <h6 class="fw-bold mb-3"><span class="badge bg-primary me-2">2</span>Carpeta en este equipo (sincronización)</h6>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="carpeta_local_activa" name="carpeta_local_activa" value="1" @checked($v('carpeta_local_activa', false))>
                <label class="form-check-label fw-semibold" for="carpeta_local_activa">Copiar cada respaldo a una carpeta</label>
            </div>
            <label class="form-label small fw-semibold mb-1">Ruta de la carpeta</label>
            <input type="text" name="carpeta_local_ruta" class="form-control form-control-sm" placeholder="D:\Respaldos\ZuraEdu" value="{{ $v('carpeta_local_ruta', '') }}" maxlength="500">
            <div class="d-flex gap-2 mt-2">
                <button type="submit" formaction="{{ route('superadmin.respaldos.carpeta.probar') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-folder-check me-1"></i>Crear y probar la carpeta</button>
            </div>
            <div class="form-text mt-2">
                Se crea sola si no existe. Sirve para <strong>sincronizar</strong>: elige una carpeta que ya sincronice <em>Google Drive para escritorio</em>, Dropbox o un disco de red,
                y cada respaldo aparecerá allí. Debe estar <strong>fuera</strong> del sitio web (no se aceptan carpetas públicas).
            </div>
        </div></div>
    </div>

    {{-- ③ Google Drive --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius:14px;"><div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <h6 class="fw-bold mb-0"><span class="badge bg-primary me-2">3</span><i class="bi bi-google me-1"></i>Google Drive (en la nube)</h6>
                @if($cfg->driveConectado())
                    <span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i>Conectado como {{ $cfg->drive_cuenta ?: 'tu cuenta' }}</span>
                @else
                    <span class="badge text-bg-secondary">No conectado</span>
                @endif
            </div>

            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="drive_activo" name="drive_activo" value="1" @checked($v('drive_activo', false)) @disabled(! $cfg->driveConectado())>
                        <label class="form-check-label fw-semibold" for="drive_activo">Subir cada respaldo a Google Drive @unless($cfg->driveConectado())<span class="text-muted fw-normal">(primero conecta la cuenta)</span>@endunless</label>
                    </div>
                    <label class="form-label small fw-semibold mb-1">Nombre de la carpeta en tu Drive</label>
                    <input type="text" name="drive_carpeta_nombre" class="form-control form-control-sm mb-2" value="{{ $v('drive_carpeta_nombre', 'ZuraEdu Respaldos') }}" maxlength="120" required>
                    <div class="form-text mb-3">Se crea sola la primera vez. ZuraEdu solo ve lo que ella misma sube; no accede al resto de tu Drive.</div>

                    <label class="form-label small fw-semibold mb-1">ID de cliente de Google</label>
                    <input type="text" name="drive_client_id" class="form-control form-control-sm mb-2" value="{{ $v('drive_client_id', '') }}" autocomplete="off" placeholder="1234567890-abc….apps.googleusercontent.com">
                    <label class="form-label small fw-semibold mb-1">Secreto de cliente</label>
                    <input type="password" name="drive_client_secret" class="form-control form-control-sm" autocomplete="new-password" placeholder="{{ filled($cfg->drive_client_secret) ? '•••••••• (guardado; escribe uno nuevo solo para cambiarlo)' : 'GOCSPX-…' }}">

                    <div class="d-flex gap-2 flex-wrap mt-3">
                        <a href="{{ route('superadmin.respaldos.drive.conectar') }}" class="btn btn-outline-primary btn-sm @if(blank($cfg->drive_client_secret)) disabled @endif"><i class="bi bi-box-arrow-up-right me-1"></i>{{ $cfg->driveConectado() ? 'Volver a conectar' : 'Conectar con Google' }}</a>
                        @if($cfg->driveConectado())
                            <button type="submit" form="f-drive-probar" class="btn btn-outline-success btn-sm"><i class="bi bi-activity me-1"></i>Probar</button>
                            <button type="submit" form="f-drive-desconectar" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Desconectar Google Drive? Los respaldos ya subidos se quedan en tu Drive.')"><i class="bi bi-x-circle me-1"></i>Desconectar</button>
                        @endif
                    </div>
                    @if(blank($cfg->drive_client_secret))<div class="form-text">Guarda primero el ID y el secreto para poder conectar.</div>@endif
                </div>

                <div class="col-lg-6">
                    <div class="p-3 rounded-3 small" style="background:#f8fafc;border:1px solid #e2e8f0;">
                        <div class="fw-bold mb-2"><i class="bi bi-list-ol me-1"></i>Cómo conseguir el ID y el secreto (una sola vez, ~5 min)</div>
                        <ol class="mb-2 ps-3">
                            <li>Entra a <strong>console.cloud.google.com</strong> con la cuenta de Google donde quieres los respaldos y crea un proyecto.</li>
                            <li><em>APIs y servicios → Biblioteca</em>: busca <strong>Google Drive API</strong> y pulsa <em>Habilitar</em>.</li>
                            <li><em>Pantalla de consentimiento de OAuth</em>: tipo <em>Externo</em>, pon un nombre y tu correo. En «Usuarios de prueba» agrega tu correo.</li>
                            <li><em>Credenciales → Crear credenciales → ID de cliente de OAuth</em>, tipo <strong>Aplicación web</strong>.</li>
                            <li>En <em>URI de redireccionamiento autorizados</em> pega exactamente:
                                <div class="input-group input-group-sm mt-1"><input type="text" class="form-control" readonly value="{{ $driveRedirect }}" id="uri-redirect"><button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('uri-redirect').value);this.innerHTML='Copiado'">Copiar</button></div></li>
                            <li>Copia el <em>ID de cliente</em> y el <em>Secreto</em> aquí, pulsa <strong>Guardar configuración</strong> y luego <strong>Conectar con Google</strong>.</li>
                        </ol>
                        <div class="text-muted">Mientras la app esté en modo «prueba», Google pide reconectar cada 7 días; para evitarlo, en la pantalla de consentimiento pulsa <em>Publicar aplicación</em>.</div>
                    </div>
                </div>
            </div>
        </div></div>
    </div>
</div>

<div class="d-flex justify-content-end mt-3">
    <button class="btn btn-success"><i class="bi bi-check2-circle me-1"></i>Guardar configuración</button>
</div>
</form>

<form id="f-drive-probar" method="POST" action="{{ route('superadmin.respaldos.drive.probar') }}">@csrf</form>
<form id="f-drive-desconectar" method="POST" action="{{ route('superadmin.respaldos.drive.desconectar') }}">@csrf</form>

{{-- Historial --}}
<div class="card border-0 shadow-sm mt-4" style="border-radius:14px;"><div class="card-body">
    <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-1"></i>Últimos respaldos</h6>
    <div class="table-responsive">
    <table class="table table-sm align-middle mb-0" data-no-dt>
        <thead><tr><th>Fecha</th><th>Resultado</th><th>Base de datos</th><th>Archivos</th><th class="text-center">Carpeta local</th><th class="text-center">Google Drive</th><th class="text-end">Duración</th></tr></thead>
        <tbody>
        @forelse($historial as $run)
            <tr>
                <td class="small">{{ $run->iniciado_en->timezone($cfg->zona_horaria ?: 'UTC')->format('d/m/Y H:i') }}</td>
                <td>@if($run->estado === 'exitoso')<span class="badge text-bg-success">Correcto</span>@else<span class="badge text-bg-danger" title="{{ $run->error_mensaje }}">Falló ({{ $run->etapa_fallo }})</span>@endif</td>
                <td class="small">{{ $run->bd_archivo ?? '—' }}</td>
                <td class="small">{{ $run->archivos_archivo ?? '—' }}</td>
                @foreach(['carpeta_local', 'drive'] as $d)
                    @php $e = $estadoDestino($run, $d); @endphp
                    <td class="text-center">@if($e === null)<span class="text-muted">—</span>@elseif($e)<i class="bi bi-check-circle-fill text-success" title="Copiado"></i>@else<i class="bi bi-x-circle-fill text-danger" title="{{ collect($run->destinos[$d])->pluck('error')->filter()->first() }}"></i>@endif</td>
                @endforeach
                <td class="text-end small">{{ $run->duracion_segundos }} s</td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-3">Todavía no hay respaldos.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div></div>

{{-- Archivos en el servidor --}}
<div class="card border-0 shadow-sm mt-3" style="border-radius:14px;"><div class="card-body">
    <h6 class="fw-bold mb-3"><i class="bi bi-hdd-fill me-1"></i>Archivos guardados en el servidor ({{ $backups->count() }})</h6>
    <div class="table-responsive">
    <table class="table table-sm align-middle mb-0" data-no-dt>
        <tbody>
        @forelse($backups as $b)
            <tr>
                <td class="small fw-semibold">{{ $b['name'] }}</td><td class="small text-muted">{{ $b['size'] }}</td><td class="small text-muted">{{ $b['date'] }}</td>
                <td class="text-end">
                    <a href="{{ route('superadmin.respaldos.descargar', ['file' => $b['name']]) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-download"></i></a>
                    <form class="d-inline" method="POST" action="{{ route('superadmin.respaldos.eliminar') }}" onsubmit="return confirm('¿Eliminar este respaldo del servidor?')">@csrf<input type="hidden" name="file" value="{{ $b['name'] }}"><button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button></form>
                </td>
            </tr>
        @empty
            <tr><td class="text-center text-muted py-3">No hay archivos.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div></div>

{{-- Cómo activar el programador --}}
<div class="card border-0 shadow-sm mt-3 mb-4" style="border-radius:14px;"><div class="card-body small">
    <h6 class="fw-bold mb-2"><i class="bi bi-gear-wide-connected me-1"></i>Cómo activar el respaldo automático en el servidor (una sola vez)</h6>
    <p class="mb-2">El respaldo se ejecuta a la hora elegida solo si el servidor llama <strong>cada minuto</strong> al programador de Laravel:</p>
    <div class="row g-3">
        <div class="col-md-6"><div class="fw-semibold">Linux (cron)</div><code class="d-block p-2 rounded" style="background:#f1f5f9;word-break:break-all;">* * * * * cd {{ $rutaProyecto }} &amp;&amp; php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code></div>
        <div class="col-md-6"><div class="fw-semibold">Windows (Programador de tareas)</div><code class="d-block p-2 rounded" style="background:#f1f5f9;word-break:break-all;">php.exe {{ $rutaProyecto }}\artisan schedule:run</code><span class="text-muted">Repetir cada 1 minuto, indefinidamente.</span></div>
    </div>
</div></div>

@push('scripts')
<script>
(function () {
    const f = document.getElementById('frecuencia'), b = document.getElementById('bloque-dia');
    const sync = () => { b.style.display = f.value === 'semanal' ? '' : 'none'; };
    f.addEventListener('change', sync); sync();
})();
</script>
@endpush
@endsection
