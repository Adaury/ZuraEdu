<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Comprobante de Préstamo — Equipo #{{ $prestamo->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            background: #fff;
            padding: 30px 40px;
        }

        /* ── Encabezado institucional ── */
        .header {
            display: flex;
            align-items: center;
            border-bottom: 3px solid #1d4ed8;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .header .logo {
            width: 70px;
            height: 70px;
            object-fit: contain;
            margin-right: 16px;
        }
        .header .inst-info h1 {
            font-size: 15px;
            font-weight: 700;
            color: #1d4ed8;
        }
        .header .inst-info p {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }

        /* ── Título del documento ── */
        .doc-title {
            text-align: center;
            background: #1d4ed8;
            color: #fff;
            padding: 10px 0;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        /* ── Sección de datos ── */
        .section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #1d4ed8;
            border-bottom: 1px solid #bfdbfe;
            padding-bottom: 4px;
            margin-bottom: 10px;
            margin-top: 16px;
        }

        .data-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .data-grid td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .data-grid .label {
            background: #f1f5f9;
            font-weight: 600;
            width: 40%;
            color: #475569;
        }
        .data-grid .value {
            color: #1e293b;
        }

        /* ── Badge de estado ── */
        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-activo   { background: #dbeafe; color: #1d4ed8; }
        .badge-devuelto { background: #dcfce7; color: #15803d; }
        .badge-vencido  { background: #fee2e2; color: #b91c1c; }

        /* ── Aviso / Condiciones ── */
        .notice-box {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 4px;
            padding: 10px 14px;
            margin-top: 20px;
            font-size: 10px;
            color: #78350f;
        }
        .notice-box strong { color: #92400e; }

        /* ── Firmas ── */
        .firmas {
            margin-top: 40px;
            width: 100%;
            border-collapse: collapse;
        }
        .firmas td {
            width: 50%;
            text-align: center;
            padding: 0 20px;
        }
        .firma-line {
            border-top: 1px solid #94a3b8;
            margin: 0 auto 6px;
            width: 85%;
        }
        .firma-label {
            font-size: 10px;
            color: #64748b;
        }
        .firma-name {
            font-size: 11px;
            font-weight: 600;
            margin-top: 4px;
        }

        /* ── Pie de página ── */
        .footer {
            margin-top: 30px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
        }

        .folio {
            float: right;
            font-size: 10px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    {{-- ══ Encabezado ══ --}}
    <div class="header">
        @if($logo)
        <img src="{{ public_path('storage/' . $logo) }}" class="logo" alt="Logo">
        @endif
        <div class="inst-info">
            <h1>{{ $inst }}</h1>
            <p>Departamento de Recursos Tecnológicos</p>
            <p>Generado el {{ now()->format('d/m/Y H:i') }}</p>
        </div>
        <div class="folio">Folio #{{ str_pad($prestamo->id, 5, '0', STR_PAD_LEFT) }}</div>
    </div>

    {{-- ══ Título ══ --}}
    <div class="doc-title">COMPROBANTE DE PRÉSTAMO DE EQUIPO</div>

    {{-- ══ Datos del equipo ══ --}}
    <div class="section-title">Datos del Equipo</div>
    <table class="data-grid">
        <tr>
            <td class="label">Nombre del Equipo</td>
            <td class="value">{{ $prestamo->equipo->nombre }}</td>
        </tr>
        <tr>
            <td class="label">Tipo</td>
            <td class="value">{{ $prestamo->equipo->etiqueta_tipo }}</td>
        </tr>
        @if($prestamo->equipo->codigo)
        <tr>
            <td class="label">Código / Serie</td>
            <td class="value">{{ $prestamo->equipo->codigo }}</td>
        </tr>
        @endif
        @if($prestamo->equipo->descripcion)
        <tr>
            <td class="label">Descripción</td>
            <td class="value">{{ $prestamo->equipo->descripcion }}</td>
        </tr>
        @endif
    </table>

    {{-- ══ Datos del usuario ══ --}}
    <div class="section-title">Datos del Usuario</div>
    <table class="data-grid">
        <tr>
            <td class="label">Nombre completo</td>
            <td class="value">{{ $prestamo->usuario->name }}</td>
        </tr>
        <tr>
            <td class="label">Correo electrónico</td>
            <td class="value">{{ $prestamo->usuario->email }}</td>
        </tr>
    </table>

    {{-- ══ Datos del préstamo ══ --}}
    <div class="section-title">Condiciones del Préstamo</div>
    <table class="data-grid">
        <tr>
            <td class="label">Fecha de Préstamo</td>
            <td class="value">{{ $prestamo->fecha_prestamo->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">Fecha de Vencimiento</td>
            <td class="value">
                {{ $prestamo->fecha_vencimiento->format('d/m/Y') }}
                @if($prestamo->estado === 'activo' && $prestamo->fecha_vencimiento >= now())
                    ({{ $prestamo->fecha_vencimiento->diffInDays(now()) }} días restantes)
                @endif
            </td>
        </tr>
        @if($prestamo->fecha_devolucion)
        <tr>
            <td class="label">Fecha de Devolución</td>
            <td class="value">{{ $prestamo->fecha_devolucion->format('d/m/Y') }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">Estado del Préstamo</td>
            <td class="value">
                <span class="badge badge-{{ $prestamo->estado }}">{{ ucfirst($prestamo->estado) }}</span>
            </td>
        </tr>
        @if($prestamo->motivo)
        <tr>
            <td class="label">Motivo / Propósito</td>
            <td class="value">{{ $prestamo->motivo }}</td>
        </tr>
        @endif
    </table>

    {{-- ══ Aviso de responsabilidad ══ --}}
    <div class="notice-box">
        <strong>Condiciones de uso:</strong> El usuario se compromete a devolver el equipo en las mismas condiciones
        en que fue recibido, antes o en la fecha de vencimiento indicada. Cualquier daño o pérdida del equipo
        será responsabilidad del usuario. En caso de no devolver el equipo en el plazo establecido, se
        aplicarán las sanciones correspondientes según el reglamento institucional.
    </div>

    {{-- ══ Firmas ══ --}}
    <table class="firmas">
        <tr>
            <td>
                <div style="height:45px;"></div>
                <div class="firma-line"></div>
                <div class="firma-label">Firma de quien entrega</div>
                <div class="firma-name">Responsable del Departamento</div>
            </td>
            <td>
                <div style="height:45px;"></div>
                <div class="firma-line"></div>
                <div class="firma-label">Firma de quien recibe</div>
                <div class="firma-name">{{ $prestamo->usuario->name }}</div>
            </td>
        </tr>
    </table>

    {{-- ══ Pie de página ══ --}}
    <div class="footer">
        {{ $inst }} &mdash; Comprobante de Préstamo de Equipo &mdash;
        Folio #{{ str_pad($prestamo->id, 5, '0', STR_PAD_LEFT) }} &mdash;
        Generado el {{ now()->format('d/m/Y \a \l\a\s H:i') }}
    </div>

</body>
</html>
