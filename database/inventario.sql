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
