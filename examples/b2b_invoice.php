<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DgiCi\FneSdk\FneClient;
use DgiCi\FneSdk\Models\InvoiceItem;
use DgiCi\FneSdk\Utils\Constants;
use DgiCi\FneSdk\Exceptions\FneException;

$apiKey = 'votre_cle_api_ici';

try {
    $client = FneClient::test($apiKey);

    // Créer une facture B2B
    $invoice = $client->invoices()->createB2BInvoice(
        pointOfSale: 'Service Commercial',
        establishment: 'Siège Social',
        clientName: 'KPMG CÔTE D\'IVOIRE',
        clientPhone: '0709080765',
        clientEmail: 'info@kpmg.ci',
        clientNcc: '9502363N', // NCC obligatoire pour B2B
        paymentMethod: Constants::PAYMENT_TRANSFER
    );

    // Services de conseil
    $service1 = new InvoiceItem(
        description: 'Audit comptable et financier',
        quantity: 1,
        amount: 2500000,
        taxes: [Constants::TAX_TVA]
    );
    $service1->setReference('AUD001')
        ->setMeasurementUnit('forfait');

    $service2 = new InvoiceItem(
        description: 'Formation comptabilité OHADA',
        quantity: 3,
        amount: 150000,
        taxes: [Constants::TAX_TVA]
    );
    $service2->setReference('FORM001')
        ->setMeasurementUnit('session')
        ->setDiscount(10); // 10% de remise sur cet article

    $invoice->addItem($service1)
        ->addItem($service2)
        ->setCommercialMessage('Merci de votre confiance')
        ->setFooter('Paiement sous 30 jours')
        ->addCustomTax('Retenue à la source', 5);

    $response = $client->invoices()->signInvoice($invoice);

    echo "✅ Facture B2B créée avec succès!\n";
    echo "Référence: " . $response->getReference() . "\n";
    echo "Montant TTC calculé: " . number_format($response->getInvoice()['amount'] ?? 0) . " FCFA\n";

} catch (FneException $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}