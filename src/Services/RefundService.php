<?php

namespace DgiCi\FneSdk\Services;

use DgiCi\FneSdk\Http\HttpClient;
use DgiCi\FneSdk\Models\RefundRequest;
use DgiCi\FneSdk\Models\ApiResponse;
use DgiCi\FneSdk\Utils\Constants;
use DgiCi\FneSdk\Exceptions\ValidationException;
use DgiCi\FneSdk\Exceptions\ApiException;

class RefundService
{
    private HttpClient $httpClient;

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Créer une facture d'avoir
     */
    public function createRefund(string $originalInvoiceId, RefundRequest $refundRequest): ApiResponse
    {
        $this->validateRefundRequest($refundRequest);

        $endpoint = str_replace('{id}', $originalInvoiceId, Constants::ENDPOINT_REFUND_INVOICE);

        $response = $this->httpClient->post($endpoint, $refundRequest->toArray());

        if (!$response->isSuccess()) {
            $this->handleErrorResponse($response);
        }

        $data = $response->json();
        return new ApiResponse($data, $response->getStatusCode());
    }

    /**
     * Créer une demande d'avoir rapide
     */
    public function createRefundRequest(): RefundRequest
    {
        return new RefundRequest();
    }

    /**
     * Créer un avoir complet (tous les articles)
     */
    public function createFullRefund(string $originalInvoiceId, array $originalInvoiceItems): ApiResponse
    {
        $refundRequest = new RefundRequest();

        foreach ($originalInvoiceItems as $item) {
            if (isset($item['id']) && isset($item['quantity'])) {
                $refundRequest->addItem($item['id'], $item['quantity']);
            }
        }

        return $this->createRefund($originalInvoiceId, $refundRequest);
    }

    /**
     * Créer un avoir partiel
     */
    public function createPartialRefund(string $originalInvoiceId, array $itemsToRefund): ApiResponse
    {
        $refundRequest = new RefundRequest();

        foreach ($itemsToRefund as $itemId => $quantity) {
            $refundRequest->addItem($itemId, $quantity);
        }

        return $this->createRefund($originalInvoiceId, $refundRequest);
    }

    private function validateRefundRequest(RefundRequest $request): void
    {
        $items = $request->getItems();

        if (empty($items)) {
            throw new ValidationException('Au moins un article doit être spécifié pour l\'avoir');
        }

        foreach ($items as $index => $item) {
            if (empty($item['id'])) {
                throw new ValidationException("L'ID de l'article {$index} est requis");
            }

            if (!isset($item['quantity']) || $item['quantity'] <= 0) {
                throw new ValidationException("La quantité de l'article {$index} doit être positive");
            }
        }
    }

    private function handleErrorResponse($response): void
    {
        $data = $response->json();
        $statusCode = $response->getStatusCode();

        switch ($statusCode) {
            case 401:
                throw ApiException::fromResponse($data ?: ['message' => 'Non autorisé'], $statusCode);
            case 500:
                throw ApiException::internalServerError($data['message'] ?? 'Erreur serveur');
            default:
                if ($data && isset($data['message'])) {
                    throw ApiException::fromResponse($data, $statusCode);
                }
                throw new ValidationException('Erreur lors de la création de l\'avoir');
        }
    }
}