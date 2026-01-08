<?php

namespace DgiCi\FneSdk\Utils;

class Constants
{
    // Types de factures
    const INVOICE_TYPE_SALE = 'sale';
    const INVOICE_TYPE_PURCHASE = 'purchase';

    // Méthodes de paiement
    const PAYMENT_CASH = 'cash';
    const PAYMENT_CARD = 'card';
    const PAYMENT_CHECK = 'check';
    const PAYMENT_MOBILE_MONEY = 'mobile-money';
    const PAYMENT_TRANSFER = 'transfer';
    const PAYMENT_DEFERRED = 'deferred';

    // Templates de facturation
    const TEMPLATE_B2B = 'B2B';
    const TEMPLATE_B2C = 'B2C';
    const TEMPLATE_B2F = 'B2F';
    const TEMPLATE_B2G = 'B2G';

    // Types de TVA
    const TAX_TVA = 'TVA';      // 18%
    const TAX_TVAB = 'TVAB';    // 9%
    const TAX_TVAC = 'TVAC';    // 0% conv
    const TAX_TVAD = 'TVAD';    // 0% leg

    // Devises (selon documentation FNE officielle)
    const CURRENCY_XOF = 'XOF';  // Franc CFA
    const CURRENCY_USD = 'USD';  // Dollar Américain
    const CURRENCY_EUR = 'EUR';  // Euro
    const CURRENCY_GBP = 'GBP';  // Livre Sterling Britannique
    const CURRENCY_JPY = 'JPY';  // Yen Japonais
    const CURRENCY_CAD = 'CAD';  // Dollar Canadien
    const CURRENCY_AUD = 'AUD';  // Dollar Australien
    const CURRENCY_CNH = 'CNH';  // Yuan Chinois
    const CURRENCY_CHF = 'CHF';  // Franc Suisse
    const CURRENCY_HKD = 'HKD';  // Dollar Hong Kong
    const CURRENCY_NZD = 'NZD';  // Dollar Néo-Zélandais

    /**
     * Liste de toutes les devises supportées par l'API FNE
     */
    const ALLOWED_CURRENCIES = [
        self::CURRENCY_XOF,
        self::CURRENCY_USD,
        self::CURRENCY_EUR,
        self::CURRENCY_GBP,
        self::CURRENCY_JPY,
        self::CURRENCY_CAD,
        self::CURRENCY_AUD,
        self::CURRENCY_CNH,
        self::CURRENCY_CHF,
        self::CURRENCY_HKD,
        self::CURRENCY_NZD,
    ];

    /**
     * Liste des méthodes de paiement autorisées
     */
    const ALLOWED_PAYMENT_METHODS = [
        self::PAYMENT_CASH,
        self::PAYMENT_CARD,
        self::PAYMENT_CHECK,
        self::PAYMENT_MOBILE_MONEY,
        self::PAYMENT_TRANSFER,
        self::PAYMENT_DEFERRED,
    ];

    /**
     * Liste des templates autorisés
     */
    const ALLOWED_TEMPLATES = [
        self::TEMPLATE_B2B,
        self::TEMPLATE_B2C,
        self::TEMPLATE_B2F,
        self::TEMPLATE_B2G,
    ];

    /**
     * Liste des types de TVA autorisés
     */
    const ALLOWED_TAX_TYPES = [
        self::TAX_TVA,
        self::TAX_TVAB,
        self::TAX_TVAC,
        self::TAX_TVAD,
    ];

    // URLs
    const TEST_BASE_URL = 'http://54.247.95.108/ws';
    const PROD_BASE_URL = ''; // À définir après validation

    // Endpoints
    const ENDPOINT_SIGN_INVOICE = '/external/invoices/sign';
    const ENDPOINT_REFUND_INVOICE = '/external/invoices/{id}/refund';

    // Codes de réponse HTTP
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_INTERNAL_ERROR = 500;
}