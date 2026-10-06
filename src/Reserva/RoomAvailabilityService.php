<?php
declare(strict_types=1);

namespace App\Reserva;

use DateTimeImmutable;
use mysqli;
use RuntimeException;

final class RoomAvailabilityService
{
    public static function validar(
        mysqli $connection,
        int $roomId,
        DateTimeImmutable $checkIn,
        DateTimeImmutable $checkOut,
        ?int $currentReservationId = null,
        bool $roomAlreadyAssigned = false
    ): void {
        $room = $connection->prepare(
            'SELECT est_hab FROM habitacion WHERE cod_hab = ? LIMIT 1 FOR UPDATE'
        );
        if (!$room) {
            throw new RuntimeException('No se pudo validar la disponibilidad de la habitación.');
        }
        if (!$room->bind_param('i', $roomId) || !$room->execute()) {
            $room->close();
            throw new RuntimeException('No se pudo validar la disponibilidad de la habitación.');
        }
        $roomResult = $room->get_result();
        $roomData = $roomResult->fetch_assoc();
        $room->close();

        if (!$roomData) {
            throw new RuntimeException('habitacion_no_existe');
        }
        if (
            !$roomAlreadyAssigned
            && in_array((string) $roomData['est_hab'], ['Mantenimiento', 'Sucia'], true)
        ) {
            throw new RuntimeException('habitacion_no_disponible');
        }

        $sql = "SELECT r.cod_res
                FROM detalle d
                INNER JOIN reservas r ON r.cod_res = d.cod_res_det
                WHERE d.cod_hab_det = ?
                  AND r.est_res NOT IN ('Cancelada', 'Cancelado', 'Finalizada')
                  AND ? < r.fec_sal_res
                  AND ? > r.fec_ent_res";
        if ($currentReservationId !== null) {
            $sql .= ' AND r.cod_res <> ?';
        }
        $sql .= ' LIMIT 1';

        $overlap = $connection->prepare($sql);
        if (!$overlap) {
            throw new RuntimeException('No se pudo comprobar la disponibilidad de la habitación.');
        }
        $checkInValue = $checkIn->format('Y-m-d H:i:s');
        $checkOutValue = $checkOut->format('Y-m-d H:i:s');
        if ($currentReservationId === null) {
            $parametrosValidos = $overlap->bind_param('iss', $roomId, $checkInValue, $checkOutValue);
        } else {
            $parametrosValidos = $overlap->bind_param('issi', $roomId, $checkInValue, $checkOutValue, $currentReservationId);
        }
        if (!$parametrosValidos || !$overlap->execute()) {
            $overlap->close();
            throw new RuntimeException('No se pudo comprobar la disponibilidad de la habitación.');
        }
        $overlap->store_result();
        $hasOverlap = $overlap->num_rows > 0;
        $overlap->close();

        if ($hasOverlap) {
            throw new RuntimeException('habitacion_reservada');
        }
    }
}
