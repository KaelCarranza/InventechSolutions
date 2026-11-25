<?php

function consultarVentasReporte(PDO $conexion, string $inicio, string $fin): array {
    $filtro = "p.Estado IN ('Pagado', 'Enviado', 'Entregado') AND p.fechaPedido BETWEEN ? AND ?";
    $parametros = [$inicio . " 00:00:00", $fin . " 23:59:59"];

    // Agrupar primero por pedido
    $consulta = $conexion->prepare("
        WITH ventas AS (
            SELECT p.idPedido, p.fechaPedido, p.Total, COALESCE(SUM(d.cantidad), 0) AS unidades
            FROM pedidos p LEFT JOIN detallepedido d ON d.idPedido = p.idPedido
            WHERE $filtro
            GROUP BY p.idPedido, p.fechaPedido, p.Total
        )
        SELECT DATE(fechaPedido) AS fecha, SUM(Total) AS importe, COUNT(*) AS pedidos,
            SUM(unidades) AS unidades, ROUND(AVG(Total), 2) AS ticket
        FROM ventas
        GROUP BY DATE(fechaPedido) WITH ROLLUP
    ");
    $consulta->execute($parametros);

    $resumen = ["importe" => 0, "pedidos" => 0, "unidades" => 0, "ticket" => 0];
    $dias = [];
    foreach ($consulta as $fila) {
        if ($fila["fecha"] === null) {
            $resumen = $fila;
        } else {
            $dias[] = $fila;
        }
    }

    // Usar el nombre histórico del último detalle
    $consulta = $conexion->prepare("
        SELECT d.nombreProducto, destacados.unidades, destacados.importe
        FROM (
            SELECT d.idProducto, MAX(d.idDetalle) AS idDetalle, SUM(d.cantidad) AS unidades,
                SUM(d.subtotal) AS importe
            FROM detallepedido d INNER JOIN pedidos p ON p.idPedido = d.idPedido
            WHERE $filtro
            GROUP BY d.idProducto
            ORDER BY unidades DESC, importe DESC, d.idProducto ASC
            LIMIT 10
        ) destacados
        INNER JOIN detallepedido d ON d.idDetalle = destacados.idDetalle
        ORDER BY destacados.unidades DESC, destacados.importe DESC, destacados.idProducto ASC
    ");
    $consulta->execute($parametros);

    return ["resumen" => $resumen, "dias" => $dias, "productos" => $consulta->fetchAll(PDO::FETCH_ASSOC)];
}
