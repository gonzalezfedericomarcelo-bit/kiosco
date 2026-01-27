-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 27-01-2026 a las 00:32:41
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
(1, '2026-01-26 15:13:44', 1, 'AJUSTE_STOCK', 'Cambio de Stock: De 15.000 a 5.000. Producto: PROCUCTO 1 CELIACO');

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
  `notas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `monto_final_declarado` decimal(12,2) DEFAULT NULL,
  `diferencia` decimal(12,2) DEFAULT NULL,
  `estado` enum('abierta','cerrada') DEFAULT 'abierta'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `saldo_actual` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombre`, `dni_cuit`, `email`, `password`, `telefono`, `direccion`, `foto_perfil`, `fecha_nacimiento`, `recibir_notificaciones`, `limite_credito`, `saldo_deudor`, `puntos_acumulados`, `fecha_registro`, `whatsapp`, `dni`, `saldo_actual`) VALUES
(1, 'Consumidor Final', '00000000', NULL, NULL, NULL, NULL, 'default_user.png', NULL, 1, 0.00, 0.00, 0, '2026-01-26 11:15:20', NULL, NULL, 0.00),
(3, 'Juan Perez', NULL, 'gonzalezmarcelo159@gmail.com', NULL, NULL, 'Teniente Primero Bustos Manuel Oscar, 370 Viviendas III Etapa, Alto Comedero, Municipio de San Salvador de Jujuy, Departamento Doctor Manuel Belgrano, Jujuy, Y4600AXX, Argentina', 'default_user.png', NULL, 1, 0.00, 0.00, 0, '2026-01-26 16:43:33', '+5491166116861', '35911753', 0.00);

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
  `dias_alerta_vencimiento` int(11) DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuracion`
--

INSERT INTO `configuracion` (`id`, `nombre_negocio`, `telefono_whatsapp`, `email_notificaciones`, `direccion_local`, `color_barra_nav`, `color_botones`, `color_fondo`, `logo_url`, `modulo_clientes`, `modulo_stock`, `modulo_reportes`, `modulo_presupuesto`, `modulo_fidelizacion`, `cuit`, `mensaje_ticket`, `color_secundario`, `direccion_degradado`, `dias_alerta_vencimiento`) VALUES
(1, 'Peca\'s', '5491166116861', 'gonzalezfedericomarcelo@gmail.com', 'Av. Siempre Viva 123', '#2754dd', '#4c83f0', '#ffffff', 'uploads/logo_1769457754.png', 1, 1, 1, 1, 0, '', '', '#6c6afb', 'to bottom', 28);

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
  `id_cliente` int(11) DEFAULT NULL COMMENT 'NULL es para todos'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cupones`
--

INSERT INTO `cupones` (`id`, `codigo`, `descuento_porcentaje`, `activo`, `fecha_limite`, `id_cliente`) VALUES
(1, 'HOLA10', 10, 1, NULL, NULL),
(2, 'VERANO', 5, 1, '2026-01-31', 3);

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
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `detalle_ventas`
--

