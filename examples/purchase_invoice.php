<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DgiCi\FneSdk\FneClient;
use DgiCi\FneSdk\Models\InvoiceItem;
use DgiCi\FneSdk\Utils\Constants;

$apiKey = 'votre_cle_api_ici';

try {
    $client = FneClient::test($apiKey);

    // Bordereau d'achat de produits agricoles
    $purchase = $client->purchases()->createCooperativePurchase(
        pointOfSale: 'Centre de collecte',
        establishment: 'Abengourou',
        cooperativeName: 'COOPÉRATION DU GRAND OUEST',
        cooperativePhone: '0709080765',
        cooperativeEmail: 'info@cgo.ci',
        paymentMethod: Constants::PAYMENT_MOBILE_MONEY
    );

    // Produits agricoles achetés
    $cacao = new InvoiceItem(
        description: 'Cacao brut premier choix',
        quantity: 2000,
        amount: 2200, // Prix par kg
        taxes: [] // Pas de TVA sur produits agricoles
    );
    $cacao->setReference('CACAO001')
        ->setMeasurementUnit('kg');

    $cafe = new InvoiceItem(
        description: 'Café robusta',
        quantity: 500,
        amount: 1800,
        taxes: []
    );
    $cafe->setReference('CAFE001')
        ->setMeasurementUnit('kg');

    $purchase->addItem($cacao)
        ->addItem($cafe)
        ->setCommercialMessage('Achat produits locaux - Campagne 2024/2025')
        ->setFooter('Paiement comptant - Reçu remis au producteur');

    $response = $client->purchases()->signPurchaseInvoice($purchase);

    echo "✅ Bordereau d'achat créé avec succès!\n";
    echo "Référence: " . $response->getReference() . "\n";
    echo "Montant total: " . number_format($response->getInvoice()['amount'] ?? 0) . " FCFA\n";

    // Calculer les totaux par produit
    $items = $response->getInvoice()['items'] ?? [];
    foreach ($items as $item) {
        $total = $item['quantity'] * $item['amount'];
        echo "- {$item['description']}: {$item['quantity']} {$item['measurementUnit']} × "
            . number_format($item['amount']) . " = " . number_format($total) . " FCFA\n";
    }

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}