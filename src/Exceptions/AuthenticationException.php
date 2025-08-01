<?php

namespace DgiCi\FneSdk\Exceptions;

class AuthenticationException extends FneException
{
    public static function invalidApiKey(): self
    {
        return new self('Clé API invalide ou manquante', 401);
    }

    public static function tokenExpired(): self
    {
        return new self('Token d\'authentification expiré', 401);
    }

    public static function unauthorized(): self
    {
        return new self('Accès non autorisé', 401);
    }
}