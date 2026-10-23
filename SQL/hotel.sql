-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-10-2026 a las 02:42:37
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
-- En MariaDB, el DDL puede confirmar cambios aunque el volcado abra una transaccion.
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `hotel`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `agenda_actividad`
--

CREATE TABLE `agenda_actividad` (
  `id_agenda` bigint(20) NOT NULL,
  `actividad` varchar(150) NOT NULL,
  `opcion_actividad` varchar(100) NOT NULL DEFAULT '',
  `selecciones_personas_json` text DEFAULT NULL,
  `fecha_agenda` date DEFAULT NULL,
  `hora_agenda` time DEFAULT NULL,
  `nombre_contacto` varchar(140) NOT NULL,
  `correo_contacto` varchar(140) NOT NULL,
  `id_usu_agenda` bigint(20) DEFAULT NULL,
  `estado_agenda` varchar(30) DEFAULT 'Pendiente',
  `creado_en` datetime DEFAULT current_timestamp(),
  `estado_pago_experiencia` varchar(20) NOT NULL DEFAULT 'Pendiente',
  `fecha_pago_experiencia` datetime DEFAULT NULL,
  `metodo_pago_experiencia` varchar(30) DEFAULT NULL,
  `cod_res_agenda` bigint(20) DEFAULT NULL,
  `monto_experiencia` decimal(12,2) DEFAULT NULL,
  `precios_personas_json` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `agenda_actividad`
--

INSERT INTO `agenda_actividad` (`id_agenda`, `actividad`, `opcion_actividad`, `selecciones_personas_json`, `fecha_agenda`, `hora_agenda`, `nombre_contacto`, `correo_contacto`, `id_usu_agenda`, `estado_agenda`, `creado_en`, `estado_pago_experiencia`, `fecha_pago_experiencia`, `metodo_pago_experiencia`, `cod_res_agenda`, `monto_experiencia`, `precios_personas_json`) VALUES
(1, 'Paseo náutico', '', NULL, '2026-08-05', '10:57:00', 'adssa', 'yuli.aa.gomez@gmail.com', NULL, 'Pendiente', '2026-08-03 09:56:17', 'Cancelada', NULL, NULL, NULL, NULL, NULL),
(2, 'Gastronomía', '', NULL, '2026-02-11', '20:00:00', 'yulieth', 'aa.gomez@gmail.com', NULL, 'Pendiente', '2026-09-28 07:31:57', 'Pendiente', NULL, NULL, NULL, NULL, NULL),
(3, 'Comida', 'Personalizada', '[\"Solicitud personalizada\"]', NULL, NULL, 'Luisa Peralta', 'luisita@gmail.com', 36, 'Pendiente', '2026-10-05 19:39:29', 'Cancelada', NULL, NULL, 48, NULL, '[null]');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle`
--

CREATE TABLE `detalle` (
  `cod_det` bigint(20) NOT NULL,
  `can_noc_det` int(11) NOT NULL,
  `cod_res_det` bigint(20) DEFAULT NULL,
  `cod_hab_det` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle`
--

INSERT INTO `detalle` (`cod_det`, `can_noc_det`, `cod_res_det`, `cod_hab_det`) VALUES
(5, 5, 6, 10),
(7, 6, 8, 18),
(9, 4, 10, 24),
(10, 7, 11, 30),
(12, 27, 13, 4),
(13, 27, 14, 5),
(14, 27, 15, 6),
(15, 27, 16, 40),
(16, 27, 17, 37),
(17, 10, 18, 4),
(18, 10, 19, 5),
(19, 2, 20, 3),
(20, 3, 21, 6),
(21, 29, 22, 7),
(22, 1, 23, 9),
(23, 1, 24, 9),
(24, 1, 25, 11),
(25, 1, 26, 11),
(26, 1, 27, 2),
(27, 1, 28, 12),
(28, 1, 29, 13),
(29, 1, 30, 18),
(30, 1, 31, 15),
(31, 7, 32, 19),
(32, 7, 33, 20),
(33, 37, 34, 22),
(34, 1, 35, 21),
(35, 1, 36, 23),
(36, 1, 37, 24),
(37, 1, 38, 11),
(38, 1, 39, 2),
(39, 1, 40, 11),
(40, 3, 41, 2),
(41, 1, 42, 36),
(42, 2, 43, 3),
(43, 1, 44, 9),
(44, 14, 45, 33),
(45, 1, 46, 22),
(46, 4, 47, 34),
(47, 8, 48, 4),
(48, 6, 49, 31),
(49, 1, 50, 5),
(50, 2, 51, 6);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `experiencias`
--

CREATE TABLE `experiencias` (
  `id` int(11) NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `imagen` varchar(255) DEFAULT NULL,
  -- Campos heredados: la aplicacion actual aun los consulta y actualiza.
  `opcion_1` varchar(100) NOT NULL DEFAULT '',
  `opcion_2` varchar(100) NOT NULL DEFAULT '',
  `opcion_3` varchar(100) NOT NULL DEFAULT '',
  `precio_opcion_1` decimal(12,2) DEFAULT NULL,
  `precio_opcion_2` decimal(12,2) DEFAULT NULL,
  `precio_opcion_3` decimal(12,2) DEFAULT NULL,
  `horarios_json` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `experiencias`
--

INSERT INTO `experiencias` (`id`, `categoria`, `nombre`, `descripcion`, `fecha_creacion`, `imagen`, `opcion_1`, `opcion_2`, `opcion_3`, `precio_opcion_1`, `precio_opcion_2`, `precio_opcion_3`, `horarios_json`) VALUES
(1, 'Diversión y entretenimiento', 'Planes especiales', 'Rituales de relajación, masajes premium y circuitos privados para renovar cuerpo y mente. jjjj', '2026-09-28 12:28:52', NULL, '', '', '', NULL, NULL, NULL, NULL),
(2, 'Bienestar y comodidad', 'Actividades', 'Agenda actividades guiadas y experiencias a la medida durante tu estancia.', '2026-09-28 12:28:52', NULL, '', '', '', NULL, NULL, NULL, NULL),
(3, 'Sabores exclusivos', 'Gastronomía', 'Menú costero, cocina de autor y maridajes elegantes para una velada inolvidable.', '2026-09-28 12:28:52', NULL, '', '', '', NULL, NULL, NULL, NULL),
(5, 'Entretenimiento', 'Parque Acuatico', 'Diviertete en el mundo acuatico!!!', '2026-10-06 04:45:58', NULL, 'Tobogan Triple', '', '', 30000.00, NULL, NULL, '{\"1\":{\"2026-10-06\":{\"inicio\":\"08:00\",\"fin\":\"19:00\"},\"2026-10-07\":{\"inicio\":\"08:00\",\"fin\":\"19:00\"},\"2026-10-08\":{\"inicio\":\"08:00\",\"fin\":\"19:00\"},\"2026-10-09\":{\"inicio\":\"08:00\",\"fin\":\"19:00\"},\"2026-10-10\":{\"inicio\":\"08:00\",\"fin\":\"19:00\"},\"2026-10-11\":{\"inicio\":\"08:00\",\"fin\":\"19:00\"},\"2026-10-12\":{\"inicio\":\"08:00\",\"fin\":\"19:00\"},\"2026-10-13\":{\"inicio\":\"08:00\",\"fin\":\"19:00\"},\"2026-10-14\":{\"inicio\":\"08:00\",\"fin\":\"19:00\"},\"2026-10-15\":{\"inicio\":\"08:00\",\"fin\":\"19:00\"},\"2026-10-16\":{\"inicio\":\"08:00\",\"fin\":\"19:00\"},\"2026-10-17\":{\"inicio\":\"08:00\",\"fin\":\"19:00\"},\"2026-10-18\":{\"inicio\":\"08:00\",\"fin\":\"19:00\"},\"2026-10-19\":{\"inicio\":\"08:00\",\"fin\":\"19:00\"},\"2026-10-20\":{\"inicio\":\"08:00\",\"fin\":\"19:00\"},\"2026-10-21\":{\"inicio\":\"08:00\",\"fin\":\"19:00\"},\"2026-10-22\":{\"inicio\":\"08:00\",\"fin\":\"19:00\"},\"2026-10-23\":{\"inicio\":\"08:00\",\"fin\":\"19:00\"},\"2026-10-24\":{\"inicio\":\"08:00\",\"fin\":\"19:00\"},\"2026-10-25\":{\"inicio\":\"08:00\",\"fin\":\"19:00\"},\"2026-10-26\":{\"inicio\":\"08:00\",\"fin\":\"19:00\"},\"2026-10-27\":{\"inicio\":\"08:00\",\"fin\":\"19:00\"},\"2026-10-28\":{\"inicio\":\"08:00\",\"fin\":\"19:00\"},\"2026-10-29\":{\"inicio\":\"08:00\",\"fin\":\"19:00\"},\"2026-10-30\":{\"inicio\":\"08:00\",\"fin\":\"19:00\"}},\"2\":[],\"3\":[]}');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `factura`
--

CREATE TABLE `factura` (
  `cod_fac` bigint(20) NOT NULL,
  `fech_fac` datetime DEFAULT current_timestamp(),
  `tot_fac` decimal(10,2) DEFAULT NULL,
  `est_fac` varchar(20) DEFAULT NULL,
  `cod_res_fac` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `habitacion`
--

CREATE TABLE `habitacion` (
  `cod_hab` bigint(20) NOT NULL,
  `num_hab` int(11) NOT NULL,
  `tipo_hab` varchar(50) DEFAULT NULL,
  -- precio_hab se conserva por compatibilidad hasta migrar la aplicacion.
  `pre_hab` decimal(10,2) DEFAULT NULL,
  `est_hab` varchar(20) DEFAULT 'Disponible',
  `precio_hab` decimal(10,2) DEFAULT 0.00,
  `capacidad_max` int(11) NOT NULL DEFAULT 4,
  `obs_hab` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `habitacion`
--

INSERT INTO `habitacion` (`cod_hab`, `num_hab`, `tipo_hab`, `pre_hab`, `est_hab`, `precio_hab`, `obs_hab`) VALUES
(1, 101, 'Sencilla', 50000.00, 'Limpio', 0.00, ''),
(2, 201, 'Doble', 85000.00, 'Disponible', 0.00, NULL),
(3, 102, 'Sencilla', 50000.00, 'Ocupada', 0.00, ''),
(4, 103, 'Sencilla', 50000.00, 'Ocupada', 0.00, NULL),
(5, 104, 'Doble', 85000.00, 'Ocupada', 0.00, NULL),
(6, 105, 'Doble', 85000.00, 'Ocupada', 0.00, NULL),
(7, 106, 'Suite', 150000.00, 'Ocupada', 0.00, NULL),
(8, 107, 'Sencilla', 50000.00, 'Sucia', 0.00, NULL),
(9, 108, 'Sencilla', 50000.00, 'Ocupada', 0.00, NULL),
(10, 109, 'Doble', 85000.00, 'Mantenimiento', 0.00, NULL),
(11, 110, 'Doble', 85000.00, 'Ocupada', 0.00, NULL),
(12, 202, 'Sencilla', 50000.00, 'Ocupada', 0.00, NULL),
(13, 203, 'Sencilla', 50000.00, 'Ocupada', 0.00, NULL),
(14, 204, 'Doble', 85000.00, 'Sucia', 0.00, NULL),
(15, 205, 'Doble', 85000.00, 'Ocupada', 0.00, NULL),
(16, 206, 'Suite', 150000.00, 'Mantenimiento', 0.00, NULL),
(17, 207, 'Sencilla', 50000.00, 'Mantenimiento', 0.00, NULL),
(18, 208, 'Sencilla', 50000.00, 'Ocupada', 0.00, NULL),
(19, 209, 'Doble', 85000.00, 'Disponible', 0.00, NULL),
(20, 210, 'Doble', 85000.00, 'Disponible', 0.00, NULL),
(21, 301, 'Sencilla', 50000.00, 'Disponible', 0.00, NULL),
(22, 302, 'Sencilla', 50000.00, 'Ocupada', 0.00, ''),
(23, 303, 'Doble', 85000.00, 'Ocupada', 0.00, ''),
(24, 304, 'Doble', 85000.00, 'Ocupada', 0.00, ''),
(25, 305, 'Suite', 150000.00, 'Disponible', 0.00, NULL),
(26, 306, 'Suite', 150000.00, 'Disponible', 0.00, NULL),
(27, 307, 'Sencilla', 50000.00, 'Disponible', 0.00, NULL),
(28, 308, 'Sencilla', 50000.00, 'Disponible', 0.00, NULL),
(29, 309, 'Doble', 85000.00, 'Sucia', 0.00, NULL),
(30, 310, 'Doble', 85000.00, 'Disponible', 0.00, NULL),
(31, 401, 'Sencilla', 50000.00, 'Disponible', 0.00, NULL),
(32, 402, 'Sencilla', 50000.00, 'Disponible', 0.00, NULL),
(33, 403, 'Doble', 85000.00, 'Ocupada', 0.00, NULL),
(34, 404, 'Doble', 85000.00, 'Ocupada', 0.00, ''),
(35, 405, 'Suite Premium', 200000.00, 'Disponible', 0.00, NULL),
(36, 406, 'Suite Premium', 200000.00, 'Ocupada', 0.00, ''),
(37, 407, 'Sencilla', 50000.00, 'Ocupada', 0.00, NULL),
(38, 408, 'Sencilla', 50000.00, 'Sucia', 0.00, NULL),
(39, 409, 'Doble', 85000.00, 'Mantenimiento', 0.00, NULL),
(40, 410, 'Doble', 85000.00, 'Ocupada', 0.00, '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id_pago` bigint(20) NOT NULL,
  `cod_res_pago` bigint(20) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(50) NOT NULL,
  `referencia_pago` varchar(100) NOT NULL,
  `estado_pago` enum('Aprobado','Pendiente','Fallido') DEFAULT 'Pendiente',
  `fecha_pago` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id_pago`, `cod_res_pago`, `monto`, `metodo_pago`, `referencia_pago`, `estado_pago`, `fecha_pago`) VALUES
(16, 40, 101150.00, 'Tarjeta', 'REF-1786030273-40', 'Aprobado', '2026-08-06 10:31:13'),
(17, 41, 303450.00, 'Transferencia', 'REF-1786030375-41', 'Aprobado', '2026-08-06 10:32:55'),
(18, 42, 119000.00, 'Tarjeta', 'REF-1786030793-42', 'Aprobado', '2026-08-06 10:39:53'),
(19, 43, 119000.00, 'Recepción', 'REF-1787230558-43', 'Pendiente', '2026-08-20 07:55:58'),
(20, 44, 59500.00, 'Recepción', 'REF-1787238618-44', 'Pendiente', '2026-08-20 10:10:18'),
(21, 45, 1416100.00, 'Tarjeta', 'REF-1790682515-45', 'Aprobado', '2026-09-29 06:48:35'),
(22, 46, 59500.00, 'Tarjeta', 'REF-1790682591-46', 'Aprobado', '2026-09-29 06:49:51'),
(23, 47, 404600.00, 'Tarjeta', 'REF-2fe96b309e19c28d304b5f33dd29ac26', 'Aprobado', '2026-10-05 16:38:19'),
(24, 48, 476000.00, 'Recepción', 'REF-277c168b5d76f85d0358d83ae0757682', 'Pendiente', '2026-10-05 19:08:58'),
(25, 49, 357000.00, 'Transferencia', 'REF-bc770a7d6cf920c82f71e10186849973', 'Aprobado', '2026-10-05 23:31:30'),
(26, 50, 101150.00, 'Transferencia', 'REF-3cfa6adfdb96d6535a767985c1bc2e2a', 'Aprobado', '2026-10-14 17:20:47'),
(27, 51, 202300.00, 'Efectivo', 'REF-2a2170f9074b6e58591a5e8ee6e77f35', 'Aprobado', '2026-10-14 17:24:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_resets`
--

CREATE TABLE `password_resets` (
  `id` bigint(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(128) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `password_resets`
--
-- No exportar tokens: son secretos temporales y los registros del respaldo ya expiraron.

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permiso`
--

CREATE TABLE `permiso` (
  `cod_permiso` varchar(60) NOT NULL,
  `modulo` varchar(40) NOT NULL,
  `accion` varchar(40) NOT NULL,
  `des_permiso` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `permiso`
--

INSERT INTO `permiso` (`cod_permiso`, `modulo`, `accion`, `des_permiso`) VALUES
('configuracion.editar', 'configuracion', 'editar', 'Editar la configuración del sistema'),
('configuracion.ver', 'configuracion', 'ver', 'Ver la sección de seguridad/configuración'),
('dashboard.ver', 'dashboard', 'ver', 'Ver el panel principal'),
('experiencias.gestionar', 'experiencias', 'gestionar', 'Crear, editar y eliminar experiencias'),
('experiencias.ver', 'experiencias', 'ver', 'Ver la sección de Experiencias en el panel'),
('finanzas.exportar', 'finanzas', 'exportar', 'Exportar reportes financieros'),
('finanzas.ver', 'finanzas', 'ver', 'Ver reportes financieros'),
('logs.ver', 'logs', 'ver', 'ver logs'),
('operaciones.editar', 'operaciones', 'editar', 'Cambiar el estado de las habitaciones (aseo, mantenimiento, etc.)'),
('operaciones.ver', 'operaciones', 'ver', 'Ver el estado de las habitaciones'),
('reservas.crear', 'reservas', 'crear', 'Crear nuevas reservas'),
('reservas.editar', 'reservas', 'editar', 'Editar reservas existentes'),
('reservas.eliminar', 'reservas', 'eliminar', 'Eliminar reservas'),
('reservas.ver', 'reservas', 'ver', 'Ver el listado de reservas'),
('roles.asignar_permisos', 'roles', 'asignar_permisos', 'Asignar permisos a un rol'),
('roles.gestionar', 'roles', 'gestionar', 'Crear, editar y eliminar roles'),
('roles.ver', 'roles', 'ver', 'Ver la sección de roles y permisos');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas`
--

CREATE TABLE `reservas` (
  `cod_res` bigint(20) NOT NULL,
  `fec_res` datetime DEFAULT current_timestamp(),
  `fec_ent_res` datetime NOT NULL,
  `fec_sal_res` datetime NOT NULL,
  `est_res` varchar(20) DEFAULT 'Pendiente',
  `not_res` text DEFAULT NULL,
  `id_usu_res` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reservas`
--

INSERT INTO `reservas` (`cod_res`, `fec_res`, `fec_ent_res`, `fec_sal_res`, `est_res`, `not_res`, `id_usu_res`) VALUES
(6, '2026-04-22 13:33:07', '2026-05-07 00:00:00', '2026-05-12 00:00:00', 'Confirmada', '- Palitos de quezo', 14),
(8, '2026-04-22 13:52:56', '2026-04-22 00:00:00', '2026-04-28 00:00:00', 'Confirmada', '- Un escenerio para bailar', 16),
(10, '2026-04-23 16:14:22', '2026-05-07 00:00:00', '2026-05-11 00:00:00', 'En Casa', '- Canasta de Rom', 21),
(11, '2026-04-24 06:49:01', '2026-05-01 00:00:00', '2026-05-08 00:00:00', 'Confirmada', '- Canasta de champañas, para las noches', 22),
(13, '2026-08-03 08:20:41', '2026-08-21 15:00:00', '2026-09-17 12:00:00', 'Pendiente', 'Adultos: 2 | Niños: 0\nReserva web usuario | Pago: Recepción | 2 adultos, 0 niños', 28),
(14, '2026-08-03 08:21:11', '2026-08-21 15:00:00', '2026-09-17 12:00:00', 'Pendiente', 'Adultos: 2 | Niños: 0\nReserva web usuario | Pago: Transferencia | 2 adultos, 0 niños', 28),
(15, '2026-08-03 08:22:00', '2026-08-21 15:00:00', '2026-09-17 12:00:00', 'Pendiente', 'Adultos: 2 | Niños: 0\nReserva web usuario | Pago: Transferencia | 2 adultos, 0 niños', 28),
(16, '2026-08-03 08:23:43', '2026-08-21 15:00:00', '2026-09-17 12:00:00', 'Pendiente', 'Adultos: 2 | Niños: 0\nReserva web usuario | Pago: Recepción | 2 adultos, 0 niños', 29),
(17, '2026-08-03 08:25:50', '2026-08-21 15:00:00', '2026-09-17 12:00:00', 'Pendiente', 'Adultos: 2 | Niños: 0\nReserva web usuario | Pago: Transferencia | 2 adultos, 0 niños', 29),
(18, '2026-08-03 08:50:23', '2026-08-03 15:00:00', '2026-08-13 12:00:00', 'Pendiente', 'Adultos: 2 | Niños: 0\nReserva web usuario | Pago: Tarjeta | 2 adultos, 0 niños', 28),
(19, '2026-08-03 09:54:49', '2026-08-03 15:00:00', '2026-08-13 12:00:00', 'Pendiente', 'Adultos: 2 | Niños: 0\nReserva web usuario | Pago: Tarjeta | 2 adultos, 0 niños', 28),
(20, '2026-08-03 11:14:42', '2026-08-04 15:00:00', '2026-08-06 12:00:00', 'Pendiente', 'Adultos: 1 | Niños: 1\nReserva web usuario | Pago: Tarjeta | 1 adultos, 1 niños', 30),
(21, '2026-08-04 07:21:02', '2026-08-04 15:00:00', '2026-08-07 12:00:00', 'Pendiente', 'Adultos: 2 | Niños: 0\nReserva web usuario | Pago: Tarjeta | 2 adultos, 0 niños', 31),
(22, '2026-08-04 07:26:30', '2026-08-04 15:00:00', '2026-09-02 12:00:00', 'Pendiente', 'Adultos: 2 | Niños: 0\nReserva web usuario | Pago: Tarjeta | 2 adultos, 0 niños', 31),
(23, '2026-08-04 07:53:11', '2026-08-05 15:00:00', '2026-08-06 12:00:00', 'Pendiente', 'Adultos: 2 | Niños: 0\nReserva web usuario | Pago: Tarjeta | 2 adultos, 0 niños', 31),
(24, '2026-08-04 08:28:03', '2026-08-04 15:00:00', '2026-08-05 12:00:00', 'Pendiente', 'Adultos: 2 | Niños: 0\nReserva web usuario | Pago: Tarjeta | 2 adultos, 0 niños', 31),
(25, '2026-08-04 08:58:40', '2026-09-18 15:00:00', '2026-09-19 12:00:00', 'Confirmada', 'Adultos: 2 | Niños: 0 | Pago: Tarjeta\nReserva web usuario | Pago: Tarjeta | 2 adultos, 0 niños', 31),
(26, '2026-08-04 09:59:27', '2026-08-04 15:00:00', '2026-08-05 12:00:00', 'Confirmada', 'Adultos: 2 | Niños: 0 | Pago: Tarjeta\nReserva web usuario | Pago: Tarjeta | 2 adultos, 0 niños', 31),
(27, '2026-08-04 10:08:07', '2026-08-04 15:00:00', '2026-08-05 12:00:00', 'Confirmada', 'Adultos: 2 | Niños: 0 | Pago: Tarjeta\nReserva web usuario | Pago: Transferencia | 2 adultos, 0 niños', 31),
(28, '2026-08-04 10:08:42', '2026-08-04 15:00:00', '2026-08-05 12:00:00', 'Confirmada', 'Adultos: 2 | Niños: 0 | Pago: Tarjeta\nReserva web usuario | Pago: Tarjeta | 2 adultos, 0 niños', 31),
(29, '2026-08-04 10:27:53', '2026-08-04 15:00:00', '2026-08-05 12:00:00', 'Confirmada', 'Adultos: 2 | Niños: 0 | Pago: Tarjeta\nReserva web usuario | Pago: Recepción | 2 adultos, 0 niños', 31),
(30, '2026-08-04 10:28:08', '2026-08-04 15:00:00', '2026-08-05 12:00:00', 'Confirmada', 'Adultos: 2 | Niños: 0 | Pago: Tarjeta\nReserva web usuario | Pago: Tarjeta | 2 adultos, 0 niños', 31),
(31, '2026-08-04 10:44:39', '2026-08-04 15:00:00', '2026-08-05 12:00:00', 'Confirmada', 'Adultos: 2 | Niños: 0 | Pago: Tarjeta\nReserva web usuario | Pago: Tarjeta | 2 adultos, 0 niños', 31),
(32, '2026-08-04 10:54:51', '2026-08-04 15:00:00', '2026-08-11 12:00:00', 'Confirmada', 'Adultos: 2 | Niños: 1 | Pago: Tarjeta\nReserva web usuario | Pago: Recepción | 2 adultos, 1 niños', 31),
(33, '2026-08-04 10:55:04', '2026-08-04 15:00:00', '2026-08-11 12:00:00', 'Confirmada', 'Adultos: 2 | Niños: 1 | Pago: Tarjeta\nReserva web usuario | Pago: Recepción | 2 adultos, 1 niños', 31),
(34, '2026-08-04 11:08:53', '2026-08-04 15:00:00', '2026-09-10 12:00:00', 'Confirmada', 'Adultos: 2 | Niños: 0 | Pago: Tarjeta\nReserva web usuario | Pago: Tarjeta | 2 adultos, 0 niños', 31),
(35, '2026-08-04 11:14:27', '2026-08-04 15:00:00', '2026-08-05 12:00:00', 'Confirmada', 'Adultos: 2 | Niños: 0 | Pago: Tarjeta\nReserva web usuario | Pago: Tarjeta | 2 adultos, 0 niños', 31),
(36, '2026-08-05 06:46:12', '2026-09-15 15:00:00', '2026-09-16 12:00:00', 'Confirmada', 'Adultos: 2 | Niños: 1 | Pago: Tarjeta\nReserva web usuario | Pago: Tarjeta | 2 adultos, 1 niños', 31),
(37, '2026-08-05 10:33:02', '2026-09-15 15:00:00', '2026-09-16 12:00:00', 'Confirmada', 'Adultos: 2 | Niños: 1 | Pago: Tarjeta\nReserva web usuario | Pago: Tarjeta | 2 adultos, 1 niños', 31),
(38, '2026-08-06 10:12:06', '2026-08-10 15:00:00', '2026-08-11 12:00:00', 'Confirmada', 'Adultos: 2 | Niños: 0 | Pago: Transferencia\nCobro web | Porcentaje: 100% | Método: Transferencia', 31),
(39, '2026-08-06 10:19:36', '2026-08-10 15:00:00', '2026-08-11 12:00:00', 'Confirmada', 'Adultos: 2 | Niños: 0 | Pago: Transferencia\nCobro web | Porcentaje: 100% | Método: Transferencia', 31),
(40, '2026-08-06 10:31:13', '2026-08-11 15:00:00', '2026-08-12 12:00:00', 'Confirmada', 'Adultos: 2 | Niños: 0 | Pago: Tarjeta\nPorcentaje de cobro: 100%', 31),
(41, '2026-08-06 10:32:55', '2026-09-16 15:00:00', '2026-09-19 12:00:00', 'Confirmada', 'Adultos: 2 | Niños: 0 | Pago: Transferencia\nPorcentaje de cobro: 100%', 31),
(42, '2026-08-06 10:39:53', '2026-08-26 15:00:00', '2026-08-27 12:00:00', 'Confirmada', 'Adultos: 2 | Niños: 0 | Pago: Tarjeta\nPorcentaje de cobro: 50%', 31),
(43, '2026-08-20 07:55:58', '2026-09-15 15:00:00', '2026-09-17 12:00:00', 'Pendiente', 'Adultos: 2 | Niños: 0 | Pago: Recepción\nPorcentaje de cobro: 100%', 31),
(44, '2026-08-20 10:10:18', '2026-09-02 15:00:00', '2026-09-03 12:00:00', 'Pendiente', 'Adultos: 2 | Niños: 0 | Pago: Recepción\nPorcentaje de cobro: 100%', 31),
(45, '2026-09-29 06:48:35', '2026-09-16 15:00:00', '2026-09-30 12:00:00', 'Confirmada', 'Adultos: 1 | Niños: 0 | Pago: Tarjeta', 28),
(46, '2026-09-29 06:49:51', '2026-09-30 15:00:00', '2026-10-01 12:00:00', 'Confirmada', 'Adultos: 1 | Niños: 0 | Pago: Tarjeta\ngccg', 28),
(47, '2026-10-05 16:38:19', '2026-10-06 15:00:00', '2026-10-10 12:00:00', 'Confirmada', 'Adultos: 2 | Niños: 0 | Pago: Tarjeta\n1 Cortel para cenar', 35),
(48, '2026-10-05 19:08:58', '2026-10-06 15:00:00', '2026-10-14 12:00:00', 'Pendiente', 'Adultos: 1 | Niños: 0 | Pago: Recepción\nPorcentaje de cobro: 100%', 36),
(49, '2026-10-05 23:31:30', '2026-10-06 15:00:00', '2026-10-12 12:00:00', 'Confirmada', 'Adultos: 1 | Niños: 0 | Pago: Transferencia\nVista a la piscina', 26),
(50, '2026-10-14 17:18:28', '2026-10-14 15:00:00', '2026-10-15 12:00:00', 'Confirmada', 'Adultos: 50 | Niños: 56 | Pago: Recepción\r\nPorcentaje de cobro: 100%', 39),
(51, '2026-10-14 17:24:13', '2026-10-14 15:00:00', '2026-10-16 12:00:00', 'Confirmada', 'Adultos: 2 | Niños: 0 | Pago: Recepción\r\nPorcentaje de cobro: 100%', 39);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

CREATE TABLE `rol` (
  `cod_rol` bigint(20) NOT NULL,
  `des_rol` varchar(200) NOT NULL,
  `detalle_rol` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rol`
--

INSERT INTO `rol` (`cod_rol`, `des_rol`, `detalle_rol`) VALUES
(1, 'Gerente General', ''),
(2, 'Gestor de Ingresos', ''),
(3, 'Recepcionista', ''),
(4, 'Conserje', ''),
(5, 'Personal de Limpieza', NULL),
(6, 'Cliente', ''),
(11, 'Guardia', 'Guardia  de Seguridad, encargado de registrar tareas y otras tareas sencillas');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol_permiso`
--

CREATE TABLE `rol_permiso` (
  `cod_rol` bigint(20) NOT NULL,
  `cod_permiso` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rol_permiso`
--

INSERT INTO `rol_permiso` (`cod_rol`, `cod_permiso`) VALUES
(1, 'configuracion.editar'),
(1, 'configuracion.ver'),
(1, 'dashboard.ver'),
(1, 'experiencias.gestionar'),
(1, 'experiencias.ver'),
(1, 'finanzas.exportar'),
(1, 'finanzas.ver'),
(1, 'logs.ver'),
(1, 'operaciones.editar'),
(1, 'operaciones.ver'),
(1, 'reservas.crear'),
(1, 'reservas.editar'),
(1, 'reservas.eliminar'),
(1, 'reservas.ver'),
(1, 'roles.asignar_permisos'),
(1, 'roles.gestionar'),
(1, 'roles.ver'),
(2, 'dashboard.ver'),
(2, 'experiencias.ver'),
(2, 'finanzas.exportar'),
(2, 'finanzas.ver'),
(2, 'operaciones.ver'),
(2, 'reservas.crear'),
(2, 'reservas.editar'),
(2, 'reservas.ver'),
(3, 'dashboard.ver'),
(3, 'operaciones.editar'),
(3, 'operaciones.ver'),
(3, 'reservas.ver'),
(4, 'dashboard.ver'),
(4, 'operaciones.editar'),
(4, 'operaciones.ver'),
(5, 'dashboard.ver'),
(5, 'operaciones.editar'),
(5, 'operaciones.ver'),
(6, 'reservas.crear'),
(6, 'reservas.ver'),
(11, 'dashboard.ver'),
(11, 'operaciones.editar'),
(11, 'operaciones.ver');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tarea`
--

CREATE TABLE `tarea` (
  `cod_tar` bigint(20) NOT NULL,
  `tit_tar` varchar(100) NOT NULL,
  `cat_tar` varchar(50) NOT NULL,
  `prioridad_tar` enum('Baja','Media','Alta') DEFAULT 'Media',
  `des_tar` text DEFAULT NULL,
  `est_tar` varchar(20) DEFAULT 'Pendiente',
  `fec_tar` datetime DEFAULT current_timestamp(),
  `cod_usu_tar` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tarea`
--

INSERT INTO `tarea` (`cod_tar`, `tit_tar`, `cat_tar`, `prioridad_tar`, `des_tar`, `est_tar`, `fec_tar`, `cod_usu_tar`) VALUES
(1, 'Limpiar ', 'GENERAL', 'Media', 'Limpiar el polvo del techo', 'Completada', '2026-04-16 22:57:45', 2),
(2, 'Limpiar', 'GENERAL', 'Media', 'Barrer las escaleras', 'Completada', '2026-04-17 08:11:56', 2),
(3, 'Trapear', 'URGENTE', 'Media', 'Trapear la recepcion principal', 'Completada', '2026-04-17 08:21:00', 2),
(4, 'Reabastecimiento', 'GENERAL', 'Media', 'Resbastecer los utensilios de limpieza, papel higienico, jabones', 'Completada', '2026-04-17 08:47:28', 2),
(5, 'Trapear', 'GENERAL', 'Media', 'Trapea los baños', 'Completada', '2026-04-17 08:48:45', 2),
(6, 'Limpiar ', 'URGENTE', 'Media', 'Barrer el pasillo del segundo piso\r\n', 'Completada', '2026-04-18 12:13:06', 2),
(7, 'Trapear', 'GENERAL', 'Media', 'Los baños', 'Completada', '2026-04-18 12:13:21', 2),
(8, 'Limpiar el mugre', 'URGENTE', 'Media', 'XD', 'Completada', '2026-04-18 12:43:13', 2),
(9, 'Limpiar ', 'LIMPIEZA', 'Media', 'dasd', 'Completada', '2026-04-18 12:48:48', 2),
(10, 'Trapear', 'LIMPIEZA', 'Media', 'fghdfhd', 'Completada', '2026-04-18 12:56:23', 2),
(11, 'Limpiar el mugre', 'LIMPIEZA', 'Media', 'gdfgdg', 'Completada', '2026-04-18 14:50:26', 2),
(12, 'Reabastecimiento', 'URGENTE', 'Media', 'Llevar papel higienico a los baños\r\n', 'Completada', '2026-04-18 15:06:34', 2),
(13, 'Trapear', 'URGENTE', 'Media', 'Las escaleras', 'Completada', '2026-04-18 15:20:15', 3),
(14, 'Barrer', 'GENERAL', 'Media', 'Piso 1', 'Completada', '2026-04-18 18:43:27', 4),
(15, 'Reabastecimiento', 'URGENTE', 'Media', 'Baños', 'Completada', '2026-04-19 22:00:54', 2),
(16, 'Barrer', 'GENERAL', 'Media', 'piso 2', 'Completada', '2026-04-19 22:02:04', 1),
(17, 'Limpiar el mugre', 'URGENTE', 'Media', 'fdhd', 'Completada', '2026-04-19 22:24:45', 2),
(18, 'Limpiar ', 'GENERAL', 'Media', 'trhn', 'Completada', '2026-04-19 22:26:05', 2),
(19, 'Lavar los baños', 'GENERAL', 'Media', 'Piso 1 y 2', 'Completada', '2026-04-23 15:16:51', 2),
(20, 'Limpiar el mugre', 'GENERAL', 'Media', 'bhhv', 'Completada', '2026-04-23 15:23:05', 1),
(21, 'Sacar la basura', 'URGENTE', 'Media', 'Piso 1, 2 y 3', 'Completada', '2026-04-24 00:37:05', 2),
(22, 'Limpiar el mugre', 'URGENTE', 'Media', 'fhdhfdfh', 'Completada', '2026-04-24 02:25:56', 1),
(23, 'Limpiar el mugre', 'GENERAL', 'Media', 'No se', 'Completada', '2026-04-24 13:42:40', 2),
(24, 'Reabastecimiento', 'URGENTE', 'Media', 'Despensa', 'Completada', '2026-04-24 13:44:22', 3),
(25, 'Trapear', 'GENERAL', 'Media', 'piso 1', 'Completada', '2026-04-24 13:46:18', 4),
(26, 'Trapear', 'URGENTE', 'Media', 'Piso 4, habitacion 401', 'Pendiente', '2026-04-24 13:57:32', 2),
(27, 'Trapear', 'URGENTE', 'Media', 'gfgfh', 'Completada', '2026-04-24 15:32:27', 2),
(28, 'fsa', 'GENERAL', 'Media', 'asd', 'Completada', '2026-09-02 17:45:27', 1),
(29, 'Quezo', 'GENERAL', 'Media', 'Piso 2', 'Completada', '2026-09-30 23:22:49', 2),
(30, 'Mugre', 'GENERAL', 'Media', 'Limpiar el pasillo 3 del piso 4', 'Pendiente', '2026-10-01 00:24:40', 2),
(31, 'Al fin VIDAAAAA', 'URGENTE', 'Media', 'color', 'Completada', '2026-10-05 05:34:53', 1),
(32, 'Mugre', 'LIMPIEZA', 'Media', 'Sala de reservas', 'Pendiente', '2026-10-06 00:44:02', 2),
(33, 'Trapear las escaleras', 'URGENTE', 'Media', 'Piso 2', 'Completada', '2026-10-06 00:48:22', 1),
(34, 'Limpiar las ventanas', 'LIMPIEZA', 'Media', 'Habs. del piso 3.', 'Completada', '2026-10-06 03:43:28', 2),
(35, 'Mugre', 'GENERAL', 'Media', 'Piso 3, cerca de las escaleras', 'Completada', '2026-10-06 05:24:32', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id_usu` bigint(20) NOT NULL,
  `doc_usu` varchar(200) DEFAULT NULL,
  `nom_usu` varchar(100) NOT NULL,
  `tel_usu` varchar(20) DEFAULT NULL,
  `corr_usu` varchar(100) NOT NULL,
  `psw_usu` varchar(255) NOT NULL,
  `est_usu` tinyint(1) DEFAULT 1,
  `cod_rol_usu` bigint(20) DEFAULT NULL,
  `foto_usu` varchar(255) DEFAULT 'default_avatar.png',
  `tema_usu` enum('claro','oscuro') DEFAULT 'claro',
  `idioma_usu` enum('es','en') DEFAULT 'es',
  `data_consent` tinyint(1) NOT NULL DEFAULT 0,
  `consent_date` datetime DEFAULT NULL,
  `consent_ip` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id_usu`, `doc_usu`, `nom_usu`, `tel_usu`, `corr_usu`, `psw_usu`, `est_usu`, `cod_rol_usu`, `foto_usu`, `tema_usu`, `idioma_usu`, `data_consent`, `consent_date`, `consent_ip`) VALUES
(1, NULL, 'Steven', NULL, 'gerente@hotel.com', '$2y$10$RblI4EQmg67F15qPDHF9Q.wJxoWvXd6/lDVOEjb5oj.G1bOvBXg2a', 1, 1, 'default_avatar.png', 'claro', 'es', 0, NULL, NULL),
(2, NULL, 'Yulli Recepcionista', NULL, 'yulli@hotel.com', '$2y$10$atnAuiX6XjARmfTHLF1yGuSAcjbhkQ//nRu7G2oN3.tL8UjGYT/1u', 1, 3, 'default_avatar.png', 'claro', 'es', 0, NULL, NULL),
(3, NULL, 'Samuel Conserje', NULL, 'samuel@hotel.com', '$2y$10$atnAuiX6XjARmfTHLF1yGuSAcjbhkQ//nRu7G2oN3.tL8UjGYT/1u', 1, 4, 'default_avatar.png', 'claro', 'es', 0, NULL, NULL),
(4, NULL, 'Samantha Limpieza', NULL, 'samantha@hotel.com', '$2y$10$atnAuiX6XjARmfTHLF1yGuSAcjbhkQ//nRu7G2oN3.tL8UjGYT/1u', 1, 5, 'default_avatar.png', 'claro', 'es', 0, NULL, NULL),
(7, NULL, 'Santiago Melo', NULL, 'gestorIngresos@hotel.com', '$2y$10$RblI4EQmg67F15qPDHF9Q.wJxoWvXd6/lDVOEjb5oj.G1bOvBXg2a', 1, 2, 'default_avatar.png', 'claro', 'es', 0, NULL, NULL),
(10, NULL, 'Will Smith', NULL, 'will@actor.com', '', 1, 3, 'default_avatar.png', 'claro', 'es', 0, NULL, NULL),
(14, NULL, 'Adam Samdler', NULL, 'adam@g.com', '', 1, 3, 'default_avatar.png', 'claro', 'es', 0, NULL, NULL),
(16, NULL, 'Michael Jordan Cruz', NULL, 'mich@g.com', '', 1, 3, 'default_avatar.png', 'claro', 'es', 0, NULL, NULL),
(17, NULL, 'Camilo', NULL, 'camilo@g.com', '$2y$10$C5MOvFA77zrRQz9IMR1Bv..iZMVmzobsnqz1u.ywBiDKvE8EvQVCi', 1, 3, 'default_avatar.png', 'claro', 'es', 0, NULL, NULL),
(18, NULL, 'Camila', NULL, 'camila@g.com', '$2y$10$xiSIbm7gVkloBJERPufmgeAGoavh5Q0vBIGwG9.X4T.TqYTRWuBNu', 1, 3, 'default_avatar.png', 'claro', 'es', 0, NULL, NULL),
(19, NULL, 'Fernando Castro Pinzon', NULL, 'ferdinan@g.com', '$2y$10$YAHpYmVFCl0wre6hKamhs.Ir5/SqtcNUfBuul/No289f/4UKzoFVC', 1, 3, 'default_avatar.png', 'claro', 'es', 0, NULL, NULL),
(21, NULL, 'Rafael Orozco', NULL, 'rafael@g.com', '$2y$10$534ZvxIYsYyJ2D.i7xUd1.M1dgU7faF24LI1lIwhj8QasRZypLyl6', 1, 3, 'default_avatar.png', 'claro', 'es', 0, NULL, NULL),
(22, NULL, 'Luis Miguel', NULL, 'luismi@g.com', '$2y$10$8HiIzSpkbPAVBfclTyACL.wyUgK.A.NvdyKvR5E.ns9sejfMoAA8q', 1, 3, 'default_avatar.png', 'claro', 'es', 0, NULL, NULL),
(23, NULL, 'Laura Muñoz', NULL, 'lauritagomezochoa@g.com', '$2y$10$mvWep4bDRpLcTEYfFIFM2.oRqJ.24TEwLgtKk5iV5I2OHTgkGq4xq', 1, 3, 'default_avatar.png', 'claro', 'es', 0, NULL, NULL),
(24, NULL, 'Rafael Dominguez', NULL, 'rafa@g.com', '$2y$10$rQzRhByDkGBr3K3S6gf4MuzQgsqW985z6h1yguZHXeodm7THBESaq', 1, 3, 'default_avatar.png', 'claro', 'es', 0, NULL, NULL),
(26, NULL, 'dana sara perez', NULL, 'dana_sara123@gmail.com', '$2y$10$vEZAxUJz4A3H1ounGeSnVukImYZaxHd6FTaGJfsdtkjEeHS7cjA8a', 1, 6, 'default_avatar.png', 'claro', 'es', 0, NULL, NULL),
(27, NULL, 'yulieth gomez', NULL, 'yuli.aa.gomez@gmsil.com', '$2y$10$i8rAos5Too9viPz8DOxI3OBIjCNOqqHT88T.mUUpHP8ENAFXcctU2', 1, 6, 'default_avatar.png', 'claro', 'es', 0, NULL, NULL),
(28, NULL, 'qasdsf', NULL, 'yuli.aa.gomez@gmail.com', '$2y$10$EFfRu1.iOeLkqxCl0/g3q.EYx8tX31TFxRjtQj9zWHnljusNxQw1G', 1, 6, 'default_avatar.png', 'claro', 'es', 0, NULL, NULL),
(29, NULL, 'noes', NULL, 'pylo@gmail.com', '$2y$10$NWneLtjhhaL4e96Mo0rWmeN4PqmeTgNfs1oM9c7T.3vPXOvWsngeG', 1, 6, 'default_avatar.png', 'claro', 'es', 0, NULL, NULL),
(30, NULL, 'yulieth gomez', NULL, 'i.aa.gomez@gmail.com', '$2y$10$0rcuXkMcvUajFBjgkphZeeCEBAggafd3IoM6QfV9LCd0wDMi5Ke2m', 1, 6, 'default_avatar.png', 'claro', 'es', 0, NULL, NULL),
(31, NULL, 'yulieth gome', NULL, 'aa.gomez@gmail.com', '$2y$10$0wRclmBPAly5kI/F85GOE.5zr4B/ycqI1S1kjFLBpaMWklkHQW3dK', 1, 6, 'default_avatar.png', 'claro', 'es', 0, NULL, NULL),
(32, NULL, 'papito3000', NULL, 'papicho@gmail.coom', '$2y$10$50Ou6sLfQjhv.kd/tm.NreDBq0fPyL9qR6aQFVzooP2ShNVIpUWAy', 1, 6, 'default_avatar.png', 'claro', 'es', 0, NULL, NULL),
(33, NULL, 'juandavid', NULL, 'juan_david@gmail.com', '$2y$10$ieQC3T6cWrRTjiu9U3y7D.HfKjxiKr9Ztn5r3hnjDXm7CgwZLtyT6', 1, 6, 'default_avatar.png', 'claro', 'es', 1, '2026-09-01 08:25:17', '::1'),
(34, NULL, 'David Gonzales Perez', NULL, 'david@gmail.com', '$2y$10$6kX4XO1HyyvzZW9Es0.4MeQzTMiiGmGXAlkYzpu9lnzctGRlo5QzO', 1, 6, 'default_avatar.png', 'claro', 'es', 1, '2026-09-30 16:39:53', '::1'),
(35, NULL, 'Diana', NULL, 'dianita@gmail.com', '$2y$10$VXV7sgD5k3ZkjXWn38sjB.m1of6gjSUd4hRIqYnL7D7cW/hCo5OuC', 1, 6, 'default_avatar.png', 'claro', 'es', 0, NULL, NULL),
(36, NULL, 'Luisa Peralta', NULL, 'luisita@gmail.com', '$2y$10$msEP/b0oSLZAdpP2Dt4Jg.4SuuOlorLuifJuwIruyTiyEETbkLjF6', 1, 6, 'default_avatar.png', 'claro', 'es', 1, '2026-10-05 19:07:51', '::1'),
(38, NULL, 'Camilo Cruz', NULL, 'camilo@gmail.com', '$2y$10$2nrC.5m2W4y2pjBTEDxi1eCRAiRVz4j4FcqiAIzOIooG5bPfr/8gu', 1, 6, 'default_avatar.png', 'claro', 'es', 1, '2026-10-07 21:22:25', '::1'),
(39, NULL, 'Enrique Hernan', NULL, 'enriquesito@gmail.com', '$2y$10$qf6C2o15SJb7grV5dElwGeeK.pIQncsZ.M.DAHlB63vcuR/WM0uOG', 1, 6, 'default_avatar.png', 'claro', 'es', 1, '2026-10-14 17:11:39', '::1');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `agenda_actividad`
--
ALTER TABLE `agenda_actividad`
  ADD PRIMARY KEY (`id_agenda`),
  ADD KEY `idx_agenda_reserva` (`cod_res_agenda`),
  ADD KEY `idx_agenda_usuario` (`id_usu_agenda`);

--
-- Indices de la tabla `detalle`
--
ALTER TABLE `detalle`
  ADD PRIMARY KEY (`cod_det`),
  ADD KEY `cod_res_det` (`cod_res_det`),
  ADD KEY `cod_hab_det` (`cod_hab_det`);

--
-- Indices de la tabla `experiencias`
--
ALTER TABLE `experiencias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `factura`
--
ALTER TABLE `factura`
  ADD PRIMARY KEY (`cod_fac`),
  ADD UNIQUE KEY `cod_res_fac` (`cod_res_fac`);

--
-- Indices de la tabla `habitacion`
--
ALTER TABLE `habitacion`
  ADD PRIMARY KEY (`cod_hab`),
  ADD UNIQUE KEY `num_hab` (`num_hab`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `cod_res_pago` (`cod_res_pago`),
  ADD UNIQUE KEY `uq_pagos_referencia` (`referencia_pago`);

--
-- Indices de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`),
  ADD UNIQUE KEY `uq_password_resets_token` (`token`);

--
-- Indices de la tabla `permiso`
--
ALTER TABLE `permiso`
  ADD PRIMARY KEY (`cod_permiso`);

--
-- Indices de la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`cod_res`),
  ADD KEY `id_usu_res` (`id_usu_res`);

--
-- Indices de la tabla `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`cod_rol`);

--
-- Indices de la tabla `rol_permiso`
--
ALTER TABLE `rol_permiso`
  ADD PRIMARY KEY (`cod_rol`,`cod_permiso`),
  ADD KEY `fk_rol_permiso_permiso` (`cod_permiso`);

--
-- Indices de la tabla `tarea`
--
ALTER TABLE `tarea`
  ADD PRIMARY KEY (`cod_tar`),
  ADD KEY `cod_usu_tar` (`cod_usu_tar`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usu`),
  ADD UNIQUE KEY `corr_usu` (`corr_usu`),
  ADD KEY `cod_rol_usu` (`cod_rol_usu`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `agenda_actividad`
--
ALTER TABLE `agenda_actividad`
  MODIFY `id_agenda` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `detalle`
--
ALTER TABLE `detalle`
  MODIFY `cod_det` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de la tabla `experiencias`
--
ALTER TABLE `experiencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `factura`
--
ALTER TABLE `factura`
  MODIFY `cod_fac` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `habitacion`
--
ALTER TABLE `habitacion`
  MODIFY `cod_hab` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id_pago` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `reservas`
--
ALTER TABLE `reservas`
  MODIFY `cod_res` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `cod_rol` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `tarea`
--
ALTER TABLE `tarea`
  MODIFY `cod_tar` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usu` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalle`
--
ALTER TABLE `detalle`
  ADD CONSTRAINT `detalle_ibfk_1` FOREIGN KEY (`cod_res_det`) REFERENCES `reservas` (`cod_res`),
  ADD CONSTRAINT `detalle_ibfk_2` FOREIGN KEY (`cod_hab_det`) REFERENCES `habitacion` (`cod_hab`),
  ADD CONSTRAINT `chk_detalle_noches` CHECK (`can_noc_det` >= 0);

--
-- Filtros para la tabla `factura`
--
ALTER TABLE `factura`
  ADD CONSTRAINT `factura_ibfk_1` FOREIGN KEY (`cod_res_fac`) REFERENCES `reservas` (`cod_res`),
  ADD CONSTRAINT `chk_factura_total` CHECK (`tot_fac` IS NULL OR `tot_fac` >= 0);

ALTER TABLE `habitacion`
  ADD CONSTRAINT `chk_habitacion_pre_hab` CHECK (`pre_hab` IS NULL OR `pre_hab` >= 0),
  ADD CONSTRAINT `chk_habitacion_precio_hab` CHECK (`precio_hab` IS NULL OR `precio_hab` >= 0);

ALTER TABLE `experiencias`
  ADD CONSTRAINT `chk_experiencia_precio_1` CHECK (`precio_opcion_1` IS NULL OR `precio_opcion_1` >= 0),
  ADD CONSTRAINT `chk_experiencia_precio_2` CHECK (`precio_opcion_2` IS NULL OR `precio_opcion_2` >= 0),
  ADD CONSTRAINT `chk_experiencia_precio_3` CHECK (`precio_opcion_3` IS NULL OR `precio_opcion_3` >= 0);

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`cod_res_pago`) REFERENCES `reservas` (`cod_res`) ON DELETE RESTRICT,
  ADD CONSTRAINT `chk_pago_monto` CHECK (`monto` >= 0);

--
-- Filtros para la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `reservas_ibfk_1` FOREIGN KEY (`id_usu_res`) REFERENCES `usuario` (`id_usu`) ON DELETE SET NULL,
  ADD CONSTRAINT `chk_reserva_fechas` CHECK (`fec_sal_res` > `fec_ent_res`);

-- Estas relaciones conservan la agenda si se elimina su usuario o reserva.
ALTER TABLE `agenda_actividad`
  ADD CONSTRAINT `agenda_ibfk_usuario` FOREIGN KEY (`id_usu_agenda`) REFERENCES `usuario` (`id_usu`) ON DELETE SET NULL,
  ADD CONSTRAINT `agenda_ibfk_reserva` FOREIGN KEY (`cod_res_agenda`) REFERENCES `reservas` (`cod_res`) ON DELETE SET NULL,
  ADD CONSTRAINT `chk_agenda_monto` CHECK (`monto_experiencia` IS NULL OR `monto_experiencia` >= 0),
  ADD CONSTRAINT `chk_agenda_fecha_hora` CHECK ((`fecha_agenda` IS NULL AND `hora_agenda` IS NULL) OR (`fecha_agenda` IS NOT NULL AND `hora_agenda` IS NOT NULL));

--
-- Filtros para la tabla `rol_permiso`
--
ALTER TABLE `rol_permiso`
  ADD CONSTRAINT `fk_rol_permiso_permiso` FOREIGN KEY (`cod_permiso`) REFERENCES `permiso` (`cod_permiso`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rol_permiso_rol` FOREIGN KEY (`cod_rol`) REFERENCES `rol` (`cod_rol`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tarea`
--
ALTER TABLE `tarea`
  ADD CONSTRAINT `tarea_ibfk_1` FOREIGN KEY (`cod_usu_tar`) REFERENCES `usuario` (`id_usu`) ON DELETE SET NULL;

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`cod_rol_usu`) REFERENCES `rol` (`cod_rol`) ON DELETE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;