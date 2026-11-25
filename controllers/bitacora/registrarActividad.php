<?php

function registrarActividad(
    PDO $conexion,
    string $modulo,
    string $accion,
    ?int $idRegistro,
    string $descripcion
): void {
    if (!$conexion->inTransaction()) {
        throw new LogicException("La actividad debe guardarse en la transacción de la operación.");
    }


    $administrador = (int) $_SESSION["userId"];
    $insertar = $conexion->prepare("
        INSERT INTO bitacora (
            idAdministrador, idAdministradorHistorico, correoAdministrador,
            Modulo, Accion, idRegistro, Descripcion
        )
        VALUES (?, ?, (SELECT email FROM users WHERE userId = ?), ?, ?, ?, ?)
    ");
    $insertar->execute([$administrador, $administrador, $administrador, $modulo, $accion, $idRegistro, $descripcion]);
}
