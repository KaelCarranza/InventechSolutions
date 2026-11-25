-- InvenTech Solutions
-- Cree una base vacía con utf8mb4, selecciónela en phpMyAdmin e importe este archivo.
-- Configure el nombre de la base en config/db.php si es diferente.
-- Registre la primera cuenta con una contraseña propia y asigne Administrador
-- a esa cuenta desde phpMyAdmin con acceso autorizado al servidor.
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

CREATE TABLE `movimientosinventario` (
  `idMovimiento` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `idProducto` int UNSIGNED DEFAULT NULL,
  `skuProducto` varchar(50) NOT NULL,
  `nombreProducto` varchar(150) NOT NULL,
  `userId` int UNSIGNED DEFAULT NULL,
  `correoAdministrador` varchar(100) NOT NULL,
  `idPedido` int UNSIGNED DEFAULT NULL,
  `Tipo` enum('Entrada','Salida') NOT NULL,
  `Cantidad` int UNSIGNED NOT NULL,
  `cantidadAnterior` int UNSIGNED NOT NULL,
  `cantidadResultante` int UNSIGNED NOT NULL,
  `Motivo` varchar(255) NOT NULL,
  `claveMovimiento` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `fechaMovimiento` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idMovimiento`),
  UNIQUE KEY `uq_movimiento_clave` (`claveMovimiento`),
  UNIQUE KEY `uq_movimiento_pedido_producto_tipo` (`idPedido`, `idProducto`, `Tipo`),
  CONSTRAINT `fk_movimiento_producto` FOREIGN KEY (`idProducto`) REFERENCES `productos` (`idProducto`) ON DELETE SET NULL,
  CONSTRAINT `fk_movimiento_usuario` FOREIGN KEY (`userId`) REFERENCES `users` (`userId`) ON DELETE SET NULL,
  CONSTRAINT `fk_movimiento_pedido` FOREIGN KEY (`idPedido`) REFERENCES `pedidos` (`idPedido`),
  CONSTRAINT `chk_movimiento_cantidad` CHECK (`Cantidad` > 0),
  CONSTRAINT `chk_movimiento_saldo` CHECK (
    (`Tipo` = 'Entrada' AND `cantidadResultante` = `cantidadAnterior` + `Cantidad`)
    OR (`Tipo` = 'Salida' AND `cantidadAnterior` = `cantidadResultante` + `Cantidad`)
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `proformas` (
  `idProforma` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `userId` int UNSIGNED DEFAULT NULL,
  `nombreSolicitante` varchar(150) NOT NULL,
  `correoSolicitante` varchar(255) NOT NULL,
  `fechaEmision` datetime NOT NULL DEFAULT current_timestamp(),
  `fechaVencimiento` date NOT NULL,
  `Subtotal` decimal(10,2) UNSIGNED NOT NULL,
  `tasaImpuesto` decimal(5,2) UNSIGNED NOT NULL,
  `Impuestos` decimal(10,2) UNSIGNED NOT NULL,
  `Total` decimal(10,2) UNSIGNED NOT NULL,
  `Estado` enum('Vigente','Cancelada') NOT NULL DEFAULT 'Vigente',
  `claveProforma` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`idProforma`),
  UNIQUE KEY `uq_proforma_clave` (`claveProforma`),
  KEY `idx_proforma_emision` (`fechaEmision`),
  KEY `idx_proforma_estado_vencimiento` (`Estado`, `fechaVencimiento`),
  CONSTRAINT `fk_proforma_usuario` FOREIGN KEY (`userId`) REFERENCES `users` (`userId`) ON DELETE SET NULL,
  CONSTRAINT `chk_proforma_vigencia` CHECK (`fechaVencimiento` >= DATE(`fechaEmision`)),
  CONSTRAINT `chk_proforma_importes` CHECK (`tasaImpuesto` <= 100 AND `Total` = `Subtotal` + `Impuestos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `detalleproforma` (
  `idDetalle` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `idProforma` int UNSIGNED NOT NULL,
  `idProducto` int UNSIGNED DEFAULT NULL,
  `nombreProducto` varchar(150) NOT NULL,
  `precioUnitario` decimal(10,2) UNSIGNED NOT NULL,
  `cantidad` int UNSIGNED NOT NULL,
  `subtotal` decimal(10,2) UNSIGNED NOT NULL,
  PRIMARY KEY (`idDetalle`),
  UNIQUE KEY `uq_detalle_proforma_producto` (`idProforma`, `idProducto`),
  CONSTRAINT `fk_detalle_proforma` FOREIGN KEY (`idProforma`) REFERENCES `proformas` (`idProforma`),
  CONSTRAINT `fk_detalle_proforma_producto` FOREIGN KEY (`idProducto`) REFERENCES `productos` (`idProducto`) ON DELETE SET NULL,
  CONSTRAINT `chk_detalle_proforma_importe` CHECK (`cantidad` > 0 AND `subtotal` = `precioUnitario` * `cantidad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `bitacora` (
  `idBitacora` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `idAdministrador` int UNSIGNED DEFAULT NULL,
  `idAdministradorHistorico` int UNSIGNED NOT NULL,
  `correoAdministrador` varchar(100) NOT NULL,
  `Modulo` varchar(30) NOT NULL,
  `Accion` varchar(30) NOT NULL,
  `idRegistro` int UNSIGNED DEFAULT NULL,
  `Descripcion` varchar(255) NOT NULL,
  `fechaRegistro` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idBitacora`),
  KEY `idx_bitacora_fecha` (`fechaRegistro`, `idBitacora`),
  CONSTRAINT `fk_bitacora_administrador` FOREIGN KEY (`idAdministrador`) REFERENCES `users` (`userId`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
