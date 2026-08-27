-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 24-04-2026 a las 20:32:51
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12



-- ESTA CARPETA ES LA BASE DE DATOS DEL PROYECTO - ELIMINENLA CUANDO YA TENGA LA BASE DE DATOS EN SU COMPUTADORA



SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
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
(5, 0, 6, 10),
(7, 0, 8, 18),
(9, 0, 10, 24),
(10, 0, 11, 30);

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
  `pre_hab` decimal(10,2) DEFAULT NULL,
  `est_hab` varchar(20) DEFAULT 'Disponible',
  `precio_hab` decimal(10,2) DEFAULT 0.00,
  `obs_hab` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `habitacion`
--

INSERT INTO `habitacion` (`cod_hab`, `num_hab`, `tipo_hab`, `pre_hab`, `est_hab`, `precio_hab`, `obs_hab`) VALUES
(1, 101, 'Sencilla', 50000.00, 'Mantenimiento', 0.00, NULL),
(2, 201, 'Doble', 85000.00, 'Disponible', 0.00, NULL),
(3, 102, 'Sencilla', 50000.00, 'Disponible', 0.00, NULL),
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
(22, 302, 'Sencilla', 50000.00, 'Disponible', 0.00, NULL),
(23, 303, 'Doble', 85000.00, 'Disponible', 0.00, NULL),
(24, 304, 'Doble', 85000.00, 'Disponible', 0.00, NULL),
(25, 305, 'Suite', 150000.00, 'Disponible', 0.00, NULL),
(26, 306, 'Suite', 150000.00, 'Disponible', 0.00, NULL),
(27, 307, 'Sencilla', 50000.00, 'Disponible', 0.00, NULL),
(28, 308, 'Sencilla', 50000.00, 'Disponible', 0.00, NULL),
(29, 309, 'Doble', 85000.00, 'Sucia', 0.00, NULL),
(30, 310, 'Doble', 85000.00, 'Disponible', 0.00, NULL),
(31, 401, 'Sencilla', 50000.00, 'Disponible', 0.00, NULL),
(32, 402, 'Sencilla', 50000.00, 'Disponible', 0.00, NULL),
(33, 403, 'Doble', 85000.00, 'Disponible', 0.00, NULL),
(34, 404, 'Doble', 85000.00, 'Disponible', 0.00, NULL),
(35, 405, 'Suite Premium', 200000.00, 'Disponible', 0.00, NULL),
(36, 406, 'Suite Premium', 200000.00, 'Disponible', 0.00, NULL),
(37, 407, 'Sencilla', 50000.00, 'Ocupada', 0.00, NULL),
(38, 408, 'Sencilla', 50000.00, 'Sucia', 0.00, NULL),
(39, 409, 'Doble', 85000.00, 'Mantenimiento', 0.00, NULL),
(40, 410, 'Doble', 85000.00, 'Disponible', 0.00, NULL);

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
(11, '2026-04-24 06:49:01', '2026-05-01 00:00:00', '2026-05-08 00:00:00', 'Confirmada', '- Canasta de champañas, para las noches', 22);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

CREATE TABLE `rol` (
  `cod_rol` bigint(20) NOT NULL,
  `des_rol` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rol`
--

INSERT INTO `rol` (`cod_rol`, `des_rol`) VALUES
(1, 'Gerente General'),
(2, 'Gestor de Ingresos'),
(3, 'Recepcionista'),
(4, 'Conserje'),
(5, 'Personal de Limpieza'),
(6, 'Cliente');

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
(27, 'Trapear', 'URGENTE', 'Media', 'gfgfh', 'Pendiente', '2026-04-24 15:32:27', 2);

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
  `idioma_usu` enum('es','en') DEFAULT 'es'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id_usu`, `doc_usu`, `nom_usu`, `tel_usu`, `corr_usu`, `psw_usu`, `est_usu`, `cod_rol_usu`, `foto_usu`, `tema_usu`, `idioma_usu`) VALUES
