<?php
require_once __DIR__ . "/../middlewares/protegerAdmin.php";
require_once __DIR__ . "/../controllers/reportes/consultarReportes.php";

function fechaValidaReporte($valor): bool {
    return is_string($valor) && preg_match('/\A([1-9]\d{3})-(\d{2})-(\d{2})\z/', $valor, $partes)
        && checkdate((int) $partes[2], (int) $partes[3], (int) $partes[1]);
}

$desde = $hasta = $generado = $error = $errorFechas = "";
$ventas = null;
$inventario = [];
$stock = ["Total de productos" => 0, "Stock suficiente" => 0, "Stock bajo" => 0, "Agotados" => 0, "Unidades disponibles" => 0];

try {
    $conexion->exec("SET TRANSACTION READ ONLY");
    $conexion->beginTransaction();
    $generado = $conexion->query("SELECT CURRENT_TIMESTAMP()")->fetchColumn();
    if (!isset($_GET["desde"]) && !isset($_GET["hasta"])) {
        $hoy = new DateTimeImmutable(substr($generado, 0, 10));
        $desde = $hoy->format("Y-m-01");
        $hasta = $hoy->format("Y-m-t");
    } else {
        $desde = fechaValidaReporte($_GET["desde"] ?? null) ? $_GET["desde"] : "";
        $hasta = fechaValidaReporte($_GET["hasta"] ?? null) ? $_GET["hasta"] : "";
        if ($desde === "" || $hasta === "") {
            $errorFechas = "Indique una fecha inicial y una fecha final válidas.";
        } elseif ($desde > $hasta) {
            $errorFechas = "La fecha inicial debe ser igual o anterior a la fecha final.";
        }
    }

    if ($errorFechas === "") {
        $ventas = consultarVentasReporte($conexion, $desde, $hasta);
    }

    $inventario = $conexion->query("
        SELECT p.SKU, p.Producto, p.Cantidad, c.Nombre AS categoria,
            CASE WHEN p.Cantidad = 0 THEN 'Agotados'
                 WHEN p.Cantidad <= 5 THEN 'Stock bajo'
                 ELSE 'Stock suficiente' END AS estadoStock
        FROM productos p LEFT JOIN categorias c ON c.idCategoria = p.idCategoria
        ORDER BY p.Cantidad ASC, p.Producto ASC, p.idProducto ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    $stock["Total de productos"] = count($inventario);
    foreach ($inventario as $producto) {
        $stock[$producto["estadoStock"]]++;
        $stock["Unidades disponibles"] += (int) $producto["Cantidad"];
    }
    $conexion->commit();
} catch (PDOException $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    error_log($e->getMessage());
    $error = "No se pudo cargar el reporte. Vuelva a intentarlo.";
}

$importe = static fn($valor): string => number_format((float) $valor, 2, ",", ".");
$entero = static fn($valor): string => number_format((int) $valor, 0, ",", ".");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes | InvenTech Solutions</title>
    <script src="https://kit.fontawesome.com/4b21b27482.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css?v=2.0">
    <link rel="stylesheet" href="../css/crud.css?v=2.0">
    <link rel="stylesheet" href="../css/reportes.css">
</head>
<body>
    <?php require_once __DIR__ . "/sidebar.php"; ?>
    <div class="admin-content">
        <header class="admin-topbar"><div class="topbar-left"><span class="topbar-title">Administración / Reportes</span></div></header>
        <main class="admin-main">
            <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
                <div>
                    <p class="small fw-bold mb-1">InvenTech Solutions</p>
                    <h1 class="mb-1">Reporte de ventas e inventario</h1>
                    <?php if ($error === "" && $errorFechas === "") { ?>
                        <p class="reporte-fecha mb-0">Período de ventas del <time><?= $desde ?></time> al <time><?= $hasta ?></time></p>
                    <?php } ?>
                    <?php if ($generado !== "") { ?>
                        <p class="reporte-fecha small mb-0">Generado el <?= htmlspecialchars($generado) ?></p>
                    <?php } ?>
                </div>
                <?php if ($error === "" && $errorFechas === "") { ?>
                    <button class="btn btn-outline-dark reporte-controles" type="button" onclick="window.print()">Imprimir o guardar PDF</button>
                <?php } ?>
            </div>

            <form action="reportes.php" method="GET" class="card border-0 shadow-sm mb-4 reporte-controles">
                <div class="card-body">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-sm">
                            <label class="form-label" for="desde">Fecha inicial</label>
                            <input class="form-control" type="date" id="desde" name="desde" value="<?= $desde ?>" required>
                        </div>
                        <div class="col-12 col-sm">
                            <label class="form-label" for="hasta">Fecha final</label>
                            <input class="form-control" type="date" id="hasta" name="hasta" value="<?= $hasta ?>" required>
                        </div>
                        <div class="col-auto d-flex flex-wrap gap-2">
                            <button class="btn btn-dark" type="submit">Aplicar filtros</button>
                            <a class="btn btn-outline-dark" href="reportes.php">Limpiar filtros</a>
                        </div>
                    </div>
                    <p class="small admin-description mt-2 mb-0">Las fechas corresponden al registro de los pedidos. Inventario muestra el saldo actual.</p>
                </div>
            </form>

            <?php if ($error !== "") { ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
            <?php } else { ?>
                <?php if ($errorFechas !== "") { ?>
                    <div class="alert alert-warning" role="alert"><?= htmlspecialchars($errorFechas) ?></div>
                <?php } ?>
                <?php if ($ventas !== null) {
                    $resumen = $ventas["resumen"];
                    $indicadores = [
                        "Ventas confirmadas" => $importe($resumen["importe"]),
                        "Pedidos con pago confirmado" => $entero($resumen["pedidos"]),
                        "Unidades vendidas" => $entero($resumen["unidades"]),
                        "Ticket promedio" => $importe($resumen["ticket"])
                    ];
                    $maximo = $ventas["dias"] ? max(array_column($ventas["dias"], "importe")) : 0;
                ?>
                    <section class="mb-4" aria-labelledby="resumenVentas">
                        <h2 class="h5" id="resumenVentas">Resumen de ventas</h2>
                        <p class="small admin-description">Incluye pedidos Pagados, Enviados y Entregados por fecha de registro, independientemente de cuándo se confirmó el pago.</p>
                        <div class="row row-cols-2 row-cols-lg-4 g-2">
                            <?php foreach ($indicadores as $nombre => $valor) { ?>
                                <div class="col">
                                    <div class="card border-0 shadow-sm h-100 <?= $nombre === "Ventas confirmadas" ? "resumen-total" : "" ?>">
                                        <div class="card-body">
                                            <p class="small mb-1"><?= $nombre ?></p>
                                            <p class="fs-4 fw-bold text-break mb-0"><?= $valor ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </section>

                    <section class="card border-0 shadow-sm mb-4" aria-labelledby="graficoVentas">
                        <div class="card-body">
                            <h2 class="h5" id="graficoVentas">Comportamiento de ventas</h2>
                            <?php if (!$ventas["dias"]) { ?>
                                <p class="admin-description mb-0">No hay ventas confirmadas en el período seleccionado.</p>
                            <?php } else { ?>
                                <p class="small admin-description">Importe por día de registro. Se muestran los días con ventas confirmadas.</p>
                                <ul class="reporte-barras list-unstyled mb-0">
                                    <?php foreach ($ventas["dias"] as $dia) {
                                        $ancho = $maximo > 0 ? max(0, (float) $dia["importe"] / $maximo * 100) : 0;
                                    ?>
                                        <li class="reporte-barra">
                                            <time datetime="<?= $dia["fecha"] ?>"><?= $dia["fecha"] ?></time>
                                            <span class="reporte-pista" aria-hidden="true"><span style="width: <?= number_format($ancho, 4, ".", "") ?>%"></span></span>
                                            <strong class="reporte-importes"><?= $importe($dia["importe"]) ?></strong>
                                        </li>
                                    <?php } ?>
                                </ul>
                            <?php } ?>
                        </div>
                    </section>

                    <section class="card border-0 shadow-sm mb-4" aria-labelledby="productosVendidos">
                        <div class="card-body">
                            <h2 class="h5 mb-3" id="productosVendidos">Productos más vendidos</h2>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead><tr><th scope="col">Posición</th><th scope="col">Producto</th><th scope="col">Unidades vendidas</th><th scope="col">Importe vendido</th></tr></thead>
                                    <tbody>
                                        <?php if (!$ventas["productos"]) { ?><tr><td colspan="4" class="text-center py-4">No hay productos vendidos en este período.</td></tr><?php } ?>
                                        <?php foreach ($ventas["productos"] as $posicion => $producto) { ?>
                                            <tr>
                                                <td><?= $posicion + 1 ?></td>
                                                <td><?= htmlspecialchars($producto["nombreProducto"]) ?></td>
                                                <td><?= $entero($producto["unidades"]) ?></td>
                                                <td><?= $importe($producto["importe"]) ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                <?php } ?>

                <section class="card border-0 shadow-sm" aria-labelledby="inventarioActual">
                    <div class="card-body">
                        <h2 class="h5" id="inventarioActual">Disponibilidad de inventario</h2>
                        <p class="small admin-description">Existencias actuales de productos activos e inactivos. El período de ventas no aplica a esta sección.</p>
                        <dl class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-2 mb-3">
                            <?php foreach ($stock as $nombre => $valor) { ?>
                                <div class="col">
                                    <div class="border rounded p-2 h-100">
                                        <dt class="small fw-normal"><?= $nombre ?></dt><dd class="fs-5 fw-bold mb-0"><?= $entero($valor) ?></dd>
                                    </div>
                                </div>
                            <?php } ?>
                        </dl>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead><tr><th scope="col">SKU</th><th scope="col">Producto</th><th scope="col">Categoría</th><th scope="col">Existencias</th><th scope="col">Estado</th></tr></thead>
                                <tbody>
                                    <?php if (!$inventario) { ?><tr><td colspan="5" class="text-center py-4">No hay productos en inventario.</td></tr><?php } ?>
                                    <?php foreach ($inventario as $producto) { ?>
                                        <tr>
                                            <td><?= htmlspecialchars($producto["SKU"]) ?></td>
                                            <td><?= htmlspecialchars($producto["Producto"]) ?></td>
                                            <td><?= htmlspecialchars($producto["categoria"] ?? "Sin categoría") ?></td>
                                            <td><?= $entero($producto["Cantidad"]) ?></td>
                                            <td class="<?= $producto["estadoStock"] !== "Stock suficiente" ? "reporte-reposicion" : "" ?>">
                                                <?= $producto["estadoStock"] === "Agotados" ? "Agotado" : $producto["estadoStock"] ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            <?php } ?>
        </main>
    </div>
</body>
</html>
