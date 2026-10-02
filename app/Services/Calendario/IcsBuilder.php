<?php

namespace App\Services\Calendario;

use App\Models\CalendarioAcademico;
use Carbon\CarbonInterface;

/**
 * Genera un archivo iCalendar (.ics, RFC 5545) de un evento del calendario escolar.
 * Lo entienden Google Calendar, Outlook y Apple Calendar: Gmail muestra "Agregar al calendario" al recibirlo adjunto.
 * El UID es estable por evento y SEQUENCE sube en cada edición: al volver a abrir el .ics se ACTUALIZA la copia ya agregada
 * en vez de duplicarla.
 */
class IcsBuilder
{
    public function evento(CalendarioAcademico $e): string
    {
        $i   = $e->intervalo();
        $uid = 'calendario-' . $e->id . '-t' . ($e->tenant_id ?? 0) . '@' . $this->dominio();

        $l = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//ZuraEdu//Calendario escolar//ES',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . now()->utc()->format('Ymd\THis\Z'),
            'SEQUENCE:' . (int) $e->ics_sequence,
            'SUMMARY:' . $this->texto($e->titulo),
        ];

        if ($i['todoElDia']) {
            $l[] = 'DTSTART;VALUE=DATE:' . $i['inicio']->format('Ymd');
            $l[] = 'DTEND;VALUE=DATE:' . $i['fin']->format('Ymd');
        } else {
            $l[] = 'DTSTART:' . $this->utc($i['inicio']);
            $l[] = 'DTEND:' . $this->utc($i['fin']);
        }

        $desc = trim(($e->descripcion ? $e->descripcion . "\n\n" : '') . (CalendarioAcademico::tiposLabels()[$e->tipo] ?? ''));
        if ($desc !== '') {
            $l[] = 'DESCRIPTION:' . $this->texto($desc);
        }
        $l[] = 'CATEGORIES:' . $this->texto(CalendarioAcademico::tiposLabels()[$e->tipo] ?? 'Evento');
        $l[] = 'STATUS:' . ($e->activo ? 'CONFIRMED' : 'CANCELLED');
        $l[] = 'END:VEVENT';
        $l[] = 'END:VCALENDAR';

        return implode("\r\n", array_map([$this, 'plegar'], $l)) . "\r\n";
    }

    private function utc(CarbonInterface $d): string
    {
        return $d->copy()->utc()->format('Ymd\THis\Z');
    }

    /** Escapa según RFC 5545: \ ; , y saltos de línea. Quita caracteres de control (evita inyectar líneas del .ics). */
    private function texto(string $s): string
    {
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $s) ?? '';
        $s = str_replace(["\r\n", "\r"], "\n", $s);
        $s = str_replace(['\\', ';', ',', "\n"], ['\\\\', '\;', '\,', '\n'], $s);

        return $s;
    }

    /** Las líneas del .ics no pueden pasar de 75 octetos: se parten y la continuación empieza con un espacio. */
    private function plegar(string $linea): string
    {
        if (strlen($linea) <= 75) {
            return $linea;
        }
        $partes = [];
        $resto  = $linea;
        $max    = 75;
        while (strlen($resto) > $max) {
            $corte = $max;
            // No cortar en medio de un carácter UTF-8 multibyte.
            while ($corte > 0 && (ord($resto[$corte]) & 0xC0) === 0x80) {
                $corte--;
            }
            $partes[] = substr($resto, 0, $corte);
            $resto    = substr($resto, $corte);
            $max      = 74; // las continuaciones llevan el espacio inicial
        }
        $partes[] = $resto;

        return implode("\r\n ", $partes);
    }

    private function dominio(): string
    {
        return parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'zuraedu.local';
    }
}
