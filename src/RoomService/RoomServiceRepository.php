<?php
declare(strict_types=1);

namespace App\RoomService;

use App\Billing\BillingRegistrar;
use mysqli;
use RuntimeException;

require_once __DIR__ . '/../Billing/BillingRegistrar.php';

/**
 * Toda la lógica de datos del room service: catálogo, pedidos del huésped,
 * cola del administrador y cobro/facturación.
 *
 * Las reglas de tiempo (tiempo de llegada y ventana de cancelación) se calculan
 * con el reloj de MySQL, igual que ya se hace con las experiencias.
 */
final class RoomServiceRepository
{
    /** Minutos que se le anuncian al cliente para recibir el pedido. */
    public const ETA_MINUTOS = 5;
    /** Minutos durante los que el cliente puede cancelar su pedido. */
    public const VENTANA_CANCELACION_MINUTOS = 3;

    public const TIPOS = ['comida', 'objeto'];
    public const ESTADOS_ACTIVOS = ['Pendiente', 'En preparación'];
    public const ESTADOS_EDITABLES = ['Pendiente', 'En preparación', 'Entregado'];
    public const METODOS_PAGO = ['Efectivo', 'Tarjeta', 'Transferencia'];

    private const MAX_LINEAS = 20;
    private const MAX_CANTIDAD = 20;
    private const MAX_PEDIDOS_ACTIVOS = 5;

    public function __construct(private mysqli $db)
    {
    }

    // ------------------------------------------------------------------
    // Catálogo
    // ------------------------------------------------------------------

    public function catalogoPublico(): array
    {
        return $this->catalogo(true);
    }

    public function catalogoAdmin(): array
    {
        return $this->catalogo(false);
    }

    private function catalogo(bool $soloDisponibles): array
    {
        $filas = $this->filas(
            'SELECT id_item, tipo, categoria, nombre, descripcion, precio, imagen, disponible, orden
             FROM room_service_items'
            . ($soloDisponibles ? ' WHERE disponible = 1' : '')
            . ' ORDER BY tipo ASC, categoria ASC, orden ASC, nombre ASC'
        );
        return array_map([$this, 'normalizarItem'], $filas);
    }

    private function normalizarItem(array $fila): array
    {
        return [
            'id' => (int) $fila['id_item'],
            'tipo' => (string) $fila['tipo'],
            'categoria' => (string) $fila['categoria'],
            'nombre' => (string) $fila['nombre'],
            'descripcion' => (string) $fila['descripcion'],
            'precio' => (float) $fila['precio'],
            'imagen' => $fila['imagen'] !== null && $fila['imagen'] !== '' ? (string) $fila['imagen'] : null,
            'disponible' => (int) $fila['disponible'] === 1,
            'orden' => (int) $fila['orden'],
        ];
    }

    public function obtenerItem(int $id): ?array
    {
        $filas = $this->filas(
            'SELECT id_item, tipo, categoria, nombre, descripcion, precio, imagen, disponible, orden
             FROM room_service_items WHERE id_item = ?',
            'i',
            [$id]
        );
        return $filas === [] ? null : $this->normalizarItem($filas[0]);
    }

    /**
     * Crea o actualiza un producto. $imagen: null = dejar la imagen como está,
     * '' = quitarla, cualquier otro texto = ruta nueva.
     */
    public function guardarItem(?int $id, array $d, ?string $imagen): int
    {
        $tipo = (string) $d['tipo'];
        $categoria = (string) $d['categoria'];
        $nombre = (string) $d['nombre'];
        $descripcion = (string) $d['descripcion'];
        $precio = (float) $d['precio'];
        $disponible = !empty($d['disponible']) ? 1 : 0;
        $orden = (int) $d['orden'];

        if ($id === null) {
            $imagenGuardar = $imagen === '' ? null : $imagen;
            $this->ejecutar(
                'INSERT INTO room_service_items (tipo, categoria, nombre, descripcion, precio, imagen, disponible, orden)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                'ssssdsii',
                [$tipo, $categoria, $nombre, $descripcion, $precio, $imagenGuardar, $disponible, $orden]
            );
            return (int) $this->db->insert_id;
        }

        if ($imagen === null) {
            $this->ejecutar(
                'UPDATE room_service_items SET tipo = ?, categoria = ?, nombre = ?, descripcion = ?, precio = ?,
                        disponible = ?, orden = ? WHERE id_item = ?',
                'ssssdiii',
                [$tipo, $categoria, $nombre, $descripcion, $precio, $disponible, $orden, $id]
            );
        } else {
            $imagenGuardar = $imagen === '' ? null : $imagen;
            $this->ejecutar(
                'UPDATE room_service_items SET tipo = ?, categoria = ?, nombre = ?, descripcion = ?, precio = ?,
                        imagen = ?, disponible = ?, orden = ? WHERE id_item = ?',
                'ssssdsiii',
                [$tipo, $categoria, $nombre, $descripcion, $precio, $imagenGuardar, $disponible, $orden, $id]
            );
        }
        return $id;
    }

