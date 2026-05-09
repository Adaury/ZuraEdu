<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud Recibida</title>
    <link href="/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Inter, sans-serif; background: #f1f5f9; min-height: 100vh; display: flex; flex-direction: column; }
    .nav { background: rgba(255,255,255,.96); border-bottom: 1px solid #e2e8f0; }
    .nav-inner { max-width: 1100px; margin: 0 auto; padding: 0 1.5rem; display: flex; align-items: center; height: 58px; }
    .nav-logo { display: flex; align-items: center; gap: .6rem; text-decoration: none; }
    .nav-logo-icon { width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg, #1e3a8a, #3b82f6); display: flex; align-items: center; justify-content: center; color: #fff; font-size: .8rem; }
    .nav-logo-name { font-size: 1rem; font-weight: 900; color: #0f172a; }
    main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 2.5rem 1.25rem; }
    .card { max-width: 640px; width: 100%; }
    .success-header { background: linear-gradient(135deg, #065f46, #059669); border-radius: 20px 20px 0 0; padding: 2.5rem 2rem; text-align: center; }
    .check-circle { width: 72px; height: 72px; border-radius: 50%; background: rgba(255,255,255,.2); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem; }
    .check-circle i { font-size: 2rem; color: #fff; }
    .success-header h1 { font-size: 1.6rem; font-weight: 900; color: #fff; margin-bottom: .5rem; }
    .success-header p { color: rgba(255,255,255,.75); font-size: .92rem; }
    .card-body { background: #fff; padding: 2rem 2.25rem; border-radius: 0 0 20px 20px; box-shadow: 0 8px 40px rgba(15,23,42,.12); }
    .code-box { background: linear-gradient(135deg, #eff6ff, #dbeafe); border: 2px solid #93c5fd; border-radius: 16px; padding: 1.5rem; text-align: center; margin-bottom: 1.75rem; }
    .code-label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #1e40af; margin-bottom: .5rem; }
    .code-value { font-size: 2rem; font-weight: 900; letter-spacing: .12em; color: #1e3a8a; font-family: monospace; }
    .code-hint { font-size: .78rem; color: #3b82f6; margin-top: .5rem; }
    .copy-btn { display: inline-flex; align-items: center; gap: .35rem; margin-top: .6rem; padding: .4rem .85rem; border: 1.5px solid #93c5fd; border-radius: 8px; background: #fff; color: #1d4ed8; font-size: .8rem; font-weight: 600; cursor: pointer; }
    .data-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem 1.5rem; margin-bottom: 1.5rem; }
    .data-item label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; display: block; margin-bottom: .15rem; }
    .data-item span { font-size: .88rem; color: #0f172a; font-weight: 500; }
    .divider { border: none; border-top: 1px solid #f1f5f9; margin: 1.5rem 0; }
    .actions { display: flex; gap: .75rem; flex-wrap: wrap; }
    .btn { display: inline-flex; align-items: center; gap: .4rem; padding: .6rem 1.1rem; border-radius: 10px; font-size: .88rem; font-weight: 600; text-decoration: none; border: none; cursor: pointer; }
    .btn-primary { background: linear-gradient(135deg, #1e3a8a, #2563eb); color: #fff; }
    .btn-outline { background: #fff; color: #374151; border: 1.5px solid #e2e8f0; }
    .docs-badge { display: inline-flex; align-items: center; gap: .3rem; background: #d1fae5; color: #065f46; border-radius: 99px; padding: .25rem .65rem; font-size: .75rem; font-weight: 700; margin: .1rem; }
    .status-info { background: #fefce8; border: 1px solid #fef08a; border-radius: 10px; padding: .85rem 1rem; font-size: .82rem; color: #78350f; display: flex; align-items: flex-start; gap: .5rem; margin-bottom: 1.5rem; }
    @media (max-width: 500px) { .data-grid { grid-template-columns: 1fr; } .card-body { padding: 1.5rem 1.25rem; } .code-value { font-size: 1.5rem; } }
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
            <span class="nav-logo-name">{{ $inst ?? config('app.name') }}</span>
        </a>
    </div>
</nav>
<main>
    <div class="card">
        <div class="success-header">
            <div class="check-circle"><i class="bi bi-check-lg"></i></div>
            <h1>&#10003; Solicitud Recibida</h1>
            <p>Hemos recibido su solicitud correctamente. Le contactaremos pronto.</p>
        </div>
        <div class="card-body">
            <div class="code-box">
                <div class="code-label"><i class="bi bi-key-fill"></i> Codigo de Seguimiento</div>
                <div class="code-value" id="codigoPM">{{ $pm->codigo }}</div>
                <div class="code-hint">Guarde este codigo para consultar el estado de su solicitud</div>
                <button class="copy-btn" onclick="copyCodigo()">
                    <i class="bi bi-clipboard" id="copyIcon"></i>
                    <span id="copyText">Copiar codigo</span>
                </button>
                @if($pm->email)
                <div style="font-size:.76rem;color:#64748b;margin-top:.6rem;">
                    <i class="bi bi-envelope-fill" style="color:#94a3b8;"></i>
                    Tambien lo recibira en <strong>{{ $pm->email }}</strong>
                </div>
                @endif
            </div>
            <div class="status-info">
                <i class="bi bi-clock-fill" style="flex-shrink:0;margin-top:.1rem;color:#d97706;"></i>
                <span>Su solicitud esta <strong>pendiente de revision</strong>. Recibira un correo con la resolucion.</span>
            </div>
            <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:.85rem;">
                Resumen de la solicitud
            </div>
            <div class="data-grid">
                <div class="data-item"><label>Estudiante</label><span>{{ $pm->nombre_completo }}</span></div>
                <div class="data-item"><label>Grado Solicitado</label><span>{{ $pm->grado_solicitado }}</span></div>
                <div class="data-item"><label>Fecha Nacimiento</label><span>{{ $pm->fecha_nacimiento->format('d/m/Y') }}</span></div>
                <div class="data-item"><label>Genero</label><span>{{ $pm->genero ?? 'No indicado' }}</span></div>
                <div class="data-item"><label>Representante</label><span>{{ $pm->nombre_representante }}</span></div>
                <div class="data-item"><label>Relacion</label><span>{{ $pm->relacion_representante ?? 'No indicada' }}</span></div>
                <div class="data-item"><label>Telefono</label><span>{{ $pm->telefono }}</span></div>
                <div class="data-item"><label>Fecha Solicitud</label><span>{{ $pm->created_at->format('d/m/Y H:i') }}</span></div>
                @if($pm->documentos)
                <div class="data-item" style="grid-column:1/-1;">
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
            <hr class="divider">
            <div class="actions">
                <a href="{{ route('inscripcion.consulta') }}?codigo={{ $pm->codigo }}" class="btn btn-primary">
                    <i class="bi bi-search"></i> Consultar Estado
                </a>
                <a href="{{ route('inscripcion') }}" class="btn btn-outline">
                    <i class="bi bi-plus-circle"></i> Nueva Solicitud
                </a>
                <a href="{{ route('landing') }}" class="btn btn-outline">
                    <i class="bi bi-house"></i> Inicio
                </a>
            </div>
        </div>
    </div>
</main>
<script>
function copyCodigo() {
    var c = document.getElementById("codigoPM").textContent.trim();
    navigator.clipboard.writeText(c).then(function() {
        document.getElementById("copyIcon").className = "bi bi-clipboard-check";
        document.getElementById("copyText").textContent = "Copiado!";
        setTimeout(function() {
            document.getElementById("copyIcon").className = "bi bi-clipboard";
            document.getElementById("copyText").textContent = "Copiar codigo";
        }, 2000);
    });
}
</script>
</body>
</html>
