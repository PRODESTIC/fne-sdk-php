<?php

namespace DgiCi\FneSdk\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use DgiCi\FneSdk\Utils\Helper;
use DgiCi\FneSdk\Utils\Constants;

class HelperTest extends TestCase
{
    // ============================================
    // TESTS DE FORMATAGE DES MONTANTS
    // ============================================

    public function testFormatAmount(): void
    {
        $this->assertEquals('1 000 FCFA', Helper::formatAmount(1000));
        $this->assertEquals('1 250 000 FCFA', Helper::formatAmount(1250000));
        $this->assertEquals('0 FCFA', Helper::formatAmount(0));
    }

    public function testFormatAmountWithoutSymbol(): void
    {
        $this->assertEquals('1 000', Helper::formatAmount(1000, false));
        $this->assertEquals('1 250 000', Helper::formatAmount(1250000, false));
    }

    public function testFormatAmountWithDecimals(): void
    {
        $this->assertEquals('1 000,50 FCFA', Helper::formatAmountWithDecimals(1000.50));
        $this->assertEquals('1 250 000,00 FCFA', Helper::formatAmountWithDecimals(1250000));
    }

    // ============================================
    // TESTS DE CALCUL TVA
    // ============================================

    public function testCalculateTTC(): void
    {
        // TVA 18%
        $this->assertEquals(11800, Helper::calculateTTC(10000, 18));

        // TVA 9%
        $this->assertEquals(10900, Helper::calculateTTC(10000, 9));

        // TVA 0%
        $this->assertEquals(10000, Helper::calculateTTC(10000, 0));
    }

    public function testCalculateVAT(): void
    {
        // TVA 18%
        $this->assertEquals(1800, Helper::calculateVAT(10000, 18));

        // TVA 9%
        $this->assertEquals(900, Helper::calculateVAT(10000, 9));
    }

    public function testCalculateHT(): void
    {
        // Retrouver le HT depuis le TTC
        $ttc = 11800;
        $ht = Helper::calculateHT($ttc, 18);
        $this->assertEquals(10000, round($ht, 2));
    }

    public function testGetVatRate(): void
    {
        $this->assertEquals(18.0, Helper::getVatRate(Constants::TAX_TVA));
        $this->assertEquals(9.0, Helper::getVatRate(Constants::TAX_TVAB));
        $this->assertEquals(0.0, Helper::getVatRate(Constants::TAX_TVAC));
        $this->assertEquals(0.0, Helper::getVatRate(Constants::TAX_TVAD));
        $this->assertEquals(0.0, Helper::getVatRate('UNKNOWN'));
    }

    // ============================================
    // TESTS DE REMISE
    // ============================================

    public function testApplyDiscount(): void
    {
        $this->assertEquals(9000, Helper::applyDiscount(10000, 10));
        $this->assertEquals(5000, Helper::applyDiscount(10000, 50));
        $this->assertEquals(10000, Helper::applyDiscount(10000, 0));
        $this->assertEquals(0, Helper::applyDiscount(10000, 100));
    }

    public function testApplyDiscountWithInvalidPercentageThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Helper::applyDiscount(10000, 150);
    }

    public function testApplyDiscountWithNegativePercentageThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Helper::applyDiscount(10000, -10);
    }

    // ============================================
    // TESTS DE VALIDATION NCC
    // ============================================

    public function testIsValidNcc(): void
    {
        // Formats valides
        $this->assertTrue(Helper::isValidNcc('9502363N'));
        $this->assertTrue(Helper::isValidNcc('1234567A'));
        $this->assertTrue(Helper::isValidNcc('0000000Z'));

        // Formats invalides
        $this->assertFalse(Helper::isValidNcc('950236N'));   // 6 chiffres
        $this->assertFalse(Helper::isValidNcc('95023633'));  // pas de lettre
        $this->assertFalse(Helper::isValidNcc('9502363n'));  // lettre minuscule
        $this->assertFalse(Helper::isValidNcc('ABCDEFGH'));  // pas de chiffres
        $this->assertFalse(Helper::isValidNcc(''));          // vide
    }

    // ============================================
    // TESTS DE VALIDATION TELEPHONE
    // ============================================

    public function testIsValidPhone(): void
    {
        // Formats valides
        $this->assertTrue(Helper::isValidPhone('0709123456'));
        $this->assertTrue(Helper::isValidPhone('07 09 12 34 56'));
        $this->assertTrue(Helper::isValidPhone('07-09-12-34-56'));
        $this->assertTrue(Helper::isValidPhone('+2250709123456'));

        // Formats invalides
        $this->assertFalse(Helper::isValidPhone('070912'));     // trop court
        $this->assertFalse(Helper::isValidPhone(''));           // vide
    }

    // ============================================
    // TESTS DE FORMATAGE DE DATE
    // ============================================

    public function testFormatDateWithDateTime(): void
    {
        $date = new \DateTime('2025-01-15 14:30:00');
        $this->assertEquals('15/01/2025 14:30:00', Helper::formatDate($date));
    }

    public function testFormatDateWithString(): void
    {
        $this->assertEquals('15/01/2025 14:30:00', Helper::formatDate('2025-01-15 14:30:00'));
    }

    public function testFormatDateWithTimestamp(): void
    {
        $timestamp = strtotime('2025-01-15 14:30:00');
        $this->assertEquals('15/01/2025 14:30:00', Helper::formatDate($timestamp));
    }

    public function testFormatDateWithCustomFormat(): void
    {
        $date = new \DateTime('2025-01-15 14:30:00');
        $this->assertEquals('15-01-2025', Helper::formatDate($date, 'd-m-Y'));
    }

    // ============================================
    // TESTS DE GENERATION D'ID
    // ============================================

    public function testGenerateUniqueId(): void
    {
        $id1 = Helper::generateUniqueId();
        $id2 = Helper::generateUniqueId();

        $this->assertStringStartsWith('FNE-', $id1);
        $this->assertStringStartsWith('FNE-', $id2);
        $this->assertNotEquals($id1, $id2);
    }

    public function testGenerateUniqueIdWithCustomPrefix(): void
    {
        $id = Helper::generateUniqueId('INV');
        $this->assertStringStartsWith('INV-', $id);
    }

    // ============================================
    // TESTS DE CONVERSION DE DEVISE
    // ============================================

    public function testConvertCurrencyFromXOF(): void
    {
        // 655.957 XOF = 1 EUR
        $amountXOF = 655957;
        $rate = 655.957;

        $amountEUR = Helper::convertCurrency($amountXOF, $rate, true);
        $this->assertEquals(1000, round($amountEUR, 2));
    }

    public function testConvertCurrencyToXOF(): void
    {
        // 1 EUR = 655.957 XOF
        $amountEUR = 1000;
        $rate = 655.957;

        $amountXOF = Helper::convertCurrency($amountEUR, $rate, false);
        $this->assertEquals(655957, round($amountXOF, 2));
    }

    public function testConvertCurrencyWithInvalidRateThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Helper::convertCurrency(1000, 0);
    }

    // ============================================
    // TESTS D'EXTRACTION DE TOKEN
    // ============================================

    public function testExtractTokenFromUrl(): void
    {
        $url = 'http://54.247.95.108/fr/verification/019465c1-3f61-766c-9652-706e32dfb436';
        $token = Helper::extractTokenFromUrl($url);

        $this->assertEquals('019465c1-3f61-766c-9652-706e32dfb436', $token);
    }

    public function testExtractTokenFromUrlWithInvalidUrl(): void
    {
        $url = 'http://example.com/invalid/url';
        $token = Helper::extractTokenFromUrl($url);

        $this->assertNull($token);
    }

    public function testBuildVerificationUrl(): void
    {
        $token = '019465c1-3f61-766c-9652-706e32dfb436';

        // Mode test
        $testUrl = Helper::buildVerificationUrl($token, true);
        $this->assertEquals('http://54.247.95.108/fr/verification/019465c1-3f61-766c-9652-706e32dfb436', $testUrl);

        // Mode production
        $prodUrl = Helper::buildVerificationUrl($token, false, 'https://fne.dgi.gouv.ci');
        $this->assertEquals('https://fne.dgi.gouv.ci/fr/verification/019465c1-3f61-766c-9652-706e32dfb436', $prodUrl);
    }
}
