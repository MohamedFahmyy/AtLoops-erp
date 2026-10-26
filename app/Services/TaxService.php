<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class TaxService
{
    /**
     * Standard tax rate rules by country / tax jurisdiction.
     */
    private const JURISDICTION_RATES = [
        'egypt' => 14.00,
        'saudi arabia' => 15.00,
        'ksa' => 15.00,
        'united arab emirates' => 5.00,
        'uae' => 5.00,
        'bahrain' => 10.00,
        'oman' => 5.00,
        'qatar' => 0.00,
        'kuwait' => 0.00,
        'united states' => 0.00,
        'usa' => 0.00,
        'united kingdom' => 0.00, // Zero-rated cross-border export from Egypt
        'germany' => 0.00,
        'france' => 0.00,
    ];

    /**
     * Resolve applicable tax rate based on company location and transaction nature.
     *
     * Under international tax and Egyptian VAT Law No. 67/2016:
     * - Domestic IT services within Egypt are subject to 14% standard VAT.
     * - Exported software and IT services to foreign clients outside Egypt are 0% (zero-rated export).
     * - Domestic Saudi/UAE billing reflects respective regional VAT rates where registered.
     *
     * @param string|null $country Company country
     * @param bool $isExport Whether the service is a cross-border export
     * @param string|null $connection Database connection name
     * @return float Applicable tax rate percentage
     */
    public static function resolveTaxRate(?string $country = null, bool $isExport = false, ?string $connection = null): float
    {
        if ($isExport) {
            return 0.00;
        }

        $normalizedCountry = strtolower(trim((string)$country));

        if (empty($normalizedCountry)) {
            // Default to domestic Egyptian rate if country unknown
            return 14.00;
        }

        if (array_key_exists($normalizedCountry, self::JURISDICTION_RATES)) {
            return (float)self::JURISDICTION_RATES[$normalizedCountry];
        }

        // For any other foreign country without local nexus, cross-border IT service export is 0.00%
        if ($normalizedCountry !== 'egypt') {
            return 0.00;
        }

        return 14.00;
    }

    /**
     * Resolve tax rate for a specific company by its ID.
     */
    public static function resolveForCompany(int $companyId, ?string $connection = null): float
    {
        try {
            $pdo = Database::pdo($connection);
            $stmt = $pdo->prepare("SELECT country FROM `companies` WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $companyId]);
            $country = (string)$stmt->fetchColumn();

            return self::resolveTaxRate($country, false, $connection);
        } catch (\Throwable $e) {
            return 14.00;
        }
    }
}
