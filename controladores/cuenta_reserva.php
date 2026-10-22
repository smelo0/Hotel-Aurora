<?php
declare(strict_types=1);

/**
 * Cuenta consolidada de una reserva: alojamiento + experiencias + room service.
 *
 *   GET  ?cod_res=N         Devuelve las líneas de la cuenta (total, pagado y pendiente de cada una).
 *   POST accion=cobrar      Cobra las líneas indicadas. Se puede dividir la cuenta: solo algunas líneas,
 *                           un monto parcial del alojamiento y un método de pago distinto por línea.
 *
 * Cada cobro se registra igual que los cobros individuales de la app (tablas `pagos` y `factura`
 * mediante BillingRegistrar, y room service mediante RoomServiceRepository::cobrar).
 * Cada línea se cobra en su propia transacción: si una falla, las que ya se cobraron quedan registradas
 * y la respuesta lo indica.
 */

require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../configuracion/conexion.php';
require_once __DIR__ . '/../configuracion/permiso.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Billing/BillingRegistrar.php';
require_once __DIR__ . '/../src/RoomService/RoomServiceRepository.php';

use App\Billing\BillingRegistrar;
use App\Logger;
use App\RoomService\RoomServiceRepository;

header('Content-Type: application/json; charset=utf-8');
/** @var mysqli $conexion */
exigir_permiso($conexion, 'reservas.editar');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
BillingRegistrar::asegurarEsquema($conexion);

$puedeCobrarExperiencias = usuario_tiene_permiso($conexion, 'finanzas.ver') && usuario_tiene_permiso($conexion, 'experiencias.gestionar');
$puedeCobrarRoomService = usuario_tiene_permiso($conexion, 'finanzas.ver') && usuario_tiene_permiso($conexion, 'roomservice.gestionar');

function cuenta_responder(int $codigo, array $datos): never
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit();
}

function cuenta_filas(mysqli $db, string $sql, string $tipos = '', array $params = []): array
{
    $stmt = $db->prepare($sql);
    if ($tipos !== '') {
        $stmt->bind_param($tipos, ...$params);
    }
    $stmt->execute();
    $resultado = $stmt->get_result();
    $filas = $resultado instanceof mysqli_result ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $filas;
}

/** Ejecuta INSERT/UPDATE/DELETE y devuelve [filas afectadas, id insertado]. */
function cuenta_ejecutar(mysqli $db, string $sql, string $tipos, array $params): array
{
    $stmt = $db->prepare($sql);
    if ($tipos !== '') {
        $stmt->bind_param($tipos, ...$params);
    }
    $stmt->execute();
    $resultado = [(int) $stmt->affected_rows, (int) $db->insert_id];
    $stmt->close();
    return $resultado;
}

function cuenta_noches(string $entrada, string $salida): int
{
    $desde = (new DateTimeImmutable($entrada))->setTime(0, 0, 0);
    $hasta = (new DateTimeImmutable($salida))->setTime(0, 0, 0);
    return max(1, (int) $desde->diff($hasta)->days);
}

function cuenta_texto_opciones(?string $json): string
{
    $opciones = json_decode((string) $json, true);
    return is_array($opciones) ? implode(' · ', array_map('strval', $opciones)) : '';
}

/**
 * Arma la cuenta de la reserva leyendo la base de datos.
 * Las experiencias siguen la misma regla del listado de Reservas: vinculadas a la reserva, o del mismo
 * huésped con fecha dentro de la estancia (día de salida incluido).
 */
