<?php
declare(strict_types=1);

/**
 * Experiencias para el personal (interfaz de empleado). Solo lectura.
 *
 *   GET  (sin parámetros)   Grupos de experiencias programadas de hoy en adelante.
 *
 * Un "grupo" reúne a todos los huéspedes que harán la misma experiencia,
 * con la misma opción, el mismo día y a la misma hora.
 * No se devuelven correos ni datos de cobro: el personal solo necesita saber
 * quién asiste, cuántas personas son y en qué habitación se hospedan.
 */

require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../configuracion/conexion.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
/** @var mysqli $conexion */

function exp_emp_responder(int $status, string $mensaje, array $datos = []): never
{
    http_response_code($status);
    echo json_encode(
        array_merge(['status' => $status < 300 ? 'exito' : 'error', 'mensaje' => $mensaje], $datos),
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

$idEmpleado = (int) ($_SESSION['emp_auth']['id_usuario'] ?? 0);
$rolEmpleado = (int) ($_SESSION['emp_auth']['rol_usuario'] ?? 0);
if ($idEmpleado < 1 || !in_array($rolEmpleado, [1, 3], true)) {
    exp_emp_responder(403, 'Tu sesión no tiene acceso a las experiencias.');
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    exp_emp_responder(405, 'Método no permitido.');
}

try {
    $hoy = (new DateTimeImmutable('now', new DateTimeZone('America/Bogota')))->format('Y-m-d');

    $sql = "SELECT a.id_agenda, a.actividad, a.opcion_actividad, a.selecciones_personas_json,
                   a.fecha_agenda, a.hora_agenda, a.estado_agenda, a.cod_res_agenda,
                   COALESCE(NULLIF(u.nom_usu, ''), a.nombre_contacto) AS huesped,
                   (SELECT GROUP_CONCAT(DISTINCT h.num_hab ORDER BY h.num_hab SEPARATOR ', ')
                      FROM detalle d
                      INNER JOIN habitacion h ON h.cod_hab = d.cod_hab_det
                     WHERE d.cod_res_det = a.cod_res_agenda) AS habitaciones
            FROM agenda_actividad a
            LEFT JOIN usuario u ON u.id_usu = a.id_usu_agenda
            WHERE a.fecha_agenda IS NOT NULL
              AND a.hora_agenda IS NOT NULL
              AND a.fecha_agenda >= ?
              AND COALESCE(a.estado_agenda, 'Pendiente') <> 'Cancelada'
            ORDER BY a.fecha_agenda ASC, a.hora_agenda ASC, a.actividad ASC, a.id_agenda ASC";
    $stmt = $conexion->prepare($sql);
    if (!$stmt || !$stmt->bind_param('s', $hoy) || !$stmt->execute()) {
        throw new RuntimeException('No se pudieron consultar las experiencias.');
    }
    $resultado = $stmt->get_result();
    if (!$resultado) {
        throw new RuntimeException('No se pudieron consultar las experiencias.');
    }

    $grupos = [];
    while ($fila = $resultado->fetch_assoc()) {
        // Cada elemento de selecciones_personas_json es la opción elegida por una persona.
        $selecciones = json_decode((string) ($fila['selecciones_personas_json'] ?? ''), true);
        $porOpcion = [];
        if (is_array($selecciones)) {
            foreach ($selecciones as $opcion) {
                $nombreOpcion = trim((string) $opcion);
                if ($nombreOpcion === '') {
                    continue;
                }
                $porOpcion[$nombreOpcion] = ($porOpcion[$nombreOpcion] ?? 0) + 1;
            }
        }
        if (!$porOpcion) {
            // Registros antiguos sin detalle: se toma el número de "N personas".
            $personas = (int) preg_replace('/\D+/', '', (string) $fila['opcion_actividad']);
            $porOpcion['General'] = max(1, $personas);
        }

        $hora = substr((string) $fila['hora_agenda'], 0, 5);
        foreach ($porOpcion as $nombreOpcion => $cantidad) {
            $clave = implode('|', [$fila['actividad'], $nombreOpcion, $fila['fecha_agenda'], $hora]);
            if (!isset($grupos[$clave])) {
                $grupos[$clave] = [
                    'actividad' => (string) $fila['actividad'],
                    'opcion' => (string) $nombreOpcion,
                    'fecha' => (string) $fila['fecha_agenda'],
                    'hora' => $hora,
                    'total_personas' => 0,
                    'participantes' => [],
                ];
            }
            $grupos[$clave]['total_personas'] += $cantidad;
            $grupos[$clave]['participantes'][] = [
                'huesped' => (string) ($fila['huesped'] ?? 'Huésped'),
                'personas' => $cantidad,
                'habitaciones' => (string) ($fila['habitaciones'] ?? ''),
                'estado' => (string) ($fila['estado_agenda'] ?? 'Pendiente'),
            ];
        }
    }
    $stmt->close();

    exp_emp_responder(200, 'OK', ['hoy' => $hoy, 'grupos' => array_values($grupos)]);
} catch (Throwable $e) {
    error_log('experiencias_empleado: ' . $e->getMessage());
    exp_emp_responder(500, 'No se pudieron cargar las experiencias.');
}