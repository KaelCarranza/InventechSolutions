-- Esquema de Kael, exportado desde MariaDB 10.11.16.
-- Ejecutar en una base vacía seleccionada explícitamente.


CREATE TABLE `categorias` (
  `idCategoria` int(10) UNSIGNED NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `Descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `detallepedido` (
  `idDetalle` int(10) UNSIGNED NOT NULL,
  `idPedido` int(10) UNSIGNED NOT NULL,
  `idProducto` int(10) UNSIGNED NOT NULL,
  `nombreProducto` varchar(150) NOT NULL,
  `precioUnitario` decimal(10,2) NOT NULL,
  `cantidad` int(10) UNSIGNED NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pedidos` (
  `idPedido` int(10) UNSIGNED NOT NULL,
  `userId` int(10) UNSIGNED NOT NULL,
  `Total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Estado` enum('Pendiente','Pagado','Enviado','Entregado','Cancelado') NOT NULL DEFAULT 'Pendiente',
  `fechaPedido` datetime NOT NULL DEFAULT current_timestamp(),
  `metodoPago` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `userId` int(10) UNSIGNED NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Administrador','Cliente') NOT NULL DEFAULT 'Cliente',
  `loginStatus` enum('Habilitado','Deshabilitado') NOT NULL DEFAULT 'Habilitado',
  `registerDate` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `categorias`
  ADD PRIMARY KEY (`idCategoria`),
  ADD UNIQUE KEY `Nombre` (`Nombre`);

ALTER TABLE `detallepedido`
  ADD PRIMARY KEY (`idDetalle`),
  ADD KEY `idx_detalle_pedido` (`idPedido`),
  ADD KEY `idx_detalle_producto` (`idProducto`);

ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`idPedido`),
  ADD KEY `idx_pedido_usuario` (`userId`),
  ADD KEY `idx_pedido_estado` (`Estado`);

ALTER TABLE `productos`
  ADD PRIMARY KEY (`idProducto`),
  ADD UNIQUE KEY `uq_productos_sku` (`SKU`),
  ADD KEY `idx_producto_categoria` (`idCategoria`),
  ADD KEY `idx_producto_nombre` (`Producto`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`userId`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `categorias`
  MODIFY `idCategoria` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `detallepedido`
  MODIFY `idDetalle` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `pedidos`
  MODIFY `idPedido` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `productos`
  MODIFY `idProducto` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `userId` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `detallepedido`
  ADD CONSTRAINT `fk_detalle_pedido` FOREIGN KEY (`idPedido`) REFERENCES `pedidos` (`idPedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`idProducto`) REFERENCES `productos` (`idProducto`) ON UPDATE CASCADE;

ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedido_usuario` FOREIGN KEY (`userId`) REFERENCES `users` (`userId`) ON UPDATE CASCADE;

ALTER TABLE `productos`
  ADD CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`idCategoria`) REFERENCES `categorias` (`idCategoria`) ON DELETE SET NULL ON UPDATE CASCADE;