function cuenta_construir(mysqli $db, int $codRes, bool $puedeExperiencias, bool $puedeRoomService, bool $bloquear = false): ?array
{
    $reservas = cuenta_filas(
        $db,
        'SELECT r.cod_res, r.est_res, r.fec_ent_res, r.fec_sal_res, r.id_usu_res, u.nom_usu, u.corr_usu
         FROM reservas r INNER JOIN usuario u ON u.id_usu = r.id_usu_res
         WHERE r.cod_res = ?' . ($bloquear ? ' FOR UPDATE' : ''),
        'i',
        [$codRes]
    );
    if ($reservas === []) {
        return null;
    }
    $reserva = $reservas[0];
    $estado = (string) $reserva['est_res'];
    $cancelada = in_array(strtolower(trim($estado)), ['cancelada', 'cancelado'], true);
    $noches = cuenta_noches((string) $reserva['fec_ent_res'], (string) $reserva['fec_sal_res']);

    $habitaciones = cuenta_filas(
        $db,
        'SELECT h.num_hab, h.tipo_hab, COALESCE(NULLIF(h.pre_hab, 0), h.precio_hab, 0) AS precio
         FROM detalle d INNER JOIN habitacion h ON h.cod_hab = d.cod_hab_det
         WHERE d.cod_res_det = ? ORDER BY h.num_hab',
        'i',
        [$codRes]
    );
    $precioNoche = 0.0;
    foreach ($habitaciones as $habitacion) {
        $precioNoche += (float) $habitacion['precio'];
    }
    $totalAlojamiento = round($precioNoche * $noches * 1.19, 2);
    // Solo cuentan los pagos de alojamiento: lo pagado en experiencias o room service no descuenta el hospedaje.
    $pagadoAlojamiento = (float) (cuenta_filas(
        $db,
        "SELECT COALESCE(SUM(monto), 0) AS suma FROM pagos
         WHERE cod_res_pago = ? AND concepto = 'alojamiento' AND estado_pago = 'Aprobado'",
        'i',
        [$codRes]
    )[0]['suma'] ?? 0);
    $pendienteAlojamiento = $cancelada ? 0.0 : round(max(0.0, $totalAlojamiento - $pagadoAlojamiento), 2);

    $numeros = array_map(static fn(array $h): string => (string) $h['num_hab'], $habitaciones);
    $tipos = array_values(array_unique(array_filter(array_map(static fn(array $h): string => (string) $h['tipo_hab'], $habitaciones))));
    $textoHabitaciones = implode(', ', array_map(
        static fn(array $h): string => 'Hab. ' . $h['num_hab'] . ($h['tipo_hab'] ? ' · ' . $h['tipo_hab'] : ''),
        $habitaciones
    ));

    $lineas = [[
        'tipo' => 'alojamiento',
        'id' => 0,
        'concepto' => 'Alojamiento',
        'detalle' => trim($textoHabitaciones . ' · ' . $noches . ' noche(s), IVA incluido', ' ·'),
        'total' => $totalAlojamiento,
        'pagado' => min($pagadoAlojamiento, $totalAlojamiento),
        'pendiente' => $pendienteAlojamiento,
        'cobrable' => $pendienteAlojamiento > 0,
        'nota' => $cancelada ? 'Reserva cancelada' : '',
        'folio' => null,
    ]];

    $experiencias = cuenta_filas(
        $db,
        "SELECT a.id_agenda, a.cod_res_agenda, a.actividad, a.selecciones_personas_json, a.fecha_agenda, a.hora_agenda,
                a.estado_agenda, a.estado_pago_experiencia, a.monto_experiencia
         FROM reservas r
         INNER JOIN agenda_actividad a
            ON a.cod_res_agenda = r.cod_res
            OR (
                a.id_usu_agenda = r.id_usu_res
                AND a.fecha_agenda IS NOT NULL
                AND a.fecha_agenda BETWEEN DATE(r.fec_ent_res) AND DATE(r.fec_sal_res)
                AND LOWER(TRIM(r.est_res)) NOT IN ('cancelada', 'cancelado')
            )
         WHERE r.cod_res = ?
         ORDER BY a.fecha_agenda, a.hora_agenda, a.id_agenda",
        'i',
        [$codRes]
    );
    foreach ($experiencias as $experiencia) {
        $estadoAgenda = trim((string) ($experiencia['estado_agenda'] ?? ''));
        $estadoPago = trim((string) ($experiencia['estado_pago_experiencia'] ?? 'Pendiente'));
        if (strtolower($estadoAgenda) === 'cancelada' || strtolower($estadoPago) === 'cancelada') {
            continue;
        }
        $monto = $experiencia['monto_experiencia'] === null ? null : round((float) $experiencia['monto_experiencia'], 2);
        $pagada = $estadoPago === 'Pagada';
        $pendiente = (!$pagada && $monto !== null && $monto > 0) ? $monto : 0.0;
        $vinculada = $experiencia['cod_res_agenda'] === null ? null : (int) $experiencia['cod_res_agenda'];
        $enOtraReserva = $vinculada !== null && $vinculada !== $codRes;
        $nota = $monto === null ? 'Sin precio definido' : ($enOtraReserva ? 'Se cobra desde la reserva #' . $vinculada : '');
        if ($pendiente > 0 && !$enOtraReserva && !$puedeExperiencias) {
            $nota = 'Sin permiso para cobrar experiencias';
        }
        $lineas[] = [
            'tipo' => 'experiencia',
            'id' => (int) $experiencia['id_agenda'],
            'concepto' => (string) $experiencia['actividad'],
            'detalle' => trim(
                date('d/m/Y', strtotime((string) $experiencia['fecha_agenda']))
                . ' ' . substr((string) $experiencia['hora_agenda'], 0, 5)
                . ' · ' . cuenta_texto_opciones($experiencia['selecciones_personas_json'] ?? null),
                ' ·'
            ),
            'total' => (float) ($monto ?? 0),
            'pagado' => $pagada ? (float) ($monto ?? 0) : 0.0,
            'pendiente' => $pendiente,
            'cobrable' => $pendiente > 0 && !$enOtraReserva && $puedeExperiencias,
            'nota' => $nota,
            'folio' => null,
        ];
    }

    try {
        $pedidos = cuenta_filas(
            $db,
            "SELECT id_pedido, estado, total, estado_pago, folio FROM room_service_pedidos
             WHERE cod_res_pedido = ? AND estado <> 'Cancelado' AND total > 0 ORDER BY id_pedido",
            'i',
            [$codRes]
        );
    } catch (Throwable $e) {
        $pedidos = []; // instalaciones sin room service
    }
    foreach ($pedidos as $pedido) {
        $total = round((float) $pedido['total'], 2);
        $pagado = (string) $pedido['estado_pago'] === 'Pagado';
        $items = cuenta_filas(
            $db,
            'SELECT nombre, cantidad FROM room_service_pedido_items WHERE id_pedido = ? ORDER BY id_linea',
            'i',
            [(int) $pedido['id_pedido']]
        );
        $lineas[] = [
            'tipo' => 'room_service',
            'id' => (int) $pedido['id_pedido'],
            'concepto' => 'Room service #' . (int) $pedido['id_pedido'],
            'detalle' => trim(implode(', ', array_map(
                static fn(array $i): string => $i['cantidad'] . '× ' . $i['nombre'],
                $items
            )) . ' · ' . $pedido['estado'], ' ·'),
            'total' => $total,
            'pagado' => $pagado ? $total : 0.0,
            'pendiente' => $pagado ? 0.0 : $total,
            'cobrable' => !$pagado && $puedeRoomService,
            'nota' => (!$pagado && !$puedeRoomService) ? 'Sin permiso para cobrar room service' : '',
            'folio' => $pedido['folio'] === null ? null : (string) $pedido['folio'],
        ];
    }

    $totales = ['total' => 0.0, 'pagado' => 0.0, 'pendiente' => 0.0];
    foreach ($lineas as $linea) {
        $totales['total'] += $linea['total'];
        $totales['pagado'] += $linea['pagado'];
        $totales['pendiente'] += $linea['pendiente'];
    }

    return [
        'cod_res' => $codRes,
        'huesped' => (string) $reserva['nom_usu'],
        'correo' => (string) $reserva['corr_usu'],
        'estado' => $estado,
        'fecha_entrada' => (string) $reserva['fec_ent_res'],
        'fecha_salida' => (string) $reserva['fec_sal_res'],
        'noches' => $noches,
        'habitaciones_numeros' => implode(', ', $numeros),
        'habitaciones_tipos' => implode(', ', $tipos),
        'lineas' => $lineas,
        'totales' => array_map(static fn(float $v): float => round($v, 2), $totales),
    ];
}