    /** @return array{encontrado: bool, imagen: ?string} */
    public function eliminarItem(int $id): array
    {
        $item = $this->obtenerItem($id);
        if ($item === null) {
            return ['encontrado' => false, 'imagen' => null];
        }
        // Los pedidos ya hechos conservan el nombre y el precio de cada línea, así que borrar es seguro.
        $this->ejecutar('DELETE FROM room_service_items WHERE id_item = ?', 'i', [$id]);
        return ['encontrado' => true, 'imagen' => $item['imagen']];
    }

    public function alternarDisponible(int $id, bool $disponible): bool
    {
        $valor = $disponible ? 1 : 0;
        return $this->ejecutar(
            'UPDATE room_service_items SET disponible = ? WHERE id_item = ?',
            'ii',
            [$valor, $id]
        ) === 1;
    }

    // ------------------------------------------------------------------
    // Estancia del huésped
    // ------------------------------------------------------------------

    /**
     * Habitaciones donde el huésped puede pedir ahora: reserva Confirmada o En Casa,
     * ya iniciada y que todavía no termina.
     */
    public function habitacionesEnEstancia(int $userId): array
    {
        $filas = $this->filas(
            "SELECT r.cod_res, r.fec_ent_res, r.fec_sal_res, r.est_res, h.cod_hab, h.num_hab, h.tipo_hab
             FROM reservas r
             INNER JOIN detalle d ON d.cod_res_det = r.cod_res
             INNER JOIN habitacion h ON h.cod_hab = d.cod_hab_det
             WHERE r.id_usu_res = ?
               AND r.est_res IN ('Confirmada', 'En Casa')
               AND DATE(r.fec_ent_res) <= CURDATE()
               AND r.fec_sal_res > NOW()
             ORDER BY (r.est_res = 'En Casa') DESC, r.fec_ent_res DESC, h.num_hab ASC",
            'i',
            [$userId]
        );

        $habitaciones = [];
        foreach ($filas as $fila) {
            $codHab = (int) $fila['cod_hab'];
            if (isset($habitaciones[$codHab])) {
                continue;
            }
            $habitaciones[$codHab] = [
                'cod_hab' => $codHab,
                'num_hab' => (int) $fila['num_hab'],
                'tipo_hab' => (string) $fila['tipo_hab'],
                'cod_res' => (int) $fila['cod_res'],
                'fec_ent_res' => (string) $fila['fec_ent_res'],
                'fec_sal_res' => (string) $fila['fec_sal_res'],
            ];
        }
        $habitaciones = array_values($habitaciones);
        usort($habitaciones, static fn(array $a, array $b): int => $a['num_hab'] <=> $b['num_hab']);
        return $habitaciones;
    }

    // ------------------------------------------------------------------
    // Pedidos del huésped
    // ------------------------------------------------------------------

