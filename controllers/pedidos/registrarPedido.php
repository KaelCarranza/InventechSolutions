<?php

function registrarPedido(PDO $conexion, array $datos, array $metodosPago): int {
    $cliente = filter_var($datos["userId"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    $metodo = $datos["metodoPago"] ?? "";
    $productos = $datos["idProducto"] ?? null;
    $cantidades = $datos["cantidad"] ?? null;

    if (!$cliente || !in_array($metodo, $metodosPago, true) || !is_array($productos) ||
        !is_array($cantidades) || !$productos || array_keys($productos) !== array_keys($cantidades)) {
        throw new InvalidArgumentException("Seleccione un cliente, un método de pago y al menos un producto.");
    }

    $consulta = $conexion->prepare("
        SELECT userId FROM users WHERE userId = ? AND role = 'Cliente' AND loginStatus = 'Habilitado' FOR UPDATE
    ");
    $consulta->execute([$cliente]);
    if (!$consulta->fetchColumn()) {
        throw new InvalidArgumentException("El cliente seleccionado debe existir y estar habilitado.");
    }

    $solicitados = [];
    foreach ($productos as $indice => $valor) {
        $id = filter_var($valor, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        $cantidad = filter_var($cantidades[$indice], FILTER_VALIDATE_INT, [
            "options" => ["min_range" => 1, "max_range" => 4294967295]
        ]);
        if (!$id || !$cantidad || isset($solicitados[$id])) {
            throw new InvalidArgumentException("Indique una cantidad entera positiva por producto, sin repetir productos.");
        }
        $solicitados[$id] = $cantidad;
    }
    ksort($solicitados, SORT_NUMERIC);

    $consulta = $conexion->prepare("
        SELECT Producto, Precio FROM productos WHERE idProducto = ? AND Activo = 1 FOR UPDATE
    ");
    $total = 0;
    $lineas = [];
    // Límite monetario de DECIMAL(10,2) en centavos
    $maximo = 9999999999;
    foreach ($solicitados as $id => $cantidad) {
        $consulta->execute([$id]);
        $producto = $consulta->fetch(PDO::FETCH_ASSOC);
        if (!$producto || !preg_match('/\A\d+\.\d{2}\z/', $producto["Precio"])) {
            throw new InvalidArgumentException("Uno de los productos no está activo o no tiene un precio válido.");
        }
        $precio = (int) str_replace(".", "", $producto["Precio"]);
        if ($precio > 0 && $cantidad > intdiv($maximo - $total, $precio)) {
            throw new InvalidArgumentException("El total del pedido supera el importe permitido.");
        }
        $subtotal = $precio * $cantidad;
        $total += $subtotal;
        $lineas[] = [$id, $producto["Producto"], $producto["Precio"], $cantidad, sprintf("%d.%02d", intdiv($subtotal, 100), $subtotal % 100)];
    }

    $insertar = $conexion->prepare("INSERT INTO pedidos (userId, Total, Estado, metodoPago) VALUES (?, ?, 'Pendiente', ?)");
    $insertar->execute([$cliente, sprintf("%d.%02d", intdiv($total, 100), $total % 100), $metodo]);
    $idPedido = (int) $conexion->lastInsertId();

    $detalle = $conexion->prepare("
        INSERT INTO detallepedido (idPedido, idProducto, nombreProducto, precioUnitario, cantidad, subtotal)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    foreach ($lineas as $linea) {
        $detalle->execute([$idPedido, ...$linea]);
    }

    return $idPedido;
}