/** Cobra (total o parcialmente) el alojamiento. Devuelve la factura. */
function cuenta_cobrar_alojamiento(mysqli $db, int $codRes, ?float $montoSolicitado, string $metodo, bool $puedeExp, bool $puedeRs): array
{
    $db->begin_transaction();
    try {
        $cuenta = cuenta_construir($db, $codRes, $puedeExp, $puedeRs, true);
        if ($cuenta === null) {
            throw new RuntimeException('reserva_no_encontrada');
        }
        $saldo = (float) $cuenta['lineas'][0]['pendiente'];
        if ($saldo <= 0) {
            throw new RuntimeException('linea_no_cobrable');
        }
        $monto = round($montoSolicitado ?? $saldo, 2);
        if ($monto <= 0 || $monto > $saldo + 0.009) {
            throw new RuntimeException('monto_invalido');
        }
        $monto = min($monto, $saldo);

        $pendientes = cuenta_filas(
            $db,
            "SELECT id_pago FROM pagos
             WHERE cod_res_pago = ? AND concepto = 'alojamiento' AND estado_pago = 'Pendiente'
             ORDER BY id_pago FOR UPDATE",
            'i',
            [$codRes]
        );
        if ($pendientes !== []) {
            // Se reutiliza el pago "Pendiente" (p. ej. pago en recepción) y se aprueba con el monto cobrado.
            $idPago = (int) $pendientes[0]['id_pago'];
            cuenta_ejecutar(
                $db,
                "UPDATE pagos SET monto = ?, metodo_pago = ?, estado_pago = 'Aprobado', fecha_pago = CURRENT_TIMESTAMP
                 WHERE id_pago = ?",
                'dsi',
                [$monto, $metodo, $idPago]
            );
            foreach (array_slice($pendientes, 1) as $sobrante) {
                cuenta_ejecutar($db, 'DELETE FROM pagos WHERE id_pago = ?', 'i', [(int) $sobrante['id_pago']]);
            }
        } else {
            [, $idPago] = cuenta_ejecutar(
                $db,
                "INSERT INTO pagos (cod_res_pago, monto, metodo_pago, referencia_pago, estado_pago, concepto)
                 VALUES (?, ?, ?, ?, 'Aprobado', 'alojamiento')",
                'idss',
                [$codRes, $monto, $metodo, 'REF-' . bin2hex(random_bytes(16))]
            );
        }

        if (strtolower(trim($cuenta['estado'])) === 'pendiente') {
            cuenta_ejecutar($db, "UPDATE reservas SET est_res = 'Confirmada' WHERE cod_res = ?", 'i', [$codRes]);
        }
        $db->commit();
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }

    $factura = [
        'tipo' => 'alojamiento',
        'folio' => 'FAC-' . $idPago,
        'cod_reserva' => $codRes,
        'fecha' => date('Y-m-d H:i:s'),
        'huesped' => $cuenta['huesped'],
        'correo' => $cuenta['correo'],
        'habitacion' => $cuenta['habitaciones_numeros'] !== '' ? $cuenta['habitaciones_numeros'] : 'Sin asignar',
        'tipo_habitacion' => $cuenta['habitaciones_tipos'],
        'fecha_entrada' => $cuenta['fecha_entrada'],
        'fecha_salida' => $cuenta['fecha_salida'],
        'noches' => $cuenta['noches'],
        'metodo_pago' => $metodo,
        'monto' => $monto,
    ];
    BillingRegistrar::registrarFactura($db, $factura['folio'], 'alojamiento', $codRes, $monto, $metodo, $factura);
    return $factura;
}

