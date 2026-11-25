<?php

require_once __DIR__ . "/../inventario/movimientos.php";

function cambiarEstadoPedido(PDO $conexion, int $idPedido, int $administrador, string $nuevo, array $transiciones): string {
    $consulta = $conexion->prepare("SELECT Estado FROM pedidos WHERE idPedido = ? FOR UPDATE");
    $consulta->execute([$idPedido]);
    $actual = $consulta->fetchColumn();

    if ($actual === false) {
        throw new InvalidArgumentException("El pedido no existe.");
    }
    if (!in_array($nuevo, $transiciones[$actual] ?? [], true)) {
        throw new InvalidArgumentException("El estado actual del pedido no permite ese cambio.");
    }

    $tipo = "";
    if ($nuevo === "Pagado") {
        $tipo = "Salida";
        $consulta = $conexion->prepare("
            SELECT idProducto, SUM(cantidad) AS cantidad FROM detallepedido
            WHERE idPedido = ? GROUP BY idProducto ORDER BY idProducto
        ");
    } elseif ($actual === "Pagado" && $nuevo === "Cancelado") {
        $tipo = "Entrada";
        $consulta = $conexion->prepare("
            SELECT idProducto, Cantidad AS cantidad FROM movimientosinventario
            WHERE idPedido = ? AND Tipo = 'Salida' ORDER BY idProducto
        ");
    }

    if ($tipo !== "") {
        $consulta->execute([$idPedido]);
        $lineas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        if ($tipo === "Salida" && !$lineas) {
            throw new InvalidArgumentException("El pedido no tiene productos que descontar.");
        }
        $motivo = $tipo === "Salida" ? "Pago del pedido #$idPedido" : "Cancelación del pedido #$idPedido";
        foreach ($lineas as $linea) {
            $idProducto = (int) $linea["idProducto"];
            aplicarMovimientoInventario(
                $conexion, $idProducto, $administrador, $tipo, (int) $linea["cantidad"],
                $motivo, bin2hex(random_bytes(32)), $idPedido
            );
        }
    }

    $actualizar = $conexion->prepare("UPDATE pedidos SET Estado = ? WHERE idPedido = ?");
    $actualizar->execute([$nuevo, $idPedido]);

    return $actual;
}
