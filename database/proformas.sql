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