    /**
     * @param array<int,int> $lineas id_item => cantidad
     * @return array{id_pedido:int,total:float,num_hab:int,minutos_estimados:int,ventana_cancelacion:int,estado_pago:string}
     */
    public function crearPedido(int $userId, array $lineas, int $codHab, string $notas): array
    {
        if ($lineas === [] || count($lineas) > self::MAX_LINEAS) {
            throw new RuntimeException('carrito_invalido');
        }
        foreach ($lineas as $idItem => $cantidad) {
            if ($idItem < 1 || $cantidad < 1 || $cantidad > self::MAX_CANTIDAD) {
                throw new RuntimeException('carrito_invalido');
            }
        }

        $this->db->begin_transaction();
        try {
            $habitacion = null;
            foreach ($this->habitacionesEnEstancia($userId) as $candidata) {
                if ($candidata['cod_hab'] === $codHab) {
                    $habitacion = $candidata;
                    break;
                }
            }
            if ($habitacion === null) {
                throw new RuntimeException('sin_estancia');
            }

            $activos = $this->filas(
                "SELECT COUNT(*) AS total FROM room_service_pedidos
                 WHERE id_usu_pedido = ? AND estado IN ('Pendiente', 'En preparación')",
                'i',
                [$userId]
            );
            if ((int) ($activos[0]['total'] ?? 0) >= self::MAX_PEDIDOS_ACTIVOS) {
                throw new RuntimeException('demasiados_pedidos');
            }

            $ids = array_keys($lineas);
            $marcadores = implode(',', array_fill(0, count($ids), '?'));
            $items = $this->filas(
                'SELECT id_item, tipo, nombre, precio FROM room_service_items
                 WHERE disponible = 1 AND id_item IN (' . $marcadores . ')',
                str_repeat('i', count($ids)),
                $ids
            );
            if (count($items) !== count($ids)) {
                throw new RuntimeException('item_no_disponible');
            }

            // El precio siempre sale de la base de datos, nunca del navegador del cliente.
            $total = 0.0;
            $detalle = [];
            foreach ($items as $item) {
                $cantidad = $lineas[(int) $item['id_item']];
                $precio = (float) $item['precio'];
                $subtotal = round($precio * $cantidad, 2);
                $total += $subtotal;
                $detalle[] = [
                    (int) $item['id_item'], (string) $item['tipo'], (string) $item['nombre'],
                    $precio, $cantidad, $subtotal,
                ];
            }
            $total = round($total, 2);
            $estadoPago = $total > 0 ? 'Pendiente' : 'Sin costo';
            $notasGuardar = $notas === '' ? null : $notas;
            $codRes = $habitacion['cod_res'];
            $eta = self::ETA_MINUTOS;

            $this->ejecutar(
                'INSERT INTO room_service_pedidos
                    (cod_res_pedido, id_usu_pedido, cod_hab_pedido, total, notas, estado_pago, minutos_estimados)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                'iiidssi',
                [$codRes, $userId, $codHab, $total, $notasGuardar, $estadoPago, $eta]
            );
            $idPedido = (int) $this->db->insert_id;

            foreach ($detalle as [$idItem, $tipo, $nombre, $precio, $cantidad, $subtotal]) {
                $this->ejecutar(
                    'INSERT INTO room_service_pedido_items (id_pedido, id_item, tipo, nombre, precio_unitario, cantidad, subtotal)
                     VALUES (?, ?, ?, ?, ?, ?, ?)',
                    'iissdid',
                    [$idPedido, $idItem, $tipo, $nombre, $precio, $cantidad, $subtotal]
                );
            }

            $this->db->commit();
            return [
                'id_pedido' => $idPedido,
                'total' => $total,
                'num_hab' => $habitacion['num_hab'],
                'minutos_estimados' => $eta,
                'ventana_cancelacion' => self::VENTANA_CANCELACION_MINUTOS,
                'estado_pago' => $estadoPago,
            ];
        } catch (\Throwable $error) {
            $this->db->rollback();
            throw $error;
        }
    }

