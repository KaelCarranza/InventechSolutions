<?php

// Bloquear administradores antes de cambiar la cuenta
function protegerUltimoAdministrador(PDO $conexion, int $idUsuario): void {
    $consulta = $conexion->query("
        SELECT userId FROM users
        WHERE role = 'Administrador' AND loginStatus = 'Habilitado'
        ORDER BY userId FOR UPDATE
    ");
    $administradores = $consulta->fetchAll(PDO::FETCH_COLUMN);
    if (count($administradores) === 1 && (int) $administradores[0] === $idUsuario) {
        throw new DomainException("Debe quedar al menos un administrador habilitado.");
    }
}
