-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 12-05-2026 a las 12:19:32
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `bdreservas`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bares`
--

CREATE TABLE `bares` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `bares`
--

INSERT INTO `bares` (`id`, `nombre`, `direccion`, `telefono`) VALUES
(1, 'Restaurante El Rincón', 'Calle Mayor, 12 - Madrid', '910000001'),
(2, 'La Tapería del Centro', 'Av. Andalucía, 45 - Madrid', '910000002'),
(3, 'Bar Marina', 'Paseo del Mar, 8 - Madrid', '910000003');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas`
--

CREATE TABLE `reservas` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_bar` int(11) NOT NULL,
  `num_personas` int(11) NOT NULL,
  `fecha_reserva` date NOT NULL,
  `hora_reserva` time NOT NULL,
  `bebes` tinyint(1) NOT NULL DEFAULT 0,
  `num_bebes` int(11) NOT NULL DEFAULT 0,
  `telefono` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `estado` enum('activa','cancelada') NOT NULL DEFAULT 'activa',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reservas`
--

INSERT INTO `reservas` (`id`, `id_usuario`, `id_bar`, `num_personas`, `fecha_reserva`, `hora_reserva`, `bebes`, `num_bebes`, `telefono`, `email`, `estado`, `fecha_creacion`) VALUES
(1, 2, 1, 10, '2026-05-01', '14:19:00', 0, 0, '695043220', 'miguel@gmail.com', 'cancelada', '2026-04-29 08:19:25'),
(2, 2, 1, 2, '2026-05-05', '12:19:00', 1, 3, '695043220', 'miguel@gmail.com', 'cancelada', '2026-04-29 08:19:57'),
(3, 2, 1, 2, '2026-04-30', '12:00:00', 1, 2, '66666666', 'miguel@gmail.com', 'activa', '2026-04-29 09:03:52'),
(4, 3, 1, 6, '2026-05-21', '09:39:00', 0, 0, '695043220', 'mgarciamarcos54@gmail.com', 'cancelada', '2026-05-07 07:36:32'),
(5, 3, 2, 6, '2026-05-21', '18:54:00', 1, 2, '695043220', 'mgarciamarcos54@gmail.com', 'activa', '2026-05-11 11:54:49'),
(6, 3, 2, 5, '2026-05-15', '22:07:00', 1, 1, '695043220', 'mgarciamarcos54@gmail.com', 'activa', '2026-05-11 12:08:13'),
(7, 3, 2, 4, '2026-05-27', '12:25:00', 0, 0, '666666666666', 'mgarciamarcos54@gmail.com', 'activa', '2026-05-12 07:26:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('usuario','admin','superadmin') NOT NULL DEFAULT 'usuario',
  `bar_asignado` int(11) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol`, `bar_asignado`, `fecha_registro`) VALUES
(1, 'Administrador Rincón', 'admin@rincon.com', '$2y$10$keyy9GsibgtekcyZpsueRe2QlA2FEp8dbg.yLuQRdRNlUmjil/OYm', 'admin', 1, '2026-04-29 08:11:04'),
(2, 'miguel', 'miguel@gmail.com', '$2y$10$eWy9ySD04HitnWL9BzJriuXB5v3.inWN.dUp64qK9zO7eCjgxxuXq', 'usuario', NULL, '2026-04-29 08:15:28'),
(3, 'meme', 'mgarciamarcos54@gmail.com', '$2y$10$ju9y3cCz/MCzyfzL8WC7NuyeYjOwVJGc7vKgLVZcVUHdmNbdMqiv2', 'usuario', NULL, '2026-05-07 07:36:01'),
(5, 'adminCentroTaperia', 'admin@centro.com', '$2y$10$TMdlZPD1kRV7kyb.gXhNtOf1p6gFEZ2bwS1SGAhH2wNw55KSeIsda', 'admin', 2, '2026-05-11 11:52:04'),
(6, 'superadmin', 'super@admin.com', '$2y$10$5Bdq035lMtht2W0B0gDrPuN1OJAM3tXQHN8QaM9pIi9AJukPeSK.S', 'superadmin', NULL, '2026-05-12 07:04:14');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bares`
--
ALTER TABLE `bares`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_bar` (`id_bar`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `bar_asignado` (`bar_asignado`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `bares`
--
ALTER TABLE `bares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `reservas`
--
ALTER TABLE `reservas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `reservas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservas_ibfk_2` FOREIGN KEY (`id_bar`) REFERENCES `bares` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`bar_asignado`) REFERENCES `bares` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
