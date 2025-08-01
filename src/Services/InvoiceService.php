<?php

namespace DgiCi\FneSdk\Services;

use DgiCi\FneSdk\Http\HttpClient;
use DgiCi\FneSdk\Models\Invoice;
use DgiCi\FneSdk\Models\ApiResponse;
use DgiCi\FneSdk\Validators\InvoiceValidator;
use DgiCi\FneSdk\Utils\Constants;
use DgiCi\FneSdk\Exceptions\ValidationException;

class InvoiceService
{
    private HttpClient $httpClient;
    private InvoiceValidator $validator;

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->validator = new InvoiceValidator();
    }

    /**
     * Certifier une facture de vente
     */
    public function signInvoice(Invoice $invoice): ApiResponse
    {
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
     * Créer une facture de vente rapide
     */
    public function createSaleInvoice(
        string $pointOfSale,
        string $establishment,
        string $clientName,
        string $clientPhone,
        string $clientEmail,
        string $paymentMethod = Constants::PAYMENT_CASH,
        string $template = Constants::TEMPLATE_B2C
    ): Invoice {
        return new Invoice(
            Constants::INVOICE_TYPE_SALE,
            $paymentMethod,
            $template,
            $pointOfSale,
            $establishment,
            $clientName,
            $clientPhone,
            $clientEmail
        );
    }

    /**
     * Créer une facture B2B
     */
    public function createB2BInvoice(
        string $pointOfSale,
        string $establishment,
        string $clientName,
        string $clientPhone,
        string $clientEmail,
        string $clientNcc,
        string $paymentMethod = Constants::PAYMENT_TRANSFER
    ): Invoice {
        $invoice = new Invoice(
            Constants::INVOICE_TYPE_SALE,
            $paymentMethod,
            Constants::TEMPLATE_B2B,
            $pointOfSale,
            $establishment,
            $clientName,
            $clientPhone,
            $clientEmail
        );

        $invoice->setClientNcc($clientNcc);
        return $invoice;
    }

    /**
     * Créer une facture B2F (international)
     */
    public function createB2FInvoice(
        string $pointOfSale,
        string $establishment,
        string $clientName,
        string $clientPhone,
        string $clientEmail,
        string $foreignCurrency,
        float $exchangeRate,
        string $paymentMethod = Constants::PAYMENT_TRANSFER
    ): Invoice {
        $invoice = new Invoice(
            Constants::INVOICE_TYPE_SALE,
            $paymentMethod,
            Constants::TEMPLATE_B2F,
            $pointOfSale,
            $establishment,
            $clientName,
            $clientPhone,
            $clientEmail
        );

        $invoice->setForeignCurrency($foreignCurrency, $exchangeRate);
        return $invoice;
    }

    /**
     * Créer une facture à partir d'un RNE
     */
    public function createFromRne(
        string $rneNumber,
        string $pointOfSale,
        string $establishment,
        string $clientName,
        string $clientPhone,
        string $clientEmail,
        string $paymentMethod = Constants::PAYMENT_CASH
    ): Invoice {
        $invoice = new Invoice(
            Constants::INVOICE_TYPE_SALE,
            $paymentMethod,
            Constants::TEMPLATE_B2C,
            $pointOfSale,
            $establishment,
            $clientName,
            $clientPhone,
            $clientEmail
        );

        $invoice->setRne(true, $rneNumber);
        return $invoice;
    }

    private function handleErrorResponse($response): void
    {
        $data = $response->json();

        if ($data && isset($data['message'])) {
            throw new ValidationException($data['message']);
        }

        throw new ValidationException('Erreur lors de la certification de la facture');
    }
}