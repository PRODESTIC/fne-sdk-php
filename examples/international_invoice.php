<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DgiCi\FneSdk\FneClient;
use DgiCi\FneSdk\Models\InvoiceItem;
use DgiCi\FneSdk\Utils\Constants;

$apiKey = 'votre_cle_api_ici';

try {
    $client = FneClient::test($apiKey);

    // Facture internationale (B2F)
    $invoice = $client->invoices()->createB2FInvoice(
        pointOfSale: 'Export Department',
        establishment: 'Headquarters',
        clientName: 'ACME Corporation',
        clientPhone: '+1234567890',
        clientEmail: 'orders@acme.com',
        foreignCurrency: Constants::CURRENCY_USD,
        exchangeRate: 0.0015, // 1 FCFA = 0.0015 USD
        paymentMethod: Constants::PAYMENT_TRANSFER
    );

    // Produits d'exportation
    $product = new InvoiceItem(
        description: 'Cacao premium grade A',
        quantity: 1000,
        amount: 2200, // Prix en FCFA
        taxes: [Constants::TAX_TVAC] // Exonéré export
    );
    $product->setReference('CACAO001')
        ->setMeasurementUnit('kg');

    $invoice->addItem($product)
        ->setCommercialMessage('Premium Ivorian cocoa for export')
        ->setFooter('FOB Abidjan - Payment by L/C');

    $response = $client->invoices()->signInvoice($invoice);

    echo "✅ Facture export créée!\n";
    echo "Référence: " . $response->getReference() . "\n";
    echo "Montant: " . number_format($response->getInvoice()['amount'] ?? 0) . " FCFA\n";

    // Calculer l'équivalent en devise étrangère
    $amountFcfa = $response->getInvoice()['amount'] ?? 0;
    $amountUsd = $amountFcfa * 0.0015;
    echo "Équivalent: $" . number_format($amountUsd, 2) . " USD\n";

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}