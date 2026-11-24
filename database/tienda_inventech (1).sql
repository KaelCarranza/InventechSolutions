-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 24-11-2026 a las 23:01:52
-- Versión del servidor: 10.11.16-MariaDB
-- Versión de PHP: 8.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `tienda_inventech`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `idCategoria` int(10) UNSIGNED NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `Descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`idCategoria`, `Nombre`, `Descripcion`) VALUES
(1, 'Tarjetas de video', 'GPUs para gaming y diseno'),
(2, 'Procesadores', 'CPUs de escritorio'),
(3, 'Memoria RAM', 'Modulos DDR4 y DDR5'),
(4, 'Almacenamiento', 'SSD y discos duros'),
(5, 'Perifericos', 'Teclados, mouse y monitores'),
(10, 'Tarjetas Madre', 'mb');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detallepedido`
--

CREATE TABLE `detallepedido` (
  `idDetalle` int(10) UNSIGNED NOT NULL,
  `idPedido` int(10) UNSIGNED NOT NULL,
  `idProducto` int(10) UNSIGNED NOT NULL,
  `nombreProducto` varchar(150) NOT NULL,
  `precioUnitario` decimal(10,2) NOT NULL,
  `cantidad` int(10) UNSIGNED NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ;

--
-- Volcado de datos para la tabla `detallepedido`
--

INSERT INTO `detallepedido` (`idDetalle`, `idPedido`, `idProducto`, `nombreProducto`, `precioUnitario`, `cantidad`, `subtotal`) VALUES
(1, 1, 1, 'Tarjeta de video RTX 4060 8GB', 329.99, 1, 329.99),
(2, 1, 10, 'Procesador Ryzen 7 5700X3D', 220000.00, 1, 220000.00),
(3, 2, 10, 'Procesador Ryzen 7 5700X3D', 220000.00, 1, 220000.00),
(4, 3, 10, 'Procesador Ryzen 7 5700X3D', 220000.00, 1, 220000.00),
(5, 4, 10, 'Procesador Ryzen 7 5700X3D', 220000.00, 1, 220000.00),
(6, 5, 10, 'Procesador Ryzen 7 5700X3D', 220000.00, 1, 220000.00),
(7, 6, 1, 'Tarjeta de video RTX 4060 8GB', 329.99, 7, 2309.93);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `idPedido` int(10) UNSIGNED NOT NULL,
  `userId` int(10) UNSIGNED NOT NULL,
  `Total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Estado` enum('Pendiente','Pagado','Enviado','Entregado','Cancelado') NOT NULL DEFAULT 'Pendiente',
  `fechaPedido` datetime NOT NULL DEFAULT current_timestamp(),
  `metodoPago` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`idPedido`, `userId`, `Total`, `Estado`, `fechaPedido`, `metodoPago`) VALUES
(1, 1, 220329.99, 'Pagado', '2026-11-22 22:54:47', 'Tarjeta'),
(2, 1, 220000.00, 'Pagado', '2026-11-22 22:55:16', 'Efectivo'),
(3, 1, 220000.00, 'Pagado', '2026-11-22 22:55:28', 'Efectivo'),
(4, 1, 220000.00, 'Pagado', '2026-11-22 22:56:51', 'Tarjeta'),
(5, 1, 220000.00, 'Pagado', '2026-11-22 22:56:56', 'Efectivo'),
(6, 1, 2309.93, 'Pagado', '2026-11-22 22:58:30', 'Efectivo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `idProducto` int(10) UNSIGNED NOT NULL,
  `idCategoria` int(10) UNSIGNED DEFAULT NULL,
  `SKU` varchar(50) NOT NULL,
  `Producto` varchar(150) NOT NULL,
  `Precio` decimal(10,2) NOT NULL,
  `Cantidad` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `Descripcion` text DEFAULT NULL,
  `Imagen` varchar(255) DEFAULT NULL,
  `Activo` tinyint(1) NOT NULL DEFAULT 1,
  `fechaRegistro` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`idProducto`, `idCategoria`, `SKU`, `Producto`, `Precio`, `Cantidad`, `Descripcion`, `Imagen`, `Activo`, `fechaRegistro`) VALUES
