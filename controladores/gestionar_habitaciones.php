<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../configuracion/conexion.php';
require_once __DIR__ . '/../configuracion/permiso.php';

use App\Logger;

header('Content-Type: application/json; charset=utf-8');

const DIRECTORIO_IMAGENES_HABITACIONES = 'assets/uploads/habitaciones';

function responder_habitaciones(int $status, string $mensaje, array $datos = []): never
{
    http_response_code($status);
    echo json_encode(
        array_merge(['status' => $status < 300 ? 'exito' : 'error', 'mensaje' => $mensaje], $datos),
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

function actor_habitaciones(): string
{
    return (string) ($_SESSION['emp_auth']['id_usuario'] ?? 'desconocido');
}

/** Borra una imagen de habitación solo si está dentro de la carpeta de cargas permitida. */
function borrar_imagen_habitacion(?string $rutaRelativa): void
{
    if (!is_string($rutaRelativa) || $rutaRelativa === '') {
        return;
    }
    $ruta = realpath(__DIR__ . '/../' . $rutaRelativa);
    $directorio = realpath(__DIR__ . '/../' . DIRECTORIO_IMAGENES_HABITACIONES);
    if ($ruta !== false && $directorio !== false && str_starts_with($ruta, $directorio . DIRECTORY_SEPARATOR) && is_file($ruta)) {
        if (!unlink($ruta)) {
            error_log('No se pudo eliminar la imagen de una habitación');
        }
    }
}

/** @return list<string> */
function comodidades_desde_json(?string $json): array
{
    $datos = json_decode((string) $json, true);
    if (!is_array($datos)) {
        return [];
    }
    return array_values(array_filter(array_map(static fn($c): string => trim((string) $c), $datos), static fn(string $c): bool => $c !== ''));
}

/** @var mysqli $conexion */
if (!usuario_tiene_permiso($conexion, 'habitaciones.ver')) {
    responder_habitaciones(403, 'No tienes permiso para consultar las habitaciones');
}

$metodo = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['accion'] ?? $_POST['accion'] ?? 'listar';

// ---------------------------------------------------------------- LISTAR
if ($metodo === 'GET' && $accion === 'listar') {
    $sql = "SELECT h.cod_hab, h.num_hab, h.tipo_hab, h.pre_hab, h.precio_hab, h.est_hab, h.des_hab, h.img_hab, h.car_hab,
                   (SELECT COUNT(*) FROM detalle d WHERE d.cod_hab_det = h.cod_hab) AS total_reservas
            FROM habitacion h
            ORDER BY h.num_hab ASC";
    $resultado = $conexion->query($sql);
    if (!$resultado) {
        error_log('Error al listar habitaciones: ' . $conexion->error);
        responder_habitaciones(500, 'No se pudieron cargar las habitaciones');
    }

    $habitaciones = [];
    $posicionPorHabitacion = [];
    while ($fila = $resultado->fetch_assoc()) {
        $precio = (float) $fila['pre_hab'] > 0 ? (float) $fila['pre_hab'] : (float) $fila['precio_hab'];
        $habitaciones[] = [
            'id'             => (int) $fila['cod_hab'],
            'numero'         => (int) $fila['num_hab'],
            'tipo'           => (string) $fila['tipo_hab'],
            'precio'         => $precio,
            'estado'         => (string) $fila['est_hab'],
            'descripcion'    => (string) ($fila['des_hab'] ?? ''),
            'imagen'         => $fila['img_hab'] !== null && $fila['img_hab'] !== '' ? (string) $fila['img_hab'] : null,
            'comodidades'    => comodidades_desde_json($fila['car_hab']),
            'total_reservas' => (int) $fila['total_reservas'],
            'ocupacion'      => 'libre',
            'reservas'       => [],
        ];
        $posicionPorHabitacion[(int) $fila['cod_hab']] = count($habitaciones) - 1;
    }
    $resultado->free();

    // Reservas vigentes o futuras de cada habitación (se excluyen canceladas y finalizadas, igual que en el sitio público).
    // "En curso": el huésped está en casa o hoy cae entre la entrada y la salida. "Reservada": la reserva empieza más adelante.
    $sqlReservas = "SELECT d.cod_hab_det AS cod_hab, r.cod_res, r.est_res, r.fec_ent_res, r.fec_sal_res, u.nom_usu AS huesped,
                           (LOWER(TRIM(r.est_res)) = 'en casa' OR (r.fec_ent_res <= NOW() AND r.fec_sal_res > NOW())) AS en_curso
                    FROM detalle d
                    INNER JOIN reservas r ON r.cod_res = d.cod_res_det
                    LEFT JOIN usuario u ON u.id_usu = r.id_usu_res
                    WHERE LOWER(TRIM(r.est_res)) NOT IN ('cancelada', 'cancelado', 'finalizada')
                      AND (LOWER(TRIM(r.est_res)) = 'en casa' OR r.fec_sal_res > NOW())
                    ORDER BY d.cod_hab_det ASC, r.fec_ent_res ASC, r.cod_res ASC";
    $resultadoReservas = $conexion->query($sqlReservas);
    if (!$resultadoReservas) {
        // Si falla esta consulta se siguen listando las habitaciones, solo sin el detalle de reservas.
        error_log('Error al consultar reservas por habitación: ' . $conexion->error);
    } else {
        while ($reserva = $resultadoReservas->fetch_assoc()) {
            $posicion = $posicionPorHabitacion[(int) $reserva['cod_hab']] ?? null;
            if ($posicion === null) {
                continue;
            }
            $enCurso = (int) $reserva['en_curso'] === 1;
            $habitaciones[$posicion]['reservas'][] = [
                'cod_res'  => (int) $reserva['cod_res'],
                'estado'   => (string) $reserva['est_res'],
                'entrada'  => (string) $reserva['fec_ent_res'],
                'salida'   => (string) $reserva['fec_sal_res'],
                'huesped'  => (string) ($reserva['huesped'] ?? ''),
                'en_curso' => $enCurso,
            ];
            if ($enCurso) {
                $habitaciones[$posicion]['ocupacion'] = 'en_curso';
            } elseif ($habitaciones[$posicion]['ocupacion'] === 'libre') {
                $habitaciones[$posicion]['ocupacion'] = 'reservada';
            }
        }
        $resultadoReservas->free();
    }

    responder_habitaciones(200, 'Habitaciones cargadas', [
        'habitaciones'    => $habitaciones,
        'puede_gestionar' => usuario_tiene_permiso($conexion, 'habitaciones.gestionar'),
    ]);
}

if ($metodo !== 'POST') {
    responder_habitaciones(405, 'Método no permitido');
}

exigir_csrf();

if (!usuario_tiene_permiso($conexion, 'habitaciones.gestionar')) {
    responder_habitaciones(403, 'No tienes permiso para gestionar las habitaciones');
}

// ---------------------------------------------------------------- GUARDAR (crear / editar)
if ($accion === 'guardar') {
    foreach (['id', 'numero', 'tipo', 'precio', 'descripcion', 'comodidades', 'quitar_imagen'] as $campo) {
        if (isset($_POST[$campo]) && !is_string($_POST[$campo])) {
            responder_habitaciones(422, 'Datos de habitación no válidos');
        }
    }

    $idTexto     = trim((string) ($_POST['id'] ?? ''));
    $id          = $idTexto === '' ? null : filter_var($idTexto, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $numero      = filter_var(trim((string) ($_POST['numero'] ?? '')), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 99999]]);
    $tipo        = trim((string) ($_POST['tipo'] ?? ''));
    $precioTexto = trim((string) ($_POST['precio'] ?? ''));
    $descripcion = trim((string) ($_POST['descripcion'] ?? ''));

    if ($idTexto !== '' && !$id) {
        responder_habitaciones(422, 'Habitación no válida');
    }
    if (!$numero) {
        responder_habitaciones(422, 'El número de habitación debe ser un entero entre 1 y 99999');
    }
    if ($tipo === '' || mb_strlen($tipo) > 50) {
        responder_habitaciones(422, 'El tipo es obligatorio y admite máximo 50 caracteres');
    }
    if (!preg_match('/^\d{1,8}$/', $precioTexto) || (int) $precioTexto < 1) {
        responder_habitaciones(422, 'El precio por noche debe ser un monto entero mayor a 0 (COP)');
    }
    if (mb_strlen($descripcion) > 500) {
        responder_habitaciones(422, 'La descripción admite máximo 500 caracteres');
    }

    // Comodidades: una por línea, máximo 8, hasta 40 caracteres cada una, sin repetidas.
    $comodidades = [];
    foreach (preg_split('/\R/u', (string) ($_POST['comodidades'] ?? '')) ?: [] as $linea) {
        $linea = trim($linea);
        if ($linea === '') {
            continue;
        }
        if (mb_strlen($linea) > 40) {
            responder_habitaciones(422, 'Cada comodidad admite máximo 40 caracteres');
        }
        $comodidades[mb_strtolower($linea, 'UTF-8')] = $linea;
    }
    $comodidades = array_values($comodidades);
    if (count($comodidades) > 8) {
        responder_habitaciones(422, 'Puedes registrar hasta 8 comodidades');
    }
    $comodidadesJson = $comodidades === [] ? null : json_encode($comodidades, JSON_UNESCAPED_UNICODE);

    $precio      = $precioTexto . '.00';
    $descripcion = $descripcion === '' ? null : $descripcion;

    // Número de habitación único (la BD también lo exige con un índice UNIQUE).
    $stmtDup = $conexion->prepare('SELECT cod_hab FROM habitacion WHERE num_hab = ? AND cod_hab <> ? LIMIT 1');
    $idComparar = $id ?? 0;
    $stmtDup->bind_param('ii', $numero, $idComparar);
    $stmtDup->execute();
    $stmtDup->store_result();
    $duplicada = $stmtDup->num_rows > 0;
    $stmtDup->close();
    if ($duplicada) {
        responder_habitaciones(409, "Ya existe una habitación con el número $numero");
    }

    // Imagen actual (solo al editar).
    $imagenActual = null;
    if ($id) {
        $stmtImg = $conexion->prepare('SELECT img_hab FROM habitacion WHERE cod_hab = ?');
        $stmtImg->bind_param('i', $id);
        $stmtImg->execute();
        $stmtImg->bind_result($imagenActual);
        $existe = $stmtImg->fetch();
        $stmtImg->close();
        if (!$existe) {
            responder_habitaciones(404, 'Habitación no encontrada');
        }
    }

    // Imagen nueva (opcional): JPG, PNG o WebP, máximo 5 MB.
    $imagenNueva = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        $archivo = $_FILES['imagen'];
        if ($archivo['error'] !== UPLOAD_ERR_OK || $archivo['size'] > 5 * 1024 * 1024) {
            responder_habitaciones(422, 'La imagen no se pudo cargar o supera los 5 MB');
        }
        if (!is_uploaded_file($archivo['tmp_name'])) {
            responder_habitaciones(422, 'El archivo de imagen no es válido');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
        $extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($extensiones[$mime])) {
            responder_habitaciones(422, 'Usa una imagen JPG, PNG o WebP');
        }
        $dimensiones = getimagesize($archivo['tmp_name']);
        if ($dimensiones === false || $dimensiones[0] * $dimensiones[1] > 20000000) {
            responder_habitaciones(422, 'La imagen no es válida o tiene dimensiones demasiado grandes');
        }
        $directorio = __DIR__ . '/../' . DIRECTORIO_IMAGENES_HABITACIONES;
        if (!is_dir($directorio) && !mkdir($directorio, 0750, true) && !is_dir($directorio)) {
            error_log('No se pudo crear el directorio de imágenes de habitaciones');
            responder_habitaciones(500, 'No se pudo preparar el almacenamiento de imágenes');
        }
        $nombreArchivo = bin2hex(random_bytes(16)) . '.' . $extensiones[$mime];
        if (!move_uploaded_file($archivo['tmp_name'], $directorio . '/' . $nombreArchivo)) {
            error_log('No se pudo mover una imagen cargada para una habitación');
            responder_habitaciones(500, 'No se pudo guardar la imagen');
        }
        $imagenNueva = DIRECTORIO_IMAGENES_HABITACIONES . '/' . $nombreArchivo;
    }
    $quitarImagen = ($_POST['quitar_imagen'] ?? '0') === '1';
    $imagen = $imagenNueva ?? ($quitarImagen ? null : $imagenActual);

    $limpiarImagenNueva = static function () use ($imagenNueva): void {
        if ($imagenNueva !== null) {
            borrar_imagen_habitacion($imagenNueva);
        }
    };

    if ($id) {
        $stmt = $conexion->prepare(
            'UPDATE habitacion SET num_hab = ?, tipo_hab = ?, pre_hab = ?, des_hab = ?, img_hab = ?, car_hab = ? WHERE cod_hab = ?'
        );
        $stmt->bind_param('isssssi', $numero, $tipo, $precio, $descripcion, $imagen, $comodidadesJson, $id);
        if (!$stmt->execute()) {
            $errno = $stmt->errno;
            error_log('Error al actualizar habitación: ' . $stmt->error);
            $stmt->close();
            $limpiarImagenNueva();
            responder_habitaciones(
                $errno === 1062 ? 409 : 500,
                $errno === 1062 ? "Ya existe una habitación con el número $numero" : 'No se pudo guardar la habitación'
            );
        }
        $stmt->close();

        // Ya guardado: se elimina la foto anterior si fue reemplazada o quitada.
        if ($imagenActual !== null && $imagenActual !== $imagen) {
            borrar_imagen_habitacion($imagenActual);
        }

        Logger::registrarLog('INFO', 'Habitación actualizada desde el panel admin', ['usuario_id' => actor_habitaciones(), 'cod_hab' => $id, 'num_hab' => $numero]);
        responder_habitaciones(200, 'Habitación actualizada correctamente', ['id' => $id]);
    }

    // Las habitaciones nuevas nacen "Disponible"; el estado lo gestiona Operaciones/Housekeeping.
    $stmt = $conexion->prepare(
        "INSERT INTO habitacion (num_hab, tipo_hab, pre_hab, est_hab, des_hab, img_hab, car_hab) VALUES (?, ?, ?, 'Disponible', ?, ?, ?)"
    );
    $stmt->bind_param('isssss', $numero, $tipo, $precio, $descripcion, $imagen, $comodidadesJson);
    if (!$stmt->execute()) {
        $errno = $stmt->errno;
        error_log('Error al crear habitación: ' . $stmt->error);
        $stmt->close();
        $limpiarImagenNueva();
        responder_habitaciones(
            $errno === 1062 ? 409 : 500,
            $errno === 1062 ? "Ya existe una habitación con el número $numero" : 'No se pudo crear la habitación'
        );
    }
    $nuevoId = (int) $stmt->insert_id;
    $stmt->close();

    Logger::registrarLog('INFO', 'Habitación creada desde el panel admin', ['usuario_id' => actor_habitaciones(), 'cod_hab' => $nuevoId, 'num_hab' => $numero]);
    responder_habitaciones(201, 'Habitación creada correctamente', ['id' => $nuevoId]);
}

