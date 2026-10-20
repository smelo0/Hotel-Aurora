<?php
declare(strict_types=1);

/**
 * Endpoint JSON del Room Service.
 *
 *  Huésped (sesión de cliente):
 *    GET  ?accion=cliente_datos      catálogo, habitaciones donde puede pedir y sus pedidos
 *    POST accion=crear_pedido        items (JSON), cod_hab, notas
 *    POST accion=cancelar_pedido     id_pedido (solo durante la ventana de cancelación)
 *
 *  Administración (permisos roomservice.*):
 *    GET  ?accion=admin_datos        catálogo completo + cola de pedidos
 *    POST accion=guardar_item | eliminar_item | alternar_item
 *    POST accion=cambiar_estado | cancelar_pedido_admin
 *    POST accion=cobrar              (requiere además finanzas.ver)
 *    GET  ?accion=factura&folio=...  datos para reimprimir una factura
 */

require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../configuracion/conexion.php';
require_once __DIR__ . '/../configuracion/permiso.php';
require_once __DIR__ . '/../src/RoomService/RoomServiceSchemaMigrator.php';
require_once __DIR__ . '/../src/RoomService/RoomServiceRepository.php';

use App\Logger;
use App\RoomService\RoomServiceRepository;
use App\RoomService\RoomServiceSchemaMigrator;

/** @var mysqli $conexion */
header('Content-Type: application/json; charset=utf-8');

