<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }

.header { text-align: center; margin-bottom: 18px; border-bottom: 2px solid #1e40af; padding-bottom: 12px; }
.header .inst  { font-size: 13px; font-weight: bold; color: #1e40af; text-transform: uppercase; }
.header .dir   { font-size: 9px; color: #475569; margin-top: 3px; }
.header .label { font-size: 10px; font-weight: bold; color: #0f172a; margin-top: 8px;
                 background: #dbeafe; padding: 4px 16px; border-radius: 4px; display: inline-block; }

.meta-chip { display: inline-block; background: #f1f5f9; border: 1px solid #e2e8f0;
             border-radius: 20px; padding: 2px 10px; font-size: 8.5px; color: #475569;
             margin: 2px 3px; font-weight: 600; }

.titulo { font-size: 15px; font-weight: 800; color: #0f172a; margin: 18px 0 10px; line-height: 1.3; }

.cuerpo { font-size: 10.5px; line-height: 1.7; color: #1e293b; margin-bottom: 24px;
          text-align: justify; }

.meta-bar { background: #f8faff; border: 1px solid #dbeafe; border-radius: 5px;
            padding: 8px 12px; margin-bottom: 14px; display: flex; justify-content: space-between; font-size: 8.5px; }
.meta-bar .lbl { color: #6b7280; }
.meta-bar .val { font-weight: 700; color: #1e293b; }

.firma-area { margin-top: 32px; display: flex; gap: 32px; }
.firma-box  { flex: 1; text-align: center; border-top: 1px solid #94a3b8; padding-top: 6px;
              font-size: 8.5px; color: #475569; margin-top: 32px; }

.footer { margin-top: 18px; border-top: 1px solid #e2e8f0; padding-top: 8px;
          display: flex; justify-content: space-between; font-size: 8px; color: #94a3b8; }

.sello-area { text-align: center; margin-top: 30px; }
.sello-circle { width: 80px; height: 80px; border-radius: 50%; border: 2px dashed #94a3b8;
                display: inline-block; line-height: 80px; color: #cbd5e1; font-size: 8px; }
</style>
</head>
<body>

{{-- Encabezado institucional --}}
<div class="header">
    <div class="inst">{{ $inst }}</div>
    @if($dir)
    <div class="dir">Director/a: {{ $dir }}</div>
    @endif
    @if($config?->codigo_centro || \App\Models\ConfigInstitucional::get('codigo_centro',''))
    <div class="dir">Código: {{ $config?->codigo_centro ?? \App\Models\ConfigInstitucional::get('codigo_centro','') }}</div>
    @endif
    <div><span class="label">COMUNICADO OFICIAL</span></div>
</div>

{{-- Metadata --}}
<div class="meta-bar">
    <div>
        <span class="lbl">Fecha:</span>
        <span class="val">{{ $comunicado->published_at ? \Carbon\Carbon::parse($comunicado->published_at)->format('d/m/Y') : now()->format('d/m/Y') }}</span>
    </div>
    <div>
        <span class="lbl">Destinatarios:</span>
        <span class="val">
            @switch($comunicado->tipo_destinatarios)
                @case('todos') Toda la comunidad educativa @break
                @case('docentes') Docentes @break
                @case('coordinadores') Coordinadores y Directivos @break
                @case('grupo') {{ $comunicado->grupo ? $comunicado->grupo->nombre_completo : 'Grupo específico' }} @break
                @default {{ ucfirst($comunicado->tipo_destinatarios) }}
            @endswitch
        </span>
    </div>
    <div>
        <span class="lbl">Emitido por:</span>
        <span class="val">{{ $comunicado->autor?->name ?? 'Administración' }}</span>
    </div>
</div>

{{-- Título --}}
<div class="titulo">{{ $comunicado->titulo }}</div>

{{-- Cuerpo del comunicado --}}
<div class="cuerpo">{!! nl2br(e($comunicado->cuerpo)) !!}</div>

{{-- Espacio de firma --}}
<div style="text-align:right;font-size:9px;color:#475569;margin-bottom:8px;">
    {{ $inst }}, {{ $comunicado->published_at ? \Carbon\Carbon::parse($comunicado->published_at)->format('d') . ' de ' . \Carbon\Carbon::parse($comunicado->published_at)->translatedFormat('F') . ' de ' . \Carbon\Carbon::parse($comunicado->published_at)->format('Y') : now()->translatedFormat('d \d\e F \d\e Y') }}.
</div>

<div class="firma-area">
    <div class="firma-box">
        <strong>{{ $dir ?: 'Director/a del Centro' }}</strong><br>
        Director/a
    </div>
    <div style="flex:1;text-align:center;margin-top:32px;">
        <div class="sello-circle">SELLO</div>
    </div>
    <div class="firma-box">
        Receptor: ____________________________<br>
        Fecha: _______________________________
    </div>
</div>

<div class="footer">
    <span>{{ $inst }} — Comunicado Oficial</span>
    <span>Generado: {{ now()->format('d/m/Y H:i') }}</span>
</div>
</body>
</html>
