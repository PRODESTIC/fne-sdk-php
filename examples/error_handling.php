<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DgiCi\FneSdk\FneClient;
use DgiCi\FneSdk\Models\InvoiceItem;
use DgiCi\FneSdk\Utils\Constants;
use DgiCi\FneSdk\Exceptions\{
    ValidationException,
    AuthenticationException,
    ApiException,
    NetworkException,
    FneException
};

$apiKey = 'cle_api_invalide';

try {
    $client = FneClient::test($apiKey);

    // Exemple de facture avec erreurs de validation
    $invoice = $client->invoices()->createSaleInvoice(
        pointOfSale: '',  // Erreur: champ vide
        establishment: 'Test',
        clientName: 'Client',
        clientPhone: '123', // Erreur: numéro invalide
        clientEmail: 'email-invalide', // Erreur: email invalide
    );

    // Ajouter un article invalide
    $item = new InvoiceItem(
        description: '', // Erreur: description vide
        quantity: -1,    // Erreur: quantité négative
        amount: 0,       // Erreur: montant zéro
        taxes: []        // Erreur: pas de taxes
    );
    $invoice->addItem($item);

    $response = $client->invoices()->signInvoice($invoice);

} catch (ValidationException $e) {
    echo "🔍 Erreurs de validation:\n";
    echo "Message: " . $e->getMessage() . "\n";

    foreach ($e->getErrors() as $field => $error) {
        echo "- {$field}: {$error}\n";
    }

} catch (AuthenticationException $e) {
    echo "🔐 Erreur d'authentification:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";

    if ($e->getCode() === 401) {
        echo "💡 Vérifiez votre clé API dans l'espace FNE\n";
    }

} catch (NetworkException $e) {
    echo "🌐 Erreur réseau:\n";
    echo "Message: " . $e->getMessage() . "\n";

    if ($e->getPrevious()) {
        echo "Détail: " . $e->getPrevious()->getMessage() . "\n";
    }

    echo "💡 Vérifiez votre connexion internet\n";

} catch (ApiException $e) {
    echo "⚠️ Erreur API:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Code HTTP: " . $e->getCode() . "\n";

    $context = $e->getContext();
    if (isset($context['response'])) {
        echo "Réponse complète:\n";
        echo json_encode($context['response'], JSON_PRETTY_PRINT) . "\n";
    }

} catch (FneException $e) {
    echo "❌ Erreur FNE générale:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";

    $context = $e->getContext();
    if (!empty($context)) {
        echo "Contexte:\n";
        echo json_encode($context, JSON_PRETTY_PRINT) . "\n";
    }

} catch (Exception $e) {
    echo "💥 Erreur inattendue:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";

    // En mode debug, afficher la stack trace
    if (getenv('APP_DEBUG') === 'true') {
        echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    }
}

// Exemple de gestion d'erreurs avec retry
function createInvoiceWithRetry(FneClient $client, $invoice, int $maxRetries = 3): ?object
{
    $lastException = null;

    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
        try {
            return $client->invoices()->signInvoice($invoice);

        } catch (NetworkException $e) {
            $lastException = $e;
            echo "Tentative {$attempt}/{$maxRetries} échouée (erreur réseau)\n";

            if ($attempt < $maxRetries) {
                sleep(pow(2, $attempt)); // Backoff exponentiel
            }

        } catch (ApiException $e) {
            // Erreurs 5xx sont retry-ables, 4xx ne le sont pas
            if ($e->getCode() >= 500) {
                $lastException = $e;
                echo "Tentative {$attempt}/{$maxRetries} échouée (erreur serveur)\n";

                if ($attempt < $maxRetries) {
                    sleep(2);
                }
            } else {
                throw $e; // Erreur client, pas de retry
            }

        } catch (FneException $e) {
            throw $e; // Autres erreurs FNE, pas de retry
        }
    }

    throw $lastException;
}