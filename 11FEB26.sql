-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 12-02-2026 a las 02:21:03
-- Versión del servidor: 11.8.3-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u415354546_kiosco`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `afip_config`
--

CREATE TABLE `afip_config` (
  `id` int(11) NOT NULL,
  `cuit` varchar(20) NOT NULL,
  `punto_venta` int(5) NOT NULL,
  `certificado_crt` varchar(255) DEFAULT NULL,
  `clave_key` varchar(255) DEFAULT NULL,
  `modo` enum('homologacion','produccion') DEFAULT 'homologacion',
  `token` text DEFAULT NULL,
  `sign` text DEFAULT NULL,
  `fecha_vencimiento_token` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Volcado de datos para la tabla `afip_config`
--

INSERT INTO `afip_config` (`id`, `cuit`, `punto_venta`, `certificado_crt`, `clave_key`, `modo`, `token`, `sign`, `fecha_vencimiento_token`) VALUES
(1, '20000000000', 1, NULL, NULL, 'homologacion', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencia`
--

CREATE TABLE `asistencia` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `ingreso` datetime DEFAULT current_timestamp(),
  `egreso` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `asistencia`
--

INSERT INTO `asistencia` (`id`, `id_usuario`, `ingreso`, `egreso`) VALUES
(1, 1, '2026-02-11 18:08:13', NULL),
(2, 1, '2026-02-12 02:19:55', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria`
--

CREATE TABLE `auditoria` (
  `id` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `id_usuario` int(11) DEFAULT NULL,
  `accion` varchar(255) NOT NULL,
  `detalles` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `auditoria`
--

INSERT INTO `auditoria` (`id`, `fecha`, `id_usuario`, `accion`, `detalles`) VALUES
(1, '2026-01-29 20:52:33', 1, 'MODIF_PRODUCTO', 'Modificación Producto \'Camel Box 20\': Precio: $3200.00 -> $5200.00'),
(2, '2026-01-29 20:52:48', 1, 'ELIMINAR_PRODUCTO', 'Eliminado: Chesterfield Box 20 (Cod: 7790000000004)'),
(3, '2026-01-29 17:58:09', 1, 'Nueva Venta', 'Venta #4 | Total: $600 | Metodo: Efectivo'),
(4, '2026-02-03 19:19:48', 2, 'Nueva Venta', 'Venta #5 | Total: $11500 | Metodo: Efectivo'),
(5, '2026-02-03 19:21:15', 1, 'Nueva Venta', 'Venta #6 | Total: $300 | Metodo: Efectivo'),
(6, '2026-02-05 17:47:52', 1, 'VENTA_REALIZADA', 'Venta #11 | Total: $600 | Cliente ID: 1'),
(7, '2026-02-05 17:48:19', 1, 'VENTA_REALIZADA', 'Venta #12 | Total: $2900 | Cliente ID: 5'),
(8, '2026-02-05 17:49:19', 1, 'VENTA_REALIZADA', 'Venta #13 | Total: $12500 | Cliente ID: 3'),
(9, '2026-02-05 17:49:29', 1, 'VENTA_REALIZADA', 'Venta #14 | Total: $3200 | Cliente ID: 1'),
(10, '2026-02-05 17:49:36', 1, 'VENTA_REALIZADA', 'Venta #15 | Total: $2600 | Cliente ID: 1'),
(11, '2026-02-05 17:50:19', 1, 'VENTA_REALIZADA', 'Venta #16 | Total: $11900 | Cliente ID: 1'),
(12, '2026-02-05 19:38:14', 1, 'VENTA_REALIZADA', 'Venta #17 | Total: $2600 | Cliente ID: 1'),
(13, '2026-02-05 20:05:46', 1, 'VENTA_REALIZADA', 'Venta #18 | Total: $15200 | Cliente ID: 6'),
(14, '2026-02-05 22:34:53', 1, 'VENTA_REALIZADA', 'Venta #19 | Total: $14300 | Cliente ID: 3'),
(15, '2026-02-05 23:14:42', 1, 'VENTA_REALIZADA', 'Venta #20 | Total: $3533 | Cliente: 3 | Puntos Usados: $67'),
(16, '2026-02-05 23:19:32', 1, 'VENTA_REALIZADA', 'Venta #21 | Total: $14692 | Cliente: 3 | Puntos Usados: $8'),
(17, '2026-02-05 23:21:25', 1, 'VENTA_REALIZADA', 'Venta #22 | Total: $12100 | Cliente: 1'),
(18, '2026-02-05 23:23:40', 1, 'VENTA_REALIZADA', 'Venta #23 | Total: $400 | Cliente: 1'),
(19, '2026-02-05 23:28:38', 1, 'VENTA_REALIZADA', 'Venta #24 | Total: $11500 | Cliente: 1'),
(20, '2026-02-05 23:30:50', 1, 'VENTA_REALIZADA', 'Venta #25 | Total: $2900 | Cliente: 1'),
(21, '2026-02-05 23:33:52', 1, 'VENTA_REALIZADA', 'Venta #26 | Total: $300 | Cliente: 1'),
(22, '2026-02-06 10:47:58', 1, 'VENTA_REALIZADA', 'Venta #27 | Total: $400 | Cliente: 1'),
(23, '2026-02-06 16:04:03', 1, 'CANJE', 'Canje Producto: COCA COLA Y UNA BIRRA (-15 pts)'),
(24, '2026-02-08 17:49:45', 1, 'BAJA_PRODUCTO', 'Archivado/Eliminado: Fede'),
(25, '2026-02-08 17:49:56', 1, 'BAJA_PRODUCTO', 'Archivado/Eliminado: Fede'),
(26, '2026-02-08 18:02:48', 1, 'BAJA_PRODUCTO', 'Archivado/Eliminado: Fede'),
(27, '2026-02-08 18:03:04', 1, 'BAJA_PRODUCTO', 'Archivado/Eliminado: Fede'),
(28, '2026-02-08 18:08:50', 1, 'ELIMINAR_FISICO', 'Producto eliminado ID: 22'),
(29, '2026-02-08 16:22:45', 1, 'VENTA_REALIZADA', 'Venta #30 | Total: $300 | Cliente ID: 1'),
(30, '2026-02-08 16:23:00', 1, 'VENTA_REALIZADA', 'Venta #31 | Total: $13000 | Cliente ID: 1'),
(31, '2026-02-08 16:32:20', 1, 'VENTA_REALIZADA', 'Venta #32 | Total: $13000 | Cliente ID: 1'),
(32, '2026-02-08 16:38:12', 1, 'VENTA_REALIZADA', 'Venta #33 | Total: $13000 | Cliente ID: 1'),
(33, '2026-02-08 16:45:43', 1, 'VENTA_REALIZADA', 'Venta #34 | Total: $13000 | Cliente ID: 1'),
(34, '2026-02-08 16:56:14', 1, 'VENTA_REALIZADA', 'Venta #35 | Total: $13000 | Cliente ID: 1'),
(35, '2026-02-08 17:15:42', 1, 'VENTA_REALIZADA', 'Venta #36 | Total: $13000 | Cliente ID: 1'),
(36, '2026-02-08 17:37:50', 1, 'VENTA_REALIZADA', 'Venta #37 | Total: $13000 | Cliente ID: 1'),
(37, '2026-02-08 18:04:49', 1, 'VENTA_REALIZADA', 'Venta #38 | Total: $13000 | Cliente ID: 1'),
(38, '2026-02-08 18:12:24', 1, 'VENTA_REALIZADA', 'Venta #39 | Total: $13000 | Cliente ID: 1'),
(39, '2026-02-08 21:18:21', 1, 'ELIMINAR_FISICO', 'Producto eliminado ID: 26'),
(40, '2026-02-08 18:18:55', 1, 'VENTA_REALIZADA', 'Venta #41 | Total: $800 | Cliente ID: 1'),
(41, '2026-02-08 18:23:51', 1, 'VENTA_REALIZADA', 'Venta #43 | Total: $11500 | Cliente ID: 1'),
(42, '2026-02-08 18:26:38', 1, 'VENTA_REALIZADA', 'Venta #44 | Total: $11000 | Cliente ID: 1'),
(43, '2026-02-08 21:29:32', 1, 'ELIMINAR_FISICO', 'Producto eliminado ID: 27'),
(44, '2026-02-08 18:30:51', 1, 'VENTA_REALIZADA', 'Venta #45 | Total: $15000 | Cliente ID: 1'),
(45, '2026-02-08 18:31:14', 1, 'VENTA_REALIZADA', 'Venta #46 | Total: $15000 | Cliente ID: 1'),
(46, '2026-02-08 18:43:07', 1, 'VENTA_REALIZADA', 'Venta #47 | Total: $20000 | Cliente ID: 1'),
(47, '2026-02-08 18:44:48', 1, 'VENTA_REALIZADA', 'Venta #48 | Total: $20000 | Cliente ID: 1'),
(48, '2026-02-08 18:46:14', 1, 'VENTA_REALIZADA', 'Venta #49 | Total: $20000 | Cliente ID: 1'),
(49, '2026-02-08 18:46:58', 1, 'VENTA_REALIZADA', 'Venta #50 | Total: $20000 | Cliente ID: 1'),
(50, '2026-02-08 18:52:14', 1, 'VENTA_REALIZADA', 'Venta #51 | Total: $20000'),
(51, '2026-02-08 18:55:28', 1, 'VENTA_REALIZADA', 'Venta #52 | Total: $20000 | Cliente ID: 1'),
(52, '2026-02-08 18:57:03', 1, 'VENTA_REALIZADA', 'Venta #53 | Total: $12333 | Cliente ID: 1'),
(53, '2026-02-08 18:57:26', 1, 'VENTA_REALIZADA', 'Venta #54 | Total: $11500 | Cliente ID: 1'),
(54, '2026-02-08 19:59:58', 1, 'VENTA_REALIZADA', 'Venta #55 | Total: $12333 | Cliente ID: 1'),
(55, '2026-02-08 20:00:16', 1, 'VENTA_REALIZADA', 'Venta #56 | Total: $12333 | Cliente ID: 1'),
(56, '2026-02-08 20:08:48', 1, 'VENTA_REALIZADA', 'Venta #57 | Total: $25000 | Cliente ID: 1'),
(57, '2026-02-08 23:09:39', 1, 'ELIMINAR_FISICO', 'Producto eliminado ID: 30'),
(58, '2026-02-08 20:23:43', 1, 'VENTA_REALIZADA', 'Venta #58 | Total: $25000 | Cliente ID: 1'),
(59, '2026-02-08 23:30:24', 1, 'ELIMINAR_FISICO', 'Producto eliminado ID: 29'),
(60, '2026-02-08 23:30:50', 1, 'ELIMINAR_FISICO', 'Producto eliminado ID: 31'),
(61, '2026-02-08 23:31:31', 1, 'ELIMINAR_FISICO', 'Producto eliminado ID: 28'),
(62, '2026-02-08 23:37:22', 1, 'ELIMINAR_FISICO', 'Producto eliminado ID: 33'),
(63, '2026-02-08 23:52:02', 1, 'ELIMINAR_FISICO', 'Producto eliminado ID: 35'),
(64, '2026-02-08 23:52:05', 1, 'ELIMINAR_FISICO', 'Producto eliminado ID: 34'),
(65, '2026-02-08 23:52:36', 1, 'ELIMINAR_FISICO', 'Producto eliminado ID: 36'),
(66, '2026-02-08 21:01:05', 1, 'VENTA_REALIZADA', 'Venta #59 | Total: $12314 | Cliente ID: 1'),
(67, '2026-02-08 21:22:59', 1, 'VENTA_REALIZADA', 'Venta #60 | Total: $12314 | Cliente ID: 1'),
(68, '2026-02-08 22:00:46', 1, 'VENTA_REALIZADA', 'Venta #61 | Total: $11000 | Cliente ID: 1'),
(69, '2026-02-09 01:26:00', 1, 'ELIMINAR_FISICO', 'Producto eliminado ID: 38'),
(70, '2026-02-09 02:30:36', 1, 'ELIMINAR_FISICO', 'Producto eliminado ID: 40'),
(71, '2026-02-09 02:33:13', 1, 'ELIMINAR_FISICO', 'Producto eliminado ID: 42'),
(72, '2026-02-09 02:52:28', 1, 'ELIMINAR_FISICO', 'Producto eliminado ID: 43'),
(73, '2026-02-08 23:53:00', 1, 'VENTA_REALIZADA', 'Venta #62 | Total: $4700 | Cliente ID: 1 | Desc.Manual: $10000'),
(74, '2026-02-09 03:16:36', 1, 'ELIMINAR_FISICO', 'Producto eliminado ID: 44'),
(75, '2026-02-09 09:04:04', 1, 'VENTA_REALIZADA', 'Venta #63 | Total: $5200 | Cliente ID: 1'),
(76, '2026-02-09 11:46:19', 1, 'VENTA_REALIZADA', 'Venta #64 | Total: $1100 | Cliente ID: 1'),
(77, '2026-02-09 13:26:23', 1, 'VENTA_REALIZADA', 'Venta #65 | Total: $11500 | Cliente ID: 3'),
(78, '2026-02-09 14:24:45', 1, 'VENTA_REALIZADA', 'Venta #66 | Total: $1000 | Cliente ID: 1'),
(79, '2026-02-09 14:40:57', 1, 'VENTA_REALIZADA', 'Venta #67 | Total: $3800 | Cliente ID: 3'),
(80, '2026-02-09 21:03:38', 1, 'INFLACION', 'Aumento Masivo del 5% en costo a 20 productos.'),
(81, '2026-02-09 22:32:40', 1, 'VENTA_REALIZADA', 'Venta #68 | Total: $630 | Cliente ID: 5'),
(82, '2026-02-09 22:33:12', 1, 'VENTA_REALIZADA', 'Venta #69 | Total: $630 | Cliente ID: 1'),
(83, '2026-02-10 07:55:28', 1, 'VENTA_REALIZADA', 'Venta #70 | Total: $630 | Cliente ID: 1'),
(84, '2026-02-10 10:18:40', 1, 'VENTA_REALIZADA', 'Venta #71 | Total: $630 | Cliente ID: 1'),
(85, '2026-02-10 10:38:53', 1, 'VENTA_REALIZADA', 'Venta #72 | Total: $12620.6 | Cliente ID: 5 | Desc.Manual: $1000'),
(86, '2026-02-10 10:43:58', 1, 'VENTA_REALIZADA', 'Venta #73 | Total: $14808.400000000001 | Cliente ID: 3 | Desc.Manual: $2000'),
(87, '2026-02-10 10:52:06', 1, 'VENTA_REALIZADA', 'Venta #74 | Total: $2100 | Cliente ID: 3'),
(88, '2026-02-10 10:53:21', 1, 'VENTA_REALIZADA', 'Venta #75 | Total: $1100 | Cliente ID: 3 | Desc.Manual: $1000'),
(89, '2026-02-10 10:54:04', 1, 'VENTA_REALIZADA', 'Venta #76 | Total: $1100 | Cliente ID: 3 | Desc.Manual: $1000'),
(90, '2026-02-10 21:56:16', 1, 'VENTA_REALIZADA', 'Venta #77 | Total: $12705 | Cliente ID: 5'),
(91, '2026-02-11 09:19:56', 1, 'VENTA_REALIZADA', 'Venta #78 | Total: $869.4 | Cliente ID: 3'),
(92, '2026-02-11 09:20:09', 1, 'VENTA_REALIZADA', 'Venta #79 | Total: $19215 | Cliente ID: 1'),
(93, '2026-02-11 09:51:42', 1, 'VENTA_REALIZADA', 'Venta #80 | Total: $15677.8 | Cliente ID: 1 | Desc.Manual: $2000'),
(94, '2026-02-11 09:52:24', 1, 'VENTA_REALIZADA', 'Venta #81 | Total: $23786.8 | Cliente ID: 1 | Desc.Manual: $5000'),
(95, '2026-02-11 14:47:36', 1, 'VENTA_REALIZADA', 'Venta #82 | Total: $0 | Cliente ID: 6'),
(96, '2026-02-11 14:48:23', 1, 'VENTA_REALIZADA', 'Venta #83 | Total: $0 | Cliente ID: 3'),
(97, '2026-02-11 14:49:59', 1, 'VENTA_REALIZADA', 'Venta #84 | Total: $0 | Cliente ID: 5'),
(98, '2026-02-11 14:51:55', 1, 'VENTA_REALIZADA', 'Venta #85 | Total: $0 | Cliente ID: 1'),
(99, '2026-02-11 14:52:12', 1, 'VENTA_REALIZADA', 'Venta #86 | Total: $0 | Cliente ID: 1'),
(100, '2026-02-11 14:52:36', 1, 'VENTA_REALIZADA', 'Venta #87 | Total: $0 | Cliente ID: 1'),
(101, '2026-02-11 14:53:53', 1, 'VENTA_REALIZADA', 'Venta #88 | Total: $1050 | Cliente ID: 1'),
(102, '2026-02-11 18:10:10', 1, 'CANJE', 'Canje Cupón $0.00 (-20 pts)'),
(103, '2026-02-11 18:40:58', 1, 'CANJE', 'Canje Cupón $0.00 (-20 pts)'),
(104, '2026-02-11 18:41:33', 1, 'CANJE', 'Canje Cupón $0.00 (-20 pts)'),
(105, '2026-02-11 18:46:41', 1, 'CANJE', 'Canje Cupón $0.00 (-10 pts)'),
(106, '2026-02-11 18:54:40', 1, 'CANJE', 'Canje Cupón $0.00 (-10 pts)'),
(107, '2026-02-11 19:03:52', 1, 'CANJE', 'Canje Cupón $0.00 (-10 pts)'),
(108, '2026-02-11 19:10:16', 1, 'CANJE', 'Canje Cupón $0.00 (-10 pts)'),
(109, '2026-02-11 19:10:45', 1, 'CANJE', 'Canje Cupón $0.00 (-10 pts)'),
(110, '2026-02-11 19:13:26', 1, 'CANJE', 'Canje Cupón $0.00 (-10 pts)'),
(111, '2026-02-11 19:18:04', 1, 'CANJE', 'Canje Cupón $0.00 (-1 pts)'),
(112, '2026-02-11 19:29:52', 1, 'CANJE', 'Canje Cupón $0.00 (-1 pts)'),
(113, '2026-02-11 19:34:09', 1, 'CANJE', 'Canje Cupón $0.00 (-1 pts)'),
(114, '2026-02-11 19:52:38', 1, 'CANJE', 'Canje Producto: fede (-1 pts)');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bienes_uso`
--

CREATE TABLE `bienes_uso` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `numero_serie` varchar(100) DEFAULT NULL,
  `fecha_compra` date DEFAULT NULL,
  `costo_compra` decimal(12,2) DEFAULT NULL,
  `estado` enum('nuevo','bueno','mantenimiento','roto','baja') DEFAULT 'bueno',
  `ubicacion` varchar(100) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `bienes_uso`
--

INSERT INTO `bienes_uso` (`id`, `nombre`, `marca`, `modelo`, `numero_serie`, `fecha_compra`, `costo_compra`, `estado`, `ubicacion`, `notas`, `foto`) VALUES
(1, 'Aire Acondicionado', 'philips', 'asdsam', '23432423', '2026-01-27', 50000.00, 'nuevo', 'Interior Bodega Central', '', 'uploads/activo_1769643857_249.jpg');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cajas_sesion`
--

CREATE TABLE `cajas_sesion` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha_apertura` datetime DEFAULT current_timestamp(),
  `fecha_cierre` datetime DEFAULT NULL,
  `monto_inicial` decimal(12,2) NOT NULL,
  `monto_final` decimal(12,2) DEFAULT NULL,
  `total_ventas` decimal(12,2) DEFAULT 0.00,
  `diferencia` decimal(12,2) DEFAULT NULL,
  `estado` enum('abierta','cerrada') DEFAULT 'abierta'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cajas_sesion`
--

INSERT INTO `cajas_sesion` (`id`, `id_usuario`, `fecha_apertura`, `fecha_cierre`, `monto_inicial`, `monto_final`, `total_ventas`, `diferencia`, `estado`) VALUES
(1, 1, '2026-01-28 22:25:26', '2026-01-28 22:26:07', 1000.00, 1000.00, 600.00, 0.00, 'cerrada'),
(2, 1, '2026-01-28 22:26:42', '2026-01-29 06:14:22', 1000.00, 500.00, 600.00, -500.00, 'cerrada'),
(3, 2, '2026-01-28 22:37:30', '2026-01-28 22:37:51', 0.00, 550.00, 600.00, -50.00, 'cerrada'),
(4, 1, '2026-01-29 17:48:54', '2026-02-03 19:20:54', 1000.00, 20000.00, 600.00, 18400.00, 'cerrada'),
(5, 2, '2026-02-03 19:19:43', NULL, 2000.00, NULL, 0.00, NULL, 'abierta'),
(6, 1, '2026-02-03 19:20:59', '2026-02-06 09:06:11', 1000.00, 0.00, 99025.00, -100025.00, 'cerrada'),
(7, 1, '2026-02-06 10:47:56', '2026-02-09 11:49:54', 1000.00, 100000.00, 424827.00, -320127.00, 'cerrada'),
(8, 1, '2026-02-09 11:50:59', '2026-02-10 10:04:14', 2000.00, 0.00, 18190.00, -6490.00, 'cerrada'),
(9, 1, '2026-02-10 10:18:34', '2026-02-11 09:18:10', 2000.00, 22220000.00, 17635.00, 22200365.00, 'cerrada'),
(11, 1, '2026-02-11 09:18:22', '2026-02-11 09:21:49', 20000.00, 15020.00, 20084.40, -25064.40, 'cerrada'),
(12, 1, '2026-02-11 09:51:35', NULL, 1000.00, NULL, 0.00, NULL, 'abierta');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `icono_web` varchar(50) DEFAULT 'box',
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `icono_web`, `activo`) VALUES
(1, 'Bebidas', 'box', 1),
(2, 'Kiosco / Golosinas', 'box', 1),
(3, 'Almacén', 'box', 1),
(4, 'Cigarrillos', 'box', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `dni_cuit` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT 'default_user.png',
  `fecha_nacimiento` date DEFAULT NULL,
  `recibir_notificaciones` tinyint(1) DEFAULT 1,
  `limite_credito` decimal(12,2) DEFAULT 0.00,
  `saldo_deudor` decimal(12,2) DEFAULT 0.00,
  `puntos_acumulados` int(11) DEFAULT 0,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `whatsapp` varchar(50) DEFAULT NULL,
  `dni` varchar(20) DEFAULT NULL,
  `usuario` varchar(50) DEFAULT NULL,
  `saldo_actual` decimal(10,2) DEFAULT 0.00,
  `saldo_favor` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombre`, `dni_cuit`, `email`, `password`, `telefono`, `direccion`, `foto_perfil`, `fecha_nacimiento`, `recibir_notificaciones`, `limite_credito`, `saldo_deudor`, `puntos_acumulados`, `fecha_registro`, `whatsapp`, `dni`, `usuario`, `saldo_actual`, `saldo_favor`) VALUES