/** Cobra una experiencia (misma lógica que gestionar_experiencias.php) y la deja "Confirmada". */
function cuenta_cobrar_experiencia(mysqli $db, int $codRes, int $idAgenda, string $metodo, array $cuenta): array
{
    $db->begin_transaction();
    try {
        $filas = cuenta_filas(
            $db,
            'SELECT id_agenda, cod_res_agenda, actividad, selecciones_personas_json, precios_personas_json,
                    fecha_agenda, hora_agenda, estado_agenda, estado_pago_experiencia, monto_experiencia
             FROM agenda_actividad WHERE id_agenda = ? FOR UPDATE',
            'i',
            [$idAgenda]
        );
        $experiencia = $filas[0] ?? null;
        if (!$experiencia
            || ($experiencia['estado_pago_experiencia'] ?? 'Pendiente') !== 'Pendiente'
            || strtolower((string) $experiencia['estado_agenda']) === 'cancelada'
            || $experiencia['monto_experiencia'] === null
            || (float) $experiencia['monto_experiencia'] <= 0) {
            throw new RuntimeException('linea_no_cobrable');
        }
        $vinculada = $experiencia['cod_res_agenda'] === null ? null : (int) $experiencia['cod_res_agenda'];
        if ($vinculada !== null && $vinculada !== $codRes) {
            throw new RuntimeException('linea_no_cobrable');
        }
        $monto = round((float) $experiencia['monto_experiencia'], 2);

        [$afectadas] = cuenta_ejecutar(
            $db,
            "UPDATE agenda_actividad
             SET estado_pago_experiencia = 'Pagada', fecha_pago_experiencia = CURRENT_TIMESTAMP,
                 metodo_pago_experiencia = ?,
                 estado_agenda = IF(COALESCE(estado_agenda, 'Pendiente') = 'Pendiente', 'Confirmada', estado_agenda),
                 cod_res_agenda = COALESCE(cod_res_agenda, ?)
             WHERE id_agenda = ? AND estado_pago_experiencia = 'Pendiente'",
            'sii',
            [$metodo, $codRes, $idAgenda]
        );
        if ($afectadas !== 1) {
            throw new RuntimeException('linea_no_cobrable');
        }
        BillingRegistrar::registrarPago($db, $codRes, $monto, $metodo, 'EXP-' . $idAgenda, 'experiencia');
        $db->commit();
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }

    $opciones = json_decode((string) $experiencia['selecciones_personas_json'], true);
    $precios = json_decode((string) $experiencia['precios_personas_json'], true);
    $detalleOpciones = [];
    foreach (is_array($opciones) ? $opciones : [] as $i => $opcion) {
        $precioPersona = is_array($precios) ? ($precios[$i] ?? null) : null;
        $detalleOpciones[] = ['opcion' => (string) $opcion, 'monto' => $precioPersona === null ? null : (float) $precioPersona];
    }
    $factura = [
        'tipo' => 'experiencia',
        'folio' => 'FAC-EXP-' . $idAgenda,
        'fecha' => date('Y-m-d H:i:s'),
        'huesped' => $cuenta['huesped'],
        'correo' => $cuenta['correo'],
        'cod_reserva' => $codRes,
        'experiencia' => (string) $experiencia['actividad'],
        'opciones' => $detalleOpciones,
        'fecha_experiencia' => (string) $experiencia['fecha_agenda'],
        'hora_experiencia' => (string) $experiencia['hora_agenda'],
        'metodo_pago' => $metodo,
        'monto' => $monto,
    ];
    BillingRegistrar::registrarFactura($db, $factura['folio'], 'experiencia', $codRes, $monto, $metodo, $factura);
    return $factura;
}

