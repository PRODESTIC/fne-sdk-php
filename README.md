# SDK PHP pour l'API FNE (Facture Normalisée Électronique) - DGI Côte d'Ivoire

[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![Tests](https://img.shields.io/badge/tests-passing-green)](tests/)

SDK PHP officieux pour l'intégration avec l'API de Facturation Normalisée Électronique (FNE) de la Direction Générale des Impôts de Côte d'Ivoire.

## 🚀 Fonctionnalités

- ✅ **Facturation de vente** (B2B, B2C, B2F, B2G)
- ✅ **Factures d'avoir** (remboursements partiels ou complets)
- ✅ **Bordereaux d'achat** de produits agricoles
- ✅ **Validation automatique** des données
- ✅ **Gestion d'erreurs** complète et typée
- ✅ **Mode test et production**
- ✅ **Retry automatique** en cas d'erreur réseau
- ✅ **Cache des réponses**
- ✅ **Support devises étrangères**
- ✅ **Documentation complète** avec exemples

## 📋 Prérequis

- PHP 7.4 ou supérieur
- Extension cURL
- Extension JSON
- Clé API FNE (obtenue après validation par la DGI)

## 📦 Installation

```bash
composer require prodestic/fne-sdk-php
```

## 🔑 Configuration

### 1. Obtenir votre clé API

#### - Inscrivez-vous sur la plateforme FNE de test : http://54.247.95.108
#### - Configurez votre environnement de test
#### - Développez et testez votre intégration
#### - Transmettez vos spécimens à support.fne@dgi.gouv.ci
#### - Récupérez votre clé API dans l'onglet "Paramétrage" après validation

### 2. Initialisation du client

```php
use DgiCi\FneSdk\FneClient;

// Mode test
$client = FneClient::test('votre_cle_api_test');

// Mode production (après validation)
$client = FneClient::production('votre_cle_api_prod', 'url_production');
```

## 🎯 Utilisation rapide

### Facture de vente simple (B2C)

```php
use DgiCi\FneSdk\FneClient;
use DgiCi\FneSdk\Models\InvoiceItem;
use DgiCi\FneSdk\Utils\Constants;

$client = FneClient::test('votre_cle_api');

// Créer la facture
$invoice = $client->invoices()->createSaleInvoice(
    pointOfSale: 'Caisse 1',
    establishment: 'Magasin Principal',
    clientName: 'Jean Dupont',
    clientPhone: '0709123456',
    clientEmail: 'jean@email.com'
);

// Ajouter des articles
$item = new InvoiceItem(
    description: 'Ordinateur portable',
    quantity: 1,
    amount: 650000,
    taxes: [Constants::TAX_TVA]
);

$invoice->addItem($item);

// Certifier la facture
$response = $client->invoices()->signInvoice($invoice);

echo "Facture créée: " . $response->getReference();
echo "QR Code: " . $response->getQrCodeUrl();
```

### Facture B2B (entreprise à entreprise)

```php
$invoice = $client->invoices()->createB2BInvoice(
    pointOfSale: 'Service Commercial',
    establishment: 'Siège Social',
    clientName: 'KPMG CÔTE D\'IVOIRE',
    clientPhone: '0709080765',
    clientEmail: 'info@kpmg.ci',
    clientNcc: '9502363N', // NCC obligatoire pour B2B
    paymentMethod: Constants::PAYMENT_TRANSFER
);

// Ajouter services avec taxes personnalisées
$service = new InvoiceItem('Audit comptable', 1, 2500000, [Constants::TAX_TVA]);
$service->addCustomTax('Retenue à la source', 5);

$invoice->addItem($service);
$response = $client->invoices()->signInvoice($invoice);
```

### Facture d'avoir

```php
// Créer une demande d'avoir
$refundRequest = $client->refunds()->createRefundRequest();
$refundRequest->addItem('item_id_1', 2); // Retourner 2 unités
$refundRequest->addItem('item_id_2', 1); // Retourner 1 unité

// Créer l'avoir
$response = $client->refunds()->createRefund('original_invoice_id', $refundRequest);
```

### Bordereau d'achat agricole

```php
$purchase = $client->purchases()->createPurchaseInvoice(
    pointOfSale: 'Centre de collecte',
    establishment: 'Abengourou',
    supplierName: 'Coopérative Agricole',
    supplierPhone: '0709080765',
    supplierEmail: 'coop@email.com'
);

$cacao = new InvoiceItem('Cacao brut premier choix', 1000, 2200, []);
$purchase->addItem($cacao);

$response = $client->purchases()->signPurchaseInvoice($purchase);
```

## 🔧 Configuration avancée

### Client avec options personnalisées

```php
$client = new FneClient([
    'api_key' => 'votre_cle',
    'base_url' => 'url_personnalisée',
    'timeout' => 60,        // Timeout en secondes
    'retry_attempts' => 5,  // Nombre de tentatives
    'test_mode' => true,
]);
```

### Gestion des devises étrangères (B2F)

```php
$invoice = $client->invoices()->createB2FInvoice(
    // ... paramètres de base
    foreignCurrency: Constants::CURRENCY_EUR,
    exchangeRate: 655.957 // 1 EUR = 655.957 FCFA
);
```

## 🚨 Gestion d'erreurs

```php
use DgiCi\FneSdk\Exceptions\{
    ValidationException,
    AuthenticationException,
    ApiException,
    NetworkException
};

try {
    $response = $client->invoices()->signInvoice($invoice);
} catch (ValidationException $e) {
    // Erreurs de validation des données
    foreach ($e->getErrors() as $field => $error) {
        echo "Erreur {$field}: {$error}\n";
    }
} catch (AuthenticationException $e) {
    // Problème d'authentification (clé API invalide)
    echo "Erreur auth: " . $e->getMessage();
} catch (NetworkException $e) {
    // Problème de réseau/connexion
    echo "Erreur réseau: " . $e->getMessage();
} catch (ApiException $e) {
    // Erreur retournée par l'API FNE
    echo "Erreur API: " . $e->getMessage();
    echo "Code: " . $e->getCode();
}
```

## 📊 Types de factures supportés

| **Type** | **Code**         | **Description**                  | **NCC requis** |
|----------|------------------|----------------------------------|----------------|
| B2C      | `TEMPLATE_B2C`   | Entreprise → Consommateur        | Non            |
| B2B      | `TEMPLATE_B2B`   | Entreprise → Entreprise          | Oui            |
| B2F      | `TEMPLATE_B2F`   | Entreprise → International       | Non            |
| B2G      | `TEMPLATE_B2G`   | Entreprise → Gouvernement        | Non            |


## 💰 Méthodes de paiement

```php
Constants::PAYMENT_CASH         // Espèces
Constants::PAYMENT_CARD         // Carte bancaire
Constants::PAYMENT_CHECK        // Chèque
Constants::PAYMENT_MOBILE_MONEY // Mobile Money
Constants::PAYMENT_TRANSFER     // Virement
Constants::PAYMENT_DEFERRED     // À terme
```

## 🏷️ Types de TVA

```php
Constants::TAX_TVA   // TVA normale 18%
Constants::TAX_TVAB  // TVA réduite 9%
Constants::TAX_TVAC  // TVA exonérée conventionnelle 0%
Constants::TAX_TVAD  // TVA exonérée légale 0%
```

## 🧪 Tests

```bash
# Tests unitaires
./vendor/bin/phpunit tests/Unit

# Tests d'intégration
./vendor/bin/phpunit tests/Integration

# Tous les tests
./vendor/bin/phpunit
```

## 📚 Exemples

Consultez le dossier `examples/` pour des exemples complets :

- `basic_invoice.php` - Facture simple

- `b2b_invoice.php` - Facture B2B

- `international_invoice.php` - Facture export

- `refund_invoice.php` - Facture d'avoir

- `purchase_invoice.php` - Bordereau d'achat

- `error_handling.php` - Gestion d'erreurs


## 🔄 Migration et mise à jour

```php
// Ancien
$client = new FneClient($apiKey, $baseUrl);

// Nouveau
$client = FneClient::test($apiKey);
// ou
$client = FneClient::production($apiKey, $baseUrl);
```

## 🐛 Débogage

### Activer le mode debug

```php
// Définir la variable d'environnement
putenv('APP_DEBUG=true');

// Les stack traces seront affichées en cas d'erreur
```

### Vérifier la configuration

```php
$client = FneClient::test($apiKey);
$config = $client->getConfig();
print_r($config);
```

## 📞 Support

- **Email support technique** : [support.fne@dgi.gouv.ci](mailto:support.fne@dgi.gouv.ci)
- **Documentation officielle** : [https://fne.dgi.gouv.ci/](https://fne.dgi.gouv.ci/)
- **Issues GitHub** : [Créer un ticket](https://github.com/<utilisateur>/<repo>/issues/new)

## 🤝 Contribution

- Fork le projet
- Créer une branche feature (`git checkout -b feature/amelioration`)
- Commit vos changements (`git commit -am 'Ajout nouvelle fonctionnalité'`)
- Push vers la branche (`git push origin feature/amelioration`)
- Créer une Pull Request

## Standards de développement

- PSR-4 pour l'autoloading
- PSR-12 pour le style de code
- PHPDoc pour la documentation
- Tests unitaires obligatoires

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier LICENSE pour plus de détails.

## 🎯 Roadmap

- Support des webhooks DGI
- CLI pour tests rapides
- Cache Redis/Memcached
- Metrics et monitoring
- Support Symfony Bundle
- Support Laravel Package

## ⚠️ Notes importantes

- Environnement de test: Utilisez toujours l'environnement de test avant la production
- Validation DGI: Votre intégration doit être validée par la DGI avant utilisation en production
- Clé API: Gardez votre clé API secrète et ne la commitez jamais
- Stickers: Surveillez votre balance de stickers pour éviter les interruptions
- Limites: Respectez les limites de taux de l'API

## 📈 Performance

- Cache automatique des réponses pour éviter les appels redondants
- Retry automatique avec backoff exponentiel
- Timeout configurable pour éviter les blocages
- Validation locale avant envoi à l'API
