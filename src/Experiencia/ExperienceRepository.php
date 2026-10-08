<?php
declare(strict_types=1);

namespace App\Experiencia;

use mysqli;

final class ExperienceRepository
{
    public function obtenerHistorial(mysqli $connection, ?int $userId = null): array
    {
        try {
            $sql = "SELECT a.id_agenda, a.actividad, a.opcion_actividad, a.selecciones_personas_json,
                           a.fecha_agenda, a.hora_agenda, a.nombre_contacto, a.correo_contacto,
                           a.estado_agenda, a.estado_pago_experiencia, a.fecha_pago_experiencia,
                           a.metodo_pago_experiencia, a.creado_en, a.monto_experiencia,
                           a.precios_personas_json, a.id_usu_agenda, a.cod_res_agenda,
                           (COALESCE(a.estado_agenda, 'Pendiente') <> 'Cancelada'
                            AND a.creado_en >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                            AND TIMESTAMP(a.fecha_agenda, a.hora_agenda) > NOW()) AS puede_cancelar,
                           GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(a.creado_en, INTERVAL 5 MINUTE))) AS segundos_cancelacion,
                           COALESCE(NULLIF(u.nom_usu, ''), a.nombre_contacto) AS nombre_cliente,
                           COALESCE(NULLIF(u.corr_usu, ''), a.correo_contacto) AS correo_cliente
                    FROM agenda_actividad a
                    LEFT JOIN usuario u ON u.id_usu = a.id_usu_agenda";
            if ($userId !== null) {
                $sql .= ' WHERE a.id_usu_agenda = ?';
            }
            $sql .= ' ORDER BY a.creado_en DESC, a.id_agenda DESC';

            $statement = $connection->prepare($sql);
            if (!$statement) {
                return [];
            }
            if ($userId !== null && !$statement->bind_param('i', $userId)) {
                $statement->close();
                return [];
            }
            if (!$statement->execute()) {
                $statement->close();
                return [];
            }

            $result = $statement->get_result();
            if (!$result instanceof \mysqli_result) {
                $statement->close();
                return [];
            }

            $requests = [];
            while ($row = $result->fetch_assoc()) {
                $selections = json_decode((string) ($row['selecciones_personas_json'] ?? ''), true);
                $row['selecciones_personas'] = is_array($selections) ? $selections : [];
                $prices = json_decode((string) ($row['precios_personas_json'] ?? ''), true);
                $row['precios_personas'] = is_array($prices) ? $prices : [];
                $row['id_agenda'] = (int) $row['id_agenda'];
                unset($row['selecciones_personas_json'], $row['precios_personas_json']);
                $requests[] = $row;
            }
            $statement->close();

            return $requests;
        } catch (\Throwable $e) {
            // Retorna un array vacío en lugar de romper el panel con un Fatal Error
            return [];
        }
    }

    public function obtenerExperiencias(mysqli $connection): array
    {
        try {
            $result = $connection->query(
                'SELECT id, categoria, nombre, descripcion, imagen, opcion_1, opcion_2, opcion_3,
                        precio_opcion_1, precio_opcion_2, precio_opcion_3, horarios_json
                 FROM experiencias ORDER BY id ASC'
            );
            if (!$result instanceof \mysqli_result) {
                return [];
            }

            $experiences = [];
            while ($experience = $result->fetch_assoc()) {
                $experience['id'] = (int) $experience['id'];
                $experience['opciones'] = [];
                $experience['precios'] = [];
                $experience['numeros_opciones'] = [];
                foreach ([1, 2, 3] as $index) {
                    $option = trim((string) ($experience['opcion_' . $index] ?? ''));
                    if ($option === '') {
                        continue;
                    }
                    $experience['opciones'][] = $option;
                    $experience['numeros_opciones'][] = $index;
                    $price = $experience['precio_opcion_' . $index] ?? null;
                    $experience['precios'][] = $price === null ? null : number_format((float) $price, 2, '.', '');
                }
                $schedule = json_decode((string) ($experience['horarios_json'] ?? ''), true);
                $experience['horarios'] = is_array($schedule) && class_exists('App\Experiencia\ExperienceSchedule')
                    ? ExperienceSchedule::normalizar($schedule, $experience['opciones'])
                    : [];
                unset(
                    $experience['opcion_1'],
                    $experience['opcion_2'],
                    $experience['opcion_3'],
                    $experience['precio_opcion_1'],
                    $experience['precio_opcion_2'],
                    $experience['precio_opcion_3'],
                    $experience['horarios_json']
                );
                $experiences[] = $experience;
            }

            return $experiences;
        } catch (\Throwable $e) {
            return [];
        }
    }
}