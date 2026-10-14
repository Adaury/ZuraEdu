<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/**
 * Inyección de fórmulas en Excel: PhpSpreadsheet convierte en FÓRMULA cualquier texto que empiece por "=".
 * Los exportes del sistema escriben datos que escribe un usuario (nombres, descripciones, motivos,
 * observaciones…): un valor como «=HYPERLINK("http://sitio-malo","Click")» se ejecutaría al abrir el archivo
 * el administrador. Este binder, registrado una sola vez para toda la app, guarda como TEXTO todo lo que
 * empiece por = + - @ (o tab/retorno, que Excel también interpreta) y no sea un número.
 *
 * Los números (incluso "-5", "+3,5" ya como numérico) y el resto de los textos siguen igual.
 */
class ValueBinderSeguro extends DefaultValueBinder
{
    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value) && $value !== '' && ! is_numeric($value) && preg_match('/^[=+\-@\t\r]/', $value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