(1, 'Consumidor Final', '00000000', NULL, NULL, '', NULL, 'default_user.png', '2026-02-11', 1, 0.00, 0.00, 0, '2026-01-26 11:15:20', NULL, '', NULL, 0.00, 0.00),
(3, 'Juan Perez', NULL, 'gonzalezmarcelo159@gmail.com', '$2y$10$IlXiIWtc20a46LntcZ9T/Ovr3m9nkPRgiRN44/.SE3ey8CHUVRRRy', NULL, 'Teniente Primero Bustos Manuel Oscar, 370 Viviendas III Etapa, Alto Comedero, Municipio de San Salvador de Jujuy, Departamento Doctor Manuel Belgrano, Jujuy, Y4600AXX, Argentina', 'default_user.png', NULL, 1, 0.00, 0.00, 26, '2026-01-26 16:43:33', '+5491166116861', '35911753', NULL, 0.00, 0.00),
(5, 'Federico', '24651315', NULL, NULL, '', 'Alto Comedero', 'default_user.png', NULL, 1, 10000.00, 0.00, 20, '2026-01-28 15:04:43', '1166116861', '', NULL, 0.00, 0.00),
(6, 'Prueba Registro', '35975342', NULL, NULL, NULL, 'Altus', 'default_user.png', NULL, 1, 0.00, 0.00, 18, '2026-01-29 20:59:30', '1166116861', NULL, NULL, 0.00, 0.00),
(7, 'Soy nuevo con puntos', NULL, NULL, '$2y$10$R6Iz8tpZEx3wk2h/df3omOW4GUsPLH3lv5o2fYJmL.eos.oqKPeDK', '1166116861', NULL, 'default_user.png', NULL, 1, 0.00, 0.00, 0, '2026-02-05 23:06:33', NULL, '35911754', NULL, 0.00, 0.00),
(8, 'Servicio', NULL, NULL, '$2y$10$qt6V0Hoo7u/Iy9mAral51O6kEmrh.fuD6wDdIAfAeg2JqdRgXpNMy', '111111112', NULL, 'uploads/perfil_8_1770334480.png', NULL, 1, 0.00, 0.00, 0, '2026-02-05 23:25:37', NULL, '111111111', NULL, 0.00, 0.00),
(9, 'Laura', NULL, NULL, '$2y$10$UGY1Ox.vsbMP/zyW85n4YeqWk6Y5M4fh/3yQaEeT1kXDxlADrTFEe', '', NULL, 'default_user.png', NULL, 1, 0.00, 0.00, 0, '2026-02-06 02:36:29', NULL, '12345678', NULL, 0.00, 0.00),
(10, 'Gonññ', NULL, 'gonzalezfedericomarcelo@gmail.com', '$2y$10$1LWU.3R4MmLo82YiSzMq7efzpDQKITA8m/WAswY/njOMqnZcqqNie', NULL, NULL, 'default_user.png', NULL, 1, 0.00, 0.00, 0, '2026-02-10 00:24:10', NULL, '31215643', 'empleado36', 0.00, 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `combos`
--

CREATE TABLE `combos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `codigo_barras` varchar(50) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `es_ilimitado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `combos`
--

INSERT INTO `combos` (`id`, `nombre`, `precio`, `codigo_barras`, `activo`, `fecha_inicio`, `fecha_fin`, `es_ilimitado`) VALUES
(1, 'Pack Fernet + Coca', 17000.00, '', 0, NULL, NULL, 0),
(2, 'Pack De birras', 20000.00, '', 0, NULL, NULL, 0),
(3, 'Fwde3(374', 23560.00, '', 0, NULL, NULL, 0),
(17, 'PACK2', 20000.00, 'COMBO-1770600377', 1, '2026-02-09', '2026-02-13', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `combo_items`
--

CREATE TABLE `combo_items` (
  `id` int(11) NOT NULL,
  `id_combo` int(11) DEFAULT NULL,
  `id_producto` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `combo_items`
--

INSERT INTO `combo_items` (`id`, `id_combo`, `id_producto`, `cantidad`) VALUES
(1, 1, 4, 1),
(6, 1, 2, 2),
(7, 1, 8, 2),
(8, 2, 5, 12),
(9, 4, 4, 1),
(11, 4, 2, 2),
(12, 27, 4, 1),
(13, 27, 2, 2),
(15, 6, 4, 1),
(16, 6, 2, 1),
(17, 7, 4, 1),
(18, 7, 2, 1),
(19, 8, 4, 1),
(20, 8, 2, 1),
(22, 9, 4, 1),
(23, 9, 3, 1),
(24, 10, 8, 2),
(25, 11, 20, 1),
(26, 16, 4, 1),
(27, 16, 2, 1),
(28, 17, 4, 1),
(33, 17, 2, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

CREATE TABLE `configuracion` (
  `id` int(11) NOT NULL,
  `nombre_negocio` varchar(100) DEFAULT NULL,
  `telefono_whatsapp` varchar(20) DEFAULT NULL,
  `email_notificaciones` varchar(100) DEFAULT NULL,
  `direccion_local` varchar(200) DEFAULT NULL,
  `color_barra_nav` varchar(7) DEFAULT '#212529',
  `color_botones` varchar(7) DEFAULT '#0d6efd',
  `color_fondo` varchar(7) DEFAULT '#f8f9fa',
  `logo_url` varchar(255) DEFAULT 'logo_default.png',
  `modulo_clientes` tinyint(1) DEFAULT 1,
  `modulo_stock` tinyint(1) DEFAULT 1,
  `modulo_reportes` tinyint(1) DEFAULT 1,
  `modulo_presupuesto` tinyint(1) DEFAULT 1,
  `modulo_fidelizacion` tinyint(1) DEFAULT 0,
  `cuit` varchar(20) DEFAULT '',
  `mensaje_ticket` text DEFAULT '',
  `color_secundario` varchar(7) DEFAULT '#0dcaf0',
  `direccion_degradado` varchar(20) DEFAULT '135deg',
  `dias_alerta_vencimiento` int(11) DEFAULT 30,
  `dinero_por_punto` decimal(10,2) DEFAULT 100.00,
  `whatsapp_pedidos` varchar(50) DEFAULT '',
  `alerta_stock_global` int(11) DEFAULT 5,
  `tipo_ticket_predeterminado` enum('afip','interno') DEFAULT 'interno',
  `redondeo_caja` tinyint(1) DEFAULT 0,
  `stock_use_global` tinyint(1) DEFAULT 0,
  `stock_global_valor` int(11) DEFAULT 5,
  `ticket_modo` varchar(20) DEFAULT 'afip',
  `redondeo_auto` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuracion`
--

INSERT INTO `configuracion` (`id`, `nombre_negocio`, `telefono_whatsapp`, `email_notificaciones`, `direccion_local`, `color_barra_nav`, `color_botones`, `color_fondo`, `logo_url`, `modulo_clientes`, `modulo_stock`, `modulo_reportes`, `modulo_presupuesto`, `modulo_fidelizacion`, `cuit`, `mensaje_ticket`, `color_secundario`, `direccion_degradado`, `dias_alerta_vencimiento`, `dinero_por_punto`, `whatsapp_pedidos`, `alerta_stock_global`, `tipo_ticket_predeterminado`, `redondeo_caja`, `stock_use_global`, `stock_global_valor`, `ticket_modo`, `redondeo_auto`) VALUES
(1, 'Drogstore El 10', '5491166116861', 'gonzalezfedericomarcelo@gmail.com', 'Av. Siempre Viva 123', '#102942', NULL, NULL, 'uploads/logo_1770687021.png', 1, 1, 0, 1, 1, '20359117532', 'Muchas gracias por tu compra. Agrr', NULL, NULL, 28, 1000.00, '5491166116861', 5, 'interno', 0, 0, 5, 'afip', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cupones`
--

CREATE TABLE `cupones` (
  `id` int(11) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `descuento_porcentaje` int(11) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_limite` date DEFAULT NULL,
  `id_cliente` int(11) DEFAULT NULL COMMENT 'NULL es para todos',
  `cantidad_limite` int(11) DEFAULT 0,
  `usos_actuales` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cupones`
--

INSERT INTO `cupones` (`id`, `codigo`, `descuento_porcentaje`, `activo`, `fecha_limite`, `id_cliente`, `cantidad_limite`, `usos_actuales`) VALUES
(3, 'VERANO', 8, 1, '2026-04-24', NULL, 0, 0),
(4, '123456', 50, 1, '2026-02-28', NULL, 2, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_ventas`
--

CREATE TABLE `detalle_ventas` (
  `id` int(11) NOT NULL,
  `id_venta` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` decimal(12,3) NOT NULL,
  `precio_historico` decimal(12,2) NOT NULL,
  `costo_historico` decimal(10,2) DEFAULT 0.00,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `detalle_ventas`
--

INSERT INTO `detalle_ventas` (`id`, `id_venta`, `id_producto`, `cantidad`, `precio_historico`, `costo_historico`, `subtotal`) VALUES
(1, 1, 8, 1.000, 600.00, 0.00, 600.00),
(2, 2, 8, 1.000, 600.00, 0.00, 600.00),
(3, 3, 8, 1.000, 600.00, 0.00, 600.00),
(4, 4, 8, 1.000, 600.00, 0.00, 600.00),
(5, 5, 4, 1.000, 11500.00, 0.00, 11500.00),
(6, 6, 12, 1.000, 300.00, 0.00, 300.00),
(7, 11, 8, 1.000, 600.00, 0.00, 600.00),
(8, 12, 19, 1.000, 2900.00, 0.00, 2900.00),
(9, 13, 4, 1.000, 11500.00, 0.00, 11500.00),
(10, 13, 9, 1.000, 1000.00, 0.00, 1000.00),
(11, 14, 18, 1.000, 3200.00, 0.00, 3200.00),
(12, 15, 21, 1.000, 2600.00, 0.00, 2600.00),
(13, 16, 11, 1.000, 400.00, 0.00, 400.00),
(14, 16, 4, 1.000, 11500.00, 0.00, 11500.00),
(15, 17, 21, 1.000, 2600.00, 0.00, 2600.00),
(16, 18, 4, 1.000, 11500.00, 0.00, 11500.00),
(17, 18, 2, 1.000, 2600.00, 0.00, 2600.00),
(18, 18, 6, 1.000, 1100.00, 0.00, 1100.00),
(19, 19, 4, 1.000, 11500.00, 0.00, 11500.00),
(20, 19, 16, 1.000, 2800.00, 0.00, 2800.00),
(21, 20, 18, 1.000, 3200.00, 0.00, 3200.00),
(22, 20, 11, 1.000, 400.00, 0.00, 400.00),
(23, 21, 4, 1.000, 11500.00, 0.00, 11500.00),
(24, 21, 18, 1.000, 3200.00, 0.00, 3200.00),
(25, 22, 8, 1.000, 600.00, 0.00, 600.00),
(26, 22, 4, 1.000, 11500.00, 0.00, 11500.00),
(27, 23, 11, 1.000, 400.00, 0.00, 400.00),
(28, 24, 4, 1.000, 11500.00, 0.00, 11500.00),
(29, 25, 19, 1.000, 2900.00, 0.00, 2900.00),
(30, 26, 12, 1.000, 300.00, 0.00, 300.00),
(31, 27, 11, 1.000, 400.00, 0.00, 400.00),
(32, 30, 12, 1.000, 300.00, 0.00, 300.00),
(33, 31, 26, 1.000, 13000.00, 0.00, 13000.00),
(34, 32, 26, 1.000, 13000.00, 0.00, 13000.00),
(35, 33, 26, 1.000, 13000.00, 0.00, 13000.00),
(36, 34, 26, 1.000, 13000.00, 0.00, 13000.00),
(37, 35, 26, 1.000, 13000.00, 0.00, 13000.00),
(38, 36, 26, 1.000, 13000.00, 0.00, 13000.00),
(39, 37, 26, 1.000, 13000.00, 0.00, 13000.00),
(40, 38, 26, 1.000, 13000.00, 0.00, 13000.00),
(41, 39, 26, 1.000, 13000.00, 0.00, 13000.00),
(43, 41, 13, 1.000, 800.00, 0.00, 800.00),
(45, 43, 4, 1.000, 11500.00, 0.00, 11500.00),
(46, 44, 27, 1.000, 11000.00, 0.00, 11000.00),
(47, 45, 28, 1.000, 15000.00, 0.00, 15000.00),
(48, 46, 28, 1.000, 15000.00, 0.00, 15000.00),
(49, 47, 29, 1.000, 20000.00, 0.00, 20000.00),
(50, 48, 29, 1.000, 20000.00, 0.00, 20000.00),
(51, 49, 29, 1.000, 20000.00, 0.00, 20000.00),
(52, 50, 29, 1.000, 20000.00, 0.00, 20000.00),
(53, 51, 29, 1.000, 20000.00, 0.00, 20000.00),
(54, 52, 29, 1.000, 20000.00, 0.00, 20000.00),
(55, 53, 30, 1.000, 12333.00, 0.00, 12333.00),
(56, 54, 4, 1.000, 11500.00, 0.00, 11500.00),
(57, 55, 30, 1.000, 12333.00, 0.00, 12333.00),
(58, 56, 30, 1.000, 12333.00, 0.00, 12333.00),
(59, 57, 31, 1.000, 25000.00, 0.00, 25000.00),
(60, 58, 31, 1.000, 25000.00, 0.00, 25000.00),
(61, 59, 38, 1.000, 12314.00, 0.00, 12314.00),
(62, 60, 38, 1.000, 12314.00, 0.00, 12314.00),
(63, 61, 9, 1.000, 1000.00, 0.00, 1000.00),
(64, 61, 38, 1.000, 10000.00, 0.00, 10000.00),
(65, 62, 4, 1.000, 11500.00, 0.00, 11500.00),
(66, 62, 11, 1.000, 400.00, 0.00, 400.00),
(67, 62, 16, 1.000, 2800.00, 0.00, 2800.00),
(68, 63, 18, 1.000, 3200.00, 0.00, 3200.00),
(69, 63, 21, 1.000, 2000.00, 0.00, 2000.00),
(70, 64, 6, 1.000, 1100.00, 0.00, 1100.00),
(71, 65, 4, 1.000, 11500.00, 0.00, 11500.00),
(72, 66, 9, 1.000, 1000.00, 0.00, 1000.00),
(73, 67, 9, 1.000, 1000.00, 0.00, 1000.00),
(74, 67, 16, 1.000, 2800.00, 0.00, 2800.00),
(75, 68, 8, 1.000, 630.00, 0.00, 630.00),
(76, 69, 8, 1.000, 630.00, 0.00, 630.00),
(77, 70, 8, 1.000, 630.00, 0.00, 630.00),
(78, 71, 8, 1.000, 630.00, 0.00, 630.00),
(79, 72, 4, 1.000, 12075.00, 0.00, 12075.00),
(80, 72, 2, 1.000, 2730.00, 0.00, 2730.00),
(81, 73, 4, 1.000, 12075.00, 0.00, 12075.00),
(82, 73, 5, 1.000, 1470.00, 0.00, 1470.00),
(83, 73, 11, 1.000, 420.00, 0.00, 420.00),
(84, 73, 8, 1.000, 630.00, 0.00, 630.00),
(85, 73, 12, 1.000, 315.00, 0.00, 315.00),
(86, 73, 18, 1.000, 3360.00, 0.00, 3360.00),
(87, 74, 21, 1.000, 2100.00, 0.00, 2100.00),
(88, 75, 21, 1.000, 2100.00, 0.00, 2100.00),
(89, 76, 21, 1.000, 2100.00, 0.00, 2100.00),
(90, 77, 8, 1.000, 630.00, 0.00, 630.00),
(91, 77, 4, 1.000, 12075.00, 0.00, 12075.00),
(92, 78, 12, 1.000, 315.00, 0.00, 315.00),
(93, 78, 8, 1.000, 630.00, 0.00, 630.00),
(94, 79, 4, 1.000, 12075.00, 0.00, 12075.00),
(95, 79, 19, 1.000, 3045.00, 0.00, 3045.00),
(96, 79, 6, 1.000, 1155.00, 0.00, 1155.00),
(97, 79, 16, 1.000, 2940.00, 0.00, 2940.00),
(98, 80, 11, 1.000, 420.00, 0.00, 420.00),
(99, 80, 4, 1.000, 12075.00, 0.00, 12075.00),
(100, 80, 18, 2.000, 3360.00, 0.00, 6720.00),
(101, 81, 18, 1.000, 3360.00, 0.00, 3360.00),
(102, 81, 11, 2.000, 420.00, 0.00, 840.00),
(103, 81, 5, 2.000, 1470.00, 0.00, 2940.00),
(104, 81, 4, 2.000, 12075.00, 0.00, 24150.00),
(105, 82, 12, 1.000, 315.00, 0.00, 315.00),
(106, 82, 8, 1.000, 630.00, 0.00, 630.00),
(107, 83, 6, 1.000, 1155.00, 0.00, 1155.00),
(108, 83, 21, 1.000, 2100.00, 0.00, 2100.00),
(109, 84, 20, 1.000, 5200.00, 0.00, 5200.00),
(110, 85, 6, 1.000, 1155.00, 0.00, 1155.00),
(111, 85, 21, 1.000, 2100.00, 0.00, 2100.00),
(112, 85, 4, 1.000, 12075.00, 0.00, 12075.00),
(113, 85, 18, 2.000, 3360.00, 0.00, 6720.00),
(114, 85, 5, 1.000, 1470.00, 0.00, 1470.00),
(115, 85, 16, 1.000, 2940.00, 0.00, 2940.00),
(116, 86, 18, 1.000, 3360.00, 0.00, 3360.00),
(117, 87, 18, 1.000, 3360.00, 0.00, 3360.00),
(118, 88, 9, 1.000, 1050.00, 0.00, 1050.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `devoluciones`
--

CREATE TABLE `devoluciones` (
  `id` int(11) NOT NULL,
  `id_venta_original` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` decimal(12,3) NOT NULL,
  `monto_devuelto` decimal(12,2) NOT NULL,
  `motivo` varchar(100) DEFAULT 'Cambio',
  `fecha` datetime DEFAULT current_timestamp(),
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Volcado de datos para la tabla `devoluciones`
--

INSERT INTO `devoluciones` (`id`, `id_venta_original`, `id_producto`, `cantidad`, `monto_devuelto`, `motivo`, `fecha`, `id_usuario`) VALUES
(1, 1, 8, 1.000, 600.00, 'Cambio', '2026-01-29 01:25:47', 1),
(2, 1, 8, 1.000, 600.00, 'Cambio', '2026-01-29 01:26:54', 1),
(3, 63, 18, 1.000, 3200.00, 'Cambio', '2026-02-09 12:35:01', 1),
(4, 65, 4, 1.000, 11500.00, 'Cambio', '2026-02-09 17:06:26', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `encuestas`
--

CREATE TABLE `encuestas` (
  `id` int(11) NOT NULL,
  `nivel` int(1) NOT NULL,
  `comentario` text DEFAULT NULL,
  `cliente_nombre` varchar(100) DEFAULT 'Anónimo',
  `contacto` varchar(100) DEFAULT NULL,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Volcado de datos para la tabla `encuestas`
--

INSERT INTO `encuestas` (`id`, `nivel`, `comentario`, `cliente_nombre`, `contacto`, `fecha`) VALUES
(1, 5, 'Amo', 'Anónimo', '', '2026-01-29 02:11:22'),
(2, 3, 'prueba', 'Anónimo', '', '2026-01-29 21:02:22'),
(3, 5, 'Perfecto', 'Anónimo', '', '2026-02-09 18:38:14'),
(4, 1, 'Prueba de mala experiencia', 'Ferchu', '388 586-5574', '2026-02-10 15:14:36'),
(5, 5, 'EXCELENTE', 'Anónimo', '5491166116861', '2026-02-11 14:05:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gastos`
--

CREATE TABLE `gastos` (
  `id` int(11) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `categoria` varchar(50) DEFAULT 'General',
  `fecha` datetime DEFAULT current_timestamp(),
  `id_usuario` int(11) NOT NULL,
  `id_caja_sesion` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Volcado de datos para la tabla `gastos`
--

INSERT INTO `gastos` (`id`, `descripcion`, `monto`, `categoria`, `fecha`, `id_usuario`, `id_caja_sesion`) VALUES
(1, 'Devolución Efectivo Ticket #1', 600.00, 'Devoluciones', '2026-01-29 01:25:47', 1, 1),
(2, 'Devolución Efectivo Ticket #1', 600.00, 'Devoluciones', '2026-01-29 01:26:54', 1, 2),
(3, 'Electricista', 2500.00, 'Otros', '2026-02-06 18:03:42', 1, 7),
(4, 'Devolución Efectivo Ticket #63', 3200.00, 'Devoluciones', '2026-02-09 12:35:01', 1, 7),
(5, 'Devolución Efectivo Ticket #65', 11500.00, 'Devoluciones', '2026-02-09 17:06:26', 1, 8),
(6, 'Compras', 200.00, 'Servicios', '2026-02-09 20:17:00', 1, 8),
(7, 'Prueba gasto ', 2000.00, 'Insumos', '2026-02-10 10:53:04', 1, 8),
(8, 'Costo Canje Fidelización: fede (Cliente #3)', 9765.00, 'Fidelizacion', '2026-02-11 19:52:38', 1, 12),
(9, 'adelando nora', 5000.00, 'Sueldos', '2026-02-11 19:54:25', 1, 12);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mermas`
--

CREATE TABLE `mermas` (
  `id` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` decimal(12,3) NOT NULL,
  `motivo` varchar(100) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Volcado de datos para la tabla `mermas`
--

INSERT INTO `mermas` (`id`, `id_producto`, `cantidad`, `motivo`, `fecha`, `id_usuario`) VALUES
(1, 8, 1.000, 'Roto (SE LE ROMPIO A FEDE)', '2026-02-06 14:50:21', 1),
(2, 8, 2.000, 'Robo (LO ROBARON)', '2026-02-09 20:58:02', 1),
(3, 8, 2.000, 'Robo (LO ROBARON)', '2026-02-09 20:58:02', 1),
(4, 9, 1.000, 'Vencido', '2026-02-09 21:01:37', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_cc`
--

CREATE TABLE `movimientos_cc` (
  `id` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_venta` int(11) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo` enum('debe','haber') NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `concepto` varchar(255) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `movimientos_cc`
--

INSERT INTO `movimientos_cc` (`id`, `id_cliente`, `id_venta`, `id_usuario`, `tipo`, `monto`, `concepto`, `fecha`) VALUES
(5, 1, NULL, 1, 'haber', 1500.00, '', '2026-02-09 18:04:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_proveedores`
--

CREATE TABLE `movimientos_proveedores` (
  `id` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `tipo` enum('compra','pago') NOT NULL COMMENT 'compra=aumenta deuda, pago=baja deuda',
  `monto` decimal(12,2) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `comprobante` varchar(100) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Volcado de datos para la tabla `movimientos_proveedores`
--

INSERT INTO `movimientos_proveedores` (`id`, `id_proveedor`, `fecha`, `tipo`, `monto`, `descripcion`, `comprobante`, `id_usuario`) VALUES
(1, 1, '2026-01-28 17:53:06', 'compra', 50000.00, '20 COCAS', '453543', 1),
(2, 1, '2026-01-28 17:53:37', 'pago', 40000.00, 'PAGO', '', 1),
(3, 1, '2026-02-07 22:19:45', 'pago', 6000.00, '', 'Hññ6637484', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_ventas`
--

CREATE TABLE `pagos_ventas` (
  `id` int(11) NOT NULL,
  `id_venta` int(11) NOT NULL,
  `metodo_pago` varchar(50) NOT NULL,
  `monto` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Volcado de datos para la tabla `pagos_ventas`
--

INSERT INTO `pagos_ventas` (`id`, `id_venta`, `metodo_pago`, `monto`) VALUES
(1, 16, 'Efectivo', 2000.00),
(2, 16, 'MP', 5000.00),
(3, 16, 'Credito', 4900.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `id` int(11) NOT NULL,
  `clave` varchar(50) NOT NULL,
  `descripcion` varchar(100) DEFAULT NULL,
  `categoria` varchar(50) DEFAULT 'General'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `permisos`
--

INSERT INTO `permisos` (`id`, `clave`, `descripcion`, `categoria`) VALUES
(1, 'ver_dashboard', 'Ver Panel Principal (Estadísticas)', 'Dashboard'),
(2, 'ver_ventas', 'Ver Historial de Ventas', 'Ventas'),
(3, 'nueva_venta', 'Acceso a Nueva Venta (Caja)', 'Ventas'),
(4, 'anular_venta', 'Anular/Eliminar Ventas', 'Ventas'),
(5, 'suspender_venta', 'Suspender y Recuperar Ventas', 'Ventas'),
(6, 'ver_devoluciones', 'Ver Historial de Devoluciones', 'Ventas'),
(7, 'gestionar_devoluciones', 'Realizar Devoluciones (Notas de Crédito)', 'Ventas'),
(8, 'apertura_caja', 'Abrir Caja', 'Caja'),
(9, 'cierre_caja', 'Cerrar Caja (Arqueo)', 'Caja'),
(10, 'ver_historial_cajas', 'Ver Historial de Cierres', 'Caja'),
(11, 'ver_detalle_caja', 'Ver Detalle Profundo de Caja', 'Caja'),
(12, 'ver_productos', 'Ver Lista de Productos', 'Productos'),
(13, 'gestionar_productos', 'Crear/Editar/Eliminar Productos', 'Productos'),
(14, 'gestionar_stock', 'Ajustar Stock Manualmente', 'Productos'),
(15, 'gestionar_precios', 'Actualización Masiva de Precios', 'Productos'),
(16, 'imprimir_carteles', 'Imprimir Etiquetas/Códigos QR', 'Productos'),
(17, 'gestionar_mermas', 'Registrar Mermas/Roturas', 'Productos'),
(18, 'ver_clientes', 'Ver Listado de Clientes', 'Clientes'),
(19, 'gestionar_clientes', 'Crear/Editar/Eliminar Clientes', 'Clientes'),
(20, 'gestionar_cta_cliente', 'Administrar Cuenta Corriente (Fiado)', 'Clientes'),
(21, 'ver_proveedores', 'Ver Listado de Proveedores', 'Proveedores'),
(22, 'gestionar_proveedores', 'Crear/Editar/Eliminar Proveedores', 'Proveedores'),
(23, 'gestionar_cta_proveedor', 'Administrar Cuenta Proveedor', 'Proveedores'),
(24, 'ver_usuarios', 'Ver Usuarios del Sistema', 'Seguridad'),
(25, 'gestionar_usuarios', 'Crear/Editar/Bloquear Usuarios', 'Seguridad'),
(26, 'gestionar_roles', 'Configurar Roles y Permisos', 'Seguridad'),
(27, 'ver_auditoria', 'Ver Registros de Auditoría (Logs)', 'Seguridad'),
(28, 'ver_config', 'Ver Configuración General', 'Configuración'),
(29, 'config_negocio', 'Editar Datos del Negocio', 'Configuración'),
(30, 'config_afip', 'Configurar Facturación AFIP', 'Configuración'),
(31, 'config_revista', 'Configurar Revista Digital', 'Configuración'),
(32, 'ver_reportes', 'Ver Reportes y Gráficos', 'Reportes'),
(33, 'exportar_reportes', 'Exportar a Excel/PDF', 'Reportes'),
(34, 'gestionar_gastos', 'Registrar y Ver Gastos', 'Finanzas'),
(35, 'gestionar_activos', 'Gestionar Bienes de Uso', 'Finanzas'),
(36, 'gestionar_premios', 'Gestionar Premios y Puntos', 'Fidelización'),
(37, 'gestionar_cupones', 'Gestionar Cupones de Descuento', 'Fidelización'),
(38, 'realizar_canje', 'Realizar Canje de Puntos', 'Fidelización'),
(39, 'ver_encuestas', 'Ver Resultados de Encuestas', 'Otros');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `premios`
--

CREATE TABLE `premios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `puntos_necesarios` int(11) NOT NULL,
  `stock` int(11) DEFAULT 100,
  `activo` tinyint(1) DEFAULT 1,
  `es_cupon` tinyint(1) DEFAULT 0,
  `monto_dinero` decimal(10,2) DEFAULT 0.00,
  `id_articulo` int(11) DEFAULT NULL,
  `tipo_articulo` enum('ninguno','producto','combo') DEFAULT 'ninguno'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Volcado de datos para la tabla `premios`
--

INSERT INTO `premios` (`id`, `nombre`, `puntos_necesarios`, `stock`, `activo`, `es_cupon`, `monto_dinero`, `id_articulo`, `tipo_articulo`) VALUES
(23, 'fede', 1, 9, 1, 0, 0.00, 17, 'combo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `codigo_barras` varchar(100) DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `descripcion_larga` text DEFAULT NULL,
  `id_categoria` int(11) DEFAULT NULL,
  `id_proveedor` int(11) DEFAULT NULL,
  `tipo` enum('unitario','combo','pesable') DEFAULT 'unitario',
  `precio_costo` decimal(12,2) NOT NULL DEFAULT 0.00,
  `precio_venta` decimal(12,2) NOT NULL DEFAULT 0.00,
  `precio_oferta` decimal(12,2) DEFAULT NULL,
  `stock_actual` decimal(12,3) NOT NULL DEFAULT 0.000,
  `stock_minimo` decimal(12,3) DEFAULT 5.000,
  `imagen_url` varchar(255) DEFAULT 'default.jpg',
  `es_destacado_web` tinyint(1) DEFAULT 0,
  `es_apto_celiaco` tinyint(1) DEFAULT 0,
  `es_apto_vegano` tinyint(1) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_vencimiento` date DEFAULT NULL,
  `dias_alerta` int(11) DEFAULT NULL,
  `es_vegano` tinyint(1) DEFAULT 0,
  `es_celiaco` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `codigo_barras`, `descripcion`, `descripcion_larga`, `id_categoria`, `id_proveedor`, `tipo`, `precio_costo`, `precio_venta`, `precio_oferta`, `stock_actual`, `stock_minimo`, `imagen_url`, `es_destacado_web`, `es_apto_celiaco`, `es_apto_vegano`, `activo`, `fecha_vencimiento`, `dias_alerta`, `es_vegano`, `es_celiaco`) VALUES
(2, '7790895000997', 'Coca-Cola Sabor Original 2.25L', NULL, 1, 1, 'unitario', 1890.00, 2730.00, NULL, 33.000, 10.000, 'uploads/prod_1770255531_532.png', 0, 1, 1, 1, NULL, NULL, 0, 0),
(3, '7790895001000', 'Coca-Cola Zero 2.25L', NULL, 1, 1, 'unitario', 1575.00, 3150.00, NULL, 11.000, 5.000, 'uploads/prod_1770255526_425.png', 0, 1, 1, 1, NULL, NULL, 1, 1),
(4, '7790895066665', 'Fernet Branca 750ml', NULL, 1, 1, 'unitario', 7875.00, 12075.00, NULL, 85.000, 10.000, 'uploads/prod_1770255511_248.png', 1, 1, 1, 1, NULL, NULL, 0, 0),
(5, '7790240032222', 'Cerveza Quilmes Clásica 473ml', NULL, 1, 1, 'unitario', 945.00, 1470.00, NULL, 86.000, 24.000, 'uploads/prod_1770255503_324.png', 1, 0, 1, 1, NULL, NULL, 0, 0),
(6, '7792799000011', 'Agua Mineral Villavicencio 1.5L', NULL, 1, 1, 'unitario', 630.00, 1155.00, NULL, 48.000, 10.000, 'uploads/prod_1770255428_805.png', 0, 1, 1, 1, NULL, NULL, 0, 0),
(7, '7791234567890', 'Monster Energy Green 473ml', NULL, 1, 1, 'unitario', 1155.00, 1890.00, NULL, 6.000, 6.000, 'uploads/prod_1770255423_790.png', 1, 0, 1, 1, NULL, NULL, 0, 0),
(8, '7790580123456', 'Alfajor Guaymallén Dulce de Leche', NULL, 2, 1, 'unitario', 315.00, 630.00, NULL, 183.000, 24.000, 'uploads/prod_1770255417_877.png', 0, 0, 0, 1, NULL, NULL, 0, 0),
(9, '7790580999999', 'Alfajor Jorgito Chocolate', NULL, 2, 1, 'unitario', 630.00, 1050.00, NULL, 33.000, 12.000, 'uploads/prod_1770255413_500.png', 0, 0, 0, 1, NULL, NULL, 0, 0),
(10, '7790060023654', 'Chocolate Milka Leger 100g', NULL, 2, 1, 'unitario', 1575.00, 2625.00, NULL, 28.000, 5.000, 'uploads/prod_1770255406_507.png', 0, 1, 0, 1, NULL, NULL, 0, 0),
(11, '7790456000021', 'Pastillas DRF Menta', NULL, 2, 1, 'unitario', 210.00, 420.00, NULL, 87.000, 10.000, 'uploads/prod_1770255400_348.png', 0, 1, 1, 1, NULL, NULL, 0, 0),
(12, '7791111222233', 'Turrón Arcor Misky', NULL, 2, 1, 'unitario', 157.50, 315.00, NULL, 493.000, 50.000, 'uploads/prod_1770255394_844.png', 0, 0, 1, 1, NULL, NULL, 0, 0),
(13, '7790999000111', 'Chicle Beldent Menta 8u', NULL, 2, 1, 'unitario', 420.00, 840.00, NULL, 58.000, 20.000, 'uploads/prod_1770255584_118.png', 1, 1, 1, 1, NULL, NULL, 0, 0),
(14, '7790040866666', 'Papas Fritas Lays Clásicas 85g', NULL, 3, 1, 'unitario', 1260.00, 2205.00, NULL, 10.000, 10.000, 'uploads/prod_1770255381_602.png', 0, 1, 1, 1, NULL, NULL, 0, 0),
(15, '7790040855555', 'Doritos Queso 85g', NULL, 3, 1, 'unitario', 1365.00, 2415.00, NULL, 3.000, 10.000, 'uploads/prod_1770255375_280.png', 0, 0, 0, 1, NULL, NULL, 0, 0),
(16, '7794444555566', 'Yerba Playadito 500g', NULL, 3, 1, 'unitario', 1890.00, 2940.00, 1500.00, 34.000, 10.000, 'uploads/prod_1770255364_257.png', 1, 1, 1, 1, NULL, NULL, 0, 0),
(17, '7792222333344', 'Galletitas 9 de Oro Clásicas', NULL, 3, 1, 'unitario', 840.00, 2940.00, NULL, 5.000, 5.000, 'uploads/prod_1770253556_135.png', 0, 0, 1, 1, '2026-03-07', 40, 1, 1),
(18, '7790000000001', 'Marlboro Box 20', NULL, 4, 1, 'unitario', 2625.00, 3360.00, NULL, 84.000, 20.000, 'uploads/prod_1770255357_603.png', 0, 0, 0, 1, NULL, NULL, 0, 0),
(19, '7790000000002', 'Philip Morris Box 20', NULL, 4, 1, 'unitario', 2415.00, 3045.00, NULL, 76.000, 20.000, 'uploads/prod_1770252536_715.png', 0, 0, 0, 1, NULL, NULL, 0, 0),
(20, '7790000000003', 'Camel Box 20', NULL, 4, 2, 'unitario', 2500.00, 5200.00, 1800.00, 34.000, 5.000, 'uploads/prod_1770252839_949.png', 1, 0, 0, 1, NULL, NULL, 1, 1),
(21, '7790000000004', 'Chesterfield Box 20', NULL, 4, 1, 'unitario', 525.00, 2100.00, 1200.00, 39.000, 5.000, 'uploads/prod_1770253452_530.png', 0, 0, 0, 1, '2026-02-13', 5, 1, 1),
(39, 'COMBO-1770600377', 'PACK2', NULL, 2, 1, 'combo', 0.00, 21000.00, 18000.00, 0.000, 5.000, 'uploads/combo_1770601303.png', 1, 0, 0, 1, NULL, NULL, 0, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos_combo`
--

CREATE TABLE `productos_combo` (
  `id_combo` int(11) NOT NULL,
  `id_producto_hijo` int(11) NOT NULL,
  `cantidad` decimal(12,3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL,
  `empresa` varchar(100) NOT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `cuit` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `dia_visita` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id`, `empresa`, `contacto`, `telefono`, `cuit`, `email`, `dia_visita`) VALUES
(1, 'Coca-Cola Oficial', 'Juan Repartidor', '1166116861', NULL, NULL, 'Lunes'),
(2, 'Mayorista Arcoiris', 'Pedro Ventas', NULL, NULL, NULL, 'Jueves'),
(3, 'La Serenísima', 'Carlos Leche', NULL, NULL, NULL, 'Martes');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `revista_config`
--

CREATE TABLE `revista_config` (
  `id` int(11) NOT NULL,
  `titulo_tapa` varchar(255) DEFAULT 'CATÁLOGO',
  `subtitulo_tapa` varchar(255) DEFAULT 'INTERACTIVO',
  `img_tapa` varchar(255) DEFAULT NULL,
  `img_bienvenida` varchar(255) DEFAULT NULL,
  `texto_bienvenida_titulo` varchar(100) DEFAULT '¡Hola Vecino!',
  `texto_bienvenida_cuerpo` text DEFAULT NULL,
  `tapa_banner_color` varchar(20) DEFAULT '#ffffff',
  `tapa_banner_opacity` decimal(3,2) DEFAULT 0.90,
  `bienv_bg_color` varchar(20) DEFAULT '#ffffff',
  `tapa_overlay` decimal(3,2) DEFAULT 0.40,
  `tapa_tit_color` varchar(20) DEFAULT '#ffde00',
  `tapa_sub_color` varchar(20) DEFAULT '#ffffff',
  `bienv_overlay` decimal(3,2) DEFAULT 0.00,
  `bienv_tit_color` varchar(20) DEFAULT '#333333',
  `bienv_txt_color` varchar(20) DEFAULT '#555555',
  `fuente_global` varchar(50) DEFAULT 'Poppins',
  `contratapa_titulo` varchar(255) DEFAULT '¡MUCHAS GRACIAS!',
  `contratapa_texto` text DEFAULT NULL,
  `img_contratapa` varchar(255) DEFAULT NULL,
  `contratapa_bg_color` varchar(20) DEFAULT '#222222',
  `contratapa_texto_color` varchar(20) DEFAULT '#ffffff',
  `contratapa_overlay` decimal(3,2) DEFAULT 0.50,
  `mostrar_qr` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `revista_config`
--

INSERT INTO `revista_config` (`id`, `titulo_tapa`, `subtitulo_tapa`, `img_tapa`, `img_bienvenida`, `texto_bienvenida_titulo`, `texto_bienvenida_cuerpo`, `tapa_banner_color`, `tapa_banner_opacity`, `bienv_bg_color`, `tapa_overlay`, `tapa_tit_color`, `tapa_sub_color`, `bienv_overlay`, `bienv_tit_color`, `bienv_txt_color`, `fuente_global`, `contratapa_titulo`, `contratapa_texto`, `img_contratapa`, `contratapa_bg_color`, `contratapa_texto_color`, `contratapa_overlay`, `mostrar_qr`) VALUES
(1, 'CATÁLOGO', 'INTERACTIVO', 'img/revista/1770396861_tapa_WhatsApp Image 2026-02-03 at 22.12.02.jpeg', 'img/revista/1770241601_bienv_Yellow Colorful Creative Abstract Welcome Banner.jpg', '¡Hola Vecino!', 'Mirá todo lo rico que llegó esta semana.', '#ffffff', 0.00, '#3c54b4', 0.40, '#ffde00', '#ffffff', 0.00, '#333333', '#555555', 'Poppins', '¡MUCHAS GRACIAS!', 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500', 'img/revista/1770465972_contra_sl_043020_30500_17.jpg', '#860909', '#bdd123', 0.50, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `revista_paginas`
--

CREATE TABLE `revista_paginas` (
  `id` int(11) NOT NULL,
  `nombre_referencia` varchar(100) DEFAULT NULL,
  `posicion` int(11) DEFAULT 5,
  `imagen_url` varchar(255) DEFAULT NULL,
  `boton_texto` varchar(50) DEFAULT NULL,
  `boton_link` varchar(255) DEFAULT NULL,
  `activa` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `revista_paginas`
--

INSERT INTO `revista_paginas` (`id`, `nombre_referencia`, `posicion`, `imagen_url`, `boton_texto`, `boton_link`, `activa`) VALUES
(1, '1', 4, 'img/revista/1770241671_valentin.jpg', 'Ver regalos', 'google.com', 1),
(2, '2', 8, 'img/revista/1770242547_ads_Beige And Red Illustrative Summer Sale Your Story.jpg', 'Boton', 'link.com', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`, `descripcion`) VALUES
(1, 'SuperAdmin', 'Acceso total al sistema y código'),
(2, 'Dueño', 'Acceso a reportes, ganancias y configuración'),
(3, 'Empleado', 'Acceso limitado a ventas y caja'),
(4, 'Logistica', 'Control de stock, mermas y proveedores'),
(5, 'Auditor', 'Acceso a reportes, gastos y logs de auditoria'),
(6, 'Marketing', 'Gestion de cupones, premios y revista digital'),
(7, 'Supervisor', 'Cajero senior con poder para anular y devolver');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol_permisos`
--

CREATE TABLE `rol_permisos` (
  `id_rol` int(11) NOT NULL,
  `id_permiso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `rol_permisos`
--

INSERT INTO `rol_permisos` (`id_rol`, `id_permiso`) VALUES
(1, 1),
(2, 1),
(3, 1),
(5, 1),
(7, 1),
(1, 2),
(2, 2),
(5, 2),
(7, 2),
(1, 3),
(2, 3),
(3, 3),
(7, 3),
(1, 4),
(2, 4),
(7, 4),
(1, 5),
(2, 5),
(3, 5),
(7, 5),
(1, 6),
(2, 6),
(7, 6),
(1, 7),
(2, 7),
(7, 7),
(1, 8),
(2, 8),
(3, 8),
(7, 8),
(1, 9),
(2, 9),
(3, 9),
(7, 9),
(1, 10),
(2, 10),
(5, 10),
(1, 11),
(2, 11),
(1, 12),
(2, 12),
(3, 12),
(4, 12),
(7, 12),
(1, 13),
(2, 13),
(4, 13),
(1, 14),
(2, 14),
(4, 14),
(1, 15),
(2, 15),
(4, 15),
(1, 16),
(2, 16),
(3, 16),
(1, 17),
(2, 17),
(4, 17),
(1, 18),
(2, 18),
(3, 18),
(6, 18),
(7, 18),
(1, 19),
(2, 19),
(6, 19),
(1, 20),
(2, 20),
(7, 20),
(1, 21),
(2, 21),
(4, 21),
(1, 22),
(2, 22),
(4, 22),
(1, 23),
(2, 23),
(1, 24),
(2, 24),
(1, 25),
(2, 25),
(1, 26),
(2, 26),
(1, 27),
(2, 27),
(5, 27),
(1, 28),
(2, 28),
(1, 29),
(2, 29),
(1, 30),
(2, 30),
(1, 31),
(2, 31),
(6, 31),
(1, 32),
(2, 32),
(5, 32),
(1, 33),
(2, 33),
(1, 34),
(2, 34),
(5, 34),
(1, 35),
(2, 35),
(1, 36),
(2, 36),
(6, 36),
(1, 37),
(2, 37),
(6, 37),
(1, 38),
(2, 38),
(3, 38),
(1, 39),
(2, 39),
(6, 39);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `ultimo_login` datetime DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `whatsapp` varchar(50) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `tema_ui` enum('light','dark') DEFAULT 'light',
  `google_2fa_secret` varchar(255) DEFAULT NULL,
  `rol` enum('admin','empleado') NOT NULL DEFAULT 'empleado',
  `forzar_logout` tinyint(1) DEFAULT 0,
  `ultimo_acceso` datetime DEFAULT NULL,
  `ip_ultimo_acceso` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre_completo`, `usuario`, `password`, `id_rol`, `activo`, `ultimo_login`, `email`, `whatsapp`, `foto_perfil`, `tema_ui`, `google_2fa_secret`, `rol`, `forzar_logout`, `ultimo_acceso`, `ip_ultimo_acceso`) VALUES
(1, 'Administrador', 'admin', '$2y$10$zqfetwARANGfV8BYfc8ay.uBb6AlhpffvjiFC.eZYHtfUqKxRMHke', 1, 1, NULL, 'gonzalezmarcelo159@gmail.com', '1166116861', 'user_1_1770284693.jpg', 'light', NULL, 'empleado', 0, NULL, NULL),
(2, 'empleado', 'empleado', '$2y$10$ZgnIc5xKurJiIgVF4hr5aek2tfCOiKC3AS9yvsUNXLQFkf33D6H..', 3, 1, NULL, NULL, NULL, NULL, 'light', NULL, 'empleado', 0, NULL, NULL),
(3, 'peca', 'peca', '$2y$10$Mo5yZele1Xnv8.cMpX.rsuZU9qj7kRPm3.Mx/QfDbQOED/APSijYG', 2, 1, NULL, NULL, NULL, NULL, 'light', NULL, 'empleado', 0, NULL, NULL),
(4, 'Emplado22', 'Emp2', '$2y$10$uY4FPI9TNK6nELw7bDScJOR7K5TNyrSn3J7VD0nOPo3DxXDOEyzZu', 3, 1, NULL, NULL, NULL, NULL, 'light', NULL, 'empleado', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id` int(11) NOT NULL,
  `codigo_ticket` varchar(50) DEFAULT NULL,
  `id_caja_sesion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `total` decimal(12,2) NOT NULL,
  `descuento_monto_cupon` decimal(12,2) DEFAULT 0.00,
  `descuento_manual` decimal(12,2) DEFAULT 0.00,
  `codigo_cupon` varchar(50) DEFAULT NULL,
  `metodo_pago` enum('Efectivo','Debito','Credito','MP','CtaCorriente','Mixto') DEFAULT 'Efectivo',
  `estado` enum('completada','anulada','pendiente_retiro') DEFAULT 'completada',
  `origen` enum('local','web') DEFAULT 'local'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id`, `codigo_ticket`, `id_caja_sesion`, `id_usuario`, `id_cliente`, `fecha`, `total`, `descuento_monto_cupon`, `descuento_manual`, `codigo_cupon`, `metodo_pago`, `estado`, `origen`) VALUES
(1, NULL, 1, 1, 1, '2026-01-28 22:25:30', 600.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(2, NULL, 3, 2, 1, '2026-01-28 22:37:39', 600.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(3, NULL, 2, 1, 3, '2026-01-29 06:14:11', 600.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(4, NULL, 4, 1, 1, '2026-01-29 17:58:09', 600.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(5, NULL, 5, 2, 1, '2026-02-03 19:19:48', 11500.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(6, NULL, 6, 1, 3, '2026-02-03 19:21:15', 300.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(11, NULL, 6, 1, 1, '2026-02-05 17:47:52', 600.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(12, NULL, 6, 1, 5, '2026-02-05 17:48:19', 2900.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(13, NULL, 6, 1, 3, '2026-02-05 17:49:19', 12500.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(14, NULL, 6, 1, 1, '2026-02-05 17:49:29', 3200.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(15, NULL, 6, 1, 1, '2026-02-05 17:49:36', 2600.00, 0.00, 0.00, '', 'Credito', 'completada', 'local'),
(16, NULL, 6, 1, 1, '2026-02-05 17:50:19', 11900.00, 0.00, 0.00, '', 'Mixto', 'completada', 'local'),
(17, NULL, 6, 1, 1, '2026-02-05 19:38:14', 2600.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(18, NULL, 6, 1, 6, '2026-02-05 20:05:46', 15200.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(19, NULL, 6, 1, 3, '2026-02-05 22:34:53', 14300.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(20, NULL, 6, 1, 3, '2026-02-05 23:14:42', 3533.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(21, NULL, 6, 1, 3, '2026-02-05 23:19:32', 14692.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(22, NULL, 6, 1, 1, '2026-02-05 23:21:25', 12100.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(23, NULL, 6, 1, 1, '2026-02-05 23:23:40', 400.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(24, NULL, 6, 1, 1, '2026-02-05 23:28:38', 11500.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(25, NULL, 6, 1, 1, '2026-02-05 23:30:50', 2900.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(26, NULL, 6, 1, 9, '2026-02-05 23:33:52', 300.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(27, NULL, 7, 1, 1, '2026-02-06 10:47:58', 400.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(28, NULL, 7, 1, 1, '2026-02-08 16:09:25', 300.00, 0.00, 0.00, NULL, 'Efectivo', 'completada', 'local'),
(29, NULL, 7, 1, 1, '2026-02-08 16:13:03', 1400.00, 0.00, 0.00, NULL, 'Efectivo', 'completada', 'local'),
(30, NULL, 7, 1, 1, '2026-02-08 16:22:45', 300.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(31, NULL, 7, 1, 1, '2026-02-08 16:23:00', 13000.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(32, NULL, 7, 1, 1, '2026-02-08 16:32:20', 13000.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(33, NULL, 7, 1, 1, '2026-02-08 16:38:12', 13000.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(34, NULL, 7, 1, 1, '2026-02-08 16:45:43', 13000.00, 0.00, 0.00, '', 'MP', 'completada', 'local'),
(35, NULL, 7, 1, 1, '2026-02-08 16:56:14', 13000.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(36, NULL, 7, 1, 1, '2026-02-08 17:15:42', 13000.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(37, NULL, 7, 1, 1, '2026-02-08 17:37:50', 13000.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(38, NULL, 7, 1, 1, '2026-02-08 18:04:49', 13000.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(39, NULL, 7, 1, 1, '2026-02-08 18:12:24', 13000.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(41, NULL, 7, 1, 1, '2026-02-08 18:18:55', 800.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(43, NULL, 7, 1, 1, '2026-02-08 18:23:51', 11500.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(44, NULL, 7, 1, 1, '2026-02-08 18:26:38', 11000.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(45, NULL, 7, 1, 1, '2026-02-08 18:30:51', 15000.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(46, NULL, 7, 1, 1, '2026-02-08 18:31:14', 15000.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(47, NULL, 7, 1, 1, '2026-02-08 18:43:07', 20000.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(48, NULL, 7, 1, 1, '2026-02-08 18:44:48', 20000.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(49, NULL, 7, 1, 1, '2026-02-08 18:46:14', 20000.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(50, NULL, 7, 1, 1, '2026-02-08 18:46:58', 20000.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(51, NULL, 7, 1, 1, '2026-02-08 18:52:14', 20000.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(52, NULL, 7, 1, 1, '2026-02-08 18:55:28', 20000.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(53, NULL, 7, 1, 1, '2026-02-08 18:57:03', 12333.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(54, NULL, 7, 1, 1, '2026-02-08 18:57:26', 11500.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(55, NULL, 7, 1, 1, '2026-02-08 19:59:58', 12333.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(56, NULL, 7, 1, 1, '2026-02-08 20:00:16', 12333.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(57, NULL, 7, 1, 1, '2026-02-08 20:08:48', 25000.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(58, NULL, 7, 1, 1, '2026-02-08 20:23:43', 25000.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(59, NULL, 7, 1, 1, '2026-02-08 21:01:05', 12314.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(60, NULL, 7, 1, 1, '2026-02-08 21:22:59', 12314.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(61, NULL, 7, 1, 1, '2026-02-08 22:00:46', 11000.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(62, NULL, 7, 1, 1, '2026-02-08 23:53:00', 4700.00, 0.00, 10000.00, '', 'Efectivo', 'completada', 'local'),
(63, NULL, 7, 1, 1, '2026-02-09 09:04:04', 5200.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(64, NULL, 7, 1, 1, '2026-02-09 11:46:19', 1100.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(65, NULL, 8, 1, 3, '2026-02-09 13:26:23', 11500.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(66, NULL, 8, 1, 1, '2026-02-09 14:24:45', 1000.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(67, NULL, 8, 1, 3, '2026-02-09 14:40:57', 3800.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(68, NULL, 8, 1, 5, '2026-02-09 22:32:40', 630.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(69, NULL, 8, 1, 1, '2026-02-09 22:33:12', 630.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(70, NULL, 8, 1, 1, '2026-02-10 07:55:28', 630.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(71, NULL, 9, 1, 1, '2026-02-10 10:18:40', 630.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(72, NULL, 9, 1, 5, '2026-02-10 10:38:53', 12620.60, 0.00, 1000.00, 'Verano', 'MP', 'completada', 'local'),
(73, NULL, 9, 1, 3, '2026-02-10 10:43:58', 14808.40, 0.00, 2000.00, 'Verano', 'Debito', 'completada', 'local'),
(74, NULL, 9, 1, 3, '2026-02-10 10:52:06', 2100.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(75, NULL, 9, 1, 3, '2026-02-10 10:53:21', 1100.00, 0.00, 1000.00, '', 'Efectivo', 'completada', 'local'),
(76, NULL, 9, 1, 3, '2026-02-10 10:54:04', 1100.00, 0.00, 1000.00, '', 'Efectivo', 'completada', 'local'),
(77, NULL, 9, 1, 5, '2026-02-10 21:56:16', 12705.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(78, NULL, 11, 1, 3, '2026-02-11 09:19:56', 869.40, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(79, NULL, 11, 1, 1, '2026-02-11 09:20:09', 19215.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(80, NULL, 12, 1, 1, '2026-02-11 09:51:42', 15677.80, 0.00, 2000.00, 'verano', 'Efectivo', 'completada', 'local'),
(81, NULL, 12, 1, 1, '2026-02-11 09:52:24', 23786.80, 0.00, 5000.00, 'verano', 'Efectivo', 'completada', 'local'),
(82, NULL, 12, 1, 6, '2026-02-11 14:47:36', 0.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(83, NULL, 12, 1, 3, '2026-02-11 14:48:23', 0.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(84, NULL, 12, 1, 5, '2026-02-11 14:49:59', 0.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(85, NULL, 12, 1, 1, '2026-02-11 14:51:55', 0.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(86, NULL, 12, 1, 1, '2026-02-11 14:52:12', 0.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(87, NULL, 12, 1, 1, '2026-02-11 14:52:36', 0.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(88, NULL, 12, 1, 1, '2026-02-11 14:53:53', 1050.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas_suspendidas`
--

CREATE TABLE `ventas_suspendidas` (
  `id` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `nombre_cliente_temporal` varchar(100) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas_suspendidas_items`
--

CREATE TABLE `ventas_suspendidas_items` (
  `id` int(11) NOT NULL,
  `id_suspendida` int(11) DEFAULT NULL,
  `id_producto` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `afip_config`
--
ALTER TABLE `afip_config`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `bienes_uso`
--
ALTER TABLE `bienes_uso`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cajas_sesion`
--
ALTER TABLE `cajas_sesion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dni_cuit` (`dni_cuit`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- Indices de la tabla `combos`
--
ALTER TABLE `combos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `combo_items`
--
ALTER TABLE `combo_items`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cupones`
--
ALTER TABLE `cupones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD UNIQUE KEY `idx_codigo` (`codigo`);

--
-- Indices de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_venta` (`id_venta`);

--
-- Indices de la tabla `devoluciones`
--
ALTER TABLE `devoluciones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `encuestas`
--
ALTER TABLE `encuestas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `gastos`
--
ALTER TABLE `gastos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `mermas`
--
ALTER TABLE `mermas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `movimientos_cc`
--
ALTER TABLE `movimientos_cc`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `idx_cliente_mov` (`id_cliente`),
  ADD KEY `idx_fecha_mov` (`fecha`);

--
-- Indices de la tabla `movimientos_proveedores`
--
ALTER TABLE `movimientos_proveedores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_proveedor` (`id_proveedor`);

--
-- Indices de la tabla `pagos_ventas`
--
ALTER TABLE `pagos_ventas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_venta` (`id_venta`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `premios`
--
ALTER TABLE `premios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_barras` (`codigo_barras`),
  ADD KEY `id_categoria` (`id_categoria`),
  ADD KEY `id_proveedor` (`id_proveedor`),
  ADD KEY `idx_codigo_barras` (`codigo_barras`);

--
-- Indices de la tabla `productos_combo`
--
ALTER TABLE `productos_combo`
  ADD KEY `id_combo` (`id_combo`),
  ADD KEY `id_producto_hijo` (`id_producto_hijo`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `revista_config`
--
ALTER TABLE `revista_config`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `revista_paginas`
--
ALTER TABLE `revista_paginas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `rol_permisos`
--
ALTER TABLE `rol_permisos`
  ADD PRIMARY KEY (`id_rol`,`id_permiso`),
  ADD KEY `id_permiso` (`id_permiso`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `id_rol` (`id_rol`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_ticket` (`codigo_ticket`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `idx_fecha_venta` (`fecha`),
  ADD KEY `idx_usuario_venta` (`id_usuario`);

--
-- Indices de la tabla `ventas_suspendidas`
--
ALTER TABLE `ventas_suspendidas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `ventas_suspendidas_items`
--
ALTER TABLE `ventas_suspendidas_items`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `afip_config`
--
ALTER TABLE `afip_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- AUTO_INCREMENT de la tabla `bienes_uso`
--
ALTER TABLE `bienes_uso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `cajas_sesion`
--
ALTER TABLE `cajas_sesion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `combos`
--
ALTER TABLE `combos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `combo_items`
--
ALTER TABLE `combo_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de la tabla `cupones`
--
ALTER TABLE `cupones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- AUTO_INCREMENT de la tabla `devoluciones`
--
ALTER TABLE `devoluciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `encuestas`
--
ALTER TABLE `encuestas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `gastos`
--
ALTER TABLE `gastos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `mermas`
--
ALTER TABLE `mermas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `movimientos_cc`
--
ALTER TABLE `movimientos_cc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `movimientos_proveedores`
--
ALTER TABLE `movimientos_proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `pagos_ventas`
--
ALTER TABLE `pagos_ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT de la tabla `premios`
--
ALTER TABLE `premios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `revista_config`
--
ALTER TABLE `revista_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `revista_paginas`
--
ALTER TABLE `revista_paginas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT de la tabla `ventas_suspendidas`
--
ALTER TABLE `ventas_suspendidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `ventas_suspendidas_items`
--
ALTER TABLE `ventas_suspendidas_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cajas_sesion`
--
ALTER TABLE `cajas_sesion`
  ADD CONSTRAINT `cajas_sesion_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD CONSTRAINT `detalle_ventas_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id`);

--
-- Filtros para la tabla `movimientos_cc`
--
ALTER TABLE `movimientos_cc`
  ADD CONSTRAINT `movimientos_cc_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `movimientos_cc_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `movimientos_proveedores`
--
ALTER TABLE `movimientos_proveedores`
  ADD CONSTRAINT `movimientos_proveedores_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`),
  ADD CONSTRAINT `productos_ibfk_2` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id`);

--
-- Filtros para la tabla `productos_combo`
--
ALTER TABLE `productos_combo`
  ADD CONSTRAINT `productos_combo_ibfk_1` FOREIGN KEY (`id_combo`) REFERENCES `productos` (`id`),
  ADD CONSTRAINT `productos_combo_ibfk_2` FOREIGN KEY (`id_producto_hijo`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `rol_permisos`
--
ALTER TABLE `rol_permisos`
  ADD CONSTRAINT `rol_permisos_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `rol_permisos_ibfk_2` FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id`);

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
