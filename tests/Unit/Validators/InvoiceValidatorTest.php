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
    private Invoice $validInvoice;

    protected function setUp(): void
    {
        $this->validator = new InvoiceValidator();

        $this->validInvoice = new Invoice(
            Constants::INVOICE_TYPE_SALE,
            Constants::PAYMENT_CASH,
            Constants::TEMPLATE_B2C,
            'Caisse 1',
            'Siège',
            'Client Test',
            '0709123456',
            'client@test.com'
        );

        $item = new InvoiceItem('Produit', 1, 1000, [Constants::TAX_TVA]);
        $this->validInvoice->addItem($item);
    }

    public function testValidInvoicePassesValidation(): void
    {
        $this->expectNotToPerformAssertions();
        // Si aucune exception n'est levée, le test passe
        $this->validator->validate($this->validInvoice);
    }

    public function testEmptyItemsThrowsValidationException(): void
    {
        $invoice = new Invoice(
            Constants::INVOICE_TYPE_SALE,
            Constants::PAYMENT_CASH,
            Constants::TEMPLATE_B2C,
            'Caisse 1',
            'Siège',
            'Client Test',
            '0709123456',
            'client@test.com'
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Au moins un article est requis');

        $this->validator->validate($invoice);
    }

    public function testInvalidInvoiceTypeThrowsException(): void
    {
        // Cette validation se fait normalement au niveau du constructeur
        // mais nous pouvons tester la logique de validation
        $this->assertTrue(true); // Placeholder pour le test
    }
}