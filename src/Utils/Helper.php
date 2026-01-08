<?php

namespace DgiCi\FneSdk\Utils;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

/**
 * Classe utilitaire pour le SDK FNE
 *
 * Fournit des méthodes pour la génération de QR codes,
 * le formatage des montants et autres utilitaires.
 */
class Helper
{
    /**
     * Génère un QR Code à partir de l'URL de vérification FNE
     *
     * @param string $tokenUrl L'URL de vérification retournée par l'API FNE
     * @param int $size Taille du QR code en pixels (défaut: 300)
     * @param string $format Format de sortie ('png' ou 'svg')
     * @return string Contenu binaire de l'image ou SVG
     * @throws FneException Si la génération échoue
     */
    public static function generateQrCode(string $tokenUrl, int $size = 300, string $format = 'png'): string
    {
        if (empty($tokenUrl)) {
            throw new \InvalidArgumentException('L\'URL du token est requise pour générer le QR code');
        }

        if (!class_exists(Builder::class)) {
            throw new \InvalidArgumentException(
                'La librairie endroid/qr-code est requise pour générer les QR codes. ' .
                'Installez-la avec: composer require endroid/qr-code'
            );
        }

        try {
            $writer = $format === 'svg' ? new SvgWriter() : new PngWriter();

            $result = Builder::create()
                ->writer($writer)
                ->data($tokenUrl)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(ErrorCorrectionLevel::High)
                ->size($size)
                ->margin(10)
                ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
                ->build();

            return $result->getString();
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Erreur lors de la génération du QR code: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Génère un QR Code et le sauvegarde dans un fichier
     *
     * @param string $tokenUrl L'URL de vérification FNE
     * @param string $filePath Chemin du fichier de destination
     * @param int $size Taille du QR code en pixels
     * @return bool True si le fichier a été créé
     * @throws FneException Si la génération ou la sauvegarde échoue
     */
    public static function saveQrCode(string $tokenUrl, string $filePath, int $size = 300): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $format = $extension === 'svg' ? 'svg' : 'png';

        $content = self::generateQrCode($tokenUrl, $size, $format);

        $result = file_put_contents($filePath, $content);

        if ($result === false) {
            throw new \InvalidArgumentException('Impossible de sauvegarder le QR code dans: ' . $filePath);
        }

        return true;
    }

    /**
     * Génère un QR Code en base64 pour l'intégration HTML
     *
     * @param string $tokenUrl L'URL de vérification FNE
     * @param int $size Taille du QR code en pixels
     * @param string $format Format de sortie ('png' ou 'svg')
     * @return string Data URI (data:image/png;base64,...)
     * @throws FneException Si la génération échoue
     */
    public static function generateQrCodeBase64(string $tokenUrl, int $size = 300, string $format = 'png'): string
    {
        $content = self::generateQrCode($tokenUrl, $size, $format);
        $mimeType = $format === 'svg' ? 'image/svg+xml' : 'image/png';

        return 'data:' . $mimeType . ';base64,' . base64_encode($content);
    }

    /**
     * Formate un montant en FCFA selon les conventions ivoiriennes
     *
     * @param float $amount Le montant à formater
     * @param bool $includeSymbol Inclure le symbole FCFA
     * @return string Montant formaté (ex: "1 250 000 FCFA")
     */
    public static function formatAmount(float $amount, bool $includeSymbol = true): string
    {
        $formatted = number_format($amount, 0, ',', ' ');

        return $includeSymbol ? $formatted . ' FCFA' : $formatted;
    }

    /**
     * Formate un montant avec décimales
     *
     * @param float $amount Le montant à formater
     * @param int $decimals Nombre de décimales
     * @param bool $includeSymbol Inclure le symbole FCFA
     * @return string Montant formaté
     */
    public static function formatAmountWithDecimals(float $amount, int $decimals = 2, bool $includeSymbol = true): string
    {
        $formatted = number_format($amount, $decimals, ',', ' ');

        return $includeSymbol ? $formatted . ' FCFA' : $formatted;
    }

    /**
     * Calcule le montant TTC à partir du HT et du taux de TVA
     *
     * @param float $amountHT Montant hors taxes
     * @param float $vatRate Taux de TVA en pourcentage (ex: 18 pour 18%)
     * @return float Montant TTC
     */
    public static function calculateTTC(float $amountHT, float $vatRate): float
    {
        return $amountHT * (1 + $vatRate / 100);
    }

    /**
     * Calcule le montant de la TVA
     *
     * @param float $amountHT Montant hors taxes
     * @param float $vatRate Taux de TVA en pourcentage
     * @return float Montant de la TVA
     */
    public static function calculateVAT(float $amountHT, float $vatRate): float
    {
        return $amountHT * ($vatRate / 100);
    }

    /**
     * Calcule le montant HT à partir du TTC
     *
     * @param float $amountTTC Montant TTC
     * @param float $vatRate Taux de TVA en pourcentage
     * @return float Montant HT
     */
    public static function calculateHT(float $amountTTC, float $vatRate): float
    {
        return $amountTTC / (1 + $vatRate / 100);
    }

    /**
     * Applique une remise à un montant
     *
     * @param float $amount Montant original
     * @param float $discountPercent Pourcentage de remise
     * @return float Montant après remise
     */
    public static function applyDiscount(float $amount, float $discountPercent): float
    {
        if ($discountPercent < 0 || $discountPercent > 100) {
            throw new \InvalidArgumentException('Le pourcentage de remise doit être entre 0 et 100');
        }

        return $amount * (1 - $discountPercent / 100);
    }

    /**
     * Retourne le taux de TVA selon le type
     *
     * @param string $taxType Type de TVA (TVA, TVAB, TVAC, TVAD)
     * @return float Taux en pourcentage
     */
    public static function getVatRate(string $taxType): float
    {
        $rates = [
            Constants::TAX_TVA => 18.0,   // TVA normale
            Constants::TAX_TVAB => 9.0,   // TVA réduite
            Constants::TAX_TVAC => 0.0,   // Exonération conventionnelle
            Constants::TAX_TVAD => 0.0,   // Exonération légale
        ];

        return $rates[$taxType] ?? 0.0;
    }

    /**
     * Valide le format d'un NCC (Numéro de Compte Contribuable)
     *
     * @param string $ncc Le NCC à valider
     * @return bool True si le format est valide
     */
    public static function isValidNcc(string $ncc): bool
    {
        // Format: 7 chiffres suivis d'une lettre majuscule
        return preg_match('/^\d{7}[A-Z]$/', $ncc) === 1;
    }

    /**
     * Valide le format d'un numéro de téléphone ivoirien
     *
     * @param string $phone Le numéro à valider
     * @return bool True si le format est valide
     */
    public static function isValidPhone(string $phone): bool
    {
        // Supprime les espaces et tirets
        $cleaned = preg_replace('/[\s\-]/', '', $phone);

        // Accepte les formats: 0X XX XX XX XX ou +225 XX XX XX XX XX
        return preg_match('/^(0|\+225)?\d{10}$/', $cleaned) === 1;
    }

    /**
     * Formate une date selon le format ivoirien
     *
     * @param \DateTimeInterface|string|int $date Date à formater
     * @param string $format Format de sortie
     * @return string Date formatée
     */
    public static function formatDate($date, string $format = 'd/m/Y H:i:s'): string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format($format);
        }

