<?php
declare(strict_types=1);

namespace App\Reserva;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final class RoomRateCalculator
{
    /** Tarifa por noche de la habitación: exactamente el precio que configuró el administrador. */
    public static function standardNightlyRate(string $roomType, float $configuredPrice): float
    {
        return max(0.0, $configuredPrice);
    }

    /** Fecha de hoy (Bogotá) en formato Y-m-d. */
    public static function today(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('America/Bogota')))->format('Y-m-d');
    }

    /**
     * Precio de oferta vigente hoy, o null si no hay oferta activa.
     * La oferta está activa si tiene precio y hoy cae dentro de su rango (fechas opcionales).
     */
    public static function activeOfferPrice(?float $offerPrice, ?string $offerStart, ?string $offerEnd, ?string $today = null): ?float
    {
        if ($offerPrice === null || $offerPrice <= 0) {
            return null;
        }
        $today ??= self::today();
        if ($offerStart !== null && $offerStart !== '' && $offerStart > $today) {
            return null;
        }
        if ($offerEnd !== null && $offerEnd !== '' && $offerEnd < $today) {
            return null;
        }

        return $offerPrice;
    }

    /**
     * Expresión SQL con el precio vigente de la habitación (oferta activa o, si no, el precio del admin).
     * Requiere las columnas oferta_precio, oferta_inicio y oferta_fin (ver RoomRateSchemaMigrator::ensureOffers).
     */
    public static function effectivePriceSql(string $alias = ''): string
    {
        $p = $alias === '' ? '' : rtrim($alias, '.') . '.';
        $today = self::today();

        return "CASE WHEN {$p}oferta_precio > 0"
            . " AND ({$p}oferta_inicio IS NULL OR {$p}oferta_inicio <= '{$today}')"
            . " AND ({$p}oferta_fin IS NULL OR {$p}oferta_fin >= '{$today}')"
            . " THEN {$p}oferta_precio"
            . " ELSE COALESCE(NULLIF({$p}pre_hab, 0), {$p}precio_hab, 0) END";
    }

    /** Tarifa por noche de una habitación: el precio del admin (o la oferta vigente), sin recargos por persona. */
    public static function nightlyRate(string $roomType, float $configuredPrice, int $persons): float
    {
        if ($persons < 1) {
            throw new InvalidArgumentException('La habitación necesita al menos una persona.');
        }

        return round(self::standardNightlyRate($roomType, $configuredPrice), 2);
    }
}