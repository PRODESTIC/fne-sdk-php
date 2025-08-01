<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DgiCi\FneSdk\FneClient;
use DgiCi\FneSdk\Models\InvoiceItem;
use DgiCi\FneSdk\Utils\Constants;
use DgiCi\FneSdk\Exceptions\FneException;

// Configuration
$apiKey = 'votre_cle_api_ici';

try {
    // Créer le client en mode test
    $client = FneClient::test($apiKey);

    // Vérifier la configuration
    $client->validateConfiguration();

    // Créer une facture B2C simple
    $invoice = $client->invoices()->createSaleInvoice(
        pointOfSale: 'Caisse 1',
        establishment: 'Magasin Principal',
        clientName: 'Jean Dupont',
        clientPhone: '0709123456',
        clientEmail: 'jean.dupont@email.com',
        paymentMethod: Constants::PAYMENT_MOBILE_MONEY
    );

    // Ajouter des articles
    $item1 = new InvoiceItem(
        description: 'Ordinateur portable HP',
        quantity: 1,
        amount: 650000,
        taxes: [Constants::TAX_TVA]
    );
    $item1->setReference('HP001')
        ->setMeasurementUnit('pcs')
        ->addCustomTax('GRA', 3);

    $item2 = new InvoiceItem(
        description: 'Souris sans fil',
        quantity: 2,
        amount: 15000,
        taxes: [Constants::TAX_TVA]
    );

    $invoice->addItem($item1)
        ->addItem($item2)
        ->setCommercialMessage('Merci pour votre achat')
        ->setDiscount(5); // 5% de remise

    // Certifier la facture
    $response = $client->invoices()->signInvoice($invoice);

    // Afficher les résultats
    echo "✅ Facture créée avec succès!\n";
    echo "Référence: " . $response->getReference() . "\n";
    echo "NCC: " . $response->getNcc() . "\n";
    echo "QR Code URL: " . $response->getQrCodeUrl() . "\n";
    echo "Balance stickers: " . $response->getBalanceSticker() . "\n";

    if ($response->hasWarning()) {
        echo "⚠️ Attention: Stock de stickers faible\n";
    }

} catch (FneException $e) {
    echo "❌ Erreur FNE: " . $e->getMessage() . "\n";

    // Afficher le contexte d'erreur si disponible
    $context = $e->getContext();
    if (!empty($context)) {
        echo "Contexte: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
    }

} catch (Exception $e) {
    echo "❌ Erreur générale: " . $e->getMessage() . "\n";
}