    public function pedidosCliente(int $userId, int $limite = 30): array
    {
        $limite = max(1, min(100, $limite));
        $ventana = self::VENTANA_CANCELACION_MINUTOS;
        $filas = $this->filas(
            "SELECT p.id_pedido, p.cod_res_pedido, p.cod_hab_pedido, p.estado, p.total, p.notas, p.estado_pago,
                    p.creado_en, p.minutos_estimados, h.num_hab, h.tipo_hab,
                    (p.estado IN ('Pendiente', 'En preparación') AND p.estado_pago <> 'Pagado'
                     AND p.creado_en >= DATE_SUB(NOW(), INTERVAL {$ventana} MINUTE)) AS puede_cancelar,
                    GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(p.creado_en, INTERVAL {$ventana} MINUTE))) AS segundos_cancelacion,
                    GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(p.creado_en, INTERVAL p.minutos_estimados MINUTE))) AS segundos_llegada
             FROM room_service_pedidos p
             LEFT JOIN habitacion h ON h.cod_hab = p.cod_hab_pedido
             WHERE p.id_usu_pedido = ?
             ORDER BY p.creado_en DESC, p.id_pedido DESC
             LIMIT {$limite}",
            'i',
            [$userId]
        );
        return $this->adjuntarLineas($filas, true);
    }

    public function cancelarPedidoCliente(int $userId, int $idPedido): bool
    {
        $ventana = self::VENTANA_CANCELACION_MINUTOS;
        return $this->ejecutar(
            "UPDATE room_service_pedidos
             SET estado = 'Cancelado', actualizado_en = NOW()
             WHERE id_pedido = ? AND id_usu_pedido = ?
               AND estado IN ('Pendiente', 'En preparación')
               AND estado_pago <> 'Pagado'
               AND creado_en >= DATE_SUB(NOW(), INTERVAL {$ventana} MINUTE)",
            'ii',
            [$idPedido, $userId]
        ) === 1;
    }

    // ------------------------------------------------------------------
    // Cola del administrador
    // ------------------------------------------------------------------

    public function pedidosAdmin(int $limite = 300): array
    {
        $limite = max(1, min(500, $limite));
        $filas = $this->filas(
            "SELECT p.id_pedido, p.cod_res_pedido, p.cod_hab_pedido, p.id_usu_pedido, p.estado, p.total, p.notas,
                    p.estado_pago, p.metodo_pago, p.fecha_pago, p.folio, p.creado_en, p.minutos_estimados,
                    h.num_hab, h.tipo_hab, u.nom_usu, u.corr_usu,
                    (p.estado IN ('Pendiente', 'En preparación')) AS activo
             FROM room_service_pedidos p
             LEFT JOIN habitacion h ON h.cod_hab = p.cod_hab_pedido
             LEFT JOIN usuario u ON u.id_usu = p.id_usu_pedido
             ORDER BY activo DESC, IF(activo = 1, 1, -1) * UNIX_TIMESTAMP(p.creado_en) ASC, p.id_pedido DESC
             LIMIT {$limite}"
        );
        return $this->adjuntarLineas($filas, false);
    }

    public function cambiarEstado(int $idPedido, string $estado): bool
    {
        if (!in_array($estado, self::ESTADOS_EDITABLES, true)) {
            throw new RuntimeException('estado_invalido');
        }
        return $this->ejecutar(
            "UPDATE room_service_pedidos SET estado = ?, actualizado_en = NOW()
             WHERE id_pedido = ? AND estado <> 'Cancelado'",
            'si',
            [$estado, $idPedido]
        ) === 1;
    }

    /** @return string ok | no_existe | ya_cancelado | pagado */
    public function cancelarPedidoAdmin(int $idPedido): string
    {
        $cancelado = $this->ejecutar(
            "UPDATE room_service_pedidos SET estado = 'Cancelado', actualizado_en = NOW()
             WHERE id_pedido = ? AND estado <> 'Cancelado' AND estado_pago <> 'Pagado'",
            'i',
            [$idPedido]
        ) === 1;
        if ($cancelado) {
            return 'ok';
        }
        $fila = $this->filas('SELECT estado, estado_pago FROM room_service_pedidos WHERE id_pedido = ?', 'i', [$idPedido]);
        if ($fila === []) {
            return 'no_existe';
        }
        return $fila[0]['estado'] === 'Cancelado' ? 'ya_cancelado' : 'pagado';
    }

    // ------------------------------------------------------------------
    // Cobro y factura
    // ------------------------------------------------------------------

    /**
     * Cobra un pedido (id_pedido) o todos los pedidos pendientes de una reserva (cod_res)
     * y devuelve la factura. Los pedidos cancelados y los sin costo no se cobran.
     */
    public function cobrar(string $metodo, ?int $codRes, ?int $idPedido): array
    {
        if (!in_array($metodo, self::METODOS_PAGO, true)) {
            throw new RuntimeException('metodo_pago_invalido');
        }
        if ($codRes === null && $idPedido === null) {
            throw new RuntimeException('datos_invalidos');
        }

        $this->db->begin_transaction();
        try {
            $filtro = $idPedido !== null ? 'p.id_pedido = ?' : 'p.cod_res_pedido = ?';
            $valorFiltro = $idPedido ?? $codRes;
            $pendientes = $this->filas(
                "SELECT p.id_pedido, p.cod_res_pedido, p.total FROM room_service_pedidos p
                 WHERE {$filtro} AND p.estado <> 'Cancelado' AND p.estado_pago = 'Pendiente' AND p.total > 0
                 ORDER BY p.id_pedido ASC FOR UPDATE",
                'i',
                [$valorFiltro]
            );
            if ($pendientes === []) {
                throw new RuntimeException('sin_pendientes');
            }

            $ids = array_map(static fn(array $f): int => (int) $f['id_pedido'], $pendientes);
            $folio = 'FAC-RS-' . $ids[0];
            $marcadores = implode(',', array_fill(0, count($ids), '?'));
            $parametros = array_merge([$metodo, $folio], $ids);
            $actualizados = $this->ejecutar(
                "UPDATE room_service_pedidos
                 SET estado_pago = 'Pagado', metodo_pago = ?, fecha_pago = NOW(), folio = ?, actualizado_en = NOW()
                 WHERE estado_pago = 'Pendiente' AND id_pedido IN ({$marcadores})",
                'ss' . str_repeat('i', count($ids)),
                $parametros
            );
            if ($actualizados !== count($ids)) {
                throw new RuntimeException('sin_pendientes');
            }

            // El cobro también queda en la tabla `pagos` (una fila por factura).
            $montoCobrado = 0.0;
            $reservaCobro = null;
            foreach ($pendientes as $pendiente) {
                $montoCobrado += (float) $pendiente['total'];
                $reservaCobro ??= $pendiente['cod_res_pedido'] === null ? null : (int) $pendiente['cod_res_pedido'];
            }
            BillingRegistrar::registrarPago($this->db, $reservaCobro, round($montoCobrado, 2), $metodo, 'RS-' . $folio, 'room_service');

            $this->db->commit();
        } catch (\Throwable $error) {
            $this->db->rollback();
            throw $error;
        }

        $factura = $this->facturaPorFolio($folio);
        if ($factura === null) {
            throw new RuntimeException('factura_no_disponible');
        }
        BillingRegistrar::registrarFactura($this->db, $folio, 'room_service', $factura['cod_reserva'], (float) $factura['monto'], $metodo, $factura);
        return $factura;
    }

    public function facturaPorFolio(string $folio): ?array
    {
        $pedidos = $this->filas(
            "SELECT p.id_pedido, p.cod_res_pedido, p.total, p.metodo_pago, p.fecha_pago, h.num_hab, h.tipo_hab,
                    u.nom_usu, u.corr_usu
             FROM room_service_pedidos p
             LEFT JOIN habitacion h ON h.cod_hab = p.cod_hab_pedido
             LEFT JOIN usuario u ON u.id_usu = p.id_usu_pedido
             WHERE p.folio = ? AND p.estado_pago = 'Pagado'
             ORDER BY p.id_pedido ASC",
            's',
            [$folio]
        );
        if ($pedidos === []) {
            return null;
        }

        $ids = array_map(static fn(array $f): int => (int) $f['id_pedido'], $pedidos);
        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $lineas = $this->filas(
            "SELECT id_pedido, tipo, nombre, precio_unitario, cantidad, subtotal
             FROM room_service_pedido_items WHERE id_pedido IN ({$marcadores}) ORDER BY id_pedido, id_linea",
            str_repeat('i', count($ids)),
            $ids
        );

        $habitaciones = [];
        $monto = 0.0;
        foreach ($pedidos as $pedido) {
            $monto += (float) $pedido['total'];
            if ($pedido['num_hab'] !== null) {
                $habitaciones[(string) $pedido['num_hab']] = true;
            }
        }

        return [
            'tipo' => 'room_service',
            'folio' => $folio,
            'fecha' => (string) $pedidos[0]['fecha_pago'],
            'huesped' => (string) ($pedidos[0]['nom_usu'] ?? 'Huésped'),
            'correo' => (string) ($pedidos[0]['corr_usu'] ?? ''),
            'cod_reserva' => $pedidos[0]['cod_res_pedido'] === null ? null : (int) $pedidos[0]['cod_res_pedido'],
            'habitacion' => $habitaciones === [] ? 'Sin asignar' : implode(', ', array_keys($habitaciones)),
            'metodo_pago' => (string) $pedidos[0]['metodo_pago'],
            'monto' => round($monto, 2),
            'pedidos' => $ids,
            'items' => array_map(static fn(array $l): array => [
                'pedido' => (int) $l['id_pedido'],
                'tipo' => (string) $l['tipo'],
                'nombre' => (string) $l['nombre'],
                'cantidad' => (int) $l['cantidad'],
                'precio_unitario' => (float) $l['precio_unitario'],
                'subtotal' => (float) $l['subtotal'],
            ], $lineas),
        ];
    }

    // ------------------------------------------------------------------
    // Integración con Reservas y Finanzas
    // ------------------------------------------------------------------

    /**
     * Resumen de room service por reserva para la tabla de Reservas.
     * @param int[] $codigosReserva
     * @return array<int, array{pedidos: array, pendiente: float, pagado: float, cantidad_pendiente: int}>
     */
    public function resumenPorReservas(array $codigosReserva): array
    {
        $codigosReserva = array_values(array_unique(array_filter(array_map('intval', $codigosReserva))));
        if ($codigosReserva === []) {
            return [];
        }
        $marcadores = implode(',', array_fill(0, count($codigosReserva), '?'));
        $filas = $this->filas(
            "SELECT p.id_pedido, p.cod_res_pedido, p.estado, p.estado_pago, p.total, p.creado_en, p.folio, h.num_hab,
                    (SELECT GROUP_CONCAT(CONCAT(l.cantidad, '× ', l.nombre) ORDER BY l.id_linea SEPARATOR ', ')
                       FROM room_service_pedido_items l WHERE l.id_pedido = p.id_pedido) AS resumen
             FROM room_service_pedidos p
             LEFT JOIN habitacion h ON h.cod_hab = p.cod_hab_pedido
             WHERE p.cod_res_pedido IN ({$marcadores})
             ORDER BY p.creado_en ASC, p.id_pedido ASC",
            str_repeat('i', count($codigosReserva)),
            $codigosReserva
        );

        $resumen = [];
        foreach ($filas as $fila) {
            $codRes = (int) $fila['cod_res_pedido'];
            $resumen[$codRes] ??= ['pedidos' => [], 'pendiente' => 0.0, 'pagado' => 0.0, 'cantidad_pendiente' => 0];
            $total = (float) $fila['total'];
            $cobrable = $fila['estado'] !== 'Cancelado' && $fila['estado_pago'] === 'Pendiente' && $total > 0;
            if ($cobrable) {
                $resumen[$codRes]['pendiente'] += $total;
                $resumen[$codRes]['cantidad_pendiente']++;
            }
            if ($fila['estado_pago'] === 'Pagado' && $fila['estado'] !== 'Cancelado') {
                $resumen[$codRes]['pagado'] += $total;
            }
            $resumen[$codRes]['pedidos'][] = [
                'id_pedido' => (int) $fila['id_pedido'],
                'estado' => (string) $fila['estado'],
                'estado_pago' => (string) $fila['estado_pago'],
                'total' => $total,
                'num_hab' => $fila['num_hab'] === null ? null : (int) $fila['num_hab'],
                'resumen' => (string) ($fila['resumen'] ?? ''),
                'folio' => $fila['folio'],
            ];
        }
        return $resumen;
    }

    /** Pedidos no cancelados, para mostrarlos como movimientos en Finanzas. */
    public function movimientosFinanzas(int $limite = 300): array
    {
        $limite = max(1, min(500, $limite));
        return $this->filas(
            "SELECT p.id_pedido, p.cod_res_pedido, p.id_usu_pedido, p.estado, p.total, p.estado_pago, p.metodo_pago,
                    p.fecha_pago, p.folio, p.creado_en, u.nom_usu, u.corr_usu, h.num_hab,
                    (SELECT GROUP_CONCAT(CONCAT(l.cantidad, '× ', l.nombre) ORDER BY l.id_linea SEPARATOR ', ')
                       FROM room_service_pedido_items l WHERE l.id_pedido = p.id_pedido) AS resumen
             FROM room_service_pedidos p
             LEFT JOIN usuario u ON u.id_usu = p.id_usu_pedido
             LEFT JOIN habitacion h ON h.cod_hab = p.cod_hab_pedido
             WHERE p.estado <> 'Cancelado'
             ORDER BY COALESCE(p.fecha_pago, p.creado_en) DESC, p.id_pedido DESC
             LIMIT {$limite}"
        );
    }

    // ------------------------------------------------------------------
    // Utilidades internas
    // ------------------------------------------------------------------

    private function adjuntarLineas(array $pedidos, bool $paraCliente): array
    {
        if ($pedidos === []) {
            return [];
        }
        $ids = array_map(static fn(array $p): int => (int) $p['id_pedido'], $pedidos);
        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $lineas = $this->filas(
            "SELECT id_pedido, tipo, nombre, precio_unitario, cantidad, subtotal
             FROM room_service_pedido_items WHERE id_pedido IN ({$marcadores}) ORDER BY id_pedido, id_linea",
            str_repeat('i', count($ids)),
            $ids
        );
        $porPedido = [];
        foreach ($lineas as $linea) {
            $porPedido[(int) $linea['id_pedido']][] = [
                'tipo' => (string) $linea['tipo'],
                'nombre' => (string) $linea['nombre'],
                'cantidad' => (int) $linea['cantidad'],
                'precio_unitario' => (float) $linea['precio_unitario'],
                'subtotal' => (float) $linea['subtotal'],
            ];
        }

        $resultado = [];
        foreach ($pedidos as $pedido) {
            $id = (int) $pedido['id_pedido'];
            $salida = [
                'id_pedido' => $id,
                'cod_res' => $pedido['cod_res_pedido'] === null ? null : (int) $pedido['cod_res_pedido'],
                'num_hab' => isset($pedido['num_hab']) && $pedido['num_hab'] !== null ? (int) $pedido['num_hab'] : null,
                'tipo_hab' => (string) ($pedido['tipo_hab'] ?? ''),
                'estado' => (string) $pedido['estado'],
                'estado_pago' => (string) $pedido['estado_pago'],
                'total' => (float) $pedido['total'],
                'notas' => (string) ($pedido['notas'] ?? ''),
                'creado_en' => (string) $pedido['creado_en'],
                'minutos_estimados' => (int) $pedido['minutos_estimados'],
                'items' => $porPedido[$id] ?? [],
            ];
            if ($paraCliente) {
                $salida['puede_cancelar'] = (int) $pedido['puede_cancelar'] === 1;
                $salida['segundos_cancelacion'] = (int) $pedido['segundos_cancelacion'];
                $salida['segundos_llegada'] = (int) $pedido['segundos_llegada'];
            } else {
                $salida['id_usuario'] = (int) $pedido['id_usu_pedido'];
                $salida['cliente'] = (string) ($pedido['nom_usu'] ?? 'Huésped');
                $salida['correo'] = (string) ($pedido['corr_usu'] ?? '');
                $salida['metodo_pago'] = $pedido['metodo_pago'];
                $salida['fecha_pago'] = $pedido['fecha_pago'];
                $salida['folio'] = $pedido['folio'];
                $salida['puede_cobrar'] = $pedido['estado'] !== 'Cancelado'
                    && $pedido['estado_pago'] === 'Pendiente' && (float) $pedido['total'] > 0;
                $salida['puede_cancelar'] = $pedido['estado'] !== 'Cancelado' && $pedido['estado_pago'] !== 'Pagado';
            }
            $resultado[] = $salida;
        }
        return $resultado;
    }

    private function filas(string $sql, string $types = '', array $params = []): array
    {
        $statement = $this->db->prepare($sql);
        if (!$statement) {
            throw new RuntimeException('Consulta no válida: ' . $this->db->error);
        }
        if ($types !== '') {
            $params = array_values($params);
            $statement->bind_param($types, ...$params);
        }
        $statement->execute();
        $resultado = $statement->get_result();
        $filas = [];
        if ($resultado instanceof \mysqli_result) {
            while ($fila = $resultado->fetch_assoc()) {
                $filas[] = $fila;
            }
        }
        $statement->close();
        return $filas;
    }

    private function ejecutar(string $sql, string $types = '', array $params = []): int
    {
        $statement = $this->db->prepare($sql);
        if (!$statement) {
            throw new RuntimeException('Consulta no válida: ' . $this->db->error);
        }
        if ($types !== '') {
            $params = array_values($params);
            $statement->bind_param($types, ...$params);
        }
        $statement->execute();
        $afectadas = $statement->affected_rows;
        $statement->close();
        return (int) $afectadas;
    }
}
