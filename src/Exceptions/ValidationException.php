<?php

namespace DgiCi\FneSdk\Exceptions;

class ValidationException extends FneException
{
    private array $errors = [];

    public function __construct(string $message, array $errors = [])
    {
        parent::__construct($message, 400);
        $this->errors = $errors;
    }

    public static function withErrors(array $errors): self
    {
        $message = 'Erreurs de validation: ' . implode(', ', array_keys($errors));
        return new self($message, $errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    public function getError(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }
}