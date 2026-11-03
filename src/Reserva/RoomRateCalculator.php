<?php
declare(strict_types=1);

namespace App\Reserva;

use InvalidArgumentException;

final class RoomRateCalculator
{
    public const STANDARD_OCCUPANCY = 4;
    public const ADDITIONAL_CHILD_SUPPLEMENT = 25000.0;
    public const FREE_CHILD_MAX_AGE = 2;
    public const MAX_CHILD_AGE = 12;

    public static function standardNightlyRate(string $roomType, float $configuredPrice): float
    {
        $type = mb_strtolower(trim($roomType), 'UTF-8');
        if (str_contains($type, 'sencilla') || str_contains($type, 'simple') || str_contains($type, 'individual')) {
            return $configuredPrice === 50000.0 ? 80000.0 : max(0.0, $configuredPrice);
        }

        return max(0.0, $configuredPrice);
    }

    /** @param list<int> $childAges */
    public static function nightlyRate(
        string $roomType,
        float $configuredPrice,
        int $adults,
        array $childAges,
        float $additionalChildSupplement = self::ADDITIONAL_CHILD_SUPPLEMENT
    ): float {
        if ($adults < 1) {
            throw new InvalidArgumentException('La habitación necesita al menos un adulto.');
        }

        $type = mb_strtolower(trim($roomType), 'UTF-8');
        $isSingleRoom = str_contains($type, 'sencilla') || str_contains($type, 'simple') || str_contains($type, 'individual');
        foreach ($childAges as $age) {
            if ($age < 0 || $age > self::MAX_CHILD_AGE) {
                throw new InvalidArgumentException('La edad de cada niño debe estar entre 0 y 12 años.');
            }
        }

        if ($isSingleRoom && $configuredPrice === 50000.0 && $adults + count($childAges) === 1) {
            return 50000.0;
        }
        if (!is_finite($additionalChildSupplement) || $additionalChildSupplement < 0) {
            throw new InvalidArgumentException('El suplemento de niño adicional no es válido.');
        }

        $standardRate = self::standardNightlyRate($roomType, $configuredPrice);
        $includedChildCount = max(0, self::STANDARD_OCCUPANCY - $adults);
        $supplement = 0.0;
        foreach (array_slice($childAges, $includedChildCount) as $age) {
            if ($age > self::FREE_CHILD_MAX_AGE) {
                $supplement += $additionalChildSupplement;
            }
        }

        return round($standardRate + $supplement, 2);
    }
}
