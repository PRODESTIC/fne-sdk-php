<?php

namespace DgiCi\FneSdk\Validators;

use DgiCi\FneSdk\Models\Invoice;
use DgiCi\FneSdk\Models\InvoiceItem;
use DgiCi\FneSdk\Utils\Constants;

/**
 * Validateur pour les factures FNE
 *
 * Valide les données d'une facture selon les spécifications
 * de l'API FNE de la DGI Côte d'Ivoire.
 */
class InvoiceValidator extends BaseValidator
{
    /**
     * Valide une facture complète
     *
     * @param Invoice $invoice La facture à valider
     * @throws \DgiCi\FneSdk\Exceptions\ValidationException Si la validation échoue
     */
    public function validate(Invoice $invoice): void
    {
        $this->errors = [];

        $this->validateInvoiceType($invoice->getInvoiceType());
        $this->validatePaymentMethod($invoice->getPaymentMethod());
        $this->validateTemplate($invoice);
        $this->validateClientData($invoice);
        $this->validateEstablishmentData($invoice);
        $this->validateItems($invoice->getItems(), $invoice->getInvoiceType());
        $this->validateForeignCurrency($invoice);
        $this->validateRne($invoice);
        $this->validateDiscount($invoice->getDiscount());

        $this->throwIfErrors();
    }

    /**
     * Valide le type de facture
     */
    private function validateInvoiceType(string $type): void
    {
        $allowedTypes = [Constants::INVOICE_TYPE_SALE, Constants::INVOICE_TYPE_PURCHASE];
        $this->validateInArray('invoiceType', $type, $allowedTypes, 'Type de facture');
    }

    /**
     * Valide la méthode de paiement
     */
    private function validatePaymentMethod(string $method): void
    {
        $this->validateInArray(
            'paymentMethod',
            $method,
            Constants::ALLOWED_PAYMENT_METHODS,
            'Méthode de paiement'
        );
    }

    /**
     * Valide le template et ses contraintes spécifiques
     */
    private function validateTemplate(Invoice $invoice): void
    {
        $template = $invoice->getTemplate();

        $this->validateInArray(
            'template',
            $template,
            Constants::ALLOWED_TEMPLATES,
            'Type de facturation'
        );

        // Validation spécifique B2B : NCC client obligatoire
        if ($template === Constants::TEMPLATE_B2B) {
            $clientNcc = $invoice->getClientNcc();
            if (empty($clientNcc)) {
                $this->addError('clientNcc', 'Le NCC client est obligatoire pour les factures B2B');
            } else {
                $this->validateNcc('clientNcc', $clientNcc);
            }
        }
    }

    /**
     * Valide les données client
     */
    private function validateClientData(Invoice $invoice): void
    {
        $this->validateRequired('clientCompanyName', $invoice->getClientCompanyName(), 'Nom du client');
        $this->validateRequired('clientPhone', $invoice->getClientPhone(), 'Téléphone client');
        $this->validateRequired('clientEmail', $invoice->getClientEmail(), 'Email client');

        // Validation du téléphone
        $phone = $invoice->getClientPhone();
        if (!empty($phone)) {
            $this->validatePhone('clientPhone', $phone);
        }

        // Validation de l'email
        $email = $invoice->getClientEmail();
        if (!empty($email)) {
            $this->validateEmail('clientEmail', $email);
        }
    }

    /**
     * Valide les données d'établissement
     */
    private function validateEstablishmentData(Invoice $invoice): void
    {
        $this->validateRequired('pointOfSale', $invoice->getPointOfSale(), 'Point de vente');
        $this->validateRequired('establishment', $invoice->getEstablishment(), 'Établissement');
    }

    /**
     * Valide les articles de la facture
     */
    private function validateItems(array $items, string $invoiceType): void
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

            $this->validateItem($item, $index, $invoiceType);
        }
    }

    /**
     * Valide un article individuel
     */
    private function validateItem(InvoiceItem $item, int $index, string $invoiceType): void
    {
        $prefix = "item_{$index}";

        $this->validateRequired("{$prefix}_description", $item->getDescription(), 'Description');
        $this->validatePositiveNumber("{$prefix}_quantity", $item->getQuantity(), 'Quantité');
        $this->validatePositiveNumber("{$prefix}_amount", $item->getAmount(), 'Prix unitaire');

        // Validation de la remise sur article
        $discount = $item->getDiscount();
        if ($discount < 0) {
            $this->addError("{$prefix}_discount", 'La remise ne peut pas être négative');
        } elseif ($discount > 100) {
            $this->addError("{$prefix}_discount", 'La remise ne peut pas dépasser 100%');
        }

        // Validation des taxes (obligatoire pour les factures de vente uniquement)
        if ($invoiceType === Constants::INVOICE_TYPE_SALE) {
            $taxes = $item->getTaxes();
            if (empty($taxes)) {
                $this->addError("{$prefix}_taxes", 'Au moins un type de taxe est requis pour les factures de vente');
            } else {
                foreach ($taxes as $tax) {
                    if (!in_array($tax, Constants::ALLOWED_TAX_TYPES)) {
                        $this->addError("{$prefix}_tax", "Type de taxe invalide: {$tax}. Valeurs autorisées: TVA, TVAB, TVAC, TVAD");
                    }
                }
            }
        }

        // Validation des taxes personnalisées
        foreach ($item->getCustomTaxes() as $taxIndex => $customTax) {
            if (empty($customTax['name'])) {
                $this->addError("{$prefix}_customTax_{$taxIndex}_name", 'Le nom de la taxe personnalisée est requis');
            }
            if (!isset($customTax['amount']) || !is_numeric($customTax['amount']) || $customTax['amount'] < 0) {
                $this->addError("{$prefix}_customTax_{$taxIndex}_amount", 'Le montant de la taxe doit être un nombre positif');
            }
        }
    }

    /**
     * Valide la devise étrangère et le taux de change
     */
    private function validateForeignCurrency(Invoice $invoice): void
    {
        $currency = $invoice->getForeignCurrency();
        $rate = $invoice->getForeignCurrencyRate();
        $template = $invoice->getTemplate();

        if (!empty($currency)) {
            // Validation de la devise
            $this->validateInArray(
                'foreignCurrency',
                $currency,
                Constants::ALLOWED_CURRENCIES,
                'Devise étrangère'
            );

            // Pour B2F avec devise étrangère, le taux est obligatoire et doit être > 0
            if ($template === Constants::TEMPLATE_B2F && $rate <= 0) {
                $this->addError(
                    'foreignCurrencyRate',
                    'Le taux de change est obligatoire et doit être supérieur à 0 pour les transactions B2F avec devise étrangère'
                );
            }

            // Si une devise est spécifiée, le taux doit être positif
            if ($rate < 0) {
                $this->addError('foreignCurrencyRate', 'Le taux de change ne peut pas être négatif');
            }
        }
    }

    /**
     * Valide les données RNE (Reçu Normalisé Électronique)
     */
    private function validateRne(Invoice $invoice): void
    {
        if ($invoice->isRne()) {
            $rne = $invoice->getRne();
            if (empty($rne)) {
                $this->addError('rne', 'Le numéro du reçu est obligatoire lorsque isRne est true');
            }
        }
    }

    /**
     * Valide la remise globale sur la facture
     */
    private function validateDiscount(float $discount): void
    {
        if ($discount < 0) {
            $this->addError('discount', 'La remise globale ne peut pas être négative');
        } elseif ($discount > 100) {
            $this->addError('discount', 'La remise globale ne peut pas dépasser 100%');
        }
    }
}
