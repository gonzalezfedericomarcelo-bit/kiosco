-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 14-02-2026 a las 15:01:11
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
(1, '20000000000', 1, '', '', 'homologacion', '', '', '0000-00-00 00:00:00');

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
(1, 1, '2026-02-11 18:08:13', '0000-00-00 00:00:00'),
(2, 1, '2026-02-12 02:19:55', '0000-00-00 00:00:00'),
(3, 1, '2026-02-13 18:42:24', NULL),
(4, 2, '2026-02-13 19:15:26', NULL),
(5, 1, '2026-02-14 13:50:29', NULL);

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
(1, '2026-02-12 00:18:36', 1, 'VENTA_REALIZADA', 'Venta #1 | Total: $12075 | Cliente ID: 1'),
(2, '2026-02-12 00:44:05', 1, 'VENTA_REALIZADA', 'Venta #2 | Total: $1470 | Cliente ID: 1'),
(3, '2026-02-12 00:48:01', 1, 'VENTA_REALIZADA', 'Venta #3 | Total: $12075 | Cliente ID: 2'),
(4, '2026-02-13 18:44:30', 1, 'VENTA_RIFA', 'Venta Ticket Rifa #1 | Sorteo: Prueba  | Valor: $1000.00 | Cliente: Fede'),
(5, '2026-02-14 13:51:26', 1, 'SORTEO_FINALIZADO', 'Sorteo Finalizado #1 (). Se entregaron 1 premios.');

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
(1, 1, '2026-02-12 03:11:41', '0000-00-00 00:00:00', 0.00, 0.00, 0.00, 0.00, 'cerrada'),
(2, 1, '2026-02-12 00:11:56', '2026-02-12 00:12:51', 1000.00, 1000.00, 0.00, 0.00, 'cerrada'),
(3, 1, '2026-02-12 00:18:34', '2026-02-12 00:18:59', 3000.00, 12070.00, 12075.00, -3005.00, 'cerrada'),
(4, 1, '2026-02-12 00:43:52', '2026-02-12 00:45:26', 1000.00, 2470.00, 1470.00, 0.00, 'cerrada'),
(5, 1, '2026-02-12 00:46:39', '2026-02-13 15:43:41', 1000.00, 0.00, 12075.00, -13075.00, 'cerrada'),
(6, 1, '2026-02-13 15:44:11', NULL, 100.00, 1000.00, 1000.00, NULL, 'abierta');

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
(1, 'Consumidor Final', '00000000', '', '', '', '', 'default_user.png', '2026-02-11', 1, 0.00, 0.00, 0, '2026-01-26 11:15:20', '', '', '', 0.00, 0.00),
(2, 'Fede', NULL, NULL, NULL, '', NULL, 'default_user.png', '2026-02-27', 1, 5000.00, 0.00, 12, '2026-02-12 00:47:40', NULL, '35911753', NULL, 0.00, 0.00);

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
(1, 'Pack Fernet + Coca', 17000.00, '', 0, '0000-00-00', '0000-00-00', 0),
(2, 'Pack De birras', 20000.00, '', 0, '0000-00-00', '0000-00-00', 0),
(3, 'Fwde3(374', 23560.00, '', 0, '0000-00-00', '0000-00-00', 0),
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
(1, 'Drogstore El 10', '5491166116861', 'gonzalezfedericomarcelo@gmail.com', 'Av. Siempre Viva 123', '#102942', '', '', 'uploads/logo_1770687021.png', 1, 1, 0, 1, 1, '20359117532', 'Muchas gracias por tu compra. Agrr', '', '', 28, 1000.00, '5491166116861', 5, 'interno', 0, 0, 5, 'afip', 0);

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
(3, 'VERANO', 8, 1, '2026-04-24', 0, 0, 0),
(4, '123456', 50, 1, '2026-02-28', 0, 2, 0);

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
(1, 1, 4, 1.000, 12075.00, 0.00, 12075.00),
(2, 2, 5, 1.000, 1470.00, 0.00, 1470.00),
(3, 3, 4, 1.000, 12075.00, 0.00, 12075.00);

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
(1, 'Premio Sorteo #1: PACK2', 0.00, 'Sorteo', '2026-02-14 13:51:26', 1, 6);

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
(39, 'ver_encuestas', 'Ver Resultados de Encuestas', 'Otros'),
(40, 'gestionar_sorteos', 'Crear y administrar Sorteos', 'Marketing');

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
(2, '7790895000997', 'Coca-Cola Sabor Original 2.25L', '', 1, 1, 'unitario', 1890.00, 2730.00, 0.00, 33.000, 10.000, 'uploads/prod_1770255531_532.png', 0, 1, 1, 1, '0000-00-00', 0, 0, 0),
(3, '7790895001000', 'Coca-Cola Zero 2.25L', '', 1, 1, 'unitario', 1575.00, 3150.00, 0.00, 11.000, 5.000, 'uploads/prod_1770255526_425.png', 0, 1, 1, 1, '0000-00-00', 0, 1, 1),
(4, '7790895066665', 'Fernet Branca 750ml', '', 1, 1, 'unitario', 7875.00, 12075.00, 0.00, 83.000, 10.000, 'uploads/prod_1770255511_248.png', 1, 1, 1, 1, '0000-00-00', 0, 0, 0),
(5, '7790240032222', 'Cerveza Quilmes Clásica 473ml', '', 1, 1, 'unitario', 945.00, 1470.00, 0.00, 85.000, 24.000, 'uploads/prod_1770255503_324.png', 1, 0, 1, 1, '0000-00-00', 0, 0, 0),
(6, '7792799000011', 'Agua Mineral Villavicencio 1.5L', '', 1, 1, 'unitario', 630.00, 1155.00, 0.00, 48.000, 10.000, 'uploads/prod_1770255428_805.png', 0, 1, 1, 1, '0000-00-00', 0, 0, 0),
(7, '7791234567890', 'Monster Energy Green 473ml', '', 1, 1, 'unitario', 1155.00, 1890.00, 0.00, 6.000, 6.000, 'uploads/prod_1770255423_790.png', 1, 0, 1, 1, '0000-00-00', 0, 0, 0),
(8, '7790580123456', 'Alfajor Guaymallén Dulce de Leche', '', 2, 1, 'unitario', 315.00, 630.00, 0.00, 183.000, 24.000, 'uploads/prod_1770255417_877.png', 0, 0, 0, 1, '0000-00-00', 0, 0, 0),
(9, '7790580999999', 'Alfajor Jorgito Chocolate', '', 2, 1, 'unitario', 630.00, 1050.00, 0.00, 33.000, 12.000, 'uploads/prod_1770255413_500.png', 0, 0, 0, 1, '0000-00-00', 0, 0, 0),
(10, '7790060023654', 'Chocolate Milka Leger 100g', '', 2, 1, 'unitario', 1575.00, 2625.00, 0.00, 28.000, 5.000, 'uploads/prod_1770255406_507.png', 0, 1, 0, 1, '0000-00-00', 0, 0, 0),
(11, '7790456000021', 'Pastillas DRF Menta', '', 2, 1, 'unitario', 210.00, 420.00, 0.00, 87.000, 10.000, 'uploads/prod_1770255400_348.png', 0, 1, 1, 1, '0000-00-00', 0, 0, 0),
(12, '7791111222233', 'Turrón Arcor Misky', '', 2, 1, 'unitario', 157.50, 315.00, 0.00, 493.000, 50.000, 'uploads/prod_1770255394_844.png', 0, 0, 1, 1, '0000-00-00', 0, 0, 0),
(13, '7790999000111', 'Chicle Beldent Menta 8u', '', 2, 1, 'unitario', 420.00, 840.00, 0.00, 58.000, 20.000, 'uploads/prod_1770255584_118.png', 1, 1, 1, 1, '0000-00-00', 0, 0, 0),
(14, '7790040866666', 'Papas Fritas Lays Clásicas 85g', '', 3, 1, 'unitario', 1260.00, 2205.00, 0.00, 10.000, 10.000, 'uploads/prod_1770255381_602.png', 0, 1, 1, 1, '0000-00-00', 0, 0, 0),
(15, '7790040855555', 'Doritos Queso 85g', '', 3, 1, 'unitario', 1365.00, 2415.00, 0.00, 3.000, 10.000, 'uploads/prod_1770255375_280.png', 0, 0, 0, 1, '0000-00-00', 0, 0, 0),
(16, '7794444555566', 'Yerba Playadito 500g', '', 3, 1, 'unitario', 1890.00, 2940.00, 1500.00, 34.000, 10.000, 'uploads/prod_1770255364_257.png', 1, 1, 1, 1, '0000-00-00', 0, 0, 0),
(17, '7792222333344', 'Galletitas 9 de Oro Clásicas', '', 3, 1, 'unitario', 840.00, 2940.00, 0.00, 5.000, 5.000, 'uploads/prod_1770253556_135.png', 0, 0, 1, 1, '2026-03-07', 40, 1, 1),
(18, '7790000000001', 'Marlboro Box 20', '', 4, 1, 'unitario', 2625.00, 3360.00, 0.00, 84.000, 20.000, 'uploads/prod_1770255357_603.png', 0, 0, 0, 1, '0000-00-00', 0, 0, 0),
(19, '7790000000002', 'Philip Morris Box 20', '', 4, 1, 'unitario', 2415.00, 3045.00, 0.00, 76.000, 20.000, 'uploads/prod_1770252536_715.png', 0, 0, 0, 1, '0000-00-00', 0, 0, 0),
(20, '7790000000003', 'Camel Box 20', '', 4, 2, 'unitario', 2500.00, 5200.00, 1800.00, 34.000, 5.000, 'uploads/prod_1770252839_949.png', 1, 0, 0, 1, '0000-00-00', 0, 1, 1),
(21, '7790000000004', 'Chesterfield Box 20', '', 4, 1, 'unitario', 525.00, 2100.00, 1200.00, 39.000, 5.000, 'uploads/prod_1770253452_530.png', 0, 0, 0, 1, '2026-02-13', 5, 1, 1),
(39, 'COMBO-1770600377', 'PACK2', '', 2, 1, 'combo', 0.00, 21000.00, 18000.00, -3.000, 5.000, 'uploads/combo_1770601303.png', 1, 0, 0, 1, '0000-00-00', 0, 0, 0);

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
(1, 'Coca-Cola Oficial', 'Juan Repartidor', '1166116861', '', '', 'Lunes'),
(2, 'Mayorista Arcoiris', 'Pedro Ventas', '', '', '', 'Jueves'),
(3, 'La Serenísima', 'Carlos Leche', '', '', '', 'Martes');

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
(6, 39),
(1, 40),
(2, 40);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sorteos`
--

CREATE TABLE `sorteos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_sorteo` datetime DEFAULT NULL,
  `precio_ticket` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cantidad_tickets` int(11) NOT NULL DEFAULT 100,
  `estado` enum('activo','finalizado','cancelado') DEFAULT 'activo',
  `ganadores_json` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sorteos`
--

INSERT INTO `sorteos` (`id`, `titulo`, `descripcion`, `fecha_creacion`, `fecha_sorteo`, `precio_ticket`, `cantidad_tickets`, `estado`, `ganadores_json`) VALUES
(1, 'Prueba ', NULL, '2026-02-12 08:07:11', '2026-02-12 00:00:00', 1000.00, 2, 'finalizado', '[{\"posicion\":1,\"premio\":\"PACK2\",\"cliente\":\"Fede\",\"ticket\":1}]');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sorteo_premios`
--