INSERT INTO `detalle_ventas` (`id`, `id_venta`, `id_producto`, `cantidad`, `precio_historico`, `subtotal`) VALUES
(4, 4, 19, 1.000, 2900.00, 2900.00),
(5, 5, 6, 1.000, 1100.00, 1100.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `encuestas`
--

CREATE TABLE `encuestas` (
  `id` int(11) NOT NULL,
  `id_venta` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `nivel_satisfaccion` enum('enojado','triste','neutral','feliz','enamorado') NOT NULL,
  `comentario` text DEFAULT NULL,
  `es_anonimo` tinyint(1) DEFAULT 0,
  `leido_por_dueno` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `id` int(11) NOT NULL,
  `clave` varchar(50) NOT NULL,
  `descripcion` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `dias_alerta` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `codigo_barras`, `descripcion`, `descripcion_larga`, `id_categoria`, `id_proveedor`, `tipo`, `precio_costo`, `precio_venta`, `precio_oferta`, `stock_actual`, `stock_minimo`, `imagen_url`, `es_destacado_web`, `es_apto_celiaco`, `es_apto_vegano`, `activo`, `fecha_vencimiento`, `dias_alerta`) VALUES
(2, '7790895000997', 'Coca-Cola Sabor Original 2.25L', NULL, 1, 1, 'unitario', 1800.00, 2600.00, NULL, 50.000, 10.000, 'https://jumboargentina.vtexassets.com/arquivos/ids/767936/Coca-cola-Sabor-Original-2-25-L-1-844274.jpg', 1, 1, 1, 1, NULL, NULL),
(3, '7790895001000', 'Coca-Cola Zero 2.25L', NULL, 1, 1, 'unitario', 1800.00, 2600.00, NULL, 15.000, 5.000, 'https://jumboargentina.vtexassets.com/arquivos/ids/767946/Coca-cola-Sabor-Original-Sin-Azucar-2-25-L-1-844284.jpg', 0, 1, 1, 1, NULL, NULL),
(4, '7790895066665', 'Fernet Branca 750ml', NULL, 1, 1, 'unitario', 7500.00, 11500.00, NULL, 120.000, 10.000, 'https://jumboargentina.vtexassets.com/arquivos/ids/772459/Fernet-Branca-750-Cc-1-758950.jpg', 1, 1, 1, 1, NULL, NULL),
(5, '7790240032222', 'Cerveza Quilmes Clásica 473ml', NULL, 1, 1, 'unitario', 900.00, 1400.00, NULL, 4.000, 24.000, 'https://jumboargentina.vtexassets.com/arquivos/ids/772186/Cerveza-Quilmes-Clasica-Lata-473-Cc-1-21343.jpg', 0, 0, 1, 1, NULL, NULL),
(6, '7792799000011', 'Agua Mineral Villavicencio 1.5L', NULL, 1, 1, 'unitario', 600.00, 1100.00, NULL, 59.000, 10.000, 'https://jumboargentina.vtexassets.com/arquivos/ids/767784/Agua-Mineral-Sin-Gas-Villavicencio-1-5-L-1-10502.jpg', 0, 1, 1, 1, NULL, NULL),
(7, '7791234567890', 'Monster Energy Green 473ml', NULL, 1, 1, 'unitario', 1100.00, 1800.00, NULL, 8.000, 6.000, 'https://jumboargentina.vtexassets.com/arquivos/ids/767972/Energizante-Monster-Energy-Lata-473-Ml-1-817812.jpg', 0, 0, 1, 1, NULL, NULL),
(8, '7790580123456', 'Alfajor Guaymallén Dulce de Leche', NULL, 2, 1, 'unitario', 300.00, 600.00, NULL, 200.000, 24.000, 'https://d2r9epyceweg5n.cloudfront.net/stores/001/151/835/products/alfajor-guaymallen-blanco1-fa2b89694c925d48a515886241951564-640-0.jpg', 1, 0, 0, 1, NULL, NULL),
(9, '7790580999999', 'Alfajor Jorgito Chocolate', NULL, 2, 1, 'unitario', 600.00, 1000.00, NULL, 45.000, 12.000, 'https://acdn.mitiendanube.com/stores/001/151/835/products/jorgito-negro1-1033c5e884e1b4334f15886245366657-640-0.jpg', 1, 0, 0, 1, NULL, NULL),
(10, '7790060023654', 'Chocolate Milka Leger 100g', NULL, 2, 1, 'unitario', 1500.00, 2500.00, NULL, 30.000, 5.000, 'https://jumboargentina.vtexassets.com/arquivos/ids/771038/Chocolate-Con-Leche-Aireado-Milka-Leger-45-Gr-1-766723.jpg', 0, 1, 0, 1, NULL, NULL),
(11, '7790456000021', 'Pastillas DRF Menta', NULL, 2, 1, 'unitario', 200.00, 400.00, NULL, 100.000, 10.000, 'https://acdn.mitiendanube.com/stores/001/214/563/products/drf-menta1-2d744b13a7c36a461315925049363063-640-0.jpg', 0, 1, 1, 1, NULL, NULL),
(12, '7791111222233', 'Turrón Arcor Misky', NULL, 2, 1, 'unitario', 150.00, 300.00, NULL, 500.000, 50.000, 'https://d2r9epyceweg5n.cloudfront.net/stores/001/151/835/products/turron-arcor1-e0c5112df380a9967115886256248386-640-0.jpg', 0, 0, 1, 1, NULL, NULL),
(13, '7790999000111', 'Chicle Beldent Menta 8u', NULL, 2, 1, 'unitario', 400.00, 800.00, NULL, 60.000, 20.000, 'https://jumboargentina.vtexassets.com/arquivos/ids/769739/Chicles-Beldent-Menta-8-Un-1-4770.jpg', 0, 1, 1, 1, NULL, NULL),
(14, '7790040866666', 'Papas Fritas Lays Clásicas 85g', NULL, 3, 1, 'unitario', 1200.00, 2100.00, NULL, 12.000, 10.000, 'https://jumboargentina.vtexassets.com/arquivos/ids/770289/Papas-Fritas-Lays-Clasicas-85-Gr-1-235122.jpg', 1, 1, 1, 1, NULL, NULL),
(15, '7790040855555', 'Doritos Queso 85g', NULL, 3, 1, 'unitario', 1300.00, 2300.00, NULL, 5.000, 10.000, 'https://jumboargentina.vtexassets.com/arquivos/ids/770271/Snack-Doritos-Sabor-Queso-94-Gr-1-12797.jpg', 0, 0, 0, 1, NULL, NULL),
(16, '7794444555566', 'Yerba Playadito 500g', NULL, 3, 1, 'unitario', 1800.00, 2800.00, NULL, 40.000, 10.000, 'https://jumboargentina.vtexassets.com/arquivos/ids/767909/Yerba-Mate-Playadito-Suave-500-Gr-1-224467.jpg', 1, 1, 1, 1, NULL, NULL),
(17, '7792222333344', 'Galletitas 9 de Oro Clásicas', NULL, 3, 1, 'unitario', 800.00, 1400.00, NULL, 25.000, 5.000, 'https://jumboargentina.vtexassets.com/arquivos/ids/768406/Bizcochos-9-De-Oro-Clasicos-200-Gr-1-14051.jpg', 0, 0, 1, 1, '2026-03-07', 40),
(18, '7790000000001', 'Marlboro Box 20', NULL, 4, 1, 'unitario', 2500.00, 3200.00, NULL, 100.000, 20.000, 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/69/Marlboro_Logo.svg/1200px-Marlboro_Logo.svg.png', 0, 0, 0, 1, NULL, NULL),
(19, '7790000000002', 'Philip Morris Box 20', NULL, 4, 1, 'unitario', 2300.00, 2900.00, NULL, 79.000, 20.000, 'https://d2r9epyceweg5n.cloudfront.net/stores/001/214/563/products/philip-morris-box-201-9a7e6f8a42e185c69715925068991461-640-0.jpg', 0, 0, 0, 1, NULL, NULL),
(20, '7790000000003', 'Camel Box 20', NULL, 4, 1, 'unitario', 2500.00, 3200.00, NULL, 40.000, 10.000, 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/91/Camel_logo.svg/2560px-Camel_logo.svg.png', 0, 0, 0, 1, NULL, NULL),
(21, '7790000000004', 'Chesterfield Box 20', NULL, 4, 1, 'unitario', 2000.00, 2600.00, NULL, 50.000, 10.000, 'https://d2r9epyceweg5n.cloudfront.net/stores/001/214/563/products/chesterfield-comun-201-49666c8f8d689b9d3e15925063008479-640-0.jpg', 0, 0, 0, 1, NULL, NULL);

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
(1, 'Coca-Cola Oficial', 'Juan Repartidor', NULL, NULL, NULL, 'Lunes'),
(2, 'Mayorista Arcoiris', 'Pedro Ventas', NULL, NULL, NULL, 'Jueves'),
(3, 'La Serenísima', 'Carlos Leche', NULL, NULL, NULL, 'Martes');

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
(3, 'Empleado', 'Acceso limitado a ventas y caja');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol_permisos`
--

CREATE TABLE `rol_permisos` (
  `id_rol` int(11) NOT NULL,
  `id_permiso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `foto_perfil` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre_completo`, `usuario`, `password`, `id_rol`, `activo`, `ultimo_login`, `email`, `whatsapp`, `foto_perfil`) VALUES
(1, 'Dueño del Kiosco', 'admin', '$2y$10$zqfetwARANGfV8BYfc8ay.uBb6AlhpffvjiFC.eZYHtfUqKxRMHke', 1, 1, NULL, '', '', 'user_1_1769448922.jpg');

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
(4, NULL, 1, 1, 1, '2026-01-26 19:15:30', 2898.00, 0.00, 2.00, '', 'Efectivo', 'completada', 'local'),
(5, NULL, 1, 1, 1, '2026-01-26 19:18:02', 1100.00, 0.00, 0.00, '', 'Efectivo', 'completada', 'local');

--
-- Índices para tablas volcadas
--

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
  ADD UNIQUE KEY `email` (`email`);

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
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_venta` (`id_venta`);

--
-- Indices de la tabla `encuestas`
--
ALTER TABLE `encuestas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_venta` (`id_venta`),
  ADD KEY `id_cliente` (`id_cliente`);

--
-- Indices de la tabla `movimientos_cc`
--
ALTER TABLE `movimientos_cc`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_barras` (`codigo_barras`),
  ADD KEY `id_categoria` (`id_categoria`),
  ADD KEY `id_proveedor` (`id_proveedor`);

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
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_cliente` (`id_cliente`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `bienes_uso`
--
ALTER TABLE `bienes_uso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cajas_sesion`
--
ALTER TABLE `cajas_sesion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `cupones`
--
ALTER TABLE `cupones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `encuestas`
--
ALTER TABLE `encuestas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `movimientos_cc`
--
ALTER TABLE `movimientos_cc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- Filtros para la tabla `encuestas`
--
ALTER TABLE `encuestas`
  ADD CONSTRAINT `encuestas_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id`),
  ADD CONSTRAINT `encuestas_ibfk_2` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`);

--
-- Filtros para la tabla `movimientos_cc`
--
ALTER TABLE `movimientos_cc`
  ADD CONSTRAINT `movimientos_cc_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `movimientos_cc_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`);

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