(1, NULL, 'Steven', NULL, 'gerente@hotel.com', '$2y$10$RblI4EQmg67F15qPDHF9Q.wJxoWvXd6/lDVOEjb5oj.G1bOvBXg2a', 1, 1, 'default_avatar.png', 'claro', 'es'),
(2, NULL, 'Yulli Recepcionista', NULL, 'yulli@hotel.com', '$2y$10$atnAuiX6XjARmfTHLF1yGuSAcjbhkQ//nRu7G2oN3.tL8UjGYT/1u', 1, 3, 'default_avatar.png', 'claro', 'es'),
(3, NULL, 'Samuel Conserje', NULL, 'samuel@hotel.com', '$2y$10$atnAuiX6XjARmfTHLF1yGuSAcjbhkQ//nRu7G2oN3.tL8UjGYT/1u', 1, 4, 'default_avatar.png', 'claro', 'es'),
(4, NULL, 'Samantha Limpieza', NULL, 'samantha@hotel.com', '$2y$10$atnAuiX6XjARmfTHLF1yGuSAcjbhkQ//nRu7G2oN3.tL8UjGYT/1u', 1, 5, 'default_avatar.png', 'claro', 'es'),
(7, NULL, 'Santiago Melo', NULL, 'gestorIngresos@hotel.com', '$2y$10$RblI4EQmg67F15qPDHF9Q.wJxoWvXd6/lDVOEjb5oj.G1bOvBXg2a', 1, 2, 'default_avatar.png', 'claro', 'es'),
(10, NULL, 'Will Smith', NULL, 'will@actor.com', '', 1, 3, 'default_avatar.png', 'claro', 'es'),
(14, NULL, 'Adam Samdler', NULL, 'adam@g.com', '', 1, 3, 'default_avatar.png', 'claro', 'es'),
(16, NULL, 'Michael Jordan Cruz', NULL, 'mich@g.com', '', 1, 3, 'default_avatar.png', 'claro', 'es'),
(17, NULL, 'Camilo', NULL, 'camilo@g.com', '$2y$10$C5MOvFA77zrRQz9IMR1Bv..iZMVmzobsnqz1u.ywBiDKvE8EvQVCi', 1, 3, 'default_avatar.png', 'claro', 'es'),
(18, NULL, 'Camila', NULL, 'camila@g.com', '$2y$10$xiSIbm7gVkloBJERPufmgeAGoavh5Q0vBIGwG9.X4T.TqYTRWuBNu', 1, 3, 'default_avatar.png', 'claro', 'es'),
(19, NULL, 'Fernando Castro Pinzon', NULL, 'ferdinan@g.com', '$2y$10$YAHpYmVFCl0wre6hKamhs.Ir5/SqtcNUfBuul/No289f/4UKzoFVC', 1, 3, 'default_avatar.png', 'claro', 'es'),
(21, NULL, 'Rafael Orozco', NULL, 'rafael@g.com', '$2y$10$534ZvxIYsYyJ2D.i7xUd1.M1dgU7faF24LI1lIwhj8QasRZypLyl6', 1, 3, 'default_avatar.png', 'claro', 'es'),
(22, NULL, 'Luis Miguel', NULL, 'luismi@g.com', '$2y$10$8HiIzSpkbPAVBfclTyACL.wyUgK.A.NvdyKvR5E.ns9sejfMoAA8q', 1, 3, 'default_avatar.png', 'claro', 'es'),
(23, NULL, 'Laura Muñoz', NULL, 'lauritagomezochoa@g.com', '$2y$10$mvWep4bDRpLcTEYfFIFM2.oRqJ.24TEwLgtKk5iV5I2OHTgkGq4xq', 1, 3, 'default_avatar.png', 'claro', 'es'),
(24, NULL, 'Rafael Dominguez', NULL, 'rafa@g.com', '$2y$10$rQzRhByDkGBr3K3S6gf4MuzQgsqW985z6h1yguZHXeodm7THBESaq', 1, 3, 'default_avatar.png', 'claro', 'es');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `detalle`
--
ALTER TABLE `detalle`
  ADD PRIMARY KEY (`cod_det`),
  ADD KEY `cod_res_det` (`cod_res_det`),
  ADD KEY `cod_hab_det` (`cod_hab_det`);

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
-- AUTO_INCREMENT de la tabla `detalle`
--
ALTER TABLE `detalle`
  MODIFY `cod_det` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
-- AUTO_INCREMENT de la tabla `reservas`
--
ALTER TABLE `reservas`
  MODIFY `cod_res` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `cod_rol` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `tarea`
--
ALTER TABLE `tarea`
  MODIFY `cod_tar` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usu` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalle`
--
ALTER TABLE `detalle`
  ADD CONSTRAINT `detalle_ibfk_1` FOREIGN KEY (`cod_res_det`) REFERENCES `reservas` (`cod_res`),
  ADD CONSTRAINT `detalle_ibfk_2` FOREIGN KEY (`cod_hab_det`) REFERENCES `habitacion` (`cod_hab`);

--
-- Filtros para la tabla `factura`
--
ALTER TABLE `factura`
  ADD CONSTRAINT `factura_ibfk_1` FOREIGN KEY (`cod_res_fac`) REFERENCES `reservas` (`cod_res`);

--
-- Filtros para la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `reservas_ibfk_1` FOREIGN KEY (`id_usu_res`) REFERENCES `usuario` (`id_usu`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tarea`
--
ALTER TABLE `tarea`
  ADD CONSTRAINT `tarea_ibfk_1` FOREIGN KEY (`cod_usu_tar`) REFERENCES `usuario` (`id_usu`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`cod_rol_usu`) REFERENCES `rol` (`cod_rol`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;