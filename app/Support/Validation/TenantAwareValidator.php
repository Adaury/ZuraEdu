<?php

namespace App\Support\Validation;

use Illuminate\Validation\Validator;

/**
 * Validador que usa `TenantPresenceVerifier` SOLO en la regla `exists`.
 *
 * Por qué no se cambia el verificador global: la misma clase la usan `exists` y `unique`, y tienen necesidades opuestas.
 * `exists` debe ignorar lo borrado y lo de otros colegios (si no, se aceptan referencias ajenas); `unique` debe seguir
 * viendo las filas borradas lógicamente, porque la restricción única de la BD también las cuenta. Hacerlo aquí, por regla,
 * evita tocar `unique` (hoy global entre colegios: una decisión aparte).
 */
class TenantAwareValidator extends Validator
{
    public function validateExists($attribute, $value, $parameters)
    {
        $original = $this->presenceVerifier;
        $this->presenceVerifier = new TenantPresenceVerifier(app('db'));

        try {
            return parent::validateExists($attribute, $value, $parameters);
        } finally {
            $this->presenceVerifier = $original;
        }
    }
}
