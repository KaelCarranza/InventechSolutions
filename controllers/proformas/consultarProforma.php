<?php

const ESTADO_PROFORMA_SQL = "CASE WHEN Estado = 'Cancelada' THEN 'Cancelada'
    WHEN fechaVencimiento < CURRENT_DATE() THEN 'Vencida' ELSE 'Vigente' END";

function consultarProforma(PDO $conexion, int $id): ?array {
    $consulta = $conexion->prepare("SELECT *, " . ESTADO_PROFORMA_SQL . " AS estadoActual FROM proformas WHERE idProforma = ?");
    $consulta->execute([$id]);
    $proforma = $consulta->fetch(PDO::FETCH_ASSOC);
    if (!$proforma) {
        return null;
    }

    $consulta = $conexion->prepare("
        SELECT nombreProducto, precioUnitario, cantidad, subtotal
        FROM detalleproforma WHERE idProforma = ? ORDER BY idDetalle
    ");
    $consulta->execute([$id]);
    $proforma["lineas"] = $consulta->fetchAll(PDO::FETCH_ASSOC);
    return $proforma;
}
