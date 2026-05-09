<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Solicitud</title>
    <link href="/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Inter, sans-serif; background: #f1f5f9; min-height: 100vh; display: flex; flex-direction: column; }
    .nav { background: rgba(255,255,255,.96); border-bottom: 1px solid #e2e8f0; }
    .nav-inner { max-width: 1100px; margin: 0 auto; padding: 0 1.5rem; display: flex; align-items: center; height: 58px; gap: 1rem; }
    .nav-logo { display: flex; align-items: center; gap: .6rem; text-decoration: none; }
    .nav-logo-icon { width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg, #1e3a8a, #3b82f6); display: flex; align-items: center; justify-content: center; color: #fff; font-size: .8rem; }
    .nav-logo-name { font-size: 1rem; font-weight: 900; color: #0f172a; }
    .nav-link { margin-left: auto; display: inline-flex; align-items: center; gap: .4rem; padding: .4rem .85rem; border-radius: 8px; font-size: .82rem; font-weight: 500; color: #64748b; text-decoration: none; border: 1.5px solid #e2e8f0; }
    .nav-link:hover { color: #1d4ed8; border-color: #3b82f6; background: #eff6ff; }
    main { flex: 1; padding: 3rem 1.25rem; }
    .wrap { max-width: 640px; margin: 0 auto; }

    /* HERO */
    .hero { background: linear-gradient(145deg, #0f172a, #1e3a8a); border-radius: 20px; padding: 2.5rem 2rem; text-align: center; margin-bottom: 2rem; }
    .hero h1 { font-size: 1.5rem; font-weight: 900; color: #fff; margin-bottom: .5rem; }
    .hero p { font-size: .9rem; color: rgba(255,255,255,.65); }

    /* FORM */
    .search-card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(15,23,42,.09); padding: 1.75rem 2rem; margin-bottom: 1.5rem; }
    .search-label { font-size: .8rem; font-weight: 700; color: #374151; margin-bottom: .5rem; display: block; }
    .search-row { display: flex; gap: .75rem; }
    .search-input { flex: 1; padding: .65rem 1rem; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: .95rem; color: #0f172a; font-family: monospace; letter-spacing: .05em; text-transform: uppercase; outline: none; transition: border-color .15s, box-shadow .15s; }
    .search-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); }
    .search-input::placeholder { letter-spacing: 0; text-transform: none; color: #94a3b8; font-family: Inter, sans-serif; }
    .search-btn { padding: .65rem 1.25rem; border-radius: 10px; background: linear-gradient(135deg, #1e3a8a, #2563eb); color: #fff; font-size: .9rem; font-weight: 700; border: none; cursor: pointer; white-space: nowrap; display: flex; align-items: center; gap: .4rem; }
    .search-btn:hover { opacity: .9; }

    /* ERROR */
    .error-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: .85rem 1rem; font-size: .85rem; color: #dc2626; display: flex; align-items: center; gap: .5rem; }

    /* RESULT */
    .result-card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(15,23,42,.09); overflow: hidden; }
    .result-header { padding: 1.25rem 1.75rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .75rem; }
    .result-title { font-size: 1rem; font-weight: 700; color: #0f172a; }
    .badge { display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .9rem; border-radius: 99px; font-size: .8rem; font-weight: 700; }
    .badge-pendiente  { background: #fef9c3; color: #854d0e; }
    .badge-aprobada   { background: #d1fae5; color: #065f46; }
    .badge-rechazada  { background: #fee2e2; color: #991b1b; }
    .result-body { padding: 1.5rem 1.75rem; }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem 1.5rem; margin-bottom: 1.5rem; }
    .info-item label { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; display: block; margin-bottom: .15rem; }
    .info-item span { font-size: .88rem; color: #0f172a; font-weight: 500; }
    .notas-box { background: #f8fafc; border-radius: 10px; padding: 1rem; margin-top: .5rem; font-size: .85rem; color: #374151; }
    .notas-box .notas-label { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; margin-bottom: .4rem; }
    .result-footer { padding: 1.25rem 1.75rem; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; gap: .75rem; flex-wrap: wrap; }
    .btn { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem 1rem; border-radius: 9px; font-size: .85rem; font-weight: 600; text-decoration: none; border: none; cursor: pointer; }
    .btn-blue { background: linear-gradient(135deg, #1e3a8a, #2563eb); color: #fff; }
    .btn-outline { background: #fff; color: #374151; border: 1.5px solid #e2e8f0; }

    /* Status info */
    .status-pendiente { background: #fefce8; border: 1px solid #fef08a; color: #78350f; border-radius: 10px; padding: .85rem 1rem; font-size: .82rem; display: flex; align-items: flex-start; gap: .5rem; margin-bottom: 1.25rem; }
    .status-aprobada  { background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; border-radius: 10px; padding: .85rem 1rem; font-size: .82rem; display: flex; align-items: flex-start; gap: .5rem; margin-bottom: 1.25rem; }
    .status-rechazada { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; border-radius: 10px; padding: .85rem 1rem; font-size: .82rem; display: flex; align-items: flex-start; gap: .5rem; margin-bottom: 1.25rem; }
    .docs-badge { display: inline-flex; align-items: center; gap: .3rem; background: #d1fae5; color: #065f46; border-radius: 99px; padding: .2rem .6rem; font-size: .73rem; font-weight: 700; margin: .1rem; }

    /* Empty state */
    .empty-state { text-align: center; padding: 3rem 1rem; }
    .empty-state i { font-size: 3rem; color: #94a3b8; margin-bottom: 1rem; display: block; }
    .empty-state p { color: #64748b; font-size: .9rem; }

    @media (max-width: 500px) { .search-row { flex-direction: column; } .info-grid { grid-template-columns: 1fr; } .result-body { padding: 1.25rem; } }
    </style>
</head>
<body>
<nav class="nav">
    <div class="nav-inner">
        <a href="{{ route('landing') }}" class="nav-logo">
            @if(!empty($logo))
                <img src="{{ $logo }}" alt="Logo" style="height:30px;width:auto;border-radius:6px;">
            @else
                <div class="nav-logo-icon"><i class="bi bi-mortarboard-fill"></i></div>
            @endif
            <span class="nav-logo-name">{{ $system_name ?? $system_abbr ?? config('app.name') }}</span>
        </a>
        <a href="{{ route('inscripcion') }}" class="nav-link">
            <i class="bi bi-pencil-square"></i> Nueva solicitud
        </a>
    </div>
</nav>

<main>
    <div class="wrap">
        <div class="hero">
            <h1><i class="bi bi-search me-2"></i>Consultar Estado de Solicitud</h1>
            <p>Ingrese el codigo de seguimiento que recibio por correo al enviar su solicitud.</p>
        </div>

        {{-- Formulario de busqueda --}}
        <div class="search-card">
            <label class="search-label" for="codigoBuscar">Codigo de seguimiento</label>
            <form method="GET" action="{{ route('inscripcion.consulta') }}">
                <div class="search-row">
                    <input type="text" id="codigoBuscar" name="codigo"
                           value="{{ $busqueda }}"
                           placeholder="Ej: PM-AB12CD34"
                           class="search-input"
                           maxlength="20" autocomplete="off">
                    <button type="submit" class="search-btn">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </div>
            </form>
        </div>

        {{-- Error --}}
        @if($error)
        <div class="error-box" style="margin-bottom:1.5rem;">
            <i class="bi bi-exclamation-circle-fill"></i> {{ $error }}
        </div>
        @endif

        {{-- Resultado --}}
        @if($pm)
        <div class="result-card">
            <div class="result-header">
                <div>
                    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:.2rem;">
                        Codigo: <span style="color:#1e3a8a;font-family:monospace;font-size:.85rem;">{{ $pm->codigo }}</span>
                    </div>
                    <div class="result-title">{{ $pm->nombre_completo }}</div>
                    <div style="font-size:.82rem;color:#64748b;margin-top:.15rem;">
                        {{ $pm->grado_solicitado }} · Solicitado el {{ $pm->created_at->format('d/m/Y') }}
                    </div>
                </div>
                @if($pm->estado === 'aprobada')
                    <span class="badge badge-aprobada"><i class="bi bi-check-circle-fill"></i> Aprobada</span>
                @elseif($pm->estado === 'rechazada')
                    <span class="badge badge-rechazada"><i class="bi bi-x-circle-fill"></i> No Aprobada</span>
                @else
                    <span class="badge badge-pendiente"><i class="bi bi-clock-fill"></i> Pendiente</span>
                @endif
            </div>

            <div class="result-body">
                {{-- Mensaje de estado --}}
                @if($pm->estado === 'aprobada')
                <div class="status-aprobada">
                    <i class="bi bi-check-circle-fill" style="flex-shrink:0;font-size:1.1rem;margin-top:.05rem;"></i>
                    <span>Su solicitud fue <strong>aprobada</strong>. Por favor acerquese a la institucion para completar el proceso de matricula formal.</span>
                </div>
                @elseif($pm->estado === 'rechazada')
                <div class="status-rechazada">
                    <i class="bi bi-x-circle-fill" style="flex-shrink:0;font-size:1.1rem;margin-top:.05rem;"></i>
                    <span>Su solicitud <strong>no fue aprobada</strong> en esta oportunidad. Revise el motivo en la seccion de notas a continuacion.</span>
                </div>
                @else
                <div class="status-pendiente">
                    <i class="bi bi-hourglass-split" style="flex-shrink:0;font-size:1.1rem;margin-top:.05rem;"></i>
                    <span>Su solicitud esta <strong>en revision</strong>. El personal administrativo la procesara pronto. Recibira un correo con la resolucion.</span>
                </div>
                @endif

                {{-- Datos --}}
                <div class="info-grid">
                    <div class="info-item"><label>Estudiante</label><span>{{ $pm->nombre_completo }}</span></div>
                    <div class="info-item"><label>Grado Solicitado</label><span>{{ $pm->grado_solicitado }}</span></div>
                    <div class="info-item"><label>Fecha Nacimiento</label><span>{{ $pm->fecha_nacimiento->format('d/m/Y') }}</span></div>
                    <div class="info-item"><label>Genero</label><span>{{ $pm->genero ?? 'No indicado' }}</span></div>
                    <div class="info-item"><label>Representante</label><span>{{ $pm->nombre_representante }}</span></div>
                    <div class="info-item"><label>Relacion</label><span>{{ $pm->relacion_representante ?? 'No indicada' }}</span></div>
                    <div class="info-item"><label>Telefono</label><span>{{ $pm->telefono }}</span></div>
                    <div class="info-item"><label>Fecha Solicitud</label><span>{{ $pm->created_at->format('d/m/Y H:i') }}</span></div>
                    @if($pm->documentos)
                    <div class="info-item" style="grid-column:1/-1;">
                        <label>Documentos adjuntos</label>
                        <div>
                            @foreach(array_keys($pm->documentos) as $doc)
                            <span class="docs-badge"><i class="bi bi-file-check-fill"></i>
                                @if($doc === 'cedula_representante') Cedula Rep.
                                @elseif($doc === 'acta_nacimiento') Acta Nacimiento
                                @elseif($doc === 'foto_estudiante') Foto
                                @else {{ $doc }} @endif
                            </span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Notas del admin --}}
                @if($pm->notas_admin)
                <div class="notas-box">
                    <div class="notas-label"><i class="bi bi-chat-text-fill me-1"></i>Observaciones del centro</div>
                    {{ $pm->notas_admin }}
                </div>
                @endif
            </div>

            <div class="result-footer">
                <a href="{{ route('inscripcion') }}" class="btn btn-blue">
                    <i class="bi bi-plus-circle"></i> Nueva Solicitud
                </a>
                <a href="{{ route('login') }}" class="btn btn-outline">
                    <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesion
                </a>
            </div>
        </div>

        @elseif($busqueda && !$error)
        {{-- Never reached, but fallback --}}
        @elseif(!$busqueda)
        <div class="empty-state">
            <i class="bi bi-search"></i>
            <p>Ingrese su codigo de seguimiento arriba para consultar el estado de su solicitud.</p>
            <p style="margin-top:.5rem;font-size:.8rem;color:#94a3b8;">
                El codigo tiene el formato <strong style="color:#1e3a8a;font-family:monospace;">PM-XXXXXXXX</strong>
                y fue enviado a su correo electronico al completar el formulario.
            </p>
        </div>
        @endif

    </div>
</main>
</body>
</html>
