<?php
declare(strict_types=1);

namespace App\Usuario;

use App\Reserva\RoomRateCalculator;
use mysqli;
use RuntimeException;

require_once __DIR__ . '/../../configuracion/capacidad_habitaciones.php';

final class PortalRepository
{
    public function fetchGuestContact(mysqli $connection, int $userId): ?array
    {
        $statement = $connection->prepare(
            'SELECT nom_usu, corr_usu FROM usuario WHERE id_usu = ? LIMIT 1'
        );
        if (!$statement) {
            throw new RuntimeException('No se pudieron consultar los datos de la cuenta.');
        }
        $statement->bind_param('i', $userId);
        $statement->execute();
        $contact = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();

        return $contact;
    }

    public function findReservationForExperience(mysqli $connection, int $userId, string $date, bool $forUpdate = false): ?int
    {
        $sql = "SELECT cod_res FROM reservas
                WHERE id_usu_res = ?
                  AND LOWER(TRIM(COALESCE(est_res, ''))) IN ('pendiente', 'confirmada', 'en casa')
                  AND DATE(fec_ent_res) <= ?
                  AND DATE(fec_sal_res) > ?
                ORDER BY fec_ent_res DESC, cod_res DESC
                LIMIT 1";
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }
        $statement = $connection->prepare($sql);
        $statement->bind_param('iss', $userId, $date, $date);

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
    ): bool {
        $statement = $connection->prepare(
            "INSERT INTO agenda_actividad (actividad, opcion_actividad, selecciones_personas_json, fecha_agenda, hora_agenda,
                                           nombre_contacto, correo_contacto, id_usu_agenda, monto_experiencia, precios_personas_json, cod_res_agenda)
             SELECT ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
             FROM reservas r
             WHERE r.cod_res = ?
               AND r.id_usu_res = ?
               AND LOWER(TRIM(COALESCE(r.est_res, ''))) IN ('pendiente', 'confirmada', 'en casa')
               AND DATE(r.fec_ent_res) <= ?
               AND DATE(r.fec_sal_res) > ?"
        );
        $statement->bind_param(
            'sssssssissiiiss',
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
            $reservationId,
            $reservationId,
            $userId,
            $date,
            $date
        );
        $statement->execute();
        $inserted = $statement->affected_rows === 1;
        $statement->close();

        return $inserted;
    }

    public function insertReceptionScheduledActivity(
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
        ?int $reservationId
    ): bool {
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
        $inserted = $statement->affected_rows === 1;
        $statement->close();

        return $inserted;
    }

    public function fetchRoomCatalog(mysqli $connection): array
    {
        $statement = $connection->prepare(
            "SELECT h.cod_hab, h.num_hab, h.tipo_hab, h.pre_hab, h.precio_hab, h.est_hab, h.des_hab, h.img_hab, h.car_hab
             FROM habitacion h
             WHERE LOWER(TRIM(COALESCE(h.est_hab, ''))) NOT IN ('mantenimiento', 'sucia')
             ORDER BY h.num_hab ASC"
        );
        if (!$statement) {
            return [];
        }

        $statement->execute();
        $statement->bind_result($roomId, $roomNumber, $roomType, $primaryPrice, $secondaryPrice, $status, $description, $image, $amenitiesJson);

        $rooms = [];
        while ($statement->fetch()) {
            $amenities = json_decode((string) $amenitiesJson, true);
            $amenities = is_array($amenities)
                ? array_values(array_filter(array_map(static fn($item): string => trim((string) $item), $amenities), static fn(string $item): bool => $item !== ''))
                : [];

            $configuredPrice = (float) ($primaryPrice ?: $secondaryPrice ?: 0);
            $isSingleRoom = preg_match('/sencilla|simple|individual/i', (string) $roomType) === 1;
            $finalPrice = RoomRateCalculator::standardNightlyRate((string) $roomType, $configuredPrice);

            $rooms[] = [
                'img_hab' => $image !== null && trim((string) $image) !== '' ? (string) $image : null,
                'car_hab' => $amenities,
                'cod_hab' => (int) $roomId,
                'id'      => (int) $roomId,
                'num_hab' => (int) $roomNumber,
                'numero'  => (int) $roomNumber,
                'tipo_hab' => (string) $roomType,
                'tipo'     => (string) $roomType,
                'pre_hab'  => $finalPrice,
                'precio_hab' => $finalPrice,
                'precio'   => $finalPrice,
                'precio_una_persona' => $isSingleRoom && $configuredPrice === 50000.0 ? 50000.0 : null,
                'est_hab'  => (string) ($status ?: 'Disponible'),
                'obs_hab'  => $description !== null && trim((string) $description) !== ''
                    ? (string) $description
                    : 'Habitación premium preparada para una estadía cómoda, luminosa y serena.',
            ];
        }

        $statement->close();
        $today = (new \DateTimeImmutable('now', new \DateTimeZone('America/Bogota')))->format('Y-m-d');
        $reservationsStatement = $connection->prepare(
            "SELECT d.cod_hab_det, DATE(r.fec_ent_res), DATE(r.fec_sal_res)
             FROM detalle d
             INNER JOIN reservas r ON r.cod_res = d.cod_res_det
             WHERE LOWER(TRIM(COALESCE(r.est_res, ''))) NOT IN ('cancelada', 'cancelado', 'finalizada')
               AND DATE(r.fec_sal_res) > ?
             ORDER BY d.cod_hab_det ASC, r.fec_ent_res ASC"
        );
        if (!$reservationsStatement) {
            throw new RuntimeException('No se pudieron consultar las fechas reservadas por habitación.');
        }

        $reservationsStatement->bind_param('s', $today);
        $reservationsStatement->execute();
        $reservationsStatement->bind_result($reservedRoomId, $reservationStart, $reservationEnd);
        $reservationsByRoom = [];
        while ($reservationsStatement->fetch()) {
            $start = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $reservationStart);
            $end = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $reservationEnd);
            if ($start === false || $end === false || $end <= $start) {
                continue;
            }

            $lastOccupiedNight = $end->modify('-1 day');
            $roomId = (int) $reservedRoomId;
            $ranges = &$reservationsByRoom[$roomId];
            $lastIndex = count($ranges ?? []) - 1;
            if ($lastIndex >= 0) {
                $previousEnd = new \DateTimeImmutable($ranges[$lastIndex]['fin']);
                if ($start <= $previousEnd->modify('+1 day')) {
                    if ($lastOccupiedNight > $previousEnd) {
                        $ranges[$lastIndex]['fin'] = $lastOccupiedNight->format('Y-m-d');
                    }
                    unset($ranges);
                    continue;
                }
            }
            $ranges[] = [
                'inicio' => $start->format('Y-m-d'),
                'fin' => $lastOccupiedNight->format('Y-m-d'),
            ];
            unset($ranges);
        }
        $reservationsStatement->close();

        foreach ($rooms as &$room) {
            $room['fechas_reservadas'] = $reservationsByRoom[(int) $room['cod_hab']] ?? [];
        }
        unset($room);

        return $rooms;
    }

    public function fetchAvailableRooms(
        mysqli $connection,
        string $checkin,
        string $checkout,
        int $adults,
        int $children
    ): array {
        $statement = $connection->prepare(
            "SELECT h.cod_hab, h.tipo_hab
             FROM habitacion h
             WHERE LOWER(TRIM(COALESCE(h.est_hab, ''))) NOT IN ('mantenimiento', 'sucia')
               AND NOT EXISTS (
                   SELECT 1
                   FROM detalle d
                   INNER JOIN reservas r ON r.cod_res = d.cod_res_det
                   WHERE d.cod_hab_det = h.cod_hab
                     AND LOWER(TRIM(r.est_res)) NOT IN ('cancelada', 'cancelado', 'finalizada')
                     AND ? < r.fec_sal_res
                     AND ? > r.fec_ent_res
               )
             ORDER BY h.num_hab ASC"
        );
        if (!$statement) {
            throw new RuntimeException('No se pudo consultar la disponibilidad de habitaciones.');
        }

        $statement->bind_param('ss', $checkin, $checkout);
        $statement->execute();
        $statement->bind_result($roomId, $roomType);

        $availableIds = [];
        $matchingIds = [];
        while ($statement->fetch()) {
            $roomId = (int) $roomId;
            $availableIds[] = $roomId;

            $capacity = capacidad_habitacion((string) $roomType);
            if (
                $adults <= $capacity['adultos']
                && $children <= $capacity['ninos']
                && $adults + $children <= $capacity['personas']
            ) {
                $matchingIds[] = $roomId;
            }
        }

        $statement->close();
        return [
            'disponibles' => $availableIds,
            'compatibles' => $matchingIds,
        ];
    }

    public function fetchNextAvailability(mysqli $connection, string $checkinDate, int $nights): array
    {
        $nights = max(1, $nights);
        $start = \DateTimeImmutable::createFromFormat('!Y-m-d', $checkinDate);
        if ($start === false) {
            return [];
        }

        $reservationsByRoom = [];
        $statement = $connection->prepare(
            "SELECT d.cod_hab_det, DATE(r.fec_ent_res), DATE(r.fec_sal_res)
             FROM detalle d
             INNER JOIN reservas r ON r.cod_res = d.cod_res_det
             WHERE LOWER(TRIM(r.est_res)) NOT IN ('cancelada', 'cancelado', 'finalizada')
               AND DATE(r.fec_sal_res) > ?
             ORDER BY d.cod_hab_det ASC, r.fec_ent_res ASC"
        );
        if ($statement) {
            $statement->bind_param('s', $checkinDate);
            $statement->execute();
            $statement->bind_result($roomId, $entry, $exit);
            while ($statement->fetch()) {
                $reservationsByRoom[(int) $roomId][] = [(string) $entry, (string) $exit];
            }
            $statement->close();
        }

        $result = [];
        $rooms = $connection->query('SELECT cod_hab, est_hab FROM habitacion ORDER BY num_hab ASC');
        if ($rooms) {
            while ($room = $rooms->fetch_assoc()) {
                $id = (int) $room['cod_hab'];
                $estado = strtolower(trim((string) $room['est_hab']));
                if ($estado === 'mantenimiento') {
                    $result[$id] = ['fecha' => null, 'motivo' => 'mantenimiento'];
                    continue;
                }
                if ($estado === 'sucia') {
                    $result[$id] = ['fecha' => null, 'motivo' => 'limpieza'];
                    continue;
                }

                $candidate = $start;
                $changed = true;
                while ($changed) {
                    $changed = false;
                    $candidateEnd = $candidate->modify('+' . $nights . ' days')->format('Y-m-d');
                    foreach ($reservationsByRoom[$id] ?? [] as [$entry, $exit]) {
                        if ($entry < $candidateEnd && $exit > $candidate->format('Y-m-d')) {
                            $candidate = \DateTimeImmutable::createFromFormat('!Y-m-d', $exit);
                            $changed = true;
                            break;
                        }
                    }
                }
                $result[$id] = ['fecha' => $candidate->format('Y-m-d'), 'motivo' => 'ocupada'];
            }
            $rooms->free();
        }

        return $result;
    }

    public function fetchReservationStays(mysqli $connection, int $userId): array
    {
        $today = (new \DateTimeImmutable('now', new \DateTimeZone('America/Bogota')))->format('Y-m-d');
        $statement = $connection->prepare(
            "SELECT DATE(fec_ent_res) AS fecha_inicio, DATE(fec_sal_res) AS fecha_fin
             FROM reservas
             WHERE id_usu_res = ?
               AND LOWER(TRIM(COALESCE(est_res, ''))) IN ('pendiente', 'confirmada', 'en casa')
               AND DATE(fec_sal_res) > ?
             ORDER BY fec_ent_res ASC"
        );
        if (!$statement) {
            return [];
        }

        $statement->bind_param('is', $userId, $today);
        $statement->execute();
        $result = $statement->get_result();

        $stays = [];
        if ($result instanceof \mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $stays[] = [
                    'inicio' => (string) $row['fecha_inicio'],
                    'fin' => (string) $row['fecha_fin'],
                ];
            }
        }

        $statement->close();
        return $stays;
    }

    public function fetchReservationHistory(mysqli $connection, int $userId): array
    {
        $statement = $connection->prepare(
            "SELECT r.cod_res, r.fec_res, r.fec_ent_res, r.fec_sal_res, r.est_res, r.not_res,
                    (LOWER(TRIM(COALESCE(r.est_res, ''))) IN ('pendiente', 'confirmada')
                     AND r.fec_res >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                     AND r.fec_ent_res > NOW()
                     AND NOT EXISTS (
                         SELECT 1 FROM pagos pg
                         WHERE pg.cod_res_pago = r.cod_res AND pg.estado_pago = 'Aprobado'
                     )
                     AND NOT EXISTS (
                         SELECT 1 FROM agenda_actividad a
                         WHERE a.estado_pago_experiencia = 'Pagada'
                           AND (
                               a.cod_res_agenda = r.cod_res
                               OR (
                                   a.cod_res_agenda IS NULL
                                   AND a.id_usu_agenda = r.id_usu_res
                                   AND a.fecha_agenda BETWEEN DATE(r.fec_ent_res) AND DATE(r.fec_sal_res)
                               )
                           )
                     )) AS puede_cancelar,
                    GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(r.fec_res, INTERVAL 10 MINUTE))) AS segundos_cancelacion,
                    GROUP_CONCAT(DISTINCT h.num_hab ORDER BY h.num_hab ASC SEPARATOR ', ') AS num_hab,
                    GROUP_CONCAT(DISTINCT h.tipo_hab ORDER BY h.tipo_hab ASC SEPARATOR ', ') AS tipo_hab
             FROM reservas r
             LEFT JOIN detalle d ON d.cod_res_det = r.cod_res
             LEFT JOIN habitacion h ON h.cod_hab = d.cod_hab_det
             WHERE r.id_usu_res = ?
             GROUP BY r.cod_res, r.fec_res, r.fec_ent_res, r.fec_sal_res, r.est_res, r.not_res
             ORDER BY r.fec_ent_res DESC"
        );
        if (!$statement) {
            return [];
        }

        $statement->bind_param('i', $userId);
        $statement->execute();
        $result = $statement->get_result();
        $reservations = [];
        if ($result instanceof \mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $reservations[] = $row;
            }
        }

        $statement->close();
        return $reservations;
    }
}