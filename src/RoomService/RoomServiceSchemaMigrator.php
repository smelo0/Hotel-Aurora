<?php
declare(strict_types=1);

namespace App\RoomService;

use mysqli;

/**
 * Crea (una sola vez) las tablas del room service, registra sus permisos y
 * carga un catálogo inicial editable desde el panel del administrador.
 *
 * Es idempotente y se ejecuta desde database/migrate.php durante el despliegue.
 */
final class RoomServiceSchemaMigrator
{
    private static bool $verificado = false;

    public static function asegurar(mysqli $connection): void
    {
        if (self::$verificado) {
            return;
        }
        self::$verificado = true;

        try {
            $existe = $connection->query("SHOW TABLES LIKE 'room_service_pedidos'");
            $itemsExiste = $connection->query("SHOW TABLES LIKE 'room_service_items'");
            $lineasExiste = $connection->query("SHOW TABLES LIKE 'room_service_pedido_items'");
            $completo = $existe instanceof \mysqli_result && $existe->num_rows > 0
                && $itemsExiste instanceof \mysqli_result && $itemsExiste->num_rows > 0
                && $lineasExiste instanceof \mysqli_result && $lineasExiste->num_rows > 0;

            if (!$completo) {
                $itemsNuevo = !($itemsExiste instanceof \mysqli_result && $itemsExiste->num_rows > 0);
                foreach (self::sentenciasTablas() as $sql) {
                    $connection->query($sql);
                }
                if ($itemsNuevo) {
                    self::cargarCatalogoInicial($connection);
                }
            }

            self::registrarPermisos($connection);
        } catch (\Throwable $error) {
            error_log('No se pudo preparar el esquema de room service: ' . $error->getMessage());
        }
    }

