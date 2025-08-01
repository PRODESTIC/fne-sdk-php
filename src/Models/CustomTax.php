<?php

namespace DgiCi\FneSdk\Models;

class CustomTax
{
    private string $name;
    private float $amount;

    public function __construct(string $name, float $amount)
    {
        $this->name = $name;
        $this->amount = $amount;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'amount' => $this->amount
        ];
    }

    public function getName(): string { return $this->name; }
    public function getAmount(): float { return $this->amount; }
}