<?php
declare(strict_types=1);

/**
 * Diagnóstico de la base de datos (solo administrador).
 *
 * Abrir en el navegador con la sesión de administrador iniciada:
 *     http://localhost/Hotel-Aurora/controladores/diagnostico_bd.php
 *
 * Muestra a qué servidor y base de datos está conectada la aplicación (para compararlo con phpMyAdmin),
 * si se puede escribir, si el esquema coincide con el que espera el código y los últimos registros guardados.
 * Si faltan tablas o columnas, el botón "Reparar" solo AGREGA lo que falta: no borra ni modifica datos.
 */

require_once __DIR__ . '/../includes/sesion_seguridad.php';

if ((int) ($_SESSION['emp_auth']['rol_usuario'] ?? 0) !== 1) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Solo el administrador puede ver este diagnóstico. Inicia sesión como administrador y vuelve a abrir esta página.');
}

require_once __DIR__ . '/../configuracion/conexion.php';
/** @var mysqli $conexion */
mysqli_report(MYSQLI_REPORT_OFF);

header('Content-Type: text/html; charset=utf-8');

// Esquema que espera la aplicación (generado a partir de la base de datos más reciente del proyecto).
$ESQUEMA_ESPERADO = json_decode(<<<'JSON'
{"agenda_actividad": {"create": "CREATE TABLE `agenda_actividad` (\n  `id_agenda` bigint(20) NOT NULL,\n  `actividad` varchar(150) NOT NULL,\n  `opcion_actividad` varchar(100) NOT NULL DEFAULT '',\n  `selecciones_personas_json` text DEFAULT NULL,\n  `fecha_agenda` date DEFAULT NULL,\n  `hora_agenda` time DEFAULT NULL,\n  `nombre_contacto` varchar(140) NOT NULL,\n  `correo_contacto` varchar(140) NOT NULL,\n  `id_usu_agenda` bigint(20) DEFAULT NULL,\n  `estado_agenda` varchar(30) DEFAULT 'Pendiente',\n  `creado_en` datetime DEFAULT current_timestamp(),\n  `cod_res_agenda` bigint(20) DEFAULT NULL,\n  `estado_pago_experiencia` varchar(20) NOT NULL DEFAULT 'Pendiente',\n  `fecha_pago_experiencia` datetime DEFAULT NULL,\n  `metodo_pago_experiencia` varchar(30) DEFAULT NULL,\n  `monto_experiencia` decimal(12,2) DEFAULT NULL,\n  `precios_personas_json` text DEFAULT NULL\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;", "columnas": {"id_agenda": "bigint(20) NOT NULL", "actividad": "varchar(150) NOT NULL", "opcion_actividad": "varchar(100) NOT NULL DEFAULT ''", "selecciones_personas_json": "text DEFAULT NULL", "fecha_agenda": "date DEFAULT NULL", "hora_agenda": "time DEFAULT NULL", "nombre_contacto": "varchar(140) NOT NULL", "correo_contacto": "varchar(140) NOT NULL", "id_usu_agenda": "bigint(20) DEFAULT NULL", "estado_agenda": "varchar(30) DEFAULT 'Pendiente'", "creado_en": "datetime DEFAULT current_timestamp()", "cod_res_agenda": "bigint(20) DEFAULT NULL", "estado_pago_experiencia": "varchar(20) NOT NULL DEFAULT 'Pendiente'", "fecha_pago_experiencia": "datetime DEFAULT NULL", "metodo_pago_experiencia": "varchar(30) DEFAULT NULL", "monto_experiencia": "decimal(12,2) DEFAULT NULL", "precios_personas_json": "text DEFAULT NULL"}, "extras": ["ALTER TABLE `agenda_actividad`\n  ADD PRIMARY KEY (`id_agenda`),\n  ADD KEY `idx_agenda_reserva` (`cod_res_agenda`);", "ALTER TABLE `agenda_actividad`\n  MODIFY `id_agenda` bigint(20) NOT NULL AUTO_INCREMENT;"]}, "detalle": {"create": "CREATE TABLE `detalle` (\n  `cod_det` bigint(20) NOT NULL,\n  `can_noc_det` int(11) NOT NULL,\n  `cod_res_det` bigint(20) DEFAULT NULL,\n  `cod_hab_det` bigint(20) DEFAULT NULL\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;", "columnas": {"cod_det": "bigint(20) NOT NULL", "can_noc_det": "int(11) NOT NULL", "cod_res_det": "bigint(20) DEFAULT NULL", "cod_hab_det": "bigint(20) DEFAULT NULL"}, "extras": ["ALTER TABLE `detalle`\n  ADD PRIMARY KEY (`cod_det`),\n  ADD KEY `cod_res_det` (`cod_res_det`),\n  ADD KEY `cod_hab_det` (`cod_hab_det`);", "ALTER TABLE `detalle`\n  MODIFY `cod_det` bigint(20) NOT NULL AUTO_INCREMENT;"]}, "experiencias": {"create": "CREATE TABLE `experiencias` (\n  `id` int(11) NOT NULL,\n  `categoria` varchar(50) NOT NULL,\n  `nombre` varchar(150) NOT NULL,\n  `descripcion` text NOT NULL,\n  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),\n  `imagen` varchar(255) DEFAULT NULL,\n  `opcion_1` varchar(100) DEFAULT NULL,\n  `opcion_2` varchar(100) DEFAULT NULL,\n  `opcion_3` varchar(100) DEFAULT NULL,\n  `precio_opcion_1` decimal(10,2) DEFAULT NULL,\n  `precio_opcion_2` decimal(10,2) DEFAULT NULL,\n  `precio_opcion_3` decimal(10,2) DEFAULT NULL,\n  `horarios_json` text DEFAULT NULL\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;", "columnas": {"id": "int(11) NOT NULL", "categoria": "varchar(50) NOT NULL", "nombre": "varchar(150) NOT NULL", "descripcion": "text NOT NULL", "fecha_creacion": "timestamp NOT NULL DEFAULT current_timestamp()", "imagen": "varchar(255) DEFAULT NULL", "opcion_1": "varchar(100) DEFAULT NULL", "opcion_2": "varchar(100) DEFAULT NULL", "opcion_3": "varchar(100) DEFAULT NULL", "precio_opcion_1": "decimal(10,2) DEFAULT NULL", "precio_opcion_2": "decimal(10,2) DEFAULT NULL", "precio_opcion_3": "decimal(10,2) DEFAULT NULL", "horarios_json": "text DEFAULT NULL"}, "extras": ["ALTER TABLE `experiencias`\n  ADD PRIMARY KEY (`id`);", "ALTER TABLE `experiencias`\n  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;"]}, "factura": {"create": "CREATE TABLE `factura` (\n  `cod_fac` bigint(20) NOT NULL,\n  `fech_fac` datetime DEFAULT current_timestamp(),\n  `tot_fac` decimal(10,2) DEFAULT NULL,\n  `est_fac` varchar(20) DEFAULT NULL,\n  `cod_res_fac` bigint(20) DEFAULT NULL,\n  `folio` varchar(40) DEFAULT NULL,\n  `tipo_fac` varchar(20) NOT NULL DEFAULT 'alojamiento',\n  `metodo_pago_fac` varchar(30) DEFAULT NULL,\n  `detalle_fac` longtext DEFAULT NULL\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;", "columnas": {"cod_fac": "bigint(20) NOT NULL", "fech_fac": "datetime DEFAULT current_timestamp()", "tot_fac": "decimal(10,2) DEFAULT NULL", "est_fac": "varchar(20) DEFAULT NULL", "cod_res_fac": "bigint(20) DEFAULT NULL", "folio": "varchar(40) DEFAULT NULL", "tipo_fac": "varchar(20) NOT NULL DEFAULT 'alojamiento'", "metodo_pago_fac": "varchar(30) DEFAULT NULL", "detalle_fac": "longtext DEFAULT NULL"}, "extras": ["ALTER TABLE `factura`\n  ADD PRIMARY KEY (`cod_fac`),\n  ADD UNIQUE KEY `uq_factura_folio` (`folio`),\n  ADD KEY `idx_factura_reserva` (`cod_res_fac`);", "ALTER TABLE `factura`\n  MODIFY `cod_fac` bigint(20) NOT NULL AUTO_INCREMENT;"]}, "habitacion": {"create": "CREATE TABLE `habitacion` (\n  `cod_hab` bigint(20) NOT NULL,\n  `num_hab` int(11) NOT NULL,\n  `tipo_hab` varchar(50) DEFAULT NULL,\n  `pre_hab` decimal(10,2) DEFAULT NULL,\n  `est_hab` varchar(20) DEFAULT 'Disponible',\n  `precio_hab` decimal(10,2) DEFAULT 0.00,\n  `obs_hab` text DEFAULT NULL,\n  `des_hab` text DEFAULT NULL,\n  `img_hab` varchar(255) DEFAULT NULL,\n  `car_hab` text DEFAULT NULL\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;", "columnas": {"cod_hab": "bigint(20) NOT NULL", "num_hab": "int(11) NOT NULL", "tipo_hab": "varchar(50) DEFAULT NULL", "pre_hab": "decimal(10,2) DEFAULT NULL", "est_hab": "varchar(20) DEFAULT 'Disponible'", "precio_hab": "decimal(10,2) DEFAULT 0.00", "obs_hab": "text DEFAULT NULL", "des_hab": "text DEFAULT NULL", "img_hab": "varchar(255) DEFAULT NULL", "car_hab": "text DEFAULT NULL"}, "extras": ["ALTER TABLE `habitacion`\n  ADD PRIMARY KEY (`cod_hab`),\n  ADD UNIQUE KEY `num_hab` (`num_hab`);", "ALTER TABLE `habitacion`\n  MODIFY `cod_hab` bigint(20) NOT NULL AUTO_INCREMENT;"]}, "pagos": {"create": "CREATE TABLE `pagos` (\n  `id_pago` bigint(20) NOT NULL,\n  `cod_res_pago` bigint(20) NOT NULL,\n  `monto` decimal(10,2) NOT NULL,\n  `metodo_pago` varchar(50) NOT NULL,\n  `referencia_pago` varchar(100) NOT NULL,\n  `estado_pago` enum('Aprobado','Pendiente','Fallido') DEFAULT 'Pendiente',\n  `fecha_pago` datetime DEFAULT current_timestamp(),\n  `concepto` varchar(20) NOT NULL DEFAULT 'alojamiento'\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;", "columnas": {"id_pago": "bigint(20) NOT NULL", "cod_res_pago": "bigint(20) NOT NULL", "monto": "decimal(10,2) NOT NULL", "metodo_pago": "varchar(50) NOT NULL", "referencia_pago": "varchar(100) NOT NULL", "estado_pago": "enum('Aprobado','Pendiente','Fallido') DEFAULT 'Pendiente'", "fecha_pago": "datetime DEFAULT current_timestamp()", "concepto": "varchar(20) NOT NULL DEFAULT 'alojamiento'"}, "extras": ["ALTER TABLE `pagos`\n  ADD PRIMARY KEY (`id_pago`),\n  ADD KEY `cod_res_pago` (`cod_res_pago`);", "ALTER TABLE `pagos`\n  MODIFY `id_pago` bigint(20) NOT NULL AUTO_INCREMENT;"]}, "password_resets": {"create": "CREATE TABLE `password_resets` (\n  `id` bigint(20) NOT NULL,\n  `email` varchar(255) NOT NULL,\n  `token` varchar(128) NOT NULL,\n  `expires_at` datetime NOT NULL,\n  `created_at` datetime DEFAULT current_timestamp()\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;", "columnas": {"id": "bigint(20) NOT NULL", "email": "varchar(255) NOT NULL", "token": "varchar(128) NOT NULL", "expires_at": "datetime NOT NULL", "created_at": "datetime DEFAULT current_timestamp()"}, "extras": ["ALTER TABLE `password_resets`\n  ADD PRIMARY KEY (`id`),\n  ADD KEY `email` (`email`),\n  ADD KEY `token` (`token`);", "ALTER TABLE `password_resets`\n  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;"]}, "permiso": {"create": "CREATE TABLE `permiso` (\n  `cod_permiso` varchar(60) NOT NULL,\n  `modulo` varchar(40) NOT NULL,\n  `accion` varchar(40) NOT NULL,\n  `des_permiso` varchar(150) NOT NULL\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;", "columnas": {"cod_permiso": "varchar(60) NOT NULL", "modulo": "varchar(40) NOT NULL", "accion": "varchar(40) NOT NULL", "des_permiso": "varchar(150) NOT NULL"}, "extras": ["ALTER TABLE `permiso`\n  ADD PRIMARY KEY (`cod_permiso`);"]}, "reservas": {"create": "CREATE TABLE `reservas` (\n  `cod_res` bigint(20) NOT NULL,\n  `fec_res` datetime DEFAULT current_timestamp(),\n  `fec_ent_res` datetime NOT NULL,\n  `fec_sal_res` datetime NOT NULL,\n  `est_res` varchar(20) DEFAULT 'Pendiente',\n  `not_res` text DEFAULT NULL,\n  `id_usu_res` bigint(20) DEFAULT NULL\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;", "columnas": {"cod_res": "bigint(20) NOT NULL", "fec_res": "datetime DEFAULT current_timestamp()", "fec_ent_res": "datetime NOT NULL", "fec_sal_res": "datetime NOT NULL", "est_res": "varchar(20) DEFAULT 'Pendiente'", "not_res": "text DEFAULT NULL", "id_usu_res": "bigint(20) DEFAULT NULL"}, "extras": ["ALTER TABLE `reservas`\n  ADD PRIMARY KEY (`cod_res`),\n  ADD KEY `id_usu_res` (`id_usu_res`);", "ALTER TABLE `reservas`\n  MODIFY `cod_res` bigint(20) NOT NULL AUTO_INCREMENT;"]}, "rol": {"create": "CREATE TABLE `rol` (\n  `cod_rol` bigint(20) NOT NULL,\n  `des_rol` varchar(200) NOT NULL,\n  `detalle_rol` varchar(200) DEFAULT NULL\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;", "columnas": {"cod_rol": "bigint(20) NOT NULL", "des_rol": "varchar(200) NOT NULL", "detalle_rol": "varchar(200) DEFAULT NULL"}, "extras": ["ALTER TABLE `rol`\n  ADD PRIMARY KEY (`cod_rol`),\n  ADD KEY `cod_rol` (`cod_rol`),\n  ADD KEY `cod_rol_2` (`cod_rol`),\n  ADD KEY `cod_rol_3` (`cod_rol`),\n  ADD KEY `cod_rol_4` (`cod_rol`);", "ALTER TABLE `rol`\n  MODIFY `cod_rol` bigint(20) NOT NULL AUTO_INCREMENT;"]}, "rol_permiso": {"create": "CREATE TABLE `rol_permiso` (\n  `cod_rol` bigint(20) NOT NULL,\n  `cod_permiso` varchar(60) NOT NULL\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;", "columnas": {"cod_rol": "bigint(20) NOT NULL", "cod_permiso": "varchar(60) NOT NULL"}, "extras": ["ALTER TABLE `rol_permiso`\n  ADD PRIMARY KEY (`cod_rol`,`cod_permiso`),\n  ADD KEY `fk_rol_permiso_permiso` (`cod_permiso`);"]}, "room_service_items": {"create": "CREATE TABLE `room_service_items` (\n  `id_item` int(11) NOT NULL,\n  `tipo` varchar(10) NOT NULL DEFAULT 'comida',\n  `categoria` varchar(60) NOT NULL DEFAULT '',\n  `nombre` varchar(120) NOT NULL,\n  `descripcion` varchar(500) NOT NULL DEFAULT '',\n  `precio` decimal(10,2) NOT NULL DEFAULT 0.00,\n  `imagen` varchar(255) DEFAULT NULL,\n  `disponible` tinyint(1) NOT NULL DEFAULT 1,\n  `orden` int(11) NOT NULL DEFAULT 0,\n  `creado_en` datetime NOT NULL DEFAULT current_timestamp()\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;", "columnas": {"id_item": "int(11) NOT NULL", "tipo": "varchar(10) NOT NULL DEFAULT 'comida'", "categoria": "varchar(60) NOT NULL DEFAULT ''", "nombre": "varchar(120) NOT NULL", "descripcion": "varchar(500) NOT NULL DEFAULT ''", "precio": "decimal(10,2) NOT NULL DEFAULT 0.00", "imagen": "varchar(255) DEFAULT NULL", "disponible": "tinyint(1) NOT NULL DEFAULT 1", "orden": "int(11) NOT NULL DEFAULT 0", "creado_en": "datetime NOT NULL DEFAULT current_timestamp()"}, "extras": ["ALTER TABLE `room_service_items`\n  ADD PRIMARY KEY (`id_item`),\n  ADD KEY `idx_rs_items_tipo` (`tipo`,`disponible`);", "ALTER TABLE `room_service_items`\n  MODIFY `id_item` int(11) NOT NULL AUTO_INCREMENT;"]}, "room_service_pedidos": {"create": "CREATE TABLE `room_service_pedidos` (\n  `id_pedido` bigint(20) NOT NULL,\n  `cod_res_pedido` bigint(20) DEFAULT NULL,\n  `id_usu_pedido` bigint(20) NOT NULL,\n  `cod_hab_pedido` bigint(20) DEFAULT NULL,\n  `estado` varchar(20) NOT NULL DEFAULT 'Pendiente',\n  `total` decimal(12,2) NOT NULL DEFAULT 0.00,\n  `notas` varchar(300) DEFAULT NULL,\n  `estado_pago` varchar(20) NOT NULL DEFAULT 'Pendiente',\n  `metodo_pago` varchar(30) DEFAULT NULL,\n  `fecha_pago` datetime DEFAULT NULL,\n  `folio` varchar(40) DEFAULT NULL,\n  `minutos_estimados` int(11) NOT NULL DEFAULT 5,\n  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),\n  `actualizado_en` datetime DEFAULT NULL\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;", "columnas": {"id_pedido": "bigint(20) NOT NULL", "cod_res_pedido": "bigint(20) DEFAULT NULL", "id_usu_pedido": "bigint(20) NOT NULL", "cod_hab_pedido": "bigint(20) DEFAULT NULL", "estado": "varchar(20) NOT NULL DEFAULT 'Pendiente'", "total": "decimal(12,2) NOT NULL DEFAULT 0.00", "notas": "varchar(300) DEFAULT NULL", "estado_pago": "varchar(20) NOT NULL DEFAULT 'Pendiente'", "metodo_pago": "varchar(30) DEFAULT NULL", "fecha_pago": "datetime DEFAULT NULL", "folio": "varchar(40) DEFAULT NULL", "minutos_estimados": "int(11) NOT NULL DEFAULT 5", "creado_en": "datetime NOT NULL DEFAULT current_timestamp()", "actualizado_en": "datetime DEFAULT NULL"}, "extras": ["ALTER TABLE `room_service_pedidos`\n  ADD PRIMARY KEY (`id_pedido`),\n  ADD KEY `idx_rs_pedidos_reserva` (`cod_res_pedido`),\n  ADD KEY `idx_rs_pedidos_usuario` (`id_usu_pedido`),\n  ADD KEY `idx_rs_pedidos_estado` (`estado`,`estado_pago`);", "ALTER TABLE `room_service_pedidos`\n  MODIFY `id_pedido` bigint(20) NOT NULL AUTO_INCREMENT;"]}, "room_service_pedido_items": {"create": "CREATE TABLE `room_service_pedido_items` (\n  `id_linea` bigint(20) NOT NULL,\n  `id_pedido` bigint(20) NOT NULL,\n  `id_item` int(11) DEFAULT NULL,\n  `tipo` varchar(10) NOT NULL DEFAULT 'comida',\n  `nombre` varchar(120) NOT NULL,\n  `precio_unitario` decimal(10,2) NOT NULL DEFAULT 0.00,\n  `cantidad` int(11) NOT NULL DEFAULT 1,\n  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;", "columnas": {"id_linea": "bigint(20) NOT NULL", "id_pedido": "bigint(20) NOT NULL", "id_item": "int(11) DEFAULT NULL", "tipo": "varchar(10) NOT NULL DEFAULT 'comida'", "nombre": "varchar(120) NOT NULL", "precio_unitario": "decimal(10,2) NOT NULL DEFAULT 0.00", "cantidad": "int(11) NOT NULL DEFAULT 1", "subtotal": "decimal(12,2) NOT NULL DEFAULT 0.00"}, "extras": ["ALTER TABLE `room_service_pedido_items`\n  ADD PRIMARY KEY (`id_linea`),\n  ADD KEY `idx_rs_lineas_pedido` (`id_pedido`);", "ALTER TABLE `room_service_pedido_items`\n  MODIFY `id_linea` bigint(20) NOT NULL AUTO_INCREMENT;"]}, "tarea": {"create": "CREATE TABLE `tarea` (\n  `cod_tar` bigint(20) NOT NULL,\n  `tit_tar` varchar(100) NOT NULL,\n  `cat_tar` varchar(50) NOT NULL,\n  `prioridad_tar` enum('Baja','Media','Alta') DEFAULT 'Media',\n  `des_tar` text DEFAULT NULL,\n  `est_tar` varchar(20) DEFAULT 'Pendiente',\n  `fec_tar` datetime DEFAULT current_timestamp(),\n  `cod_usu_tar` bigint(20) DEFAULT NULL\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;", "columnas": {"cod_tar": "bigint(20) NOT NULL", "tit_tar": "varchar(100) NOT NULL", "cat_tar": "varchar(50) NOT NULL", "prioridad_tar": "enum('Baja','Media','Alta') DEFAULT 'Media'", "des_tar": "text DEFAULT NULL", "est_tar": "varchar(20) DEFAULT 'Pendiente'", "fec_tar": "datetime DEFAULT current_timestamp()", "cod_usu_tar": "bigint(20) DEFAULT NULL"}, "extras": ["ALTER TABLE `tarea`\n  ADD PRIMARY KEY (`cod_tar`),\n  ADD KEY `cod_usu_tar` (`cod_usu_tar`),\n  ADD KEY `cod_tar` (`cod_tar`),\n  ADD KEY `cod_usu_tar_2` (`cod_usu_tar`);", "ALTER TABLE `tarea`\n  MODIFY `cod_tar` bigint(20) NOT NULL AUTO_INCREMENT;"]}, "usuario": {"create": "CREATE TABLE `usuario` (\n  `id_usu` bigint(20) NOT NULL,\n  `doc_usu` varchar(200) DEFAULT NULL,\n  `nom_usu` varchar(100) NOT NULL,\n  `tel_usu` varchar(20) DEFAULT NULL,\n  `corr_usu` varchar(100) NOT NULL,\n  `psw_usu` varchar(255) NOT NULL,\n  `est_usu` tinyint(1) DEFAULT 1,\n  `cod_rol_usu` bigint(20) DEFAULT NULL,\n  `foto_usu` varchar(255) DEFAULT 'default_avatar.png',\n  `tema_usu` enum('claro','oscuro') DEFAULT 'claro',\n  `idioma_usu` enum('es','en') DEFAULT 'es',\n  `data_consent` tinyint(1) NOT NULL DEFAULT 0,\n  `consent_date` datetime DEFAULT NULL,\n  `consent_ip` varchar(45) DEFAULT NULL\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;", "columnas": {"id_usu": "bigint(20) NOT NULL", "doc_usu": "varchar(200) DEFAULT NULL", "nom_usu": "varchar(100) NOT NULL", "tel_usu": "varchar(20) DEFAULT NULL", "corr_usu": "varchar(100) NOT NULL", "psw_usu": "varchar(255) NOT NULL", "est_usu": "tinyint(1) DEFAULT 1", "cod_rol_usu": "bigint(20) DEFAULT NULL", "foto_usu": "varchar(255) DEFAULT 'default_avatar.png'", "tema_usu": "enum('claro','oscuro') DEFAULT 'claro'", "idioma_usu": "enum('es','en') DEFAULT 'es'", "data_consent": "tinyint(1) NOT NULL DEFAULT 0", "consent_date": "datetime DEFAULT NULL", "consent_ip": "varchar(45) DEFAULT NULL"}, "extras": ["ALTER TABLE `usuario`\n  ADD PRIMARY KEY (`id_usu`),\n  ADD UNIQUE KEY `corr_usu` (`corr_usu`),\n  ADD KEY `cod_rol_usu` (`cod_rol_usu`);", "ALTER TABLE `usuario`\n  MODIFY `id_usu` bigint(20) NOT NULL AUTO_INCREMENT;"]}}
JSON, true);