$idUsuarioLog = $_SESSION['emp_auth']['id_usuario'] ?? $_SESSION['user_auth']['id_usuario'] ?? 0;
$metodoHttp = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($metodoHttp === 'GET') {
    $codRes = filter_var($_GET['cod_res'] ?? null, FILTER_VALIDATE_INT);
    if (!$codRes) {
        cuenta_responder(422, ['status' => 'error', 'mensaje' => 'Reserva no válida.']);
    }
    try {
        $cuenta = cuenta_construir($conexion, (int) $codRes, $puedeCobrarExperiencias, $puedeCobrarRoomService);
    } catch (Throwable $e) {
        Logger::registrarLog('ERROR', 'No se pudo consultar la cuenta de la reserva', [
            'cod_res' => $codRes, 'error_excepcion' => $e->getMessage(),
        ]);
        cuenta_responder(500, ['status' => 'error', 'mensaje' => 'No se pudo consultar la cuenta de la reserva.']);
    }
    if ($cuenta === null) {
        cuenta_responder(404, ['status' => 'error', 'mensaje' => 'No se encontró la reserva.']);
    }
    cuenta_responder(200, ['status' => 'exito', 'cuenta' => $cuenta]);
}

if ($metodoHttp !== 'POST') {
    cuenta_responder(405, ['status' => 'error', 'mensaje' => 'Método no permitido.']);
}

exigir_csrf();

const CUENTA_MENSAJES = [
    'linea_no_cobrable' => 'Ya fue pagado o no se puede cobrar. Actualiza la cuenta e inténtalo de nuevo.',
    'monto_invalido' => 'El monto del alojamiento debe ser mayor que cero y no superar el saldo pendiente.',
    'reserva_no_encontrada' => 'No se encontró la reserva.',
    'sin_pendientes' => 'No hay pedidos de room service pendientes de cobro.',
];

$codRes = filter_var($_POST['cod_res'] ?? null, FILTER_VALIDATE_INT);
$metodoGeneral = trim((string) ($_POST['metodo_pago'] ?? ''));
$solicitadas = json_decode((string) ($_POST['lineas'] ?? ''), true);
if (!$codRes || !in_array($metodoGeneral, RoomServiceRepository::METODOS_PAGO, true) || !is_array($solicitadas) || $solicitadas === []) {
    cuenta_responder(422, ['status' => 'error', 'mensaje' => 'Selecciona un método de pago y al menos un concepto para cobrar.']);
}
$codRes = (int) $codRes;

try {
    $cuentaInicial = cuenta_construir($conexion, $codRes, $puedeCobrarExperiencias, $puedeCobrarRoomService);
} catch (Throwable $e) {
    cuenta_responder(500, ['status' => 'error', 'mensaje' => 'No se pudo consultar la cuenta de la reserva.']);
}
if ($cuentaInicial === null) {
    cuenta_responder(404, ['status' => 'error', 'mensaje' => 'No se encontró la reserva.']);
}
$indice = [];
foreach ($cuentaInicial['lineas'] as $linea) {
    $indice[$linea['tipo'] . '|' . $linea['id']] = $linea;
}

