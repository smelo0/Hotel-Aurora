<?php
declare(strict_types=1);

namespace App\Usuario;

use mysqli;

final class PortalRepository
{
    public function findReservationForExperience(mysqli $connection, int $userId, ?string $date): ?int
    {
        if ($date === null) {
            $statement = $connection->prepare(
                "SELECT cod_res FROM reservas
                 WHERE id_usu_res = ?
                   AND est_res NOT IN ('Cancelada', 'Cancelado')
                 ORDER BY fec_ent_res DESC, cod_res DESC
                 LIMIT 1"
            );
            $statement->bind_param('i', $userId);
        } else {
            $statement = $connection->prepare(
                "SELECT cod_res FROM reservas
                 WHERE id_usu_res = ?
                   AND est_res NOT IN ('Cancelada', 'Cancelado')
                   AND DATE(fec_ent_res) <= ?
                   AND DATE(fec_sal_res) > ?
                 ORDER BY fec_ent_res DESC, cod_res DESC
                 LIMIT 1"
            );
            $statement->bind_param('iss', $userId, $date, $date);
        }

        $statement->execute();
        $statement->bind_result($reservationId);
        $found = $statement->fetch();
        $statement->close();

        return $found ? (int) $reservationId : null;
    }

    public function fetchExperienceForActivity(mysqli $connection, int $experienceId): ?array
    {
        $statement = $connection->prepare(
            'SELECT nombre, opcion_1, opcion_2, opcion_3, precio_opcion_1, precio_opcion_2, precio_opcion_3, horarios_json
             FROM experiencias WHERE id = ?'
        );
        $statement->bind_param('i', $experienceId);
        $statement->execute();
        $experience = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();

        return $experience;
    }

    public function insertCustomActivity(
        mysqli $connection,
        string $activity,
        string $selectionsJson,
        string $name,
        string $email,
        int $userId,
        string $pricesJson,
        ?int $reservationId
    ): void {
        $statement = $connection->prepare(
            "INSERT INTO agenda_actividad (actividad, opcion_actividad, selecciones_personas_json, fecha_agenda, hora_agenda,
                                           nombre_contacto, correo_contacto, id_usu_agenda, monto_experiencia, precios_personas_json, cod_res_agenda)
             VALUES (?, ?, ?, NULL, NULL, ?, ?, ?, NULL, ?, ?)"
        );
        $option = 'Personalizada';
        $statement->bind_param(
            'sssssisi',
            $activity,
            $option,
            $selectionsJson,
            $name,
            $email,
            $userId,
            $pricesJson,
            $reservationId
        );
        $statement->execute();
        $statement->close();
    }

    public function insertScheduledActivity(
        mysqli $connection,
        string $activity,
        string $optionSummary,
        string $selectionsJson,
        string $date,
        string $time,
        string $name,
        string $email,
        int $userId,
        ?string $amount,
        string $pricesJson,
        int $reservationId
    ): void {
        $statement = $connection->prepare(
            "INSERT INTO agenda_actividad (actividad, opcion_actividad, selecciones_personas_json, fecha_agenda, hora_agenda,
                                           nombre_contacto, correo_contacto, id_usu_agenda, monto_experiencia, precios_personas_json, cod_res_agenda)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $statement->bind_param(
            'sssssssissi',
            $activity,
            $optionSummary,
            $selectionsJson,
            $date,
            $time,
            $name,
            $email,
            $userId,
            $amount,
            $pricesJson,
            $reservationId
        );
        $statement->execute();
        $statement->close();
    }

    public function fetchRoomCatalog(mysqli $connection): array
    {
        $statement = $connection->prepare(
            "SELECT h.cod_hab, h.num_hab, h.tipo_hab, h.pre_hab, h.precio_hab, h.est_hab, h.obs_hab
             FROM habitacion h
             WHERE h.est_hab NOT IN ('Mantenimiento', 'Sucia')
             ORDER BY h.num_hab ASC
             LIMIT 20"
        );
        $statement->execute();
        $statement->bind_result($roomId, $roomNumber, $roomType, $primaryPrice, $secondaryPrice, $status, $description);

        $rooms = [];
        while ($statement->fetch()) {
            $rooms[] = [
                'cod_hab' => (int) $roomId,
                'num_hab' => (int) $roomNumber,
                'tipo_hab' => (string) $roomType,
                'pre_hab' => (float) ($primaryPrice ?: $secondaryPrice ?: 0),
                'est_hab' => (string) $status,
                'obs_hab' => $description !== null && trim((string) $description) !== ''
                    ? (string) $description
                    : 'Habitación premium preparada para una estadía cómoda, luminosa y serena.',
            ];
        }

        $statement->close();
        return $rooms;
    }

    public function fetchAvailableRoomIds(mysqli $connection, string $checkin, string $checkout): array
    {
        $statement = $connection->prepare(
            "SELECT h.cod_hab
             FROM habitacion h
             WHERE h.est_hab NOT IN ('Mantenimiento', 'Sucia')
               AND NOT EXISTS (
                   SELECT 1
                   FROM detalle d
                   INNER JOIN reservas r ON r.cod_res = d.cod_res_det
                   WHERE d.cod_hab_det = h.cod_hab
                     AND r.est_res NOT IN ('Cancelada', 'Cancelado', 'Finalizada')
                     AND ? < r.fec_sal_res
                     AND ? > r.fec_ent_res
               )
             ORDER BY h.num_hab ASC
             LIMIT 20"
        );
        $statement->bind_param('ss', $checkin, $checkout);
        $statement->execute();
        $statement->bind_result($roomId);

        $availableIds = [];
        while ($statement->fetch()) {
            $availableIds[] = (int) $roomId;
        }

        $statement->close();
        return $availableIds;
    }

    public function fetchReservationStays(mysqli $connection, int $userId): array
    {
        $statement = $connection->prepare(
            "SELECT DATE(fec_ent_res) AS fecha_inicio, DATE(fec_sal_res) AS fecha_fin
             FROM reservas
             WHERE id_usu_res = ?
               AND est_res NOT IN ('Cancelada', 'Cancelado')
               AND DATE(fec_sal_res) > CURDATE()
             ORDER BY fec_ent_res ASC"
        );
        $statement->bind_param('i', $userId);
        $statement->execute();
        $result = $statement->get_result();

        $stays = [];
        while ($row = $result->fetch_assoc()) {
            $stays[] = [
                'inicio' => (string) $row['fecha_inicio'],
                'fin' => (string) $row['fecha_fin'],
            ];
        }

        $statement->close();
        return $stays;
    }

    public function fetchReservationHistory(mysqli $connection, int $userId): array
    {
        $statement = $connection->prepare(
            "SELECT r.cod_res, r.fec_ent_res, r.fec_sal_res, r.est_res, r.not_res,
                    h.num_hab, h.tipo_hab
             FROM reservas r
             LEFT JOIN detalle d ON d.cod_res_det = r.cod_res
             LEFT JOIN habitacion h ON h.cod_hab = d.cod_hab_det
             WHERE r.id_usu_res = ?
             ORDER BY r.fec_ent_res DESC"
        );
        $statement->bind_param('i', $userId);
        $statement->execute();
        $result = $statement->get_result();
        $reservations = [];
        while ($row = $result->fetch_assoc()) {
            $reservations[] = $row;
        }

        $statement->close();
        return $reservations;
    }
}
