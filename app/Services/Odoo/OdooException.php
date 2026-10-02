<?php

namespace App\Services\Odoo;

/** Error al hablar con Odoo, con un mensaje apto para mostrar al administrador (nunca incluye la clave de API). */
class OdooException extends \RuntimeException
{
    public function __construct(string $message, public readonly bool $credenciales = false, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
