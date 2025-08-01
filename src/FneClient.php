<?php

namespace DgiCi\FneSdk;

use DgiCi\FneSdk\Http\HttpClient;
use DgiCi\FneSdk\Auth\TokenManager;
use DgiCi\FneSdk\Services\InvoiceService;
use DgiCi\FneSdk\Services\RefundService;
use DgiCi\FneSdk\Services\PurchaseService;
use DgiCi\FneSdk\Utils\Constants;
use DgiCi\FneSdk\Exceptions\AuthenticationException;

class FneClient
{
    private HttpClient $httpClient;
    private TokenManager $tokenManager;
    private InvoiceService $invoiceService;
    private RefundService $refundService;
    private PurchaseService $purchaseService;

    private bool $isTestMode;
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'api_key' => null,
            'base_url' => null,
            'test_mode' => true,
            'timeout' => 30,
            'retry_attempts' => 3,
        ], $config);

        $this->isTestMode = $this->config['test_mode'];
        $baseUrl = $this->config['base_url'] ?? $this->getDefaultBaseUrl();

        $this->tokenManager = new TokenManager($this->config['api_key']);
        $this->httpClient = new HttpClient(
            $baseUrl,
            $this->config['api_key'],
            $this->config['timeout'],
            $this->config['retry_attempts']
        );

        $this->initializeServices();
    }

    /**
     * Créer une instance pour l'environnement de test
     */
    public static function test(string $apiKey): self
    {
        return new self([
            'api_key' => $apiKey,
            'test_mode' => true,
        ]);
    }

    /**
     * Créer une instance pour l'environnement de production
     */
    public static function production(string $apiKey, string $baseUrl): self
    {
        return new self([
            'api_key' => $apiKey,
            'base_url' => $baseUrl,
            'test_mode' => false,
        ]);
    }

    /**
     * Définir la clé API
     */
    public function setApiKey(string $apiKey): self
    {
        $this->tokenManager->setApiKey($apiKey);
        $this->httpClient->setApiKey($apiKey);
        return $this;
    }

    /**
     * Vérifier la configuration
     */
    public function validateConfiguration(): void
    {
        $this->tokenManager->validateApiKey();
    }

    /**
     * Obtenir le service de facturation
     */
    public function invoices(): InvoiceService
    {
        return $this->invoiceService;
    }

    /**
     * Obtenir le service d'avoir
     */
    public function refunds(): RefundService
    {
        return $this->refundService;
    }

    /**
     * Obtenir le service de bordereau d'achat
     */
    public function purchases(): PurchaseService
    {
        return $this->purchaseService;
    }

    /**
     * Basculer en mode test
     */
    public function enableTestMode(): self
    {
        $this->isTestMode = true;
        $this->httpClient = new HttpClient(
            Constants::TEST_BASE_URL,
            $this->config['api_key'],
            $this->config['timeout'],
            $this->config['retry_attempts']
        );
        $this->initializeServices();
        return $this;
    }

    /**
     * Basculer en mode production
     */
    public function enableProductionMode(string $productionUrl): self
    {
        $this->isTestMode = false;
        $this->httpClient = new HttpClient(
            $productionUrl,
            $this->config['api_key'],
            $this->config['timeout'],
            $this->config['retry_attempts']
        );
        $this->initializeServices();
        return $this;
    }

    /**
     * Vérifier si on est en mode test
     */
    public function isTestMode(): bool
    {
        return $this->isTestMode;
    }

    /**
     * Obtenir les informations de configuration
     */
    public function getConfig(): array
    {
        return [
            'test_mode' => $this->isTestMode,
            'base_url' => $this->getDefaultBaseUrl(),
            'timeout' => $this->config['timeout'],
            'retry_attempts' => $this->config['retry_attempts'],
            'has_api_key' => $this->tokenManager->hasApiKey(),
        ];
    }

    /**
     * Vider le cache
     */
    public function clearCache(): self
    {
        $this->tokenManager->clearCache();
        return $this;
    }

    private function initializeServices(): void
    {
        $this->invoiceService = new InvoiceService($this->httpClient);
        $this->refundService = new RefundService($this->httpClient);
        $this->purchaseService = new PurchaseService($this->httpClient);
    }

    private function getDefaultBaseUrl(): string
    {
        return $this->isTestMode ? Constants::TEST_BASE_URL : Constants::PROD_BASE_URL;
    }
}