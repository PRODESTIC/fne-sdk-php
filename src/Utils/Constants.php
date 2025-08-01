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

    // Devises
    const CURRENCY_XOF = 'XOF';
    const CURRENCY_USD = 'USD';
    const CURRENCY_EUR = 'EUR';
    const CURRENCY_GBP = 'GBP';

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