(1, 1, 'GPU-RTX-4060', 'Tarjeta de video RTX 4060 8GB', 329.99, 0, 'GPU 8GB GDDR6, ideal para 1080p', 'fcc5581b8d176c8c0cd8afc4f113336c.jpg', 1, '2026-11-21 22:11:54'),
(10, 2, 'CPU-R-7', 'Procesador Ryzen 7 5700X3D', 220000.00, 0, 'Procesador Ryzen 7', '06d05e95e247f1fddc40f66de33b341b.jpg', 1, '2026-11-22 00:12:26'),
(11, 2, 'CPU-INT-7', 'Procesador Intel Core Ultra 7 265K / 20 Nucleos / 3.9 GHz / Intel Graphics', 175000.00, 5, 'intel', '3d2d11dd958bba410dadc4e925f4dcfb.png', 1, '2026-11-23 23:37:58'),
(12, 3, 'RAM-DDR4', 'Kingston FURY Desktop Gaming Memory RAM DDR4 3200MT/s 16GB(2x8GB) FURY Beast RGB CL16', 220000.00, 10, 'Ram', 'c893d975cc602ec44d0d95cde5703fb6.webp', 1, '2026-11-23 23:39:48'),
(13, 10, 'MB-AMD-B550', 'Tarjeta Madre MSI B550M PRO-VHD', 45000.00, 10, 'Tarjeta madre msi', 'e66b928a40ea56fae0ebf820430993e5.png', 1, '2026-11-24 11:35:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `userId` int(10) UNSIGNED NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Administrador','Cliente') NOT NULL DEFAULT 'Cliente',
  `loginStatus` enum('Habilitado','Deshabilitado') NOT NULL DEFAULT 'Habilitado',
  `registerDate` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`userId`, `email`, `password`, `role`, `loginStatus`, `registerDate`) VALUES
(1, 'admin@inventech.com', '$2y$12$ATXx1TrJs6zK4v1jymetxu66uYFYnn9TzoXLxySJA473AslnfK.FK', 'Administrador', 'Habilitado', '2026-11-21 22:11:54'),
(2, 'cliente@inventech.com', '$2y$12$HSNQme8cwSDHJEW.Ts4tWOaxcOOhdAApcUUsGA.CTiiBD6yh3c8R.', 'Cliente', 'Habilitado', '2026-11-21 22:14:29');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`idCategoria`),
  ADD UNIQUE KEY `Nombre` (`Nombre`);

--
-- Indices de la tabla `detallepedido`
--
ALTER TABLE `detallepedido`
  ADD PRIMARY KEY (`idDetalle`),
  ADD KEY `idx_detalle_pedido` (`idPedido`),
  ADD KEY `idx_detalle_producto` (`idProducto`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`idPedido`),
  ADD KEY `idx_pedido_usuario` (`userId`),
  ADD KEY `idx_pedido_estado` (`Estado`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`idProducto`),
  ADD UNIQUE KEY `uq_productos_sku` (`SKU`),
  ADD KEY `idx_producto_categoria` (`idCategoria`),
  ADD KEY `idx_producto_nombre` (`Producto`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userId`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `idCategoria` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `detallepedido`
--
ALTER TABLE `detallepedido`
  MODIFY `idDetalle` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `idPedido` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `idProducto` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `userId` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detallepedido`
--
ALTER TABLE `detallepedido`
  ADD CONSTRAINT `fk_detalle_pedido` FOREIGN KEY (`idPedido`) REFERENCES `pedidos` (`idPedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`idProducto`) REFERENCES `productos` (`idProducto`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedido_usuario` FOREIGN KEY (`userId`) REFERENCES `users` (`userId`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`idCategoria`) REFERENCES `categorias` (`idCategoria`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