function h(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function consulta_filas(mysqli $db, string $sql): array|false
{
    $resultado = $db->query($sql);
    return $resultado instanceof mysqli_result ? $resultado->fetch_all(MYSQLI_ASSOC) : false;
}

function variable_servidor(mysqli $db, string $nombre): string
{
    $filas = consulta_filas($db, "SELECT @@{$nombre} AS valor");
    return $filas === false ? '(no disponible)' : (string) ($filas[0]['valor'] ?? '');
}

$baseDatos = (string) (consulta_filas($conexion, 'SELECT DATABASE() AS d')[0]['d'] ?? '');

// ---- Esquema real frente al esperado ----
$reales = [];
foreach (consulta_filas($conexion, "SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()") ?: [] as $fila) {
    $reales[$fila['TABLE_NAME']][$fila['COLUMN_NAME']] = true;
}
$tablasFaltantes = [];
$columnasFaltantes = [];
foreach ($ESQUEMA_ESPERADO as $tabla => $datos) {
    if (!isset($reales[$tabla])) {
        $tablasFaltantes[] = $tabla;
        continue;
    }
    foreach ($datos['columnas'] as $columna => $definicion) {
        if (!isset($reales[$tabla][$columna])) {
            $columnasFaltantes[] = ['tabla' => $tabla, 'columna' => $columna, 'definicion' => $definicion];
        }
    }
}

// ---- Reparación aditiva ----
$resultadoReparacion = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    exigir_csrf();
    foreach ($tablasFaltantes as $tabla) {
        $pasos = array_merge([$ESQUEMA_ESPERADO[$tabla]['create']], $ESQUEMA_ESPERADO[$tabla]['extras']);
        foreach ($pasos as $sentencia) {
            $ok = $conexion->query($sentencia) !== false;
            $resultadoReparacion[] = [$ok, strtok($sentencia, "\n") . ($ok ? '' : ' -> ' . $conexion->error)];
        }
    }
    foreach ($columnasFaltantes as $falta) {
        $sentencia = "ALTER TABLE `{$falta['tabla']}` ADD COLUMN IF NOT EXISTS `{$falta['columna']}` {$falta['definicion']}";
        $ok = $conexion->query($sentencia) !== false;
        $resultadoReparacion[] = [$ok, "Agregar {$falta['tabla']}.{$falta['columna']}" . ($ok ? '' : ' -> ' . $conexion->error)];
    }
}

