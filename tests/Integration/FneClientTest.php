<?php

namespace DgiCi\FneSdk\Tests\Integration;

use PHPUnit\Framework\TestCase;
use DgiCi\FneSdk\FneClient;
use DgiCi\FneSdk\Models\InvoiceItem;
use DgiCi\FneSdk\Utils\Constants;
use DgiCi\FneSdk\Exceptions\AuthenticationException;

class FneClientTest extends TestCase
{
    private string $testApiKey = 'test_api_key_12345678901234567890';

    public function testClientCreationInTestMode(): void
    {
        $client = FneClient::test($this->testApiKey);

        $this->assertTrue($client->isTestMode());

        $config = $client->getConfig();
        $this->assertTrue($config['test_mode']);
        $this->assertTrue($config['has_api_key']);
    }

    public function testClientCreationInProductionMode(): void
    {
        $productionUrl = 'https://production.fne.dgi.gouv.ci/ws';
        $client = FneClient::production($this->testApiKey, $productionUrl);

        $this->assertFalse($client->isTestMode());
    }

    public function testClientWithoutApiKeyThrowsException(): void
    {
        $client = new FneClient();

        $this->expectException(AuthenticationException::class);
        $client->validateConfiguration();
    }

    public function testServiceAccess(): void
    {
        $client = FneClient::test($this->testApiKey);

        $this->assertInstanceOf(\DgiCi\FneSdk\Services\InvoiceService::class, $client->invoices());
        $this->assertInstanceOf(\DgiCi\FneSdk\Services\RefundService::class, $client->refunds());
        $this->assertInstanceOf(\DgiCi\FneSdk\Services\PurchaseService::class, $client->purchases());
    }

    public function testModeSwitch(): void
    {
        $client = FneClient::test($this->testApiKey);
        $this->assertTrue($client->isTestMode());

        $productionUrl = 'https://production.fne.dgi.gouv.ci/ws';
        $client->enableProductionMode($productionUrl);
        $this->assertFalse($client->isTestMode());

        $client->enableTestMode();
        $this->assertTrue($client->isTestMode());
    }
}