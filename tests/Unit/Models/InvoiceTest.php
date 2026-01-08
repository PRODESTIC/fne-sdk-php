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
            'Siege Principal',
            'Jean Dupont',
            '0709123456',
            'jean.dupont@email.com'
        );
    }

    public function testInvoiceCreation(): void
    {
        $this->assertEquals(Constants::INVOICE_TYPE_SALE, $this->invoice->getInvoiceType());
        $this->assertEquals(Constants::PAYMENT_CASH, $this->invoice->getPaymentMethod());
        $this->assertEquals(Constants::TEMPLATE_B2C, $this->invoice->getTemplate());
        $this->assertEquals('Caisse 1', $this->invoice->getPointOfSale());
        $this->assertEquals('Siege Principal', $this->invoice->getEstablishment());
        $this->assertEquals('Jean Dupont', $this->invoice->getClientCompanyName());
        $this->assertEquals('0709123456', $this->invoice->getClientPhone());
        $this->assertEquals('jean.dupont@email.com', $this->invoice->getClientEmail());
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

        $this->assertEquals($ncc, $this->invoice->getClientNcc());
    }

    public function testSetClientSellerName(): void
    {
        $sellerName = 'Vendeur Test';
        $this->invoice->setClientSellerName($sellerName);

        $this->assertEquals($sellerName, $this->invoice->getClientSellerName());
    }

    public function testSetCommercialMessage(): void
    {
        $message = 'Merci pour votre achat!';
        $this->invoice->setCommercialMessage($message);

        $this->assertEquals($message, $this->invoice->getCommercialMessage());
    }

    public function testSetFooter(): void
    {
        $footer = 'Conditions generales de vente';
        $this->invoice->setFooter($footer);

        $this->assertEquals($footer, $this->invoice->getFooter());
    }

    public function testSetForeignCurrency(): void
    {
        $this->invoice->setForeignCurrency(Constants::CURRENCY_EUR, 655.957);

        $this->assertEquals(Constants::CURRENCY_EUR, $this->invoice->getForeignCurrency());
        $this->assertEquals(655.957, $this->invoice->getForeignCurrencyRate());
    }

    public function testSetForeignCurrencyWithNewCurrencies(): void
    {
        // Test avec les nouvelles devises ajoutees
        $this->invoice->setForeignCurrency(Constants::CURRENCY_JPY, 0.0058);
        $this->assertEquals(Constants::CURRENCY_JPY, $this->invoice->getForeignCurrency());

        $this->invoice->setForeignCurrency(Constants::CURRENCY_CHF, 700);
        $this->assertEquals(Constants::CURRENCY_CHF, $this->invoice->getForeignCurrency());
    }

    public function testSetDiscount(): void
    {
        $this->invoice->setDiscount(15.5);

        $this->assertEquals(15.5, $this->invoice->getDiscount());
    }

    public function testSetRne(): void
    {
        $rneNumber = '2302695L25000057108';
        $this->invoice->setRne(true, $rneNumber);

        $this->assertTrue($this->invoice->isRne());
        $this->assertEquals($rneNumber, $this->invoice->getRne());
    }

    public function testSetRneFalse(): void
    {
        $this->invoice->setRne(false);

        $this->assertFalse($this->invoice->isRne());
        $this->assertNull($this->invoice->getRne());
    }

    public function testAddCustomTax(): void
    {
        $this->invoice->addCustomTax('DTD', 5);
        $this->invoice->addCustomTax('AIRSI', 2);

        $customTaxes = $this->invoice->getCustomTaxes();
        $this->assertCount(2, $customTaxes);
        $this->assertEquals('DTD', $customTaxes[0]['name']);
        $this->assertEquals(5, $customTaxes[0]['amount']);
        $this->assertEquals('AIRSI', $customTaxes[1]['name']);
        $this->assertEquals(2, $customTaxes[1]['amount']);
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
        $this->assertEquals('Caisse 1', $array['pointOfSale']);
        $this->assertEquals('Siege Principal', $array['establishment']);
        $this->assertEquals('Jean Dupont', $array['clientCompanyName']);
        $this->assertEquals('0709123456', $array['clientPhone']);
        $this->assertEquals('jean.dupont@email.com', $array['clientEmail']);
        $this->assertEquals(10, $array['discount']);
        $this->assertFalse($array['isRne']);
        $this->assertCount(1, $array['items']);
    }

    public function testToArrayWithAllOptionalFields(): void
    {
        $item = new InvoiceItem('Produit', 1, 1000, [Constants::TAX_TVA]);
        $this->invoice->addItem($item);
        $this->invoice->setClientNcc('9500015F');
        $this->invoice->setClientSellerName('Vendeur');
        $this->invoice->setCommercialMessage('Message');
        $this->invoice->setFooter('Footer');
        $this->invoice->setForeignCurrency(Constants::CURRENCY_EUR, 655);
        $this->invoice->setRne(true, 'RNE123');
        $this->invoice->setDiscount(5);
        $this->invoice->addCustomTax('TAX', 3);

        $array = $this->invoice->toArray();

        $this->assertEquals('9500015F', $array['clientNcc']);
        $this->assertEquals('Vendeur', $array['clientSellerName']);
        $this->assertEquals('Message', $array['commercialMessage']);
        $this->assertEquals('Footer', $array['footer']);
        $this->assertEquals(Constants::CURRENCY_EUR, $array['foreignCurrency']);
        $this->assertEquals(655, $array['foreignCurrencyRate']);
        $this->assertTrue($array['isRne']);
        $this->assertEquals('RNE123', $array['rne']);
        $this->assertEquals(5, $array['discount']);
        $this->assertCount(1, $array['customTaxes']);
    }

    public function testToArrayWithoutForeignCurrency(): void
    {
        $item = new InvoiceItem('Produit', 1, 1000, [Constants::TAX_TVA]);
        $this->invoice->addItem($item);

        $array = $this->invoice->toArray();

        $this->assertEquals('', $array['foreignCurrency']);
        $this->assertEquals(0, $array['foreignCurrencyRate']);
    }

    public function testFluentInterface(): void
    {
        $result = $this->invoice
            ->setClientNcc('9500015F')
            ->setClientSellerName('Vendeur')
            ->setCommercialMessage('Message')
            ->setFooter('Footer')
            ->setForeignCurrency(Constants::CURRENCY_EUR, 655)
            ->setRne(true, 'RNE123')
            ->setDiscount(5)
            ->addCustomTax('TAX', 3);

        $this->assertInstanceOf(Invoice::class, $result);
    }

    public function testB2BInvoiceWithNcc(): void
    {
        $b2bInvoice = new Invoice(
            Constants::INVOICE_TYPE_SALE,
            Constants::PAYMENT_TRANSFER,
            Constants::TEMPLATE_B2B,
            'Caisse Pro',
            'Siege',
            'Entreprise XYZ',
            '0102030405',
            'contact@xyz.ci'
        );

        $b2bInvoice->setClientNcc('9502363N');
        $item = new InvoiceItem('Service', 1, 50000, [Constants::TAX_TVA]);
        $b2bInvoice->addItem($item);

        $array = $b2bInvoice->toArray();

        $this->assertEquals(Constants::TEMPLATE_B2B, $array['template']);
        $this->assertEquals('9502363N', $array['clientNcc']);
    }

    public function testB2FInvoiceWithForeignCurrency(): void
    {
        $b2fInvoice = new Invoice(
            Constants::INVOICE_TYPE_SALE,
            Constants::PAYMENT_TRANSFER,
            Constants::TEMPLATE_B2F,
            'Export',
            'Siege',
            'Foreign Company',
            '0102030405',
            'contact@foreign.com'
        );

        $b2fInvoice->setForeignCurrency(Constants::CURRENCY_USD, 600);
        $item = new InvoiceItem('Export Product', 10, 1000, [Constants::TAX_TVAC]);
        $b2fInvoice->addItem($item);

        $array = $b2fInvoice->toArray();

        $this->assertEquals(Constants::TEMPLATE_B2F, $array['template']);
        $this->assertEquals(Constants::CURRENCY_USD, $array['foreignCurrency']);
        $this->assertEquals(600, $array['foreignCurrencyRate']);
    }

    public function testPurchaseInvoice(): void
    {
        $purchaseInvoice = new Invoice(
            Constants::INVOICE_TYPE_PURCHASE,
            Constants::PAYMENT_MOBILE_MONEY,
            Constants::TEMPLATE_B2B,
            'Achat',
            'Cooperative',
            'Fournisseur Agricole',
            '0709876543',
            'fournisseur@coop.ci'
        );

        $item = new InvoiceItem('Cacao Brut', 2000, 2200);
        $purchaseInvoice->addItem($item);

        $this->assertEquals(Constants::INVOICE_TYPE_PURCHASE, $purchaseInvoice->getInvoiceType());
    }
}