$resultados = [];
$facturas = [];
$totalCobrado = 0.0;
$vistas = [];
$repoRoomService = new RoomServiceRepository($conexion);

foreach ($solicitadas as $pedido) {
    $tipo = (string) ($pedido['tipo'] ?? '');
    $id = (int) ($pedido['id'] ?? 0);
    $clave = $tipo . '|' . $id;
    $metodo = in_array($pedido['metodo'] ?? null, RoomServiceRepository::METODOS_PAGO, true) ? (string) $pedido['metodo'] : $metodoGeneral;
    $resultado = ['tipo' => $tipo, 'id' => $id, 'concepto' => $indice[$clave]['concepto'] ?? $tipo, 'ok' => false, 'mensaje' => ''];

    try {
        if (!isset($indice[$clave]) || isset($vistas[$clave])) {
            throw new RuntimeException('linea_no_cobrable');
        }
        $vistas[$clave] = true;
        if (!$indice[$clave]['cobrable']) {
            throw new RuntimeException('linea_no_cobrable');
        }

        if ($tipo === 'alojamiento') {
            $monto = isset($pedido['monto']) ? round((float) $pedido['monto'], 2) : null;
            $factura = cuenta_cobrar_alojamiento($conexion, $codRes, $monto, $metodo, $puedeCobrarExperiencias, $puedeCobrarRoomService);
        } elseif ($tipo === 'experiencia') {
            $factura = cuenta_cobrar_experiencia($conexion, $codRes, $id, $metodo, $cuentaInicial);
        } elseif ($tipo === 'room_service') {
            $factura = $repoRoomService->cobrar($metodo, null, $id);
        } else {
            throw new RuntimeException('linea_no_cobrable');
        }

        $resultado['ok'] = true;
        $resultado['mensaje'] = 'Cobrado';
        $resultado['metodo'] = $metodo;
        $resultado['monto'] = (float) $factura['monto'];
        $resultado['folio'] = (string) $factura['folio'];
        $facturas[] = $factura;
        $totalCobrado += (float) $factura['monto'];
    } catch (Throwable $e) {
        $resultado['mensaje'] = CUENTA_MENSAJES[$e->getMessage()] ?? 'No se pudo registrar este cobro.';
        if (!isset(CUENTA_MENSAJES[$e->getMessage()])) {
            Logger::registrarLog('ERROR', 'Fallo al cobrar una línea de la cuenta de la reserva', [
                'id_usuario' => $idUsuarioLog, 'cod_res' => $codRes, 'linea' => $clave, 'error_excepcion' => $e->getMessage(),
            ]);
        }
    }
    $resultados[] = $resultado;
}

$cobradas = count(array_filter($resultados, static fn(array $r): bool => $r['ok']));
if ($cobradas > 0) {
    Logger::registrarLog('INFO', 'Cobro de cuenta de reserva registrado', [
        'id_usuario' => $idUsuarioLog,
        'cod_res' => $codRes,
        'lineas_cobradas' => $cobradas,
        'lineas_con_error' => count($resultados) - $cobradas,
        'monto_cobrado' => round($totalCobrado, 2),
    ]);
}

try {
    $cuentaFinal = cuenta_construir($conexion, $codRes, $puedeCobrarExperiencias, $puedeCobrarRoomService);
} catch (Throwable $e) {
    $cuentaFinal = null;
}

$estado = $cobradas === 0 ? 'error' : ($cobradas === count($resultados) ? 'exito' : 'parcial');
$errores = array_values(array_filter($resultados, static fn(array $r): bool => !$r['ok']));
cuenta_responder($cobradas === 0 ? 422 : 200, [
    'status' => $estado,
    'mensaje' => $estado === 'exito'
        ? 'Cobro registrado correctamente.'
        : ($estado === 'parcial'
            ? 'Algunos conceptos se cobraron y otros no: ' . implode(' · ', array_map(static fn(array $r): string => $r['concepto'] . ': ' . $r['mensaje'], $errores))
            : ($errores[0]['mensaje'] ?? 'No se pudo registrar el cobro.')),
    'resultados' => $resultados,
    'facturas' => $facturas,
    'total_cobrado' => round($totalCobrado, 2),
    'cuenta' => $cuentaFinal,
]);