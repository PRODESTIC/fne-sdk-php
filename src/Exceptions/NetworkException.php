<?php

namespace DgiCi\FneSdk\Exceptions;

use Exception;

class NetworkException extends FneException
{
    public static function connectionFailed(Exception $previous): self
    {
        return new self('Impossible de se connecter à l\'API FNE', 0, $previous);
    }

    public static function timeout(): self
    {
        return new self('Délai d\'attente dépassé lors de l\'appel API', 0);
    }

    public static function dnsResolution(): self
    {
        return new self('Impossible de résoudre le nom de domaine', 0);
    }
}