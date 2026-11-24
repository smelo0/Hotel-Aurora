<?php
declare(strict_types=1);

namespace App\Pago;

use RuntimeException;

/**
 * Pasarela de pago SIMULADA (tarjeta y transferencia bancaria) para probar el flujo de reserva con
 * abono del 50 % o pago del 100 % sin dinero real.
 *
 * - No guarda ni registra el número de tarjeta ni el CVC: solo se conserva la marca y los últimos 4 dígitos
 *   dentro de la referencia del pago (SIM-...).
 * - Tarjetas de prueba: cualquier número válido (algoritmo de Luhn) se aprueba, salvo las de RECHAZOS.
 */
final class PagoSimulado
{
    /** Tarjetas de prueba que el "banco" rechaza, con el motivo que se muestra al huésped. */
    public const RECHAZOS = [
        '4000000000000002' => 'El banco rechazó la tarjeta.',
        '4000000000009995' => 'Fondos insuficientes.',
        '4000000000000069' => 'La tarjeta está vencida.',
    ];

    public const BANCOS = [
        'Bancolombia',
        'Davivienda',
        'Banco de Bogotá',
        'BBVA Colombia',
        'Nequi',
        'Banco de prueba (rechaza)',
    ];

    /**
     * @param array<string,mixed> $datos tarjeta: numero, vencimiento (MM/AA), cvc, titular | transferencia: banco
     * @return array{referencia:string,detalle:string}
     * @throws RuntimeException con un mensaje listo para mostrar al huésped
     */
    public static function procesar(string $tipo, array $datos): array
    {
        return match ($tipo) {
            'tarjeta' => self::tarjeta($datos),
            'transferencia' => self::transferencia($datos),
            default => throw new RuntimeException('Método de pago simulado no válido.'),
        };
    }

    private static function tarjeta(array $datos): array
    {
        $numero = preg_replace('/\D+/', '', (string) ($datos['numero'] ?? '')) ?? '';
        $titular = trim((string) ($datos['titular'] ?? ''));
        $cvc = trim((string) ($datos['cvc'] ?? ''));
        $vencimiento = trim((string) ($datos['vencimiento'] ?? ''));

        if (mb_strlen($titular) < 3 || mb_strlen($titular) > 60 || preg_match('/[\x00-\x1F\x7F]/u', $titular) === 1) {
            throw new RuntimeException('Escribe el nombre del titular tal como aparece en la tarjeta.');
        }
        if (strlen($numero) < 13 || strlen($numero) > 19 || !self::luhn($numero)) {
            throw new RuntimeException('El número de tarjeta no es válido.');
        }
        $marca = self::marca($numero);
        $largoCvc = $marca === 'AMEX' ? 4 : 3;
        if (preg_match('/^\d{' . $largoCvc . '}$/', $cvc) !== 1) {
            throw new RuntimeException('El CVC debe tener ' . $largoCvc . ' dígitos.');
        }
        if (preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $vencimiento, $m) !== 1) {
            throw new RuntimeException('La fecha de expiración debe tener el formato MM/AA.');
        }
        $finDeMes = \DateTimeImmutable::createFromFormat('!Y-m-d', '20' . $m[2] . '-' . $m[1] . '-01');
        if ($finDeMes === false || $finDeMes->modify('last day of this month 23:59:59') < new \DateTimeImmutable('now')) {
            throw new RuntimeException('La tarjeta está vencida.');
        }
        if (isset(self::RECHAZOS[$numero])) {
            throw new RuntimeException(self::RECHAZOS[$numero]);
        }

        $ultimos = substr($numero, -4);
        return [
            'referencia' => 'SIM-TC-' . $marca . '-' . $ultimos . '-' . strtoupper(bin2hex(random_bytes(3))),
            'detalle' => 'Tarjeta ' . $marca . ' terminada en ' . $ultimos,
        ];
    }

    private static function transferencia(array $datos): array
    {
        $banco = trim((string) ($datos['banco'] ?? ''));
        if (!in_array($banco, self::BANCOS, true)) {
            throw new RuntimeException('Selecciona un banco para la transferencia.');
        }
        if ($banco === 'Banco de prueba (rechaza)') {
            throw new RuntimeException('La transferencia fue rechazada por el banco.');
        }
        return [
            'referencia' => 'SIM-TRF-' . strtoupper(bin2hex(random_bytes(5))),
            'detalle' => 'Transferencia desde ' . $banco,
        ];
    }

    private static function luhn(string $numero): bool
    {
        $suma = 0;
        $alterno = false;
        for ($i = strlen($numero) - 1; $i >= 0; $i--) {
            $d = (int) $numero[$i];
            if ($alterno) {
                $d *= 2;
                if ($d > 9) {
                    $d -= 9;
                }
            }
            $suma += $d;
            $alterno = !$alterno;
        }
        return $suma % 10 === 0;
    }

    private static function marca(string $numero): string
    {
        if (preg_match('/^3[47]/', $numero) === 1) {
            return 'AMEX';
        }
        if (preg_match('/^4/', $numero) === 1) {
            return 'VISA';
        }
        if (preg_match('/^(5[1-5]|2(2[2-9]|[3-6]\d|7[01]|720))/', $numero) === 1) {
            return 'MC';
        }
        return 'TARJETA';
    }
}