// ---------------------------------------------------------------- ELIMINAR
if ($accion === 'eliminar') {
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) {
        responder_habitaciones(422, 'Habitación no válida');
    }

    $stmtHab = $conexion->prepare('SELECT num_hab, est_hab, img_hab FROM habitacion WHERE cod_hab = ?');
    $stmtHab->bind_param('i', $id);
    $stmtHab->execute();
    $habitacion = $stmtHab->get_result()->fetch_assoc();
    $stmtHab->close();
    if (!$habitacion) {
        responder_habitaciones(404, 'Habitación no encontrada');
    }
    if ($habitacion['est_hab'] === 'Ocupada') {
        responder_habitaciones(409, 'No puedes eliminar una habitación que está ocupada');
    }

    // detalle.cod_hab_det tiene llave foránea: con reservas asociadas (activas o históricas) no se puede borrar.
    $stmtRes = $conexion->prepare('SELECT COUNT(*) FROM detalle WHERE cod_hab_det = ?');
    $stmtRes->bind_param('i', $id);
    $stmtRes->execute();
    $stmtRes->bind_result($totalReservas);
    $stmtRes->fetch();
    $stmtRes->close();
    if ($totalReservas > 0) {
        responder_habitaciones(409, 'Esta habitación tiene reservas asociadas y no puede eliminarse. Para retirarla de la venta, ponla en Mantenimiento desde Operaciones.');
    }

    $stmt = $conexion->prepare('DELETE FROM habitacion WHERE cod_hab = ?');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) {
        error_log('Error al eliminar habitación: ' . $stmt->error);
        $stmt->close();
        responder_habitaciones(500, 'No se pudo eliminar la habitación');
    }
    $eliminada = $stmt->affected_rows === 1;
    $stmt->close();

    if (!$eliminada) {
        responder_habitaciones(404, 'Habitación no encontrada');
    }
    borrar_imagen_habitacion($habitacion['img_hab'] ?? null);
    Logger::registrarLog('INFO', 'Habitación eliminada desde el panel admin', ['usuario_id' => actor_habitaciones(), 'cod_hab' => $id, 'num_hab' => (int) $habitacion['num_hab']]);
    responder_habitaciones(200, 'Habitación eliminada correctamente');
}

responder_habitaciones(400, 'Acción no válida');