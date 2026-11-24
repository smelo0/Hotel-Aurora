<?php
/**
 * API REST Móvil — Habitaciones Disponibles
 * Hotel Aurora Sanctuary & Concierge
 * Consulta sin solapamiento de fechas con reservas activas en MariaDB
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../configuracion/conexion.php';

$fec_ent = isset($_GET['fec_ent']) ? trim((string) $_GET['fec_ent']) : date('Y-m-d');
$fec_sal = isset($_GET['fec_sal']) ? trim((string) $_GET['fec_sal']) : date('Y-m-d', strtotime('+2 days'));

// Consulta de habitaciones que no tengan solapamiento con reservas activas
$sql = "SELECT 
            h.cod_hab,
            h.num_hab,
            h.tipo_hab,
            COALESCE(NULLIF(h.pre_hab, 0), h.precio_hab, 90000) AS pre_hab,
            h.est_hab,
            h.des_hab,
            h.car_hab,
            h.img_hab
        FROM habitacion h
        WHERE h.est_hab != 'Mantenimiento'
        AND h.cod_hab NOT IN (
            SELECT d.cod_hab_det
            FROM detalle d
            INNER JOIN reservas r ON r.cod_res = d.cod_res_det
            WHERE r.est_res NOT IN ('Cancelada')
            AND NOT (
                r.fec_sal_res <= CONCAT(?, ' 12:00:00')
                OR r.fec_ent_res >= CONCAT(?, ' 15:00:00')
            )
        )
        ORDER BY h.num_hab ASC";

$stmt = $conexion->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al preparar consulta de disponibilidad: ' . $conexion->error
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$stmt->bind_param('ss', $fec_ent, $fec_sal);
$stmt->execute();
$resultado = $stmt->get_result();

$habitaciones = [];
while ($row = $resultado->fetch_assoc()) {
    $tipo = (string) $row['tipo_hab'];
    $categoria = 'CONFORT';
    $imgDefault = 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=400&auto=format&fit=crop&q=80';

    if (stripos($tipo, 'suite premium') !== false) {
        $categoria = 'SUITE PREMIUM';
        $imgDefault = 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=400&auto=format&fit=crop&q=80';
    } elseif (stripos($tipo, 'suite') !== false) {
        $categoria = 'SUITE PRIVILEGE';
        $imgDefault = 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=400&auto=format&fit=crop&q=80';
    } elseif (stripos($tipo, 'doble') !== false) {
        $categoria = 'DOBLE CONFORT';
        $imgDefault = 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=400&auto=format&fit=crop&q=80';
    } else {
        $categoria = 'SENCILLA EJECUTIVA';
        $imgDefault = 'https://images.unsplash.com/photo-1595526114035-0d45ed16cfbf?w=400&auto=format&fit=crop&q=80';
    }

    $carHab = $row['car_hab'];
    $amenities = [];
    if ($carHab) {
        $decoded = json_decode($carHab, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $amenities = $decoded;
        } else {
            $amenities = array_map('trim', explode(',', $carHab));
        }
    } else {
        $amenities = ['Cama confort', 'Smart TV', 'Wi-Fi 6', 'Baño Privado'];
    }

    $habitaciones[] = [
        'cod_hab' => (int) $row['cod_hab'],
        'num_hab' => (string) $row['num_hab'],
        'tipo_hab' => $tipo,
        'categoria' => $categoria,
        'pre_hab' => (float) $row['pre_hab'],
        'est_hab' => (string) $row['est_hab'],
        'des_hab' => (string) ($row['des_hab'] ?: 'Habitación equipada con acabados de lujo y vista al santuario.'),
        'car_hab' => $amenities,
        'imagen' => $row['img_hab'] ? ('http://localhost/Hotel-Aurora/' . $row['img_hab']) : $imgDefault,
    ];
}

$stmt->close();

echo json_encode($habitaciones, JSON_UNESCAPED_UNICODE);
