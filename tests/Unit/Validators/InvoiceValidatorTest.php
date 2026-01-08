<?php

namespace DgiCi\FneSdk\Tests\Unit\Validators;

use PHPUnit\Framework\TestCase;
use DgiCi\FneSdk\Validators\InvoiceValidator;
use DgiCi\FneSdk\Models\Invoice;
use DgiCi\FneSdk\Models\InvoiceItem;
use DgiCi\FneSdk\Utils\Constants;
use DgiCi\FneSdk\Exceptions\ValidationException;

class InvoiceValidatorTest extends TestCase
{
    private InvoiceValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new InvoiceValidator();
    }

    private function createValidB2CInvoice(): Invoice
    {
        $invoice = new Invoice(
            Constants::INVOICE_TYPE_SALE,
            Constants::PAYMENT_CASH,
            Constants::TEMPLATE_B2C,
            'Caisse 1',
            'Siege',
            'Client Test',
            '0709123456',
            'client@test.com'
        );

        $item = new InvoiceItem('Produit', 1, 1000, [Constants::TAX_TVA]);
        $invoice->addItem($item);

        return $invoice;
    }

    private function createValidB2BInvoice(): Invoice
    {
        $invoice = new Invoice(
            Constants::INVOICE_TYPE_SALE,
            Constants::PAYMENT_TRANSFER,
            Constants::TEMPLATE_B2B,
            'Caisse Pro',
            'Siege',
            'Entreprise XYZ',
            '0102030405',
            'contact@xyz.ci'
        );

        $invoice->setClientNcc('9502363N');
        $item = new InvoiceItem('Service', 1, 50000, [Constants::TAX_TVA]);
        $invoice->addItem($item);

        return $invoice;
    }

    // ============================================
    // TESTS DE VALIDATION REUSSIE
    // ============================================

    public function testValidB2CInvoicePassesValidation(): void
    {
        $invoice = $this->createValidB2CInvoice();

        $this->expectNotToPerformAssertions();
        $this->validator->validate($invoice);
    }

    public function testValidB2BInvoicePassesValidation(): void
    {
        $invoice = $this->createValidB2BInvoice();

        $this->expectNotToPerformAssertions();
        $this->validator->validate($invoice);
    }

    public function testValidB2FInvoicePassesValidation(): void
    {
        $invoice = new Invoice(
            Constants::INVOICE_TYPE_SALE,
            Constants::PAYMENT_TRANSFER,
            Constants::TEMPLATE_B2F,
            'Export',
            'Siege',
            'Foreign Company',
            '0102030405',
            'contact@foreign.com'
        );

        $invoice->setForeignCurrency(Constants::CURRENCY_EUR, 655);
        $item = new InvoiceItem('Export', 10, 1000, [Constants::TAX_TVAC]);
        $invoice->addItem($item);

        $this->expectNotToPerformAssertions();
        $this->validator->validate($invoice);
    }

    public function testValidPurchaseInvoicePassesValidation(): void
    {
        $invoice = new Invoice(
            Constants::INVOICE_TYPE_PURCHASE,
            Constants::PAYMENT_MOBILE_MONEY,
            Constants::TEMPLATE_B2C,
            'Achat',
            'Cooperative',
            'Fournisseur',
            '0709876543',
            'fournisseur@coop.ci'
        );

        // Les factures d'achat n'ont pas besoin de taxes
        $item = new InvoiceItem('Cacao', 100, 2200);
        $invoice->addItem($item);

        $this->expectNotToPerformAssertions();
        $this->validator->validate($invoice);
    }

    // ============================================
    // TESTS DE VALIDATION DES ARTICLES
    // ============================================

    public function testEmptyItemsThrowsValidationException(): void
    {
        $invoice = new Invoice(
            Constants::INVOICE_TYPE_SALE,
            Constants::PAYMENT_CASH,
            Constants::TEMPLATE_B2C,
            'Caisse 1',
            'Siege',
            'Client Test',
            '0709123456',
            'client@test.com'
        );

        try {
            $this->validator->validate($invoice);
            $this->fail('ValidationException should have been thrown');
        } catch (ValidationException $e) {
            $this->assertTrue($e->hasError('items'));
            $this->assertStringContainsString('Au moins un article', $e->getError('items'));
        }
    }

    public function testItemWithoutTaxesThrowsExceptionForSaleInvoice(): void
    {
        $invoice = new Invoice(
            Constants::INVOICE_TYPE_SALE,
            Constants::PAYMENT_CASH,
            Constants::TEMPLATE_B2C,
            'Caisse 1',
            'Siege',
            'Client',
            '0709123456',
            'client@test.com'
        );

        // Article sans taxes
        $item = new InvoiceItem('Produit', 1, 1000);
        $invoice->addItem($item);

        try {
            $this->validator->validate($invoice);
            $this->fail('ValidationException should have been thrown');
        } catch (ValidationException $e) {
            $this->assertTrue($e->hasError('item_0_taxes'));
            $this->assertStringContainsString('Au moins un type de taxe', $e->getError('item_0_taxes'));
        }
    }

    public function testItemWithInvalidTaxTypeThrowsException(): void
    {
        $invoice = new Invoice(
            Constants::INVOICE_TYPE_SALE,
            Constants::PAYMENT_CASH,
            Constants::TEMPLATE_B2C,
            'Caisse 1',
            'Siege',
            'Client',
            '0709123456',
            'client@test.com'
        );

        $item = new InvoiceItem('Produit', 1, 1000, ['INVALID_TAX']);
        $invoice->addItem($item);

        try {
            $this->validator->validate($invoice);
            $this->fail('ValidationException should have been thrown');
        } catch (ValidationException $e) {
            $this->assertTrue($e->hasError('item_0_tax'));
            $this->assertStringContainsString('Type de taxe invalide', $e->getError('item_0_tax'));
        }
    }

    public function testItemWithDiscountOver100ThrowsException(): void
    {
        $invoice = $this->createValidB2CInvoice();

        // Remplacer l'article par un avec remise > 100%
        $reflection = new \ReflectionClass($invoice);
        $property = $reflection->getProperty('items');
        $property->setAccessible(true);
        $property->setValue($invoice, []);

        $item = new InvoiceItem('Produit', 1, 1000, [Constants::TAX_TVA]);
        $item->setDiscount(150);
        $invoice->addItem($item);

        try {
            $this->validator->validate($invoice);
            $this->fail('ValidationException should have been thrown');
        } catch (ValidationException $e) {
            $this->assertTrue($e->hasError('item_0_discount'));
            $this->assertStringContainsString('remise', $e->getError('item_0_discount'));
        }
    }

    // ============================================
    // TESTS DE VALIDATION B2B - NCC OBLIGATOIRE
    // ============================================

    public function testB2BWithoutNccThrowsException(): void
    {
        $invoice = new Invoice(
            Constants::INVOICE_TYPE_SALE,
            Constants::PAYMENT_TRANSFER,
            Constants::TEMPLATE_B2B,
            'Caisse Pro',
            'Siege',
            'Entreprise XYZ',
            '0102030405',
            'contact@xyz.ci'
        );

        // Pas de NCC defini
        $item = new InvoiceItem('Service', 1, 50000, [Constants::TAX_TVA]);
        $invoice->addItem($item);

        try {
            $this->validator->validate($invoice);
            $this->fail('ValidationException should have been thrown');
        } catch (ValidationException $e) {
            $this->assertTrue($e->hasError('clientNcc'));
            $this->assertStringContainsString('NCC client est obligatoire', $e->getError('clientNcc'));
        }
    }

    public function testB2BWithInvalidNccFormatThrowsException(): void
    {
        $invoice = new Invoice(
            Constants::INVOICE_TYPE_SALE,
            Constants::PAYMENT_TRANSFER,
            Constants::TEMPLATE_B2B,
            'Caisse Pro',
            'Siege',
            'Entreprise XYZ',
            '0102030405',
            'contact@xyz.ci'
        );

        // NCC avec format invalide
        $invoice->setClientNcc('INVALID');
        $item = new InvoiceItem('Service', 1, 50000, [Constants::TAX_TVA]);
        $invoice->addItem($item);

        try {
            $this->validator->validate($invoice);
            $this->fail('ValidationException should have been thrown');
        } catch (ValidationException $e) {
            $this->assertTrue($e->hasError('clientNcc'));
            $this->assertStringContainsString('NCC', $e->getError('clientNcc'));
        }
    }

    // ============================================
    // TESTS DE VALIDATION DEVISE ETRANGERE
    // ============================================

    public function testB2FWithForeignCurrencyButNoRateThrowsException(): void
    {
        $invoice = new Invoice(
            Constants::INVOICE_TYPE_SALE,
            Constants::PAYMENT_TRANSFER,
            Constants::TEMPLATE_B2F,
            'Export',
            'Siege',
            'Foreign Company',
            '0102030405',
            'contact@foreign.com'
        );

        // Devise sans taux de change
        $invoice->setForeignCurrency(Constants::CURRENCY_EUR, 0);
        $item = new InvoiceItem('Export', 10, 1000, [Constants::TAX_TVAC]);
        $invoice->addItem($item);

        try {
            $this->validator->validate($invoice);
            $this->fail('ValidationException should have been thrown');
        } catch (ValidationException $e) {
            $this->assertTrue($e->hasError('foreignCurrencyRate'));
            $this->assertStringContainsString('taux de change', $e->getError('foreignCurrencyRate'));
        }
    }

    public function testValidCurrenciesAreAccepted(): void
    {
        $currencies = [
            Constants::CURRENCY_USD,
            Constants::CURRENCY_EUR,
            Constants::CURRENCY_GBP,
            Constants::CURRENCY_JPY,
            Constants::CURRENCY_CAD,
            Constants::CURRENCY_CHF,
        ];

        foreach ($currencies as $currency) {
            $invoice = new Invoice(
                Constants::INVOICE_TYPE_SALE,
                Constants::PAYMENT_TRANSFER,
                Constants::TEMPLATE_B2F,
                'Export',
                'Siege',
                'Foreign Company',
                '0102030405',
                'contact@foreign.com'
            );

            $invoice->setForeignCurrency($currency, 600);
            $item = new InvoiceItem('Export', 10, 1000, [Constants::TAX_TVAC]);
            $invoice->addItem($item);

            // Ne doit pas lever d'exception
            $this->validator->validate($invoice);
        }

        $this->assertTrue(true);
    }

    // ============================================
    // TESTS DE VALIDATION RNE
    // ============================================

    public function testRneWithoutNumberThrowsException(): void
    {
        $invoice = $this->createValidB2CInvoice();
        $invoice->setRne(true); // isRne = true mais pas de numero

        try {
            $this->validator->validate($invoice);
            $this->fail('ValidationException should have been thrown');
        } catch (ValidationException $e) {
            $this->assertTrue($e->hasError('rne'));
            $this->assertStringContainsString('obligatoire', $e->getError('rne'));
        }
    }

    public function testRneWithNumberPassesValidation(): void
    {
        $invoice = $this->createValidB2CInvoice();
        $invoice->setRne(true, '2302695L25000057108');

        $this->expectNotToPerformAssertions();
        $this->validator->validate($invoice);
    }

    // ============================================
    // TESTS DE VALIDATION REMISE GLOBALE
    // ============================================

    public function testGlobalDiscountOver100ThrowsException(): void
    {
        $invoice = $this->createValidB2CInvoice();
        $invoice->setDiscount(150);

        try {
            $this->validator->validate($invoice);
            $this->fail('ValidationException should have been thrown');
        } catch (ValidationException $e) {
            $this->assertTrue($e->hasError('discount'));
            $this->assertStringContainsString('remise globale', $e->getError('discount'));
        }
    }

    public function testNegativeGlobalDiscountThrowsException(): void
    {
        $invoice = $this->createValidB2CInvoice();
        $invoice->setDiscount(-10);

        try {
            $this->validator->validate($invoice);
            $this->fail('ValidationException should have been thrown');
        } catch (ValidationException $e) {
            $this->assertTrue($e->hasError('discount'));
            $this->assertStringContainsString('remise globale', $e->getError('discount'));
        }
    }

    // ============================================
    // TESTS AVEC TOUTES LES METHODES DE PAIEMENT
    // ============================================

    public function testAllPaymentMethodsAreValid(): void
    {
        $paymentMethods = [
            Constants::PAYMENT_CASH,
            Constants::PAYMENT_CARD,
            Constants::PAYMENT_CHECK,
            Constants::PAYMENT_MOBILE_MONEY,
            Constants::PAYMENT_TRANSFER,
            Constants::PAYMENT_DEFERRED,
        ];

        foreach ($paymentMethods as $method) {
            $invoice = new Invoice(
                Constants::INVOICE_TYPE_SALE,
                $method,
                Constants::TEMPLATE_B2C,
                'Caisse',
                'Siege',
                'Client',
                '0709123456',
                'client@test.com'
            );

            $item = new InvoiceItem('Produit', 1, 1000, [Constants::TAX_TVA]);
            $invoice->addItem($item);

            $this->validator->validate($invoice);
        }

        $this->assertTrue(true);
    }

    // ============================================
    // TESTS AVEC TOUS LES TYPES DE TVA
    // ============================================

    public function testAllTaxTypesAreValid(): void
    {
        $taxTypes = [
            Constants::TAX_TVA,
            Constants::TAX_TVAB,
            Constants::TAX_TVAC,
            Constants::TAX_TVAD,
        ];

        foreach ($taxTypes as $taxType) {
            $invoice = new Invoice(
                Constants::INVOICE_TYPE_SALE,
                Constants::PAYMENT_CASH,
                Constants::TEMPLATE_B2C,
                'Caisse',
                'Siege',
                'Client',
                '0709123456',
                'client@test.com'
            );

            $item = new InvoiceItem('Produit', 1, 1000, [$taxType]);
            $invoice->addItem($item);

            $this->validator->validate($invoice);
        }

        $this->assertTrue(true);
    }
}
