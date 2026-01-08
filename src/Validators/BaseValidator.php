<?php

namespace DgiCi\FneSdk\Validators;

use DgiCi\FneSdk\Exceptions\ValidationException;

abstract class BaseValidator
{
    protected array $errors = [];

    protected function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }

    protected function validateRequired(string $field, $value, ?string $fieldName = null): bool
    {
        $fieldName = $fieldName ?? $field;

        if (empty($value)) {
            $this->addError($field, "Le champ '{$fieldName}' est obligatoire");
            return false;
        }
        return true;
    }

    protected function validateEmail(string $field, string $email): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "L'adresse email n'est pas valide");
            return false;
        }
        return true;
    }

    protected function validatePhone(string $field, string $phone): bool
    {
        // Validation basique pour numéro ivoirien
        if (!preg_match('/^[0-9]{8,10}$/', $phone)) {
            $this->addError($field, "Le numéro de téléphone doit contenir entre 8 et 10 chiffres");
            return false;
        }
        return true;
    }

    protected function validateInArray(string $field, $value, array $allowedValues, ?string $fieldName = null): bool
    {
        $fieldName = $fieldName ?? $field;

        if (!in_array($value, $allowedValues)) {
            $allowed = implode(', ', $allowedValues);
            $this->addError($field, "Le champ '{$fieldName}' doit être l'une des valeurs suivantes: {$allowed}");
            return false;
        }
        return true;
    }

    protected function validatePositiveNumber(string $field, $value, ?string $fieldName = null): bool
    {
        $fieldName = $fieldName ?? $field;

        if (!is_numeric($value) || $value < 0) {
            $this->addError($field, "Le champ '{$fieldName}' doit être un nombre positif");
            return false;
        }
        return true;
    }

    protected function validateNcc(string $field, string $ncc): bool
    {
        // Format NCC: 7 chiffres + 1 lettre (ex: 9500015F)
        if (!preg_match('/^[0-9]{7}[A-Z]$/', $ncc)) {
            $this->addError($field, "Le NCC doit être composé de 7 chiffres suivis d'une lettre majuscule");
            return false;
        }
        return true;
    }

    protected function throwIfErrors(): void
    {
        if (!empty($this->errors)) {
            throw ValidationException::withErrors($this->errors);
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}