    /** @return string[] */
    public static function sentenciasTablas(): array
    {
        return [
            "CREATE TABLE IF NOT EXISTS `room_service_items` (
                `id_item` int(11) NOT NULL AUTO_INCREMENT,
                `tipo` varchar(10) NOT NULL DEFAULT 'comida',
                `categoria` varchar(60) NOT NULL DEFAULT '',
                `nombre` varchar(120) NOT NULL,
                `descripcion` varchar(500) NOT NULL DEFAULT '',
                `precio` decimal(10,2) NOT NULL DEFAULT 0.00,
                `imagen` varchar(255) DEFAULT NULL,
                `disponible` tinyint(1) NOT NULL DEFAULT 1,
                `orden` int(11) NOT NULL DEFAULT 0,
                `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id_item`),
                KEY `idx_rs_items_tipo` (`tipo`,`disponible`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE IF NOT EXISTS `room_service_pedidos` (
                `id_pedido` bigint(20) NOT NULL AUTO_INCREMENT,
                `cod_res_pedido` bigint(20) DEFAULT NULL,
                `id_usu_pedido` bigint(20) NOT NULL,
                `cod_hab_pedido` bigint(20) DEFAULT NULL,
                `estado` varchar(20) NOT NULL DEFAULT 'Pendiente',
                `total` decimal(12,2) NOT NULL DEFAULT 0.00,
                `notas` varchar(300) DEFAULT NULL,
                `estado_pago` varchar(20) NOT NULL DEFAULT 'Pendiente',
                `metodo_pago` varchar(30) DEFAULT NULL,
                `fecha_pago` datetime DEFAULT NULL,
                `folio` varchar(40) DEFAULT NULL,
                `minutos_estimados` int(11) NOT NULL DEFAULT 5,
                `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
                `actualizado_en` datetime DEFAULT NULL,
                PRIMARY KEY (`id_pedido`),
                KEY `idx_rs_pedidos_reserva` (`cod_res_pedido`),
                KEY `idx_rs_pedidos_usuario` (`id_usu_pedido`),
                KEY `idx_rs_pedidos_estado` (`estado`,`estado_pago`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE IF NOT EXISTS `room_service_pedido_items` (
                `id_linea` bigint(20) NOT NULL AUTO_INCREMENT,
                `id_pedido` bigint(20) NOT NULL,
                `id_item` int(11) DEFAULT NULL,
                `tipo` varchar(10) NOT NULL DEFAULT 'comida',
                `nombre` varchar(120) NOT NULL,
                `precio_unitario` decimal(10,2) NOT NULL DEFAULT 0.00,
                `cantidad` int(11) NOT NULL DEFAULT 1,
                `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
                PRIMARY KEY (`id_linea`),
                KEY `idx_rs_lineas_pedido` (`id_pedido`),
                CONSTRAINT `fk_rs_lineas_pedido` FOREIGN KEY (`id_pedido`)
                    REFERENCES `room_service_pedidos` (`id_pedido`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        ];
    }

    /** Permisos nuevos; los roles que ya ven/gestionan experiencias reciben los equivalentes. */
    private static function registrarPermisos(mysqli $connection): void
    {
        $permisos = [
            ['roomservice.ver', 'roomservice', 'ver', 'Ver pedidos y catálogo de Room Service en el panel', 'experiencias.ver'],
            ['roomservice.gestionar', 'roomservice', 'gestionar', 'Gestionar pedidos, catálogo y cobros de Room Service', 'experiencias.gestionar'],
        ];

        foreach ($permisos as [$codigo, $modulo, $accion, $descripcion, $permisoBase]) {
            $stmt = $connection->prepare(
                'INSERT INTO permiso (cod_permiso, modulo, accion, des_permiso)
                 SELECT ?, ?, ?, ? FROM DUAL
                 WHERE NOT EXISTS (SELECT 1 FROM permiso WHERE cod_permiso = ?)'
            );
            $recienCreado = false;
            if ($stmt) {
                $stmt->bind_param('sssss', $codigo, $modulo, $accion, $descripcion, $codigo);
                $stmt->execute();
                $recienCreado = $stmt->affected_rows === 1;
                $stmt->close();
            }

            // Solo se copia a los roles cuando el permiso se acaba de crear; así, si el
            // administrador quita el permiso a un rol desde "Roles y Permisos", no se vuelve a poner.
            if ($recienCreado) {
                $copiar = $connection->prepare(
                    'INSERT INTO rol_permiso (cod_rol, cod_permiso)
                     SELECT rp.cod_rol, ? FROM rol_permiso rp WHERE rp.cod_permiso = ?'
                );
                if ($copiar) {
                    $copiar->bind_param('ss', $codigo, $permisoBase);
                    $copiar->execute();
                    $copiar->close();
                }
            }
        }
    }

    private static function cargarCatalogoInicial(mysqli $connection): void
    {
        // Catálogo de ejemplo (precios en COP). El administrador puede cambiarlo todo desde el panel.
        $items = [
            ['comida', 'Desayunos', 'Desayuno continental', 'Pan, mantequilla, mermelada, fruta fresca y café o té.', 22000, 1],
            ['comida', 'Desayunos', 'Huevos al gusto con arepa', 'Huevos revueltos o pericos, arepa y chocolate caliente.', 18000, 2],
            ['comida', 'Platos fuertes', 'Hamburguesa Aurora', 'Carne de res, queso, tocineta y papas a la francesa.', 32000, 1],
            ['comida', 'Platos fuertes', 'Pasta cremosa de pollo', 'Fettuccine en salsa cremosa con pollo y champiñones.', 29000, 2],
            ['comida', 'Para picar', 'Sándwich club', 'Pollo, jamón, queso, lechuga y tomate con papas.', 24000, 1],
            ['comida', 'Para picar', 'Ensalada fresca', 'Lechuga, tomate cherry, pepino y aderezo de la casa.', 16000, 2],
            ['comida', 'Bebidas', 'Café americano', 'Taza de café colombiano recién preparado.', 6000, 1],
            ['comida', 'Bebidas', 'Jugo natural', 'Mora, lulo, maracuyá o naranja, en agua o en leche.', 8000, 2],
            ['comida', 'Bebidas', 'Agua o gaseosa', 'Botella individual.', 5000, 3],
            ['objeto', 'Ropa de cama y baño', 'Toallas adicionales', 'Juego de toallas limpias para el baño.', 0, 1],
            ['objeto', 'Ropa de cama y baño', 'Almohada extra', 'Almohada adicional para tu cama.', 0, 2],
            ['objeto', 'Ropa de cama y baño', 'Cobija adicional', 'Cobija extra para las noches frescas.', 0, 3],
            ['objeto', 'Aseo personal', 'Kit de aseo', 'Jabón, champú, acondicionador y crema corporal.', 8000, 1],
            ['objeto', 'Aseo personal', 'Cepillo y crema dental', 'Kit de higiene bucal de un solo uso.', 5000, 2],
            ['objeto', 'Comodidad', 'Plancha de ropa', 'Plancha con tabla, para devolver al terminar.', 0, 1],
            ['objeto', 'Comodidad', 'Cargador universal', 'Cargador para celular, para devolver al terminar.', 0, 2],
        ];

        $stmt = $connection->prepare(
            'INSERT INTO room_service_items (tipo, categoria, nombre, descripcion, precio, disponible, orden)
             VALUES (?, ?, ?, ?, ?, 1, ?)'
        );
        if (!$stmt) {
            return;
        }
        foreach ($items as [$tipo, $categoria, $nombre, $descripcion, $precio, $orden]) {
            $precioDecimal = (float) $precio;
            $stmt->bind_param('ssssdi', $tipo, $categoria, $nombre, $descripcion, $precioDecimal, $orden);
            $stmt->execute();
        }
        $stmt->close();
    }
}
