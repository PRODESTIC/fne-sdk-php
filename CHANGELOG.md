### CHANGELOG.md
```markdown
# Changelog

Toutes les modifications notables de ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
et ce projet adhère au [Versioning Sémantique](https://semver.org/spec/v2.0.0.html).

## [Non publié]

### Ajouté
- Support des webhooks DGI
- CLI pour tests rapides
- Métriques et monitoring

## [1.0.0] - 2025-01-15

### Ajouté
- Client FNE principal avec modes test/production
- Support complet des factures de vente (B2B, B2C, B2F, B2G)
- Support des factures d'avoir (remboursements)
- Support des bordereaux d'achat de produits agricoles
- Validation automatique des données
- Gestion d'erreurs typée et complète
- Cache automatique des réponses
- Retry automatique avec backoff exponentiel
- Support des devises étrangères
- Documentation complète avec exemples
- Tests unitaires et d'intégration
- Configuration flexible

### Services
- `InvoiceService` - Gestion des factures de vente
- `RefundService` - Gestion des factures d'avoir
- `PurchaseService` - Gestion des bordereaux d'achat

### Modèles
- `Invoice` - Modèle de facture principal
- `InvoiceItem` - Modèle d'article de facture
- `RefundRequest` - Modèle de demande d'avoir
- `ApiResponse` - Modèle de réponse API
- `CustomTax` - Modèle de taxe personnalisée

### Exceptions
- `FneException` - Exception de base
- `ValidationException` - Erreurs de validation
- `AuthenticationException` - Erreurs d'authentification
- `ApiException` - Erreurs API
- `NetworkException` - Erreurs réseau

### Utilitaires
- `Constants` - Constantes pour types, méthodes de paiement, devises
- `TokenManager` - Gestion des tokens d'authentification
- `HttpClient` - Client HTTP avec retry et timeout
- `InvoiceValidator` - Validation des données de facture

## [0.9.0] - 2025-01-10

### Ajouté
- Version bêta initiale
- Structure de base du SDK
- Support basique des factures de vente

### Modifié
- Architecture refactorisée pour plus de flexibilité

### Corrigé
- Problèmes de validation des NCC
- Gestion des erreurs de timeout

## [0.1.0] - 2025-01-05

### Ajouté
- Première version de développement
- Proof of concept avec l'API FNE
