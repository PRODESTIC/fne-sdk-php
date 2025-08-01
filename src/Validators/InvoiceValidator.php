<?php

namespace DgiCi\FneSdk\Validators;

use DgiCi\FneSdk\Models\Invoice;
use DgiCi\FneSdk\Models\InvoiceItem;
use DgiCi\FneSdk\Utils\Constants;

class InvoiceValidator extends BaseValidator
{
    public function validate(Invoice $invoice): void
    {
        $this->errors = []; // Reset errors

        $this->validateInvoiceType($invoice->getInvoiceType());
        $this->validatePaymentMethod($invoice);
        $this->validateTemplate($invoice);
        $this->validateClientData($invoice);
        $this->validateEstablishmentData($invoice);
        $this->validateItems($invoice->getItems());
        $this->validateForeignCurrency($invoice);
        $this->validateRne($invoice);

        $this->throwIfErrors();
    }

    private function validateInvoiceType(string $type): void
    {
        $allowedTypes = [Constants::INVOICE_TYPE_SALE, Constants::INVOICE_TYPE_PURCHASE];
        $this->validateInArray('invoiceType', $type, $allowedTypes, 'Type de facture');
    }

    private function validatePaymentMethod(Invoice $invoice): void
    {
        $allowedMethods = [
            Constants::PAYMENT_CASH,
            Constants::PAYMENT_CARD,
            Constants::PAYMENT_CHECK,
            Constants::PAYMENT_MOBILE_MONEY,
            Constants::PAYMENT_TRANSFER,
            Constants::PAYMENT_DEFERRED
        ];

        // Note: On devrait avoir une méthode getPaymentMethod() dans Invoice
        // Pour l'instant, on assume qu'on peut accéder à cette propriété
    }

    private function validateTemplate(Invoice $invoice): void
    {
        $template = $invoice->getTemplate();
        $allowedTemplates = [
            Constants::TEMPLATE_B2B,
            Constants::TEMPLATE_B2C,
            Constants::TEMPLATE_B2F,
            Constants::TEMPLATE_B2G
        ];

        $this->validateInArray('template', $template, $allowedTemplates, 'Type de facturation');

        // Validation spécifique B2B
        if ($template === Constants::TEMPLATE_B2B) {
            // Le NCC client est obligatoire pour B2B
            // Note: Il faudrait ajouter une méthode getClientNcc() à Invoice
        }
    }

    private function validateClientData(Invoice $invoice): void
    {
        // Note: Il faudrait ajouter les getters correspondants à Invoice
        // $this->validateRequired('clientCompanyName', $invoice->getClientCompanyName(), 'Nom du client');
        // $this->validateRequired('clientPhone', $invoice->getClientPhone(), 'Téléphone client');
        // $this->validateRequired('clientEmail', $invoice->getClientEmail(), 'Email client');

        // if (!empty($invoice->getClientPhone())) {
        //     $this->validatePhone('clientPhone', $invoice->getClientPhone());
        // }

        // if (!empty($invoice->getClientEmail())) {
        //     $this->validateEmail('clientEmail', $invoice->getClientEmail());
        // }

        // if ($invoice->getTemplate() === Constants::TEMPLATE_B2B && !empty($invoice->getClientNcc())) {
        //     $this->validateNcc('clientNcc', $invoice->getClientNcc());
        // }
    }

    private function validateEstablishmentData(Invoice $invoice): void
    {
        // $this->validateRequired('pointOfSale', $invoice->getPointOfSale(), 'Point de vente');
        // $this->validateRequired('establishment', $invoice->getEstablishment(), 'Établissement');
    }

    private function validateItems(array $items): void
    {
        if (empty($items)) {
            $this->addError('items', 'Au moins un article est requis');
            return;
        }

        foreach ($items as $index => $item) {
            if (!$item instanceof InvoiceItem) {
                $this->addError("items.{$index}", 'Article invalide');
                continue;
            }

            $this->validateItem($item, $index);
        }
    }

    private function validateItem(InvoiceItem $item, int $index): void
    {
        $prefix = "items.{$index}";

        $this->validateRequired("{$prefix}.description", $item->getDescription(), 'Description');
        $this->validatePositiveNumber("{$prefix}.quantity", $item->getQuantity(), 'Quantité');
        $this->validatePositiveNumber("{$prefix}.amount", $item->getAmount(), 'Prix unitaire');

        if ($item->getDiscount() > 100) {
            $this->addError("{$prefix}.discount", 'La remise ne peut pas dépasser 100%');
        }

        // Validation des taxes
        $taxes = $item->getTaxes();
        if (empty($taxes)) {
            $this->addError("{$prefix}.taxes", 'Au moins un type de taxe est requis');
        } else {
            $allowedTaxes = [Constants::TAX_TVA, Constants::TAX_TVAB, Constants::TAX_TVAC, Constants::TAX_TVAD];
            foreach ($taxes as $tax) {
                if (!in_array($tax, $allowedTaxes)) {
                    $this->addError("{$prefix}.taxes", "Type de taxe invalide: {$tax}");
                }
            }
        }

        // Validation des taxes personnalisées
        foreach ($item->getCustomTaxes() as $taxIndex => $customTax) {
            if (empty($customTax['name'])) {
                $this->addError("{$prefix}.customTaxes.{$taxIndex}.name", 'Le nom de la taxe personnalisée est requis');
            }
            if (!isset($customTax['amount']) || !is_numeric($customTax['amount']) || $customTax['amount'] < 0) {
                $this->addError("{$prefix}.customTaxes.{$taxIndex}.amount", 'Le montant de la taxe doit être un nombre positif');
            }
        }
    }

    private function validateForeignCurrency(Invoice $invoice): void
    {
        // Note: Il faudrait ajouter les getters correspondants
        // $currency = $invoice->getForeignCurrency();
        // $rate = $invoice->getForeignCurrencyRate();

        // if (!empty($currency)) {
        //     $allowedCurrencies = [
        //         Constants::CURRENCY_XOF,
        //         Constants::CURRENCY_USD,
        //         Constants::CURRENCY_EUR,
        //         Constants::CURRENCY_GBP
        //     ];
        //     $this->validateInArray('foreignCurrency', $currency, $allowedCurrencies, 'Devise étrangère');

        //     if ($invoice->getTemplate() === Constants::TEMPLATE_B2F && $rate <= 0) {
        //         $this->addError('foreignCurrencyRate', 'Le taux de change est obligatoire pour les transactions B2F');
        //     }
        // }
    }

    private function validateRne(Invoice $invoice): void
    {
        // Note: Il faudrait ajouter les getters correspondants
        // if ($invoice->isRne() && empty($invoice->getRne())) {
        //     $this->addError('rne', 'Le numéro du reçu est obligatoire si isRne est true');
        // }
    }
}