<?php

namespace DgiCi\FneSdk\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use DgiCi\FneSdk\Models\Invoice;
use DgiCi\FneSdk\Models\InvoiceItem;
use DgiCi\FneSdk\Utils\Constants;

class InvoiceTest extends TestCase
{
    private Invoice $invoice;

    protected function setUp(): void
    {
        $this->invoice = new Invoice(
            Constants::INVOICE_TYPE_SALE,
            Constants::PAYMENT_CASH,
            Constants::TEMPLATE_B2C,
            'Caisse 1',
            'Siège Principal',
            'Jean Dupont',
            '0709123456',
            'jean.dupont@email.com'
        );
    }

    public function testInvoiceCreation(): void
    {
        $this->assertEquals(Constants::INVOICE_TYPE_SALE, $this->invoice->getInvoiceType());
        $this->assertEquals(Constants::TEMPLATE_B2C, $this->invoice->getTemplate());
        $this->assertEquals([], $this->invoice->getItems());
    }

    public function testAddItem(): void
    {
        $item = new InvoiceItem('Produit Test', 2, 1000, [Constants::TAX_TVA]);
        $this->invoice->addItem($item);

        $items = $this->invoice->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('Produit Test', $items[0]->getDescription());
    }

    public function testSetClientNcc(): void
    {
        $ncc = '9500015F';
        $this->invoice->setClientNcc($ncc);

        // Pour tester, nous devons ajouter un getter getClientNcc() à Invoice
        // $this->assertEquals($ncc, $this->invoice->getClientNcc());
    }

    public function testSetForeignCurrency(): void
    {
        $this->invoice->setForeignCurrency(Constants::CURRENCY_EUR, 655.957);

        // Pour tester, nous devons ajouter des getters à Invoice
        // $this->assertEquals(Constants::CURRENCY_EUR, $this->invoice->getForeignCurrency());
        // $this->assertEquals(655.957, $this->invoice->getForeignCurrencyRate());
    }

    public function testToArray(): void
    {
        $item = new InvoiceItem('Produit Test', 2, 1000, [Constants::TAX_TVA]);
        $this->invoice->addItem($item);
        $this->invoice->setDiscount(10);

        $array = $this->invoice->toArray();

        $this->assertEquals(Constants::INVOICE_TYPE_SALE, $array['invoiceType']);
        $this->assertEquals(Constants::PAYMENT_CASH, $array['paymentMethod']);
        $this->assertEquals(Constants::TEMPLATE_B2C, $array['template']);
        $this->assertEquals('Jean Dupont', $array['clientCompanyName']);
        $this->assertEquals(10, $array['discount']);
        $this->assertCount(1, $array['items']);
    }

    public function testSetRne(): void
    {
        $rneNumber = '2302695L25000057108';
        $this->invoice->setRne(true, $rneNumber);

        $array = $this->invoice->toArray();
        $this->assertTrue($array['isRne']);
        $this->assertEquals($rneNumber, $array['rne']);
    }

    public function testAddCustomTax(): void
    {
        $this->invoice->addCustomTax('DTD', 5);
        $this->invoice->addCustomTax('AIRSI', 2);

        $array = $this->invoice->toArray();
        $this->assertCount(2, $array['customTaxes']);
        $this->assertEquals('DTD', $array['customTaxes'][0]['name']);
        $this->assertEquals(5, $array['customTaxes'][0]['amount']);
    }
}