CREATE TABLE `sorteo_premios` (
  `id` int(11) NOT NULL,
  `id_sorteo` int(11) NOT NULL,
  `posicion` int(11) NOT NULL COMMENT '1=1er premio, etc',
  `tipo` enum('interno','externo') DEFAULT 'interno',
  `id_producto` int(11) DEFAULT NULL,
  `descripcion_externa` varchar(255) DEFAULT NULL,
  `cantidad` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sorteo_premios`
--

INSERT INTO `sorteo_premios` (`id`, `id_sorteo`, `posicion`, `tipo`, `id_producto`, `descripcion_externa`, `cantidad`) VALUES
(1, 1, 1, 'interno', 39, NULL, 1),
(2, 1, 2, 'interno', 4, NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sorteo_tickets`
--

CREATE TABLE `sorteo_tickets` (
  `id` int(11) NOT NULL,
  `id_sorteo` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `numero_ticket` int(11) NOT NULL,
  `fecha_compra` datetime DEFAULT current_timestamp(),
  `pagado` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sorteo_tickets`
--

INSERT INTO `sorteo_tickets` (`id`, `id_sorteo`, `id_cliente`, `numero_ticket`, `fecha_compra`, `pagado`) VALUES
(1, 1, 2, 1, '2026-02-13 18:44:30', 1);

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
(1, 'Administrador', 'admin', '$2y$10$zqfetwARANGfV8BYfc8ay.uBb6AlhpffvjiFC.eZYHtfUqKxRMHke', 1, 1, '0000-00-00 00:00:00', 'gonzalezmarcelo159@gmail.com', '1166116861', 'user_1_1770284693.jpg', 'light', '', 'empleado', 0, '0000-00-00 00:00:00', ''),
(2, 'empleado', 'empleado', '$2y$10$ZgnIc5xKurJiIgVF4hr5aek2tfCOiKC3AS9yvsUNXLQFkf33D6H..', 3, 1, '0000-00-00 00:00:00', '', '', '', 'light', '', 'empleado', 0, '0000-00-00 00:00:00', ''),
(3, 'peca', 'peca', '$2y$10$Mo5yZele1Xnv8.cMpX.rsuZU9qj7kRPm3.Mx/QfDbQOED/APSijYG', 2, 1, '0000-00-00 00:00:00', '', '', '', 'light', '', 'empleado', 0, '0000-00-00 00:00:00', ''),
(4, 'Emplado22', 'Emp2', '$2y$10$uY4FPI9TNK6nELw7bDScJOR7K5TNyrSn3J7VD0nOPo3DxXDOEyzZu', 3, 1, '0000-00-00 00:00:00', '', '', '', 'light', '', 'empleado', 0, '0000-00-00 00:00:00', '');

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
(1, '', 3, 1, 1, '2026-02-12 00:18:36', 12075.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(2, NULL, 4, 1, 1, '2026-02-12 00:44:05', 1470.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(3, NULL, 5, 1, 2, '2026-02-12 00:48:01', 12075.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local'),
(4, 'RIFA-1-NUM-1', 6, 1, 2, '2026-02-13 18:44:30', 1000.00, 0.00, 0.00, NULL, 'Efectivo', 'completada', 'local');

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
-- Indices de la tabla `sorteos`
--
ALTER TABLE `sorteos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `sorteo_premios`
--
ALTER TABLE `sorteo_premios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_sorteo` (`id_sorteo`);

--
-- Indices de la tabla `sorteo_tickets`
--
ALTER TABLE `sorteo_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_sorteo` (`id_sorteo`),
  ADD KEY `id_cliente` (`id_cliente`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `bienes_uso`
--
ALTER TABLE `bienes_uso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `cajas_sesion`
--
ALTER TABLE `cajas_sesion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `devoluciones`
--
ALTER TABLE `devoluciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `encuestas`
--
ALTER TABLE `encuestas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `gastos`
--
ALTER TABLE `gastos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `mermas`
--
ALTER TABLE `mermas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `movimientos_cc`
--
ALTER TABLE `movimientos_cc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `movimientos_proveedores`
--
ALTER TABLE `movimientos_proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `pagos_ventas`
--
ALTER TABLE `pagos_ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

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
-- AUTO_INCREMENT de la tabla `sorteos`
--
ALTER TABLE `sorteos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `sorteo_premios`
--
ALTER TABLE `sorteo_premios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `sorteo_tickets`
--
ALTER TABLE `sorteo_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- Filtros para la tabla `sorteo_premios`
--
ALTER TABLE `sorteo_premios`
  ADD CONSTRAINT `sorteo_premios_ibfk_1` FOREIGN KEY (`id_sorteo`) REFERENCES `sorteos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `sorteo_tickets`
--
ALTER TABLE `sorteo_tickets`
  ADD CONSTRAINT `sorteo_tickets_ibfk_1` FOREIGN KEY (`id_sorteo`) REFERENCES `sorteos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sorteo_tickets_ibfk_2` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`);

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
