<?php

namespace DgiCi\FneSdk\Models;

class RefundRequest
{
    private array $items = [];

    public function addItem(string $itemId, float $quantity): self
    {
        $this->items[] = [
            'id' => $itemId,
            'quantity' => $quantity
        ];
        return $this;
    }

    public function toArray(): array
    {
        return ['items' => $this->items];
    }

    public function getItems(): array { return $this->items; }
}