        if (is_numeric($date)) {
            return date($format, (int) $date);
        }

        $dateTime = new \DateTime($date);
        return $dateTime->format($format);
    }

    /**
     * Génère un identifiant unique pour le suivi interne
     *
     * @param string $prefix Préfixe optionnel
     * @return string Identifiant unique
     */
    public static function generateUniqueId(string $prefix = 'FNE'): string
    {
        return $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -8));
    }

    /**
     * Convertit un montant d'une devise vers une autre
     *
     * @param float $amount Montant à convertir
     * @param float $exchangeRate Taux de change
     * @param bool $fromXOF True si conversion de XOF vers devise, false si inverse
     * @return float Montant converti
     */
    public static function convertCurrency(float $amount, float $exchangeRate, bool $fromXOF = true): float
    {
        if ($exchangeRate <= 0) {
            throw new \InvalidArgumentException('Le taux de change doit être supérieur à 0');
        }

        return $fromXOF ? $amount / $exchangeRate : $amount * $exchangeRate;
    }

    /**
     * Extrait le token UUID d'une URL de vérification FNE
     *
     * @param string $tokenUrl L'URL complète
     * @return string|null Le token UUID ou null si non trouvé
     */
    public static function extractTokenFromUrl(string $tokenUrl): ?string
    {
        // Format: http://54.247.95.108/fr/verification/019465c1-3f61-766c-9652-706e32dfb436
        if (preg_match('/\/verification\/([a-f0-9\-]{36})$/i', $tokenUrl, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Construit l'URL de vérification à partir d'un token
     *
     * @param string $token Le token UUID
     * @param bool $isTestMode Mode test ou production
     * @param string|null $productionUrl URL de production (optionnel)
     * @return string URL de vérification complète
     */
    public static function buildVerificationUrl(string $token, bool $isTestMode = true, ?string $productionUrl = null): string
    {
        $baseUrl = $isTestMode ? 'http://54.247.95.108' : ($productionUrl ?? '');

        return rtrim($baseUrl, '/') . '/fr/verification/' . $token;
    }
}
