<?php

namespace DgiCi\FneSdk\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use DgiCi\FneSdk\Models\InvoiceItem;
use DgiCi\FneSdk\Utils\Constants;

class InvoiceItemTest extends TestCase
{
    public function testItemCreation(): void
    {
        $item = new InvoiceItem('Ordinateur portable', 1, 500000, [Constants::TAX_TVA]);

        $this->assertEquals('Ordinateur portable', $item->getDescription());
        $this->assertEquals(1, $item->getQuantity());
        $this->assertEquals(500000, $item->getAmount());
        $this->assertEquals([Constants::TAX_TVA], $item->getTaxes());
    }

    public function testSetOptionalFields(): void
    {
        $item = new InvoiceItem('Sac de riz', 10, 25000, [Constants::TAX_TVA]);
        $item->setReference('REF001')
            ->setDiscount(5)
            ->setMeasurementUnit('sac');

        $this->assertEquals('REF001', $item->getReference());
        $this->assertEquals(5, $item->getDiscount());
        $this->assertEquals('sac', $item->getMeasurementUnit());
    }

    public function testAddCustomTax(): void
    {
        $item = new InvoiceItem('Produit test', 1, 1000, [Constants::TAX_TVA]);
        $item->addCustomTax('GRA', 3);
        $item->addCustomTax('AIRSI', 2);

        $customTaxes = $item->getCustomTaxes();
        $this->assertCount(2, $customTaxes);
        $this->assertEquals('GRA', $customTaxes[0]['name']);
        $this->assertEquals(3, $customTaxes[0]['amount']);
    }

    public function testToArray(): void
    {
        $item = new InvoiceItem('Test Product', 2, 15000, [Constants::TAX_TVA]);
        $item->setReference('TEST001')
            ->setDiscount(10)
            ->setMeasurementUnit('pcs')
            ->addCustomTax('DTD', 5);

        $array = $item->toArray();

        $this->assertEquals('Test Product', $array['description']);
        $this->assertEquals(2, $array['quantity']);
        $this->assertEquals(15000, $array['amount']);
        $this->assertEquals([Constants::TAX_TVA], $array['taxes']);
        $this->assertEquals('TEST001', $array['reference']);
        $this->assertEquals(10, $array['discount']);
        $this->assertEquals('pcs', $array['measurementUnit']);
        $this->assertCount(1, $array['customTaxes']);
    }
}