// ---- Prueba de escritura ----
$pruebaEscritura = [];
$tablaPrueba = 'zz_diagnostico_escritura';
$pasos = [
    'Crear tabla de prueba' => "CREATE TABLE IF NOT EXISTS `{$tablaPrueba}` (id INT AUTO_INCREMENT PRIMARY KEY, nota VARCHAR(40)) ENGINE=InnoDB",
    'Insertar una fila' => "INSERT INTO `{$tablaPrueba}` (nota) VALUES ('prueba')",
    'Leer la fila' => "SELECT COUNT(*) FROM `{$tablaPrueba}`",
    'Borrar la fila' => "DELETE FROM `{$tablaPrueba}`",
    'Eliminar tabla de prueba' => "DROP TABLE IF EXISTS `{$tablaPrueba}`",
];
foreach ($pasos as $descripcion => $sentencia) {
    $ok = $conexion->query($sentencia) !== false;
    $pruebaEscritura[] = [$descripcion, $ok, $ok ? '' : $conexion->error];
    if (!$ok) {
        $conexion->query("DROP TABLE IF EXISTS `{$tablaPrueba}`");
        break;
    }
}
$escrituraOk = count(array_filter($pruebaEscritura, static fn(array $p): bool => !$p[1])) === 0;

// ---- Conteos y últimos registros ----
$conteos = [];
$consultasConteo = [
    'habitacion' => 'SELECT COUNT(*) AS total, MAX(cod_hab) AS ultimo_id, NULL AS ultima_fecha FROM habitacion',
    'reservas' => 'SELECT COUNT(*) AS total, MAX(cod_res) AS ultimo_id, MAX(fec_res) AS ultima_fecha FROM reservas',
    'pagos' => 'SELECT COUNT(*) AS total, MAX(id_pago) AS ultimo_id, MAX(fecha_pago) AS ultima_fecha FROM pagos',
    'factura' => 'SELECT COUNT(*) AS total, MAX(cod_fac) AS ultimo_id, MAX(fech_fac) AS ultima_fecha FROM factura',
    'room_service_pedidos' => 'SELECT COUNT(*) AS total, MAX(id_pedido) AS ultimo_id, MAX(creado_en) AS ultima_fecha FROM room_service_pedidos',
    'agenda_actividad' => 'SELECT COUNT(*) AS total, MAX(id_agenda) AS ultimo_id, MAX(creado_en) AS ultima_fecha FROM agenda_actividad',
    'tarea' => 'SELECT COUNT(*) AS total, MAX(cod_tar) AS ultimo_id, NULL AS ultima_fecha FROM tarea',
    'usuario' => 'SELECT COUNT(*) AS total, MAX(id_usu) AS ultimo_id, NULL AS ultima_fecha FROM usuario',
];
foreach ($consultasConteo as $tabla => $sql) {
    $filas = consulta_filas($conexion, $sql);
    $conteos[$tabla] = $filas === false ? ['error' => $conexion->error] : $filas[0];
}

// ---- Motores de tablas ----
$noInnoDb = [];
foreach (consulta_filas($conexion, "SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'") ?: [] as $fila) {
    if (strtolower((string) $fila['ENGINE']) !== 'innodb') {
        $noInnoDb[] = $fila['TABLE_NAME'] . ' (' . $fila['ENGINE'] . ')';
    }
}

$datosConexion = [
    'Base de datos conectada' => $baseDatos,
    'Servidor (host:puerto) configurado en .env' => ($_ENV['DB_HOST'] ?? '?') . ':' . ($_ENV['DB_PORT'] ?? '?') . ' · base "' . ($_ENV['DB_NAME'] ?? '?') . '" · usuario "' . ($_ENV['DB_USER'] ?? '?') . '"',
    'Puerto real del servidor' => variable_servidor($conexion, 'port'),
    'Carpeta de datos (datadir)' => variable_servidor($conexion, 'datadir'),
    'Versión' => variable_servidor($conexion, 'version'),
    'Solo lectura (read_only)' => variable_servidor($conexion, 'read_only') === '1' ? 'SÍ' : 'no',
    'Autocommit' => variable_servidor($conexion, 'autocommit') === '1' ? 'activado' : 'DESACTIVADO',
    'Modo de recuperación de InnoDB' => variable_servidor($conexion, 'innodb_force_recovery'),
    'Zona horaria del servidor' => variable_servidor($conexion, 'time_zone') . ' · hora actual del servidor: ' . (string) (consulta_filas($conexion, 'SELECT NOW() AS ahora')[0]['ahora'] ?? '?'),
];
$tokenCsrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Diagnóstico de la base de datos · Hotel Aurora</title>
<style>
    body { font-family: Arial, sans-serif; background: #f8fafc; color: #0f172a; margin: 0; padding: 24px; }
    main { max-width: 960px; margin: 0 auto; }
    h1 { font-size: 24px; margin: 0 0 4px; } h2 { font-size: 16px; margin: 28px 0 10px; color: #17354f; }
    p.sub { color: #64748b; margin: 0 0 18px; font-size: 14px; }
    table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; }
    th, td { text-align: left; padding: 9px 12px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
    th { background: #f1f5f9; font-size: 12px; text-transform: uppercase; letter-spacing: .05em; color: #475569; }
    .ok { color: #047857; font-weight: bold; } .mal { color: #b91c1c; font-weight: bold; } .aviso { color: #b45309; font-weight: bold; }
    code { background: #f1f5f9; padding: 1px 5px; border-radius: 4px; }
    button { background: #17354f; color: #fff; border: 0; border-radius: 8px; padding: 12px 20px; font-weight: bold; cursor: pointer; }
    .caja { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 16px; }
</style>
</head>
<body>
<main>
    <h1>Diagnóstico de la base de datos</h1>
    <p class="sub">Compara estos datos con lo que ves en phpMyAdmin. Si no coinciden, la aplicación y phpMyAdmin están usando bases distintas.</p>

    <h2>1. Conexión de la aplicación</h2>
    <table><tbody>
    <?php foreach ($datosConexion as $etiqueta => $valor): ?>
        <tr><th style="width:38%"><?php echo h($etiqueta); ?></th><td><?php echo h($valor); ?></td></tr>
    <?php endforeach; ?>
    </tbody></table>

    <h2>2. Prueba de escritura</h2>
    <table><tbody>
    <?php foreach ($pruebaEscritura as [$descripcion, $ok, $error]): ?>
        <tr><td><?php echo h($descripcion); ?></td><td class="<?php echo $ok ? 'ok' : 'mal'; ?>"><?php echo $ok ? 'Correcto' : 'ERROR: ' . h($error); ?></td></tr>
    <?php endforeach; ?>
    </tbody></table>
    <p class="<?php echo $escrituraOk ? 'ok' : 'mal'; ?>"><?php echo $escrituraOk ? 'La base de datos acepta escrituras.' : 'La base de datos NO acepta escrituras: este es el problema.'; ?></p>

    <h2>3. Esquema frente a lo que espera el código</h2>
    <?php if ($tablasFaltantes === [] && $columnasFaltantes === []): ?>
        <p class="ok">Todas las tablas y columnas esperadas existen.</p>
    <?php else: ?>
        <div class="caja">
            <?php if ($tablasFaltantes !== []): ?>
                <p class="mal">Tablas que faltan: <?php echo h(implode(', ', $tablasFaltantes)); ?></p>
            <?php endif; ?>
            <?php if ($columnasFaltantes !== []): ?>
                <p class="mal">Columnas que faltan:</p>
                <ul><?php foreach ($columnasFaltantes as $falta): ?><li><code><?php echo h($falta['tabla'] . '.' . $falta['columna']); ?></code></li><?php endforeach; ?></ul>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo h($tokenCsrf); ?>">
                <button type="submit">Reparar: agregar lo que falta (no borra datos)</button>
            </form>
        </div>
    <?php endif; ?>
    <?php if ($resultadoReparacion !== []): ?>
        <h2>Resultado de la reparación</h2>
        <table><tbody>
        <?php foreach ($resultadoReparacion as [$ok, $texto]): ?>
            <tr><td class="<?php echo $ok ? 'ok' : 'mal'; ?>"><?php echo $ok ? 'OK' : 'ERROR'; ?></td><td><?php echo h($texto); ?></td></tr>
        <?php endforeach; ?>
        </tbody></table>
        <p class="sub">Recarga esta página para ver el estado actualizado.</p>
    <?php endif; ?>
    <?php if ($noInnoDb !== []): ?>
        <p class="aviso">Tablas que no usan InnoDB (no admiten transacciones): <?php echo h(implode(', ', $noInnoDb)); ?></p>
    <?php endif; ?>

    <h2>4. Registros guardados (compáralos con phpMyAdmin)</h2>
    <table>
        <thead><tr><th>Tabla</th><th>Filas</th><th>Último id</th><th>Último registro</th></tr></thead>
        <tbody>
        <?php foreach ($conteos as $tabla => $datos): ?>
            <tr>
                <td><code><?php echo h($tabla); ?></code></td>
                <?php if (isset($datos['error'])): ?>
                    <td colspan="3" class="mal"><?php echo h($datos['error']); ?></td>
                <?php else: ?>
                    <td><?php echo h($datos['total']); ?></td><td><?php echo h($datos['ultimo_id'] ?? ''); ?></td><td><?php echo h($datos['ultima_fecha'] ?? ''); ?></td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>
</body>
</html>