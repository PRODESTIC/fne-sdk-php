<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DgiCi\FneSdk\FneClient;
use DgiCi\FneSdk\Models\InvoiceItem;
use DgiCi\FneSdk\Utils\Constants;

$apiKey = 'votre_cle_api_ici';

try {
    $client = FneClient::test($apiKey);

    // 1. D'abord, créer une facture normale
    $originalInvoice = $client->invoices()->createSaleInvoice(
        pointOfSale: 'Caisse 1',
        establishment: 'Magasin',
        clientName: 'Client Test',
        clientPhone: '0709123456',
        clientEmail: 'client@test.com'
    );

    $item1 = new InvoiceItem('Produit A', 5, 10000, [Constants::TAX_TVA]);
    $item2 = new InvoiceItem('Produit B', 3, 15000, [Constants::TAX_TVA]);

    $originalInvoice->addItem($item1)->addItem($item2);

    $originalResponse = $client->invoices()->signInvoice($originalInvoice);
    echo "Facture originale créée: " . $originalResponse->getReference() . "\n";

    // 2. Créer un avoir partiel
    $refundRequest = $client->refunds()->createRefundRequest();

    // Récupérer les IDs des articles de la facture originale
    $originalItems = $originalResponse->getInvoice()['items'] ?? [];

    if (count($originalItems) >= 2) {
        // Retourner 2 unités du premier produit et 1 unité du second
        $refundRequest->addItem($originalItems[0]['id'], 2);
        $refundRequest->addItem($originalItems[1]['id'], 1);

        // ID de la facture originale depuis la réponse
        $originalInvoiceId = $originalResponse->getInvoice()['id'];

        $refundResponse = $client->refunds()->createRefund($originalInvoiceId, $refundRequest);

        echo "✅ Avoir créé avec succès!\n";
        echo "Référence avoir: " . $refundResponse->getReference() . "\n";
        echo "Balance stickers: " . $refundResponse->getBalanceSticker() . "\n";
    }

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}