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
