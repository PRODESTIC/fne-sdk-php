<?php

namespace DgiCi\FneSdk\Models;

class InvoiceItem
{
    private ?string $reference = null;
    private string $description;
    private float $quantity;
    private float $amount;
    private float $discount = 0;
    private ?string $measurementUnit = null;
    private array $taxes = [];
    private array $customTaxes = [];

    public function __construct(
        string $description,
        float $quantity,
        float $amount,
        array $taxes = []
    ) {
        $this->description = $description;
        $this->quantity = $quantity;
        $this->amount = $amount;
        $this->taxes = $taxes;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;
        return $this;
    }

    public function setDiscount(float $discount): self
    {
        $this->discount = $discount;
        return $this;
    }

    public function setMeasurementUnit(?string $unit): self
    {
        $this->measurementUnit = $unit;
        return $this;
    }

    public function addCustomTax(string $name, float $amount): self
    {
        $this->customTaxes[] = ['name' => $name, 'amount' => $amount];
        return $this;
    }

    public function toArray(): array
    {
        $data = [
            'description' => $this->description,
            'quantity' => $this->quantity,
            'amount' => $this->amount,
            'taxes' => $this->taxes
        ];

        if ($this->reference !== null) {
            $data['reference'] = $this->reference;
        }

        if ($this->discount > 0) {
            $data['discount'] = $this->discount;
        }

        if ($this->measurementUnit !== null) {
            $data['measurementUnit'] = $this->measurementUnit;
        }

        if (!empty($this->customTaxes)) {
            $data['customTaxes'] = $this->customTaxes;
        }

        return $data;
    }

    // Getters
    public function getReference(): ?string { return $this->reference; }
    public function getDescription(): string { return $this->description; }
    public function getQuantity(): float { return $this->quantity; }
    public function getAmount(): float { return $this->amount; }
    public function getDiscount(): float { return $this->discount; }
    public function getMeasurementUnit(): ?string { return $this->measurementUnit; }
    public function getTaxes(): array { return $this->taxes; }
    public function getCustomTaxes(): array { return $this->customTaxes; }
}