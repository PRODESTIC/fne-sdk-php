# SDK PHP pour l'API FNE (Facture Normalisee Electronique) - DGI Cote d'Ivoire

[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![Tests](https://img.shields.io/badge/tests-passing-green)](tests/)

SDK PHP non-officiel pour l'integration avec l'API de Facturation Normalisee Electronique (FNE) de la Direction Generale des Impots de Cote d'Ivoire.

## Fonctionnalites

- **Facturation de vente** (B2B, B2C, B2F, B2G)
- **Factures d'avoir** (remboursements partiels ou complets)
- **Bordereaux d'achat** de produits agricoles
- **Generation de QR Code** pour la verification des factures
- **Validation automatique** des donnees selon les specifications FNE
- **Gestion d'erreurs** complete et typee
- **Mode test et production**
- **Retry automatique** en cas d'erreur reseau
- **Support de 11 devises etrangeres** (USD, EUR, GBP, JPY, CAD, AUD, CNH, CHF, HKD, NZD)
- **Utilitaires de calcul** (TVA, remises, conversions)

## Prerequis

- PHP 7.4 ou superieur
- Extension cURL
- Extension JSON
- Cle API FNE (obtenue apres validation par la DGI)

## Installation

```bash
composer require prodestic/fne-sdk-php
```

### Installation optionnelle pour la generation de QR Code

```bash
composer require endroid/qr-code
```

## Configuration

### 1. Obtenir votre cle API

1. Inscrivez-vous sur la plateforme FNE de test : http://54.247.95.108
2. Configurez votre environnement de test
3. Developpez et testez votre integration
4. Transmettez vos specimens de factures a support.fne@dgi.gouv.ci
5. Recuperez votre cle API dans l'onglet "Parametrage" apres validation par la DGI

### 2. Initialisation du client

```php
use DgiCi\FneSdk\FneClient;

// Mode test
$client = FneClient::test('votre_cle_api_test');

// Mode production (apres validation DGI)
$client = FneClient::production('votre_cle_api_prod', 'url_production_fournie_par_dgi');
```

## Utilisation

### Facture de vente B2C (particulier)

```php
use DgiCi\FneSdk\FneClient;
use DgiCi\FneSdk\Models\Invoice;
use DgiCi\FneSdk\Models\InvoiceItem;
use DgiCi\FneSdk\Utils\Constants;

$client = FneClient::test('votre_cle_api');

// Creer la facture
$invoice = new Invoice(
    Constants::INVOICE_TYPE_SALE,
    Constants::PAYMENT_CASH,
    Constants::TEMPLATE_B2C,
    'Caisse 1',           // Point de vente
    'Magasin Principal',  // Etablissement
    'Jean Dupont',        // Nom client
    '0709123456',         // Telephone client
    'jean@email.com'      // Email client
);

// Ajouter des articles
$item = new InvoiceItem(
    'Ordinateur portable', // Description
    1,                     // Quantite
    650000,                // Prix unitaire HT
    [Constants::TAX_TVA]   // Type de TVA (18%)
);
$invoice->addItem($item);

// Certifier la facture
$response = $client->invoices()->signInvoice($invoice);

echo "Reference: " . $response->getReference();
echo "NCC: " . $response->getNcc();
echo "QR Code URL: " . $response->getToken();
echo "Stickers restants: " . $response->getBalanceSticker();
```

### Facture B2B (entreprise a entreprise)

Le NCC du client est **obligatoire** pour les factures B2B.

```php
$invoice = new Invoice(
    Constants::INVOICE_TYPE_SALE,
    Constants::PAYMENT_TRANSFER,
    Constants::TEMPLATE_B2B,
    'Service Commercial',
    'Siege Social',
    'KPMG COTE D\'IVOIRE',
    '0709080765',
    'info@kpmg.ci'
);

// NCC obligatoire pour B2B
$invoice->setClientNcc('9502363N');

// Article avec taxes personnalisees
$service = new InvoiceItem('Audit comptable', 1, 2500000, [Constants::TAX_TVA]);
$service->addCustomTax('Retenue a la source', 5);
$invoice->addItem($service);

// Remise globale de 10%
$invoice->setDiscount(10);

$response = $client->invoices()->signInvoice($invoice);
```

### Facture B2F (export international)

Les champs `foreignCurrency` et `foreignCurrencyRate` sont **obligatoires** pour B2F.

```php
$invoice = new Invoice(
    Constants::INVOICE_TYPE_SALE,
    Constants::PAYMENT_TRANSFER,
    Constants::TEMPLATE_B2F,
    'Export',
    'Siege',
    'Foreign Company Ltd',
    '0102030405',
    'contact@foreign.com'
);

// Devise et taux de change obligatoires
$invoice->setForeignCurrency(Constants::CURRENCY_EUR, 655.957);

$item = new InvoiceItem('Produit export', 100, 10000, [Constants::TAX_TVAC]);
$invoice->addItem($item);

$response = $client->invoices()->signInvoice($invoice);
```

### Facture d'avoir (remboursement)

```php
use DgiCi\FneSdk\Models\RefundRequest;

// ID de la facture originale (recupere lors de la certification)
$originalInvoiceId = 'e2b2d8da-a532-4c08-9182-f5b428ca468d';

// Creer la demande d'avoir
$refundRequest = new RefundRequest();
$refundRequest->addItem('bf9cc241-9b5f-4d26-a570-aa8e682a759e', 20); // ID article, quantite
$refundRequest->addItem('50b5c9d9-e22d-4dce-ba3c-5d2519c3418f', 10);

// Creer l'avoir
$response = $client->refunds()->createRefund($originalInvoiceId, $refundRequest);

echo "Avoir cree: " . $response->getReference();
```

### Bordereau d'achat agricole

```php
$purchase = new Invoice(
    Constants::INVOICE_TYPE_PURCHASE,
    Constants::PAYMENT_MOBILE_MONEY,
    Constants::TEMPLATE_B2C,
    'Centre de collecte',
    'Abengourou',
    'Cooperative Agricole',
    '0709080765',
    'coop@email.ci'
);

// Les articles d'achat agricole n'ont pas de taxes
$cacao = new InvoiceItem('Cacao brut premier choix', 2000, 2200);
$cacao->setMeasurementUnit('kg');
$purchase->addItem($cacao);

$response = $client->purchases()->signPurchaseInvoice($purchase);
```

## Generation de QR Code

La classe `Helper` fournit des utilitaires pour generer le QR code de verification.

```php
use DgiCi\FneSdk\Utils\Helper;

// Apres certification
$response = $client->invoices()->signInvoice($invoice);
$tokenUrl = $response->getToken();

// Generer le QR code en base64 (pour affichage HTML)
$qrCodeBase64 = Helper::generateQrCodeBase64($tokenUrl, 300);
echo '<img src="' . $qrCodeBase64 . '" alt="QR Code FNE">';

// Sauvegarder le QR code dans un fichier
Helper::saveQrCode($tokenUrl, '/path/to/qrcode.png', 300);

// Generer en SVG
$svg = Helper::generateQrCode($tokenUrl, 300, 'svg');
```

## Utilitaires de calcul

```php
use DgiCi\FneSdk\Utils\Helper;

// Calcul de TVA
$ht = 100000;
$ttc = Helper::calculateTTC($ht, 18);        // 118000
$tva = Helper::calculateVAT($ht, 18);        // 18000
$htFromTtc = Helper::calculateHT(118000, 18); // 100000

// Appliquer une remise
$apresRemise = Helper::applyDiscount(100000, 10); // 90000

// Conversion de devises
$xof = 655957;
$eur = Helper::convertCurrency($xof, 655.957, true);  // XOF -> EUR
$xof = Helper::convertCurrency(1000, 655.957, false); // EUR -> XOF

// Formater un montant
echo Helper::formatAmount(1250000);      // "1 250 000 FCFA"
echo Helper::formatAmount(1250000, false); // "1 250 000"

// Obtenir le taux de TVA
$rate = Helper::getVatRate(Constants::TAX_TVA);  // 18.0
$rate = Helper::getVatRate(Constants::TAX_TVAB); // 9.0

// Valider un NCC
$valid = Helper::isValidNcc('9502363N'); // true

// Extraire le token d'une URL
$token = Helper::extractTokenFromUrl($tokenUrl);
```

## Gestion d'erreurs

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
    // Erreurs de validation des donnees
    foreach ($e->getErrors() as $field => $error) {
        echo "Erreur sur {$field}: {$error}\n";
    }
} catch (AuthenticationException $e) {
    // Cle API invalide ou expiree (HTTP 401)
    echo "Erreur d'authentification: " . $e->getMessage();
} catch (NetworkException $e) {
    // Probleme de connexion reseau
    echo "Erreur reseau: " . $e->getMessage();
} catch (ApiException $e) {
    // Erreur retournee par l'API FNE (HTTP 400, 500)
    echo "Erreur API: " . $e->getMessage();
    echo "Code: " . $e->getCode();
}
```

## Reference des constantes

### Types de factures

| Type | Constante | Description | NCC requis |
|------|-----------|-------------|------------|
| B2C | `TEMPLATE_B2C` | Entreprise vers Particulier | Non |
| B2B | `TEMPLATE_B2B` | Entreprise vers Entreprise | **Oui** |
| B2F | `TEMPLATE_B2F` | Entreprise vers International | Non |
| B2G | `TEMPLATE_B2G` | Entreprise vers Gouvernement | Non |

### Methodes de paiement

| Methode | Constante | Description |
|---------|-----------|-------------|
| Especes | `PAYMENT_CASH` | Paiement en especes |
| Carte | `PAYMENT_CARD` | Carte bancaire |
| Cheque | `PAYMENT_CHECK` | Cheque |
| Mobile Money | `PAYMENT_MOBILE_MONEY` | Orange Money, MTN, Wave, etc. |
| Virement | `PAYMENT_TRANSFER` | Virement bancaire |
| A terme | `PAYMENT_DEFERRED` | Paiement differe |

### Types de TVA

| Type | Constante | Taux | Description |
|------|-----------|------|-------------|
| TVA | `TAX_TVA` | 18% | TVA normale |
| TVAB | `TAX_TVAB` | 9% | TVA reduite |
| TVAC | `TAX_TVAC` | 0% | Exoneration conventionnelle |
| TVAD | `TAX_TVAD` | 0% | Exoneration legale (TEE, RME) |

### Devises supportees

| Devise | Constante | Description |
|--------|-----------|-------------|
| XOF | `CURRENCY_XOF` | Franc CFA |
| USD | `CURRENCY_USD` | Dollar Americain |
| EUR | `CURRENCY_EUR` | Euro |
| GBP | `CURRENCY_GBP` | Livre Sterling |
| JPY | `CURRENCY_JPY` | Yen Japonais |
| CAD | `CURRENCY_CAD` | Dollar Canadien |
| AUD | `CURRENCY_AUD` | Dollar Australien |
| CNH | `CURRENCY_CNH` | Yuan Chinois |
| CHF | `CURRENCY_CHF` | Franc Suisse |
| HKD | `CURRENCY_HKD` | Dollar Hong Kong |
| NZD | `CURRENCY_NZD` | Dollar Neo-Zelandais |

## Tests

```bash
# Tous les tests
composer test

# Tests unitaires uniquement
composer test:unit

# Tests d'integration
composer test:integration

# Couverture de code
composer test:coverage
```

## Exemples

Consultez le dossier `examples/` pour des exemples complets :

- `basic_invoice.php` - Facture simple B2C
- `b2b_invoice.php` - Facture B2B avec NCC
- `international_invoice.php` - Facture B2F avec devise
- `refund_invoice.php` - Facture d'avoir
- `purchase_invoice.php` - Bordereau d'achat agricole
- `error_handling.php` - Gestion des erreurs

## Notes importantes

1. **Environnement de test**: Utilisez toujours l'environnement de test avant la production
2. **Validation DGI**: Votre integration doit etre validee par la DGI avant utilisation en production
3. **Cle API**: Gardez votre cle API secrete et ne la commitez jamais dans votre code source
4. **Stickers**: Surveillez votre balance de stickers via `$response->getBalanceSticker()`
5. **NCC B2B**: Le NCC client est obligatoire pour toutes les factures B2B
6. **Devise B2F**: La devise et le taux de change sont obligatoires pour les factures B2F

## Support

- **Email support DGI**: support.fne@dgi.gouv.ci
- **Plateforme de test**: http://54.247.95.108
- **Issues**: https://github.com/prodestic/fne-sdk-php/issues

## Contribution

1. Fork le projet
2. Creez votre branche (`git checkout -b feature/amelioration`)
3. Committez vos changements (`git commit -m 'Ajout fonctionnalite'`)
4. Pushez vers la branche (`git push origin feature/amelioration`)
5. Ouvrez une Pull Request

## Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de details.

---

**Developpe par [PRODESTIC SARL](https://prodestic.net)** - SDK non-officiel pour l'API FNE de la DGI Cote d'Ivoire.
