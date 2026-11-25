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
