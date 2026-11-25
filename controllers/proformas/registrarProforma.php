<?php

// Validar acceso y abrir la transacción antes de llamar
function registrarProforma(PDO $conexion, array $datos, string $clave): int {
    $nombre = is_string($datos["nombre"] ?? null) ? trim($datos["nombre"]) : "";
    $correo = is_string($datos["correo"] ?? null) ? trim($datos["correo"]) : "";
    $vencimiento = $datos["fechaVencimiento"] ?? "";
    $tasa = $datos["tasaImpuesto"] ?? "";
    $usuario = $datos["userId"] ?? "";
    $productos = $datos["idProducto"] ?? null;
    $cantidades = $datos["cantidad"] ?? null;

    if (!$conexion->inTransaction()) {
        throw new LogicException("La proforma requiere una transacción.");
    }
    if ($nombre === "" || !mb_check_encoding($nombre, "UTF-8") || mb_strlen($nombre, "UTF-8") > 150 ||
        !filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 255) {
        throw new InvalidArgumentException("Indique un nombre y un correo electrónico válidos.");
    }
    if (!is_string($vencimiento) || !preg_match('/\A([1-9]\d{3})-(\d{2})-(\d{2})\z/', $vencimiento, $fecha) ||
        !checkdate((int) $fecha[2], (int) $fecha[3], (int) $fecha[1])) {
        throw new InvalidArgumentException("Indique una fecha de vencimiento válida.");
    }
    $emision = $conexion->query("SELECT CURRENT_TIMESTAMP()")->fetchColumn();
    if ($vencimiento < substr($emision, 0, 10)) {
        throw new InvalidArgumentException("El vencimiento debe ser igual o posterior a la fecha de emisión.");
    }
    if (!is_string($tasa) || !preg_match('/\A(\d{1,3})(?:\.(\d{1,2}))?\z/', $tasa, $partes)) {
        throw new InvalidArgumentException("Indique la tasa de impuesto con hasta dos decimales.");
    }
    $tasaEntera = (int) $partes[1] * 100 + (int) str_pad($partes[2] ?? "", 2, "0");
    if ($tasaEntera > 10000) {
        throw new InvalidArgumentException("La tasa de impuesto debe estar entre 0 y 100 por ciento.");
    }
    if (!preg_match('/\A[a-f0-9]{64}\z/', $clave) || !is_array($productos) || !is_array($cantidades) ||
        !$productos || array_keys($productos) !== array_keys($cantidades)) {
        throw new InvalidArgumentException("Seleccione al menos un producto y su cantidad.");
    }
    $idUsuario = null;
    if ($usuario !== "") {
        $idUsuario = filter_var($usuario, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        $consulta = $conexion->prepare("SELECT userId FROM users WHERE userId = ? AND role = 'Cliente' FOR UPDATE");
        $consulta->execute([$idUsuario ?: 0]);
        if (!$consulta->fetchColumn()) {
            throw new InvalidArgumentException("Seleccione un cliente registrado o deje la asociación vacía.");
        }
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

    $consulta = $conexion->prepare("SELECT Producto, Precio FROM productos WHERE idProducto = ? AND Activo = 1 FOR UPDATE");
    $subtotal = 0;
    $lineas = [];
    $maximo = 9999999999;
    $decimal = static fn(int $centavos): string => sprintf("%d.%02d", intdiv($centavos, 100), $centavos % 100);
    foreach ($solicitados as $id => $cantidad) {
        $consulta->execute([$id]);
        $producto = $consulta->fetch(PDO::FETCH_ASSOC);
        if (!$producto || !preg_match('/\A\d+\.\d{2}\z/', $producto["Precio"])) {
            throw new InvalidArgumentException("Uno de los productos no está activo o no tiene un precio válido.");
        }
        $precio = (int) str_replace(".", "", $producto["Precio"]);
        if ($precio > 0 && $cantidad > intdiv($maximo - $subtotal, $precio)) {
            throw new InvalidArgumentException("El subtotal de la proforma supera el importe permitido.");
        }
        $importe = $precio * $cantidad;
        $subtotal += $importe;
        $lineas[] = [$id, $producto["Producto"], $producto["Precio"], $cantidad, $decimal($importe)];
    }

    // Redondear el impuesto al centavo
    $impuestos = intdiv($subtotal * $tasaEntera + 5000, 10000);
    $total = $subtotal + $impuestos;
    if ($total > $maximo) {
        throw new InvalidArgumentException("El total con impuestos supera el importe permitido.");
    }
    $guardar = $conexion->prepare("
        INSERT INTO proformas (userId, nombreSolicitante, correoSolicitante, fechaEmision, fechaVencimiento,
            Subtotal, tasaImpuesto, Impuestos, Total, claveProforma)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $guardar->execute([$idUsuario, $nombre, $correo, $emision, $vencimiento,
        $decimal($subtotal), $decimal($tasaEntera), $decimal($impuestos), $decimal($total), $clave]);
    $idProforma = (int) $conexion->lastInsertId();

    $guardar = $conexion->prepare("
        INSERT INTO detalleproforma (idProforma, idProducto, nombreProducto, precioUnitario, cantidad, subtotal)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    foreach ($lineas as $linea) {
        $guardar->execute([$idProforma, ...$linea]);
    }
    return $idProforma;
}
