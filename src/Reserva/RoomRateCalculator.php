<?php
declare(strict_types=1);

namespace App\Reserva;

use InvalidArgumentException;

final class RoomRateCalculator
{
    /** Personas cubiertas por la tarifa de la habitación; desde la siguiente se cobra el adicional por persona. */
    public const STANDARD_OCCUPANCY = 4;
    public const ADDITIONAL_PERSON_SUPPLEMENT = 25000.0;

    public static function standardNightlyRate(string $roomType, float $configuredPrice): float
    {
        $type = mb_strtolower(trim($roomType), 'UTF-8');
        if (str_contains($type, 'sencilla') || str_contains($type, 'simple') || str_contains($type, 'individual')) {
            return $configuredPrice === 50000.0 ? 80000.0 : max(0.0, $configuredPrice);
        }

        return max(0.0, $configuredPrice);
    }

    /** Tarifa por noche de una habitación según la cantidad de personas que se alojan en ella. */
    public static function nightlyRate(
        string $roomType,
        float $configuredPrice,
        int $persons,
        float $additionalPersonSupplement = self::ADDITIONAL_PERSON_SUPPLEMENT
    ): float {
        if ($persons < 1) {
            throw new InvalidArgumentException('La habitación necesita al menos una persona.');
        }

        $type = mb_strtolower(trim($roomType), 'UTF-8');
        $isSingleRoom = str_contains($type, 'sencilla') || str_contains($type, 'simple') || str_contains($type, 'individual');
        if ($isSingleRoom && $configuredPrice === 50000.0 && $persons === 1) {
            return 50000.0;
        }
        if (!is_finite($additionalPersonSupplement) || $additionalPersonSupplement < 0) {
            throw new InvalidArgumentException('El cargo por persona adicional no es válido.');
        }

        $standardRate = self::standardNightlyRate($roomType, $configuredPrice);
        $additionalPersons = max(0, $persons - self::STANDARD_OCCUPANCY);

        return round($standardRate + $additionalPersons * $additionalPersonSupplement, 2);
    }
}