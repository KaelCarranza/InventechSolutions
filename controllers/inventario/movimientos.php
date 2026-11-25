<?php

// Usar la transacción del controlador
function aplicarMovimientoInventario(
    PDO $conexion,
    int $idProducto,
    int $userId,
    string $tipo,
    int $cantidad,
    string $motivo,
    string $claveMovimiento,
    ?int $idPedido = null
): int {
    $motivo = trim($motivo);

    if (!$conexion->inTransaction()) {
        throw new LogicException("El movimiento requiere una transacción.");
    }

    if (
        $idProducto < 1 || $userId < 1 ||
        !in_array($tipo, ["Entrada", "Salida"], true) ||
        $cantidad < 1 || $cantidad > 4294967295 ||
        $motivo === "" || !mb_check_encoding($motivo, "UTF-8") || mb_strlen($motivo, "UTF-8") > 255 ||
        !preg_match('/\A[a-f0-9]{64}\z/', $claveMovimiento) ||
        ($idPedido !== null && $idPedido < 1)
    ) {
        throw new InvalidArgumentException("Revise el producto, el tipo, la cantidad y el motivo del movimiento.");
    }

    $consulta = $conexion->prepare("
        SELECT SKU, Producto, Cantidad FROM productos WHERE idProducto = ? FOR UPDATE
    ");
    $consulta->execute([$idProducto]);
    $producto = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        throw new InvalidArgumentException("El producto seleccionado no existe.");
    }

    if ($tipo === "Entrada" && $idPedido !== null) {
        $salida = $conexion->prepare("
            SELECT Cantidad FROM movimientosinventario
            WHERE idPedido = ? AND idProducto = ? AND Tipo = 'Salida'
        ");
        $salida->execute([$idPedido, $idProducto]);

        if ((int) $salida->fetchColumn() !== $cantidad) {
            throw new InvalidArgumentException("El pedido no tiene una salida registrada por esa cantidad.");
        }
    }

    $anterior = (int) $producto["Cantidad"];
    $resultante = $tipo === "Entrada" ? $anterior + $cantidad : $anterior - $cantidad;

    if ($resultante < 0) {
        throw new InvalidArgumentException("La salida supera las existencias disponibles del producto "
            . $producto["SKU"] . ". Solicitadas: $cantidad. Disponibles: $anterior. Reduzca la cantidad o registre una entrada.");
    }

    if ($resultante > 4294967295) {
        throw new InvalidArgumentException("La entrada supera el límite de existencias permitido.");
    }

    $movimiento = $conexion->prepare("
        INSERT INTO movimientosinventario (
            idProducto, skuProducto, nombreProducto, userId, correoAdministrador,
            idPedido, Tipo, Cantidad, cantidadAnterior, cantidadResultante, Motivo, claveMovimiento
        )
        SELECT ?, ?, ?, userId, email, ?, ?, ?, ?, ?, ?, ?
        FROM users WHERE userId = ? AND role = 'Administrador' AND loginStatus = 'Habilitado'
    ");
    $movimiento->execute([
        $idProducto, $producto["SKU"], $producto["Producto"], $idPedido, $tipo,
        $cantidad, $anterior, $resultante, $motivo, $claveMovimiento, $userId
    ]);

    if ($movimiento->rowCount() !== 1) {
        throw new InvalidArgumentException("La cuenta responsable ya no tiene permisos administrativos.");
    }

    $idMovimiento = (int) $conexion->lastInsertId();

    $actualizar = $conexion->prepare("UPDATE productos SET Cantidad = ? WHERE idProducto = ?");
    $actualizar->execute([$resultante, $idProducto]);

    return $idMovimiento;
}