RoomServiceSchemaMigrator::asegurar($conexion);
\App\Billing\BillingRegistrar::asegurarEsquema($conexion);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function rs_responder(int $status, string $mensaje, array $datos = []): never
{
    http_response_code($status);
    echo json_encode(
        array_merge(['status' => $status < 300 ? 'exito' : 'error', 'mensaje' => $mensaje], $datos),
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

const RS_RUTA_IMAGENES = 'assets/uploads/roomservice';

function rs_borrar_imagen(?string $ruta): void
{
    if ($ruta === null || !preg_match('#^' . RS_RUTA_IMAGENES . '/[a-f0-9]{32}\.(?:jpg|png|webp)$#', $ruta)) {
        return;
    }
    $archivo = realpath(__DIR__ . '/../' . $ruta);
    $directorio = realpath(__DIR__ . '/../' . RS_RUTA_IMAGENES);
    if ($archivo !== false && $directorio !== false
        && str_starts_with($archivo, $directorio . DIRECTORY_SEPARATOR) && is_file($archivo)) {
        @unlink($archivo);
    }
}

/** Valida y guarda la imagen subida; devuelve la ruta relativa para guardar en la base de datos. */
function rs_guardar_imagen(array $archivo): string
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException(
            in_array($archivo['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                ? 'La imagen supera el tamaño permitido (5 MB)'
                : 'No se pudo subir la imagen'
        );
    }
    if (($archivo['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('La imagen supera el tamaño permitido (5 MB)');
    }
    $temporal = (string) ($archivo['tmp_name'] ?? '');
    if (!is_uploaded_file($temporal)) {
        throw new RuntimeException('No se pudo subir la imagen');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporal);
    $extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensiones[$mime]) || @getimagesize($temporal) === false) {
        throw new RuntimeException('La imagen debe ser JPG, PNG o WebP');
    }
    $directorio = __DIR__ . '/../' . RS_RUTA_IMAGENES;
    if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
        throw new RuntimeException('No se pudo preparar la carpeta de imágenes');
    }
    $nombre = bin2hex(random_bytes(16)) . '.' . $extensiones[$mime];
    if (!move_uploaded_file($temporal, $directorio . '/' . $nombre)) {
        throw new RuntimeException('No se pudo guardar la imagen');
    }
    @chmod($directorio . '/' . $nombre, 0644);
    return RS_RUTA_IMAGENES . '/' . $nombre;
}

$repo = new RoomServiceRepository($conexion);
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$accion = (string) ($_GET['accion'] ?? $_POST['accion'] ?? '');

$idCliente = (int) ($_SESSION['user_auth']['id_usuario'] ?? 0);
$esCliente = $idCliente > 0 && (int) ($_SESSION['user_auth']['rol_usuario'] ?? 0) === 6;
$idStaff = (int) ($_SESSION['emp_auth']['id_usuario'] ?? 0);

try {
    // ==================================================================
    // HUÉSPED
    // ==================================================================
    if ($metodo === 'GET' && $accion === 'cliente_datos') {
        if (!$esCliente) {
            rs_responder(401, 'Inicia sesión con tu cuenta de huésped para usar el room service.');
        }
        rs_responder(200, 'Datos cargados', [
            'catalogo' => $repo->catalogoPublico(),
            'habitaciones' => $repo->habitacionesEnEstancia($idCliente),
            'pedidos' => $repo->pedidosCliente($idCliente),
            'eta_minutos' => RoomServiceRepository::ETA_MINUTOS,
            'ventana_cancelacion_minutos' => RoomServiceRepository::VENTANA_CANCELACION_MINUTOS,
        ]);
    }

    if ($metodo === 'POST' && $accion === 'crear_pedido') {
        if (!$esCliente) {
            rs_responder(401, 'Inicia sesión con tu cuenta de huésped para pedir al room service.');
        }
        exigir_csrf();

        $itemsJson = $_POST['items'] ?? '';
        $codHab = filter_var($_POST['cod_hab'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $notas = trim((string) ($_POST['notas'] ?? ''));
        $items = is_string($itemsJson) ? json_decode($itemsJson, true) : null;

        if (!is_array($items) || !array_is_list($items) || $items === [] || !$codHab || mb_strlen($notas) > 300) {
            rs_responder(422, 'Revisa tu pedido: elige al menos un artículo y una habitación válida.');
        }

        $lineas = [];
        foreach ($items as $linea) {
            $id = is_array($linea) ? filter_var($linea['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
            $cantidad = is_array($linea) ? filter_var($linea['cantidad'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 20]]) : false;
            if (!$id || !$cantidad) {
                rs_responder(422, 'Revisa las cantidades de tu pedido (de 1 a 20 por artículo).');
            }
            $lineas[$id] = ($lineas[$id] ?? 0) + $cantidad;
            if ($lineas[$id] > 20) {
                rs_responder(422, 'Revisa las cantidades de tu pedido (de 1 a 20 por artículo).');
            }
        }

        try {
            $pedido = $repo->crearPedido($idCliente, $lineas, $codHab, $notas);
        } catch (RuntimeException $error) {
            $mensajes = [
                'sin_estancia' => 'El room service está disponible durante tu estancia, en una habitación con reserva vigente (no necesitas haber pagado antes).',
                'item_no_disponible' => 'Alguno de los artículos ya no está disponible. Actualiza el catálogo e inténtalo de nuevo.',
                'carrito_invalido' => 'Revisa tu pedido: elige al menos un artículo.',
                'demasiados_pedidos' => 'Ya tienes varios pedidos en curso. Espera a que lleguen para hacer otro.',
            ];
            if (isset($mensajes[$error->getMessage()])) {
                rs_responder(422, $mensajes[$error->getMessage()]);
            }
            throw $error;
        }

        Logger::registrarLog('INFO', 'Pedido de room service creado', [
            'id_usuario' => $idCliente,
            'id_pedido' => $pedido['id_pedido'],
            'total' => $pedido['total'],
        ]);
        rs_responder(200, sprintf(
            'Tu pedido llegará a la habitación %d en aproximadamente %d minutos. Puedes cancelarlo durante los próximos %d minutos.',
            $pedido['num_hab'],
            $pedido['minutos_estimados'],
            $pedido['ventana_cancelacion']
        ), ['pedido' => $pedido]);
    }

    if ($metodo === 'POST' && $accion === 'cancelar_pedido') {
        if (!$esCliente) {
            rs_responder(401, 'Inicia sesión con tu cuenta de huésped para cancelar un pedido.');
        }
        exigir_csrf();
        $idPedido = filter_var($_POST['id_pedido'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$idPedido) {
            rs_responder(422, 'El pedido que quieres cancelar no es válido.');
        }
        if (!$repo->cancelarPedidoCliente($idCliente, $idPedido)) {
            rs_responder(409, sprintf(
                'Solo puedes cancelar el pedido durante los primeros %d minutos después de pedirlo.',
                RoomServiceRepository::VENTANA_CANCELACION_MINUTOS
            ));
        }
        Logger::registrarLog('INFO', 'Pedido de room service cancelado por el huésped', [
            'id_usuario' => $idCliente,
            'id_pedido' => $idPedido,
        ]);
        rs_responder(200, 'Tu pedido fue cancelado correctamente.');
    }

    // ==================================================================
    // ADMINISTRACIÓN
    // ==================================================================
    if ($idStaff < 1 || !usuario_tiene_permiso($conexion, 'roomservice.ver')) {
        rs_responder(403, 'No tienes permiso para usar el room service desde el panel.');
    }
    $puedeGestionar = usuario_tiene_permiso($conexion, 'roomservice.gestionar');
    $puedeCobrar = $puedeGestionar && usuario_tiene_permiso($conexion, 'finanzas.ver');

    if ($metodo === 'GET' && $accion === 'admin_datos') {
        rs_responder(200, 'Datos cargados', [
            'items' => $repo->catalogoAdmin(),
            'pedidos' => $repo->pedidosAdmin(),
            'puede_gestionar' => $puedeGestionar,
            'puede_cobrar' => $puedeCobrar,
        ]);
    }

    if ($metodo === 'GET' && $accion === 'factura') {
        if (!$puedeCobrar) {
            rs_responder(403, 'No tienes permiso para ver facturas de room service.');
        }
        $folio = trim((string) ($_GET['folio'] ?? ''));
        $factura = preg_match('/^FAC-RS-\d{1,12}$/', $folio) ? $repo->facturaPorFolio($folio) : null;
        if ($factura === null) {
            rs_responder(404, 'No se encontró esa factura.');
        }
        rs_responder(200, 'Factura cargada', ['factura' => $factura]);
    }

    if ($metodo !== 'POST') {
        rs_responder(405, 'Método no permitido.');
    }
    exigir_csrf();
    if (!$puedeGestionar) {
        rs_responder(403, 'No tienes permiso para gestionar el room service.');
    }

    switch ($accion) {
        case 'guardar_item':
            $idRaw = (string) ($_POST['id'] ?? '');
            $id = $idRaw === '' ? null : filter_var($idRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($idRaw !== '' && !$id) {
                rs_responder(422, 'El producto no es válido.');
            }
            $tipo = (string) ($_POST['tipo'] ?? '');
            $categoria = trim((string) ($_POST['categoria'] ?? ''));
            $nombre = trim((string) ($_POST['nombre'] ?? ''));
            $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
            $precioRaw = str_replace(',', '.', trim((string) ($_POST['precio'] ?? '0')));
            $ordenRaw = trim((string) ($_POST['orden'] ?? '0'));
            $orden = $ordenRaw === '' ? 0 : filter_var($ordenRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 9999]]);

            if (!in_array($tipo, RoomServiceRepository::TIPOS, true)) {
                rs_responder(422, 'Elige si el producto es una comida o un objeto.');
            }
            if ($nombre === '' || mb_strlen($nombre) > 120) {
                rs_responder(422, 'Escribe un nombre de hasta 120 caracteres.');
            }
            if (mb_strlen($categoria) > 60 || mb_strlen($descripcion) > 500) {
                rs_responder(422, 'La categoría admite 60 caracteres y la descripción 500.');
            }
            if (!is_numeric($precioRaw) || (float) $precioRaw < 0 || (float) $precioRaw > 9999999) {
                rs_responder(422, 'El precio debe ser un número igual o mayor que 0.');
            }
            if ($orden === false) {
                rs_responder(422, 'El orden debe ser un número entre 0 y 9999.');
            }

            $itemActual = $id === null ? null : $repo->obtenerItem($id);
            if ($id !== null && $itemActual === null) {
                rs_responder(404, 'No se encontró el producto.');
            }

            $imagenNueva = null;
            $subida = $_FILES['imagen'] ?? null;
            $hayArchivo = is_array($subida) && ($subida['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            if ($hayArchivo) {
                try {
                    $imagenNueva = rs_guardar_imagen($subida);
                } catch (RuntimeException $errorImagen) {
                    rs_responder(422, $errorImagen->getMessage());
                }
            } elseif (($_POST['quitar_imagen'] ?? '0') === '1') {
                $imagenNueva = '';
            }

            try {
                $idGuardado = $repo->guardarItem($id, [
                    'tipo' => $tipo,
                    'categoria' => $categoria === '' ? 'General' : $categoria,
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'precio' => round((float) $precioRaw, 2),
                    'disponible' => ($_POST['disponible'] ?? '1') === '1',
                    'orden' => $orden,
                ], $imagenNueva);
            } catch (Throwable $error) {
                if ($hayArchivo) {
                    rs_borrar_imagen($imagenNueva);
                }
                throw $error;
            }
            if ($itemActual !== null && $imagenNueva !== null) {
                rs_borrar_imagen($itemActual['imagen']);
            }
            Logger::registrarLog('INFO', $id === null ? 'Producto de room service creado' : 'Producto de room service actualizado', [
                'id_usuario' => $idStaff,
                'id_item' => $idGuardado,
            ]);
            rs_responder(200, $id === null ? 'Producto creado correctamente.' : 'Producto actualizado correctamente.', [
                'item' => $repo->obtenerItem($idGuardado),
            ]);

        case 'eliminar_item':
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!$id) {
                rs_responder(422, 'El producto no es válido.');
            }
            $resultado = $repo->eliminarItem($id);
            if (!$resultado['encontrado']) {
                rs_responder(404, 'No se encontró el producto.');
            }
            rs_borrar_imagen($resultado['imagen']);
            Logger::registrarLog('INFO', 'Producto de room service eliminado', ['id_usuario' => $idStaff, 'id_item' => $id]);
            rs_responder(200, 'Producto eliminado correctamente.');

        case 'alternar_item':
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!$id) {
                rs_responder(422, 'El producto no es válido.');
            }
            $repo->alternarDisponible($id, ($_POST['disponible'] ?? '1') === '1');
            rs_responder(200, 'Disponibilidad actualizada.');

        case 'cambiar_estado':
            $id = filter_var($_POST['id_pedido'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $estado = (string) ($_POST['estado'] ?? '');
            if (!$id || !in_array($estado, RoomServiceRepository::ESTADOS_EDITABLES, true)) {
                rs_responder(422, 'El estado del pedido no es válido.');
            }
            if (!$repo->cambiarEstado($id, $estado)) {
                rs_responder(409, 'No se pudo cambiar el estado: el pedido no existe o ya fue cancelado.');
            }
            Logger::registrarLog('INFO', 'Estado de pedido de room service actualizado', [
                'id_usuario' => $idStaff, 'id_pedido' => $id, 'estado' => $estado,
            ]);
            rs_responder(200, 'Estado actualizado.');

        case 'cancelar_pedido_admin':
            $id = filter_var($_POST['id_pedido'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!$id) {
                rs_responder(422, 'El pedido no es válido.');
            }
            $resultado = $repo->cancelarPedidoAdmin($id);
            $mensajes = [
                'ok' => [200, 'Pedido cancelado.'],
                'no_existe' => [404, 'No se encontró el pedido.'],
                'ya_cancelado' => [409, 'El pedido ya estaba cancelado.'],
                'pagado' => [409, 'El pedido ya fue cobrado y no se puede cancelar desde aquí.'],
            ];
            if ($resultado === 'ok') {
                Logger::registrarLog('INFO', 'Pedido de room service cancelado por administración', [
                    'id_usuario' => $idStaff, 'id_pedido' => $id,
                ]);
            }
            rs_responder($mensajes[$resultado][0], $mensajes[$resultado][1]);

        case 'cobrar':
            if (!$puedeCobrar) {
                rs_responder(403, 'No tienes permiso para cobrar room service.');
            }
            $idPedido = filter_var($_POST['id_pedido'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
            $codRes = filter_var($_POST['cod_res'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
            $metodoPago = (string) ($_POST['metodo_pago'] ?? '');
            if (($idPedido === null && $codRes === null) || !in_array($metodoPago, RoomServiceRepository::METODOS_PAGO, true)) {
                rs_responder(422, 'Selecciona qué cobrar y un método de pago válido.');
            }
            try {
                $factura = $repo->cobrar($metodoPago, $codRes, $idPedido);
            } catch (RuntimeException $error) {
                if ($error->getMessage() === 'sin_pendientes') {
                    rs_responder(422, 'No hay pedidos de room service pendientes de cobro.');
                }
                throw $error;
            }
            Logger::registrarLog('INFO', 'Cobro de room service registrado', [
                'id_usuario' => $idStaff,
                'folio' => $factura['folio'],
                'pedidos' => $factura['pedidos'],
                'metodo_pago' => $metodoPago,
                'monto_cobrado' => $factura['monto'],
            ]);
            rs_responder(200, 'Cobro registrado correctamente.', ['factura' => $factura]);

        default:
            rs_responder(400, 'Acción no válida.');
    }
} catch (Throwable $error) {
    error_log('Error en room_service.php (' . $accion . '): ' . $error->getMessage());
    Logger::registrarLog('ERROR', 'Fallo en el módulo de room service', [
        'accion' => $accion,
        'error' => $error->getMessage(),
    ]);
    rs_responder(500, 'No fue posible completar la operación de room service.');
}