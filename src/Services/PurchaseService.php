<?php

namespace DgiCi\FneSdk\Services;

use DgiCi\FneSdk\Http\HttpClient;
use DgiCi\FneSdk\Models\Invoice;
use DgiCi\FneSdk\Models\ApiResponse;
use DgiCi\FneSdk\Validators\InvoiceValidator;
use DgiCi\FneSdk\Utils\Constants;
use DgiCi\FneSdk\Exceptions\ValidationException;

class PurchaseService
{
    private HttpClient $httpClient;
    private InvoiceValidator $validator;

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->validator = new InvoiceValidator();
    }

    /**
     * Certifier un bordereau d'achat de produits agricoles
     */
    public function signPurchaseInvoice(Invoice $invoice): ApiResponse
    {
        // Vérifier que c'est bien une facture d'achat
        if ($invoice->getInvoiceType() !== Constants::INVOICE_TYPE_PURCHASE) {
            throw new ValidationException('Le type de facture doit être "purchase" pour les bordereaux d\'achat');
        }

        // Validation
        $this->validator->validate($invoice);

        // Envoi de la requête
        $response = $this->httpClient->post(
            Constants::ENDPOINT_SIGN_INVOICE,
            $invoice->toArray()
        );

        if (!$response->isSuccess()) {
            $this->handleErrorResponse($response);
        }

        $data = $response->json();
        return new ApiResponse($data, $response->getStatusCode());
    }

    /**
     * Créer un bordereau d'achat de produits agricoles
     */
    public function createPurchaseInvoice(
        string $pointOfSale,
        string $establishment,
        string $supplierName,
        string $supplierPhone,
        string $supplierEmail,
        string $paymentMethod = Constants::PAYMENT_CASH,
        string $template = Constants::TEMPLATE_B2C
    ): Invoice {
        return new Invoice(
            Constants::INVOICE_TYPE_PURCHASE,
            $paymentMethod,
            $template,
            $pointOfSale,
            $establishment,
            $supplierName,
            $supplierPhone,
            $supplierEmail
        );
    }

    /**
     * Créer un bordereau d'achat B2B (fournisseur avec NCC)
     */
    public function createB2BPurchaseInvoice(
        string $pointOfSale,
        string $establishment,
        string $supplierName,
        string $supplierPhone,
        string $supplierEmail,
        string $supplierNcc,
        string $paymentMethod = Constants::PAYMENT_CASH
    ): Invoice {
        $invoice = new Invoice(
            Constants::INVOICE_TYPE_PURCHASE,
            $paymentMethod,
            Constants::TEMPLATE_B2B,
            $pointOfSale,
            $establishment,
            $supplierName,
            $supplierPhone,
            $supplierEmail
        );

        $invoice->setClientNcc($supplierNcc);
        return $invoice;
    }

    /**
     * Créer un bordereau pour coopérative agricole
     */
    public function createCooperativePurchase(
        string $pointOfSale,
        string $establishment,
        string $cooperativeName,
        string $cooperativePhone,
        string $cooperativeEmail,
        string $paymentMethod = Constants::PAYMENT_MOBILE_MONEY
    ): Invoice {
        $invoice = $this->createPurchaseInvoice(
            $pointOfSale,
            $establishment,
            $cooperativeName,
            $cooperativePhone,
            $cooperativeEmail,
            $paymentMethod,
            Constants::TEMPLATE_B2C
        );

        // Ajouter un message personnalisé pour les coopératives
        $invoice->setCommercialMessage('Achat de produits agricoles via coopérative');

        return $invoice;
    }

    private function handleErrorResponse($response): void
    {
        $data = $response->json();

        if ($data && isset($data['message'])) {
            throw new ValidationException($data['message']);
        }

        throw new ValidationException('Erreur lors de la certification du bordereau d\'achat');
    }
}