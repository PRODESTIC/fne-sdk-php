<?php

namespace DgiCi\FneSdk\Exceptions;

class ApiException extends FneException
{
    public static function badRequest(string $message, array $context = []): self
    {
        return new self($message, 400, null, $context);
    }

    public static function internalServerError(string $message = 'Erreur interne du serveur'): self
    {
        return new self($message, 500);
    }

    public static function endpointNotAvailable(): self
    {
        return new self('Point de terminaison non disponible', 500);
    }

    public static function fromResponse(array $response, int $statusCode): self
    {
        $message = $response['message'] ?? 'Erreur API';
        $error = $response['error'] ?? '';
        $context = [
            'error_type' => $error,
            'response' => $response
        ];

        return new self($message, $statusCode, null, $context);
    }
}