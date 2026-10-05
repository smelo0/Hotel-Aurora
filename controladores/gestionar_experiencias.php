<?php
require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../configuracion/conexion.php';
require_once __DIR__ . '/../configuracion/permiso.php';
require_once __DIR__ . '/../includes/experiencias.php';

header('Content-Type: application/json; charset=utf-8');

function responder_experiencias($status, $mensaje, $datos = []) {
    http_response_code($status);
    echo json_encode(array_merge(['status' => $status < 300 ? 'exito' : 'error', 'mensaje' => $mensaje], $datos), JSON_UNESCAPED_UNICODE);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['accion'] ?? $_POST['accion'] ?? 'listar';

if ($metodo === 'POST' && $accion === 'cancelar_solicitud') {
    $idUsuario = (int) ($_SESSION['user_auth']['id_usuario'] ?? 0);
    $rolUsuario = (int) ($_SESSION['user_auth']['rol_usuario'] ?? 0);
    $esAdministrador = usuario_tiene_permiso($conexion, 'experiencias.gestionar');
    if (!$esAdministrador && ($idUsuario < 1 || $rolUsuario !== 6)) {
        responder_experiencias(401, 'Inicia sesión con una cuenta autorizada para cancelar una solicitud');
    }

    exigir_csrf();
    $idSolicitud = filter_var($_POST['id_agenda'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$idSolicitud) {
        responder_experiencias(422, 'La solicitud que quieres cancelar no es válida');
    }

    asegurar_esquema_agenda_experiencias($conexion);
    $sqlCancelar = "UPDATE agenda_actividad
                    SET estado_agenda = 'Cancelada'
                    WHERE id_agenda = ?
                      AND COALESCE(estado_agenda, 'Pendiente') <> 'Cancelada'
                      AND TIMESTAMP(fecha_agenda, hora_agenda) > NOW()";
    if (!$esAdministrador) {
        $sqlCancelar .= ' AND id_usu_agenda = ? AND creado_en >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)';
    }
    $stmt = $conexion->prepare($sqlCancelar);
    if ($esAdministrador) {
        $stmt->bind_param('i', $idSolicitud);
    } else {
        $stmt->bind_param('ii', $idSolicitud, $idUsuario);
    }
    $stmt->execute();
    $cancelada = $stmt->affected_rows === 1;
    $stmt->close();

    if (!$cancelada) {
        $mensaje = $esAdministrador
            ? 'No se encontró una solicitud futura que todavía pueda cancelarse'
            : 'Solo puedes cancelar la experiencia durante los primeros 5 minutos después de programarla y antes de que comience';
        responder_experiencias(404, $mensaje);
    }
    responder_experiencias(200, 'La experiencia fue cancelada correctamente');
}

if ($metodo === 'POST' && $accion === 'actualizar_pago_experiencia') {
    if (!usuario_tiene_permiso($conexion, 'finanzas.ver') || !usuario_tiene_permiso($conexion, 'experiencias.gestionar')) {
        responder_experiencias(403, 'No tienes permiso para actualizar pagos de experiencias');
    }

    exigir_csrf();
    $idSolicitud = filter_var($_POST['id_agenda'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $estadoPago = $_POST['estado_pago'] ?? null;
    if (!$idSolicitud || !is_string($estadoPago) || !in_array($estadoPago, ['Pendiente', 'Cancelada'], true)) {
        responder_experiencias(422, 'El estado de pago de la experiencia no es válido');
    }

    asegurar_esquema_agenda_experiencias($conexion);
    $sqlPago = "UPDATE agenda_actividad
                SET estado_pago_experiencia = ?,
                    fecha_pago_experiencia = NULL,
                    metodo_pago_experiencia = NULL
                WHERE id_agenda = ? AND estado_pago_experiencia <> 'Pagada'";
    $stmtPago = $conexion->prepare($sqlPago);
    $stmtPago->bind_param('si', $estadoPago, $idSolicitud);
    $stmtPago->execute();
    $actualizado = $stmtPago->affected_rows === 1;
    $stmtPago->close();
    if (!$actualizado) {
        $stmtExiste = $conexion->prepare(
            'SELECT monto_experiencia, estado_agenda, estado_pago_experiencia
             FROM agenda_actividad WHERE id_agenda = ?'
        );
        $stmtExiste->bind_param('i', $idSolicitud);
        $stmtExiste->execute();
        $solicitudExistente = $stmtExiste->get_result()->fetch_assoc();
        $stmtExiste->close();
        if (!$solicitudExistente) {
            responder_experiencias(404, 'No se encontró la experiencia');
        }
        if ($solicitudExistente['estado_pago_experiencia'] === 'Pagada') {
            responder_experiencias(409, 'El pago ya fue cobrado y no se puede cambiar desde este control');
        } elseif ($solicitudExistente['estado_pago_experiencia'] !== $estadoPago) {
            responder_experiencias(409, 'El estado de pago cambió. Actualiza Finanzas e inténtalo nuevamente');
        }
    }
    responder_experiencias(200, 'Estado de pago actualizado', ['estado_pago' => $estadoPago]);
}

if ($metodo === 'POST' && $accion === 'cobrar_experiencia') {
    if (!usuario_tiene_permiso($conexion, 'finanzas.ver') || !usuario_tiene_permiso($conexion, 'experiencias.gestionar')) {
        responder_experiencias(403, 'No tienes permiso para cobrar experiencias');
    }

    exigir_csrf();
    $idSolicitud = filter_var($_POST['id_agenda'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $metodoPago = $_POST['metodo_pago'] ?? null;
    if (!$idSolicitud || !is_string($metodoPago) || !in_array($metodoPago, ['Efectivo', 'Tarjeta', 'Transferencia'], true)) {
        responder_experiencias(422, 'Selecciona una experiencia y un método de pago válido');
    }

    asegurar_esquema_agenda_experiencias($conexion);
    $transaccionIniciada = false;
    try {
        $conexion->begin_transaction();
        $transaccionIniciada = true;
        $stmtCobro = $conexion->prepare(
            "SELECT id_agenda, cod_res_agenda, actividad, selecciones_personas_json, fecha_agenda, hora_agenda,
                    nombre_contacto, correo_contacto, estado_agenda, estado_pago_experiencia,
                    monto_experiencia, precios_personas_json,
                    COALESCE(NULLIF(u.nom_usu, ''), a.nombre_contacto) AS nombre_cliente,
                    COALESCE(NULLIF(u.corr_usu, ''), a.correo_contacto) AS correo_cliente
             FROM agenda_actividad a
             LEFT JOIN usuario u ON u.id_usu = a.id_usu_agenda
             WHERE a.id_agenda = ?
             FOR UPDATE"
        );
        $stmtCobro->bind_param('i', $idSolicitud);
        $stmtCobro->execute();
        $experienciaCobro = $stmtCobro->get_result()->fetch_assoc();
        $stmtCobro->close();
        if (!$experienciaCobro) {
            throw new RuntimeException('experiencia_no_encontrada');
        }
        if (($experienciaCobro['estado_pago_experiencia'] ?? 'Pendiente') !== 'Pendiente') {
            throw new RuntimeException('pago_no_pendiente');
        }
        if (($experienciaCobro['estado_agenda'] ?? '') === 'Cancelada') {
            throw new RuntimeException('experiencia_cancelada');
        }
        if ($experienciaCobro['monto_experiencia'] === null || (float) $experienciaCobro['monto_experiencia'] <= 0) {
            throw new RuntimeException('precio_no_cobrable');
        }

        $stmtGuardarCobro = $conexion->prepare(
            "UPDATE agenda_actividad
             SET estado_pago_experiencia = 'Pagada',
                 fecha_pago_experiencia = CURRENT_TIMESTAMP,
                 metodo_pago_experiencia = ?
             WHERE id_agenda = ? AND estado_pago_experiencia = 'Pendiente'"
        );
        $stmtGuardarCobro->bind_param('si', $metodoPago, $idSolicitud);
        $stmtGuardarCobro->execute();
        if ($stmtGuardarCobro->affected_rows !== 1) {
            $stmtGuardarCobro->close();
            throw new RuntimeException('pago_no_pendiente');
        }
        $stmtGuardarCobro->close();
        $conexion->commit();
        $transaccionIniciada = false;

        $opcionesSeleccionadas = json_decode((string) ($experienciaCobro['selecciones_personas_json'] ?? ''), true);
        $preciosPorPersona = json_decode((string) ($experienciaCobro['precios_personas_json'] ?? ''), true);
        $detalleOpciones = [];
        if (is_array($opcionesSeleccionadas)) {
            foreach ($opcionesSeleccionadas as $indice => $opcion) {
                $precioPersona = is_array($preciosPorPersona) ? ($preciosPorPersona[$indice] ?? null) : null;
                $detalleOpciones[] = [
                    'opcion' => (string) $opcion,
                    'monto' => $precioPersona === null ? null : (float) $precioPersona,
                ];
            }
        }
        $factura = [
            'tipo' => 'experiencia',
            'folio' => 'FAC-EXP-' . (int) $experienciaCobro['id_agenda'],
            'fecha' => date('Y-m-d H:i:s'),
            'huesped' => (string) $experienciaCobro['nombre_cliente'],
            'correo' => (string) $experienciaCobro['correo_cliente'],
            'cod_reserva' => $experienciaCobro['cod_res_agenda'] === null ? null : (int) $experienciaCobro['cod_res_agenda'],
            'experiencia' => (string) $experienciaCobro['actividad'],
            'opciones' => $detalleOpciones,
            'fecha_experiencia' => (string) $experienciaCobro['fecha_agenda'],
            'hora_experiencia' => (string) $experienciaCobro['hora_agenda'],
            'metodo_pago' => $metodoPago,
            'monto' => (float) $experienciaCobro['monto_experiencia'],
        ];
        responder_experiencias(200, 'Cobro registrado correctamente', ['factura' => $factura]);
    } catch (Throwable $error) {
        if ($transaccionIniciada) {
            $conexion->rollback();
        }
        if ($error instanceof RuntimeException && in_array($error->getMessage(), [
            'experiencia_no_encontrada',
            'pago_no_pendiente',
            'experiencia_cancelada',
            'precio_no_cobrable',
        ], true)) {
            $mensaje = match ($error->getMessage()) {
                'experiencia_no_encontrada' => 'No se encontró la experiencia',
                'pago_no_pendiente' => 'Esta experiencia ya no tiene un pago pendiente',
                'experiencia_cancelada' => 'No se puede cobrar una experiencia cancelada',
                'precio_no_cobrable' => 'La experiencia no tiene un precio válido para cobrar',
            };
            $codigo = $error->getMessage() === 'experiencia_no_encontrada' ? 404 : 422;
            responder_experiencias($codigo, $mensaje);
        }
        error_log('Error al cobrar experiencia: ' . $error->getMessage());
        responder_experiencias(500, 'No se pudo registrar el cobro de la experiencia');
    }
}

if (!usuario_tiene_permiso($conexion, 'experiencias.ver')) {
    responder_experiencias(403, 'No tienes permiso para consultar las experiencias');
}

asegurar_esquema_experiencias($conexion);

if ($metodo === 'GET' && $accion === 'listar') {
    $puedeGestionar = usuario_tiene_permiso($conexion, 'experiencias.gestionar');
    $historial = [];
    if ($puedeGestionar) {
        asegurar_esquema_agenda_experiencias($conexion);
        $historial = obtener_historial_experiencias($conexion);
    }
    responder_experiencias(200, 'Experiencias cargadas', [
        'experiencias' => obtener_experiencias($conexion),
        'historial' => $historial,
        'puede_gestionar' => $puedeGestionar,
    ]);
}

if ($metodo !== 'POST') {
    responder_experiencias(405, 'Método no permitido');
}

exigir_csrf();

if (!usuario_tiene_permiso($conexion, 'experiencias.gestionar')) {
    responder_experiencias(403, 'No tienes permiso para gestionar las experiencias');
}

if ($accion === 'guardar') {
    $zonaHorariaHotel = new DateTimeZone('America/Bogota');
    $fechaHoy = (new DateTimeImmutable('now', $zonaHorariaHotel))->format('Y-m-d');
    $idDato = $_POST['id'] ?? '';
    if (!is_string($idDato)) {
        responder_experiencias(422, 'Experiencia no válida');
    }
    $idOriginal = $idDato;
    $id = $idOriginal === '' ? null : filter_var($idOriginal, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($idOriginal !== '' && !$id) {
        responder_experiencias(422, 'Experiencia no válida');
    }
    foreach (['categoria', 'nombre', 'descripcion'] as $campo) {
        if (!isset($_POST[$campo]) || !is_string($_POST[$campo])) {
            responder_experiencias(422, 'Completa todos los campos de la experiencia');
        }
    }
    $categoria = trim($_POST['categoria']);
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $opciones = [];
    foreach ([1, 2, 3] as $indice) {
        $valor = $_POST['opcion_' . $indice] ?? null;
        if (!is_string($valor)) {
            responder_experiencias(422, 'Revisa las opciones de la experiencia');
        }
        $opciones[] = trim($valor);
    }
    $precios = [];
    foreach ([1, 2, 3] as $indice) {
        $precio = $_POST['precio_opcion_' . $indice] ?? '';
        if (!is_string($precio)) {
            responder_experiencias(422, 'Revisa los precios de las opciones');
        }
        $precio = trim($precio);
        if ($precio !== '' && (!preg_match('/^\d{1,10}$/', $precio) || (float) $precio > 9999999999)) {
            responder_experiencias(422, 'Cada precio debe ser un monto entero válido en pesos colombianos');
        }
        $precios[] = $precio === '' ? null : $precio . '.00';
    }
    $horariosTexto = $_POST['horarios'] ?? null;
    if (!is_string($horariosTexto)) {
        responder_experiencias(422, 'Configura los horarios de la experiencia');
    }
    $horarios = json_decode($horariosTexto, true);

    $horariosExistentes = [];
    if ($id) {
        $stmtHorarios = $conexion->prepare('SELECT horarios_json FROM experiencias WHERE id = ?');
        $stmtHorarios->bind_param('i', $id);
        $stmtHorarios->execute();
        $stmtHorarios->bind_result($horariosExistentesJson);
        if ($stmtHorarios->fetch()) {
            $horariosExistentes = json_decode((string) $horariosExistentesJson, true) ?: [];
        }
        $stmtHorarios->close();
    }

    if ($categoria === '' || mb_strlen($categoria) > 50) {
        responder_experiencias(422, 'La categoría es obligatoria y debe tener máximo 50 caracteres');
    }
    if ($nombre === '' || mb_strlen($nombre) > 150) {
        responder_experiencias(422, 'El nombre es obligatorio y debe tener máximo 150 caracteres');
    }
    if ($descripcion === '') {
        responder_experiencias(422, 'La descripción es obligatoria');
    }

    $opcionesDefinidas = array_filter($opciones, static fn(string $opcion): bool => $opcion !== '');
    if ($opcionesDefinidas === []) {
        responder_experiencias(422, 'Define al menos una opción para la experiencia');
    }
    $opcionVaciaEncontrada = false;
    foreach ($opciones as $opcion) {
        if ($opcion === '') {
            $opcionVaciaEncontrada = true;
            continue;
        }
        if ($opcionVaciaEncontrada) {
            responder_experiencias(422, 'Completa las opciones en orden, sin dejar espacios entre ellas');
        }
        if (mb_strlen($opcion) > 100) {
            responder_experiencias(422, 'Cada opción admite máximo 100 caracteres');
        }
    }
    if (count(array_unique(array_map(static fn(string $opcion): string => mb_strtolower($opcion, 'UTF-8'), $opcionesDefinidas))) !== count($opcionesDefinidas)) {
        responder_experiencias(422, 'Las opciones deben ser diferentes');
    }
    foreach ($precios as $indice => $precio) {
        if ($opciones[$indice] === '') {
            $precios[$indice] = null;
        }
    }

    if (!is_array($horarios) || !isset($horarios['opciones']) || !is_array($horarios['opciones'])) {
        responder_experiencias(422, 'Configura los horarios de la experiencia');
    }
    $horariosValidados = [];
    foreach ([1, 2, 3] as $numeroOpcion) {
        if ($opciones[$numeroOpcion - 1] === '') {
            $horariosValidados[(string) $numeroOpcion] = [];
            continue;
        }
        $fechasOpcion = $horarios['opciones'][(string) $numeroOpcion] ?? $horarios['opciones'][$numeroOpcion] ?? null;
        if (!is_array($fechasOpcion) || count($fechasOpcion) === 0) {
            responder_experiencias(422, 'Configura al menos una fecha y un horario para cada opción definida');
        }
        if (count($fechasOpcion) > 100) {
            responder_experiencias(422, 'Cada opción admite hasta 100 fechas programadas');
        }
        $horariosValidados[(string) $numeroOpcion] = [];
        foreach ($fechasOpcion as $fecha => $franjas) {
            $fecha = (string) $fecha;
<<<<<<< HEAD
            $fechaValidada = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha, $zonaHorariaHotel);
            if ($fechaValidada === false || $fechaValidada->format('Y-m-d') !== $fecha || $fecha < $fechaHoy) {
=======
            $fechaValidada = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
            $fechaPasadaNueva = $fecha < date('Y-m-d')
                && !isset($horariosExistentes[(string) $numeroOpcion][$fecha]);
            if ($fechaValidada === false || $fechaValidada->format('Y-m-d') !== $fecha || $fechaPasadaNueva) {
>>>>>>> 5e3348b9c14dfceda52231c3b04767be5145f18c
                responder_experiencias(422, 'Selecciona fechas válidas, desde hoy en adelante');
            }
            $normalizados = normalizar_horarios_experiencia([(string) $numeroOpcion => [$fecha => $franjas]]);
            $rango = $normalizados[(string) $numeroOpcion][$fecha] ?? null;
            if ($rango === null) {
                responder_experiencias(422, 'Define una hora de inicio y una de fin válidas, en intervalos de 30 minutos');
            }
            $horariosValidados[(string) $numeroOpcion][$fecha] = $rango;
        }
    }

    $imagenActual = null;
    $quitarImagen = ($_POST['quitar_imagen'] ?? '0') === '1';
    if ($id) {
        $stmtImagen = $conexion->prepare('SELECT imagen FROM experiencias WHERE id = ?');
        $stmtImagen->bind_param('i', $id);
        $stmtImagen->execute();
        $stmtImagen->bind_result($imagenActual);
        if (!$stmtImagen->fetch()) {
            $stmtImagen->close();
            responder_experiencias(404, 'Experiencia no encontrada');
        }
        $stmtImagen->close();
    }

    $imagenNueva = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        $archivo = $_FILES['imagen'];
        if ($archivo['error'] !== UPLOAD_ERR_OK || $archivo['size'] > 5 * 1024 * 1024) {
            responder_experiencias(422, 'La imagen no se pudo cargar o supera los 5 MB');
        }
        if (!is_uploaded_file($archivo['tmp_name'])) {
            responder_experiencias(422, 'El archivo de imagen no es válido');
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($archivo['tmp_name']);
        $extensionesPermitidas = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        if (!isset($extensionesPermitidas[$mime])) {
            responder_experiencias(422, 'Usa una imagen JPG, PNG o WebP');
        }
        $dimensiones = getimagesize($archivo['tmp_name']);
        if ($dimensiones === false || $dimensiones[0] * $dimensiones[1] > 20000000) {
            responder_experiencias(422, 'La imagen no es válida o tiene dimensiones demasiado grandes');
        }

        $directorio = __DIR__ . '/../assets/uploads/experiencias';
        if (!is_dir($directorio) && !mkdir($directorio, 0750, true) && !is_dir($directorio)) {
            error_log('No se pudo crear el directorio de imágenes de experiencias');
            responder_experiencias(500, 'No se pudo preparar el almacenamiento de imágenes');
        }
        $nombreArchivo = bin2hex(random_bytes(16)) . '.' . $extensionesPermitidas[$mime];
        if (!move_uploaded_file($archivo['tmp_name'], $directorio . '/' . $nombreArchivo)) {
            error_log('No se pudo mover una imagen cargada para una experiencia');
            responder_experiencias(500, 'No se pudo guardar la imagen');
        }
        $imagenNueva = 'assets/uploads/experiencias/' . $nombreArchivo;
    }

    $imagen = $imagenNueva ?? ($quitarImagen ? null : $imagenActual);
    $horariosJson = json_encode($horariosValidados, JSON_UNESCAPED_UNICODE);
    if ($horariosJson === false) {
        responder_experiencias(500, 'No se pudieron procesar los horarios');
    }
    [$opcionUno, $opcionDos, $opcionTres] = $opciones;
    [$precioOpcionUno, $precioOpcionDos, $precioOpcionTres] = $precios;

    try {
        if ($id) {
            $stmt = $conexion->prepare(
                'UPDATE experiencias
                 SET categoria = ?, nombre = ?, descripcion = ?, imagen = ?, opcion_1 = ?, opcion_2 = ?, opcion_3 = ?,
                     precio_opcion_1 = ?, precio_opcion_2 = ?, precio_opcion_3 = ?, horarios_json = ?
                 WHERE id = ?'
            );
            $stmt->bind_param(
                'sssssssssssi',
                $categoria,
                $nombre,
                $descripcion,
                $imagen,
                $opcionUno,
                $opcionDos,
                $opcionTres,
                $precioOpcionUno,
                $precioOpcionDos,
                $precioOpcionTres,
                $horariosJson,
                $id
            );
            if (!$stmt->execute()) {
                throw new RuntimeException('MySQL rechazó la actualización de la experiencia: ' . $stmt->error);
            }
        } else {
            $stmt = $conexion->prepare(
                'INSERT INTO experiencias (categoria, nombre, descripcion, imagen, opcion_1, opcion_2, opcion_3,
                                           precio_opcion_1, precio_opcion_2, precio_opcion_3, horarios_json)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param(
                'sssssssssss',
                $categoria,
                $nombre,
                $descripcion,
                $imagen,
                $opcionUno,
                $opcionDos,
                $opcionTres,
                $precioOpcionUno,
                $precioOpcionDos,
                $precioOpcionTres,
                $horariosJson
            );
            if (!$stmt->execute()) {
                throw new RuntimeException('MySQL rechazó la inserción de la experiencia: ' . $stmt->error);
            }
            $id = $stmt->insert_id;
        }

        if (($imagenNueva !== null || $quitarImagen) && is_string($imagenActual)) {
            $rutaAnterior = realpath(__DIR__ . '/../' . $imagenActual);
            $directorioCarga = realpath(__DIR__ . '/../assets/uploads/experiencias');
            if ($rutaAnterior !== false && $directorioCarga !== false && str_starts_with($rutaAnterior, $directorioCarga . DIRECTORY_SEPARATOR) && is_file($rutaAnterior)) {
                if (!unlink($rutaAnterior)) {
                    error_log('No se pudo eliminar la imagen anterior de una experiencia');
                }
            }
        }

        responder_experiencias(200, 'Experiencia guardada correctamente', ['id' => (int) $id]);
    } catch (Throwable $error) {
        if ($imagenNueva !== null && is_file(__DIR__ . '/../' . $imagenNueva) && !unlink(__DIR__ . '/../' . $imagenNueva)) {
            error_log('No se pudo limpiar una nueva imagen tras fallar la actualización de experiencia');
        }
        error_log('Error al guardar experiencia: ' . $error->getMessage());
        responder_experiencias(500, 'No se pudo guardar la experiencia');
    }
}

if ($accion === 'eliminar') {
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) {
        responder_experiencias(422, 'Experiencia no válida');
    }

    $stmtImagen = $conexion->prepare('SELECT imagen FROM experiencias WHERE id = ?');
    $stmtImagen->bind_param('i', $id);
    $stmtImagen->execute();
    $stmtImagen->bind_result($imagenEliminada);
    $encontrada = $stmtImagen->fetch();
    $stmtImagen->close();
    if (!$encontrada) {
        responder_experiencias(404, 'Experiencia no encontrada');
    }

    $stmt = $conexion->prepare('DELETE FROM experiencias WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    if ($stmt->affected_rows === 1 && is_string($imagenEliminada)) {
        $rutaImagen = realpath(__DIR__ . '/../' . $imagenEliminada);
        $directorioCarga = realpath(__DIR__ . '/../assets/uploads/experiencias');
        if ($rutaImagen !== false && $directorioCarga !== false && str_starts_with($rutaImagen, $directorioCarga . DIRECTORY_SEPARATOR) && is_file($rutaImagen)) {
            if (!unlink($rutaImagen)) {
                error_log('No se pudo eliminar la imagen de una experiencia eliminada');
            }
        }
    }

    responder_experiencias(
        $stmt->affected_rows === 1 ? 200 : 404,
        $stmt->affected_rows === 1 ? 'Experiencia eliminada correctamente' : 'Experiencia no encontrada'
    );
}

responder_experiencias(400, 'Acción no válida');