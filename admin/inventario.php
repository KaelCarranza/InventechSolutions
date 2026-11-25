<?php
require_once __DIR__ . "/../middlewares/protegerAdmin.php";
require_once __DIR__ . "/../middlewares/protegerCsrfAdmin.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../controllers/inventario/registrarMovimiento.php";

$resultadoMovimiento = $_SESSION["resultadoMovimiento"] ?? null;
unset($_SESSION["resultadoMovimiento"]);
$claveMovimiento = bin2hex(random_bytes(32));
$errorInventario = "";

$busqueda = is_string($_GET["buscar"] ?? null) ? trim($_GET["buscar"]) : "";
$idCategoria = filter_input(INPUT_GET, "categoria", FILTER_VALIDATE_INT, [
    "options" => ["min_range" => 1]
]) ?: 0;
$disponibilidad = $_GET["disponibilidad"] ?? "";
$filtrosStock = [
    "existencias" => ["Con existencias", "p.Cantidad > 0"],
    "bajo" => ["Stock bajo (1 a 5)", "p.Cantidad BETWEEN 1 AND 5"],
    "agotado" => ["Agotado", "p.Cantidad = 0"]
];
if (!is_string($disponibilidad) || !isset($filtrosStock[$disponibilidad])) {
    $disponibilidad = "";
}

// Estadísticas
$totalProductos = 0;
$totalConExistencias = 0;
$totalStockBajo = 0;
$totalAgotados = 0;
$categorias = [];
$productos = [];
$opcionesProductos = [];
$movimientos = [];

try {

    $sql = $conexion->query("
        SELECT
            COUNT(*) AS totalProductos,
            COUNT(CASE WHEN Cantidad > 0 THEN 1 END) AS totalConExistencias,
            COUNT(CASE WHEN Cantidad BETWEEN 1 AND 5 THEN 1 END) AS totalStockBajo,
            COUNT(CASE WHEN Cantidad = 0 THEN 1 END) AS totalAgotados
        FROM productos
    ");

    $estadisticas = $sql->fetch(PDO::FETCH_ASSOC);

    $totalProductos = (int) $estadisticas["totalProductos"];
    $totalConExistencias = (int) $estadisticas["totalConExistencias"];
    $totalStockBajo = (int) $estadisticas["totalStockBajo"];
    $totalAgotados = (int) $estadisticas["totalAgotados"];

    $categorias = $conexion->query("SELECT idCategoria, Nombre FROM categorias ORDER BY Nombre")
        ->fetchAll(PDO::FETCH_ASSOC);
    $opcionesProductos = $conexion->query("SELECT idProducto, SKU, Producto FROM productos ORDER BY Producto, SKU")
        ->fetchAll(PDO::FETCH_ASSOC);

    $condiciones = [];
    $parametros = [];
    if ($busqueda !== "") {
        $patron = "%" . strtr($busqueda, ["!" => "!!", "%" => "!%", "_" => "!_"]) . "%";
        $condiciones[] = "(p.Producto LIKE ? ESCAPE '!' OR p.SKU LIKE ? ESCAPE '!')";
        $parametros[] = $patron;
        $parametros[] = $patron;
    }
    if ($idCategoria > 0) {
        $condiciones[] = "p.idCategoria = ?";
        $parametros[] = $idCategoria;
    }
    if ($disponibilidad !== "") {
        $condiciones[] = $filtrosStock[$disponibilidad][1];
    }
    $where = $condiciones ? " WHERE " . implode(" AND ", $condiciones) : "";
    $sql = $conexion->prepare("
        SELECT p.SKU, p.Producto, p.Cantidad, c.Nombre AS categoria
        FROM productos p LEFT JOIN categorias c ON p.idCategoria = c.idCategoria
        $where ORDER BY p.Producto, p.SKU
    ");
    $sql->execute($parametros);
    $productos = $sql->fetchAll(PDO::FETCH_ASSOC);

    $movimientos = $conexion->query("
        SELECT skuProducto, nombreProducto, Tipo, Cantidad, cantidadAnterior,
            cantidadResultante, Motivo, correoAdministrador, fechaMovimiento
        FROM movimientosinventario ORDER BY idMovimiento DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $errorInventario = "No se pudo cargar el inventario.";
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel de Administración</title>

    <script src="https://kit.fontawesome.com/4b21b27482.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css?v=2.0">
    <link rel="stylesheet" href="../css/crud.css?v=2.0">

</head>

<body>


    <?php require_once __DIR__ . "/sidebar.php"; ?>

    <div class="admin-content">


        <header class="admin-topbar">

            <div class="topbar-left">

                <span class="topbar-title"> Administración / Inventario</span>

            </div>

        </header>


        <main class="admin-main">

            <h1>Inventario</h1>

            <?php if ($resultadoMovimiento) { ?>
                <div class="alert alert-<?= $resultadoMovimiento[0] ?>" role="alert">
                    <?= htmlspecialchars($resultadoMovimiento[1]) ?>
                </div>
            <?php } ?>
            <?php if ($errorInventario !== "") { ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errorInventario) ?></div>
            <?php } ?>

            <div class="row g-3 mb-4">


                <div class="col-12 col-sm-6 col-xl-3">

                    <div class="card resumen-card resumen-total border-0 shadow-sm h-100">

                        <div class="card-body d-flex align-items-center justify-content-between">

                            <div>
                                <p class="resumen-label mb-1">Total de productos</p>
                                <h3 class="resumen-numero mb-0"><?= $totalProductos ?></h3>
                            </div>

                            <div class="resumen-icono">
                                <i class="fa-solid fa-boxes-stacked"></i>
                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card resumen-card resumen-admin border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <p class="resumen-label mb-1">Productos con existencias</p>
                                <h3 class="resumen-numero mb-0"><?= $totalConExistencias ?></h3>
                            </div>
                            <div class="resumen-icono">
                                <i class="fa-solid fa-box"></i>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="col-12 col-sm-6 col-xl-3">

                    <div class="card resumen-card resumen-stock-bajo border-0 shadow-sm h-100">

                        <div class="card-body d-flex align-items-center justify-content-between">

                            <div>
                                <p class="resumen-label mb-1">Stock bajo</p>
                                <h3 class="resumen-numero mb-0"><?= $totalStockBajo ?></h3>
                            </div>

                            <div class="resumen-icono">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-12 col-sm-6 col-xl-3">

                    <div class="card resumen-card resumen-agotados border-0 shadow-sm h-100">

                        <div class="card-body d-flex align-items-center justify-content-between">

                            <div>
                                <p class="resumen-label mb-1">Productos agotados</p>
                                <h3 class="resumen-numero mb-0"><?= $totalAgotados ?></h3>
                            </div>

                            <div class="resumen-icono">
                                <i class="fa-solid fa-box-open"></i>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <?php if ($errorInventario === "") { ?>
                <section class="card crud-card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                            <h2 class="h5 mb-0">Existencias por producto</h2>
                            <?php if ($opcionesProductos) { ?>
                                <button type="button" class="btn btn-registrar" data-bs-toggle="modal"
                                    data-bs-target="#modalMovimiento">Registrar movimiento</button>
                            <?php } ?>
                        </div>

                        <form method="GET" class="row g-2 align-items-end mb-3">
                            <div class="col-12 col-md">
                                <label class="form-label" for="buscar">Nombre o SKU</label>
                                <input class="form-control" id="buscar" name="buscar" value="<?= htmlspecialchars($busqueda) ?>">
                            </div>
                            <div class="col-12 col-sm-6 col-md">
                                <label class="form-label" for="categoria">Categoría</label>
                                <select class="form-select" id="categoria" name="categoria">
                                    <option value="">Todas</option>
                                    <?php foreach ($categorias as $categoria) { ?>
                                        <option value="<?= (int) $categoria["idCategoria"] ?>" <?= $idCategoria === (int) $categoria["idCategoria"] ? "selected" : "" ?>>
                                            <?= htmlspecialchars($categoria["Nombre"]) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-md">
                                <label class="form-label" for="disponibilidad">Disponibilidad</label>
                                <select class="form-select" id="disponibilidad" name="disponibilidad">
                                    <option value="">Cualquiera</option>
                                    <?php foreach ($filtrosStock as $valor => $filtro) { ?>
                                        <option value="<?= $valor ?>" <?= $disponibilidad === $valor ? "selected" : "" ?>><?= $filtro[0] ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-dark">Filtrar</button>
                                <a href="inventario.php" class="btn btn-outline-dark">Limpiar</a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr><th>SKU</th><th>Producto</th><th>Categoría</th><th>Cantidad disponible</th><th>Estado</th></tr>
                                </thead>
                                <tbody>
                                    <?php if (!$productos) { ?>
                                        <tr><td colspan="5" class="text-center py-4">No hay productos para mostrar.</td></tr>
                                    <?php } ?>
                                    <?php foreach ($productos as $producto) {
                                        $disponible = (int) $producto["Cantidad"];
                                        $estado = $disponible === 0 ? "Agotado" : ($disponible <= 5 ? "Stock bajo" : "Disponible");
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($producto["SKU"]) ?></td>
                                            <td><?= htmlspecialchars($producto["Producto"]) ?></td>
                                            <td><?= htmlspecialchars($producto["categoria"] ?? "Sin categoría") ?></td>
                                            <td><?= $disponible ?></td>
                                            <td><?= $estado ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section class="card crud-card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h5 mb-3">Historial de movimientos</h2>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Fecha y hora</th><th>SKU</th><th>Producto</th><th>Tipo</th>
                                        <th>Cantidad</th><th>Anterior</th><th>Resultante</th><th>Motivo</th><th>Administrador</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!$movimientos) { ?>
                                        <tr><td colspan="9" class="text-center py-4">Todavía no hay movimientos registrados.</td></tr>
                                    <?php } ?>
                                    <?php foreach ($movimientos as $movimiento) { ?>
                                        <tr>
                                            <td class="text-nowrap"><?= htmlspecialchars($movimiento["fechaMovimiento"]) ?></td>
                                            <td><?= htmlspecialchars($movimiento["skuProducto"]) ?></td>
                                            <td><?= htmlspecialchars($movimiento["nombreProducto"]) ?></td>
                                            <td><?= htmlspecialchars($movimiento["Tipo"]) ?></td>
                                            <td><?= (int) $movimiento["Cantidad"] ?></td>
                                            <td><?= (int) $movimiento["cantidadAnterior"] ?></td>
                                            <td><?= (int) $movimiento["cantidadResultante"] ?></td>
                                            <td class="text-break"><?= htmlspecialchars($movimiento["Motivo"]) ?></td>
                                            <td><?= htmlspecialchars($movimiento["correoAdministrador"]) ?></td>
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

    <?php if ($errorInventario === "" && $opcionesProductos) { ?>
        <div class="modal fade" id="modalMovimiento" tabindex="-1" aria-labelledby="tituloMovimiento" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <input type="hidden" name="csrf_admin" value="<?= $tokenCsrfAdmin ?>">
                        <input type="hidden" name="claveMovimiento" value="<?= $claveMovimiento ?>">
                        <div class="modal-header">
                            <h2 id="tituloMovimiento" class="modal-title h5">Registrar movimiento</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="productoMovimiento">Producto</label>
                                <select class="form-select" id="productoMovimiento" name="idProducto" required>
                                    <option value="">Seleccione un producto</option>
                                    <?php foreach ($opcionesProductos as $producto) { ?>
                                        <option value="<?= (int) $producto["idProducto"] ?>">
                                            <?= htmlspecialchars($producto["SKU"] . " · " . $producto["Producto"]) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="tipoMovimiento">Tipo de movimiento</label>
                                <select class="form-select" id="tipoMovimiento" name="tipo" required>
                                    <option value="">Seleccione el tipo</option>
                                    <option value="Entrada">Entrada</option>
                                    <option value="Salida">Salida</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="cantidadMovimiento">Cantidad</label>
                                <input class="form-control" type="number" id="cantidadMovimiento" name="cantidad" min="1" max="4294967295" step="1" required>
                            </div>
                            <div>
                                <label class="form-label" for="motivoMovimiento">Motivo</label>
                                <textarea class="form-control" id="motivoMovimiento" name="motivo" rows="3" maxlength="255" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Cerrar</button>
                            <button type="submit" class="btn btn-registrar">Registrar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php } ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
