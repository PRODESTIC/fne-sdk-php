<?php

namespace DgiCi\FneSdk\Models;

use DgiCi\FneSdk\Utils\Constants;

class Invoice
{
    private string $invoiceType;
    private string $paymentMethod;
    private string $template;
    private string $pointOfSale;
    private string $establishment;
    private string $clientCompanyName;
    private string $clientPhone;
    private string $clientEmail;
    private array $items = [];

    // Champs optionnels
    private ?string $clientNcc = null;
    private ?string $clientSellerName = null;
    private ?string $commercialMessage = null;
    private ?string $footer = null;
    private ?string $foreignCurrency = null;
    private float $foreignCurrencyRate = 0;
    private bool $isRne = false;
    private ?string $rne = null;
    private float $discount = 0;
    private array $customTaxes = [];

    public function __construct(
        string $invoiceType,
        string $paymentMethod,
        string $template,
        string $pointOfSale,
        string $establishment,
        string $clientCompanyName,
        string $clientPhone,
        string $clientEmail
    ) {
        $this->invoiceType = $invoiceType;
        $this->paymentMethod = $paymentMethod;
        $this->template = $template;
        $this->pointOfSale = $pointOfSale;
        $this->establishment = $establishment;
        $this->clientCompanyName = $clientCompanyName;
        $this->clientPhone = $clientPhone;
        $this->clientEmail = $clientEmail;
    }

    public function setClientNcc(?string $ncc): self
    {
        $this->clientNcc = $ncc;
        return $this;
    }

    public function setClientSellerName(?string $name): self
    {
        $this->clientSellerName = $name;
        return $this;
    }

    public function setCommercialMessage(?string $message): self
    {
        $this->commercialMessage = $message;
        return $this;
    }

    public function setFooter(?string $footer): self
    {
        $this->footer = $footer;
        return $this;
    }

    public function setForeignCurrency(?string $currency, float $rate = 0): self
    {
        $this->foreignCurrency = $currency;
        $this->foreignCurrencyRate = $rate;
        return $this;
    }

    public function setRne(bool $isRne, ?string $rne = null): self
    {
        $this->isRne = $isRne;
        $this->rne = $rne;
        return $this;
    }

    public function setDiscount(float $discount): self
    {
        $this->discount = $discount;
        return $this;
    }

    public function addItem(InvoiceItem $item): self
    {
        $this->items[] = $item;
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
            'invoiceType' => $this->invoiceType,
            'paymentMethod' => $this->paymentMethod,
            'template' => $this->template,
            'pointOfSale' => $this->pointOfSale,
            'establishment' => $this->establishment,
            'clientCompanyName' => $this->clientCompanyName,
            'clientPhone' => $this->clientPhone,
            'clientEmail' => $this->clientEmail,
            'isRne' => $this->isRne,
            'items' => array_map(fn($item) => $item->toArray(), $this->items)
        ];

        // Champs conditionnels
        if ($this->clientNcc !== null) {
            $data['clientNcc'] = $this->clientNcc;
        }

        if ($this->clientSellerName !== null) {
            $data['clientSellerName'] = $this->clientSellerName;
        }

        if ($this->commercialMessage !== null) {
            $data['commercialMessage'] = $this->commercialMessage;
        }

        if ($this->footer !== null) {
            $data['footer'] = $this->footer;
        }

        if ($this->foreignCurrency !== null) {
            $data['foreignCurrency'] = $this->foreignCurrency;
            $data['foreignCurrencyRate'] = $this->foreignCurrencyRate;
        } else {
            $data['foreignCurrency'] = '';
            $data['foreignCurrencyRate'] = 0;
        }

        if ($this->isRne && $this->rne !== null) {
            $data['rne'] = $this->rne;
        }

        if ($this->discount > 0) {
            $data['discount'] = $this->discount;
        }

        if (!empty($this->customTaxes)) {
            $data['customTaxes'] = $this->customTaxes;
        }

        return $data;
    }

    // Getters
    public function getInvoiceType(): string
    {
        return $this->invoiceType;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getPointOfSale(): string
    {
        return $this->pointOfSale;
    }

    public function getEstablishment(): string
    {
        return $this->establishment;
    }

    public function getClientCompanyName(): string
    {
        return $this->clientCompanyName;
    }

    public function getClientPhone(): string
    {
        return $this->clientPhone;
    }

    public function getClientEmail(): string
    {
        return $this->clientEmail;
    }

    public function getClientNcc(): ?string
    {
        return $this->clientNcc;
    }

    public function getClientSellerName(): ?string
    {
        return $this->clientSellerName;
    }

    public function getCommercialMessage(): ?string
    {
        return $this->commercialMessage;
    }

    public function getFooter(): ?string
    {
        return $this->footer;
    }

    public function getForeignCurrency(): ?string
    {
        return $this->foreignCurrency;
    }

    public function getForeignCurrencyRate(): float
    {
        return $this->foreignCurrencyRate;
    }

    public function isRne(): bool
    {
        return $this->isRne;
    }

    public function getRne(): ?string
    {
        return $this->rne;
    }

    public function getDiscount(): float
    {
        return $this->discount;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getCustomTaxes(): array
    {
        return $this->customTaxes;
    }
}