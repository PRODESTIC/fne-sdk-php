<?php

namespace DgiCi\FneSdk\Models;

class ApiResponse
{
    private string $ncc;
    private string $reference;
    private string $token;
    private bool $warning;
    private int $balanceSticker;
    private array $invoice;
    private int $statusCode;

    public function __construct(array $data, int $statusCode = 200)
    {
        $this->ncc = $data['ncc'] ?? '';
        $this->reference = $data['reference'] ?? '';
        $this->token = $data['token'] ?? '';
        $this->warning = $data['warning'] ?? false;
        $this->balanceSticker = $data['balance_sticker'] ?? 0;
        $this->invoice = $data['invoice'] ?? [];
        $this->statusCode = $statusCode;
    }

    public function getNcc(): string { return $this->ncc; }
    public function getReference(): string { return $this->reference; }
    public function getToken(): string { return $this->token; }
    public function getQrCodeUrl(): string { return $this->token; }
    public function hasWarning(): bool { return $this->warning; }
    public function getBalanceSticker(): int { return $this->balanceSticker; }
    public function getInvoice(): array { return $this->invoice; }
    public function getStatusCode(): int { return $this->statusCode; }

    public function isSuccess(): bool
    {
        return in_array($this->statusCode, [200, 201]);
    }
}