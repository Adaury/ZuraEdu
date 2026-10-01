<?php

namespace App\Support\Validation;

use Illuminate\Validation\Validator;

/**
 * Validador con las reglas `exists` y `unique` conscientes del COLEGIO.
 *
 * Dos verificadores distintos, porque tienen necesidades opuestas:
 *  - `exists` (TenantPresenceVerifier): solo cuenta lo del colegio actual y lo NO borrado lógicamente; si no, se aceptaban
 *    referencias ajenas o a registros borrados.
 *  - `unique` (TenantUniquePresenceVerifier): replica la restricción única real de la BD (por colegio o global, según el
 *    esquema) y SÍ ve las filas borradas, que la restricción también cuenta.
 */
class TenantAwareValidator extends Validator
{
    public function validateExists($attribute, $value, $parameters)
    {
        return $this->conVerificador(new TenantPresenceVerifier(app('db')), fn () => parent::validateExists($attribute, $value, $parameters));
    }

    public function validateUnique($attribute, $value, $parameters)
    {
        return $this->conVerificador(new TenantUniquePresenceVerifier(app('db')), fn () => parent::validateUnique($attribute, $value, $parameters));
    }

    private function conVerificador($verificador, callable $regla): bool
    {
        $original = $this->presenceVerifier;
        $this->presenceVerifier = $verificador;

        try {
            return $regla();
        } finally {
            $this->presenceVerifier = $original;
        }
    }
}
