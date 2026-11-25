<?php
require_once __DIR__ . "/../middlewares/protegerAdmin.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../controllers/proformas/consultarProforma.php";

// Estadísticas
$mensaje = "";

try {

    $sql = $conexion->query("
        SELECT
            (SELECT COUNT(*) FROM users) AS usuarios,
            (SELECT COUNT(*) FROM categorias) AS categorias,
            COUNT(CASE WHEN Activo = 1 THEN 1 END) AS activos,
            COUNT(CASE WHEN Cantidad BETWEEN 1 AND 5 THEN 1 END) AS bajo,
            COUNT(CASE WHEN Cantidad = 0 THEN 1 END) AS agotados,
            (SELECT COUNT(*) FROM pedidos WHERE Estado = 'Pendiente') AS pendientes,
            (SELECT COALESCE(SUM(Total), 0) FROM pedidos
                WHERE Estado IN ('Pagado','Enviado','Entregado')
                  AND fechaPedido >= CAST(DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01') AS DATE)
                  AND fechaPedido < LAST_DAY(CURRENT_DATE()) + INTERVAL 1 DAY) AS ventas,
            (SELECT COUNT(*) FROM proformas WHERE (" . ESTADO_PROFORMA_SQL . ") = 'Vigente') AS proformas
        FROM productos
    ");

    $estadisticas = $sql->fetch(PDO::FETCH_ASSOC);

    $indicadores = [
        ["Total de usuarios", (int) $estadisticas["usuarios"], "fa-users", "resumen-total"],
        ["Total de categorías", (int) $estadisticas["categorias"], "fa-layer-group", "resumen-categorias"],
        ["Productos activos", (int) $estadisticas["activos"], "fa-box", "resumen-categorias"],
        ["Stock bajo", (int) $estadisticas["bajo"], "fa-triangle-exclamation", "resumen-admin"],
        ["Productos agotados", (int) $estadisticas["agotados"], "fa-box-open", "resumen-categorias"],
        ["Pedidos pendientes", (int) $estadisticas["pendientes"], "fa-cart-shopping", "resumen-categorias"],
        ["Ventas confirmadas del mes", number_format((float) $estadisticas["ventas"], 2, ",", "."), "fa-chart-column", "resumen-total"],
        ["Proformas vigentes", (int) $estadisticas["proformas"], "fa-file-invoice", "resumen-categorias"]
    ];

    $pedidosRecientes = $conexion->query("
        SELECT p.idPedido, u.email, p.fechaPedido, p.Total, p.Estado
        FROM pedidos p INNER JOIN users u ON u.userId = p.userId
        ORDER BY p.fechaPedido DESC, p.idPedido DESC LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    $reposicion = $conexion->query("
        SELECT SKU, Producto, Cantidad FROM productos
        WHERE Cantidad BETWEEN 0 AND 5 ORDER BY Cantidad, Producto, idProducto LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log($e->getMessage());
    $mensaje = "No se pudieron cargar las estadísticas del panel.";

}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <link rel="icon" href="../favicon.ico" type="image/x-icon">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel administrativo | InvenTech Solutions</title>

    <script src="https://kit.fontawesome.com/4b21b27482.js"
        crossorigin="anonymous"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css?v=2.0">
    <link rel="stylesheet" href="../css/crud.css?v=2.0">

    <style>
        .dashboard .resumen-categorias {
            background: var(--it-soft-neutral);
            color: var(--it-primary);
        }

        .dashboard .resumen-numero {
            font-size: clamp(1.875rem, 3vw, 2.375rem);
            font-weight: 700;
            line-height: 1.1;
            overflow-wrap: anywhere;
        }

        .dashboard .resumen-icono {
            flex-shrink: 0;
            font-size: 1.375rem;
        }

        .dashboard .acceso-rapido i {
            color: var(--it-primary-active);
        }

        .dashboard .acceso-rapido:hover i,
        .dashboard .acceso-rapido:focus-visible i {
            color: var(--it-accent);
        }
    </style>

</head>

<body>

    <?php require_once __DIR__ . "/sidebar.php"; ?>

    <div class="admin-content">

        <header class="admin-topbar">
            <div class="topbar-left">
                <span class="topbar-title">Administración / Inicio</span>
            </div>
        </header>

        <main class="admin-main dashboard">

            <h1 class="mb-2">Bienvenido al panel administrativo</h1>
            <p class="admin-description mb-4">Consulta el estado de la tienda y accede a los módulos administrativos.</p>

            <?php if ($mensaje !== "") { ?>

                <div class="alert alert-danger" role="alert"><?= $mensaje ?></div>

            <?php } else { ?>

                <div class="row g-3 mb-4">

                    <?php foreach ($indicadores as $indicador) { ?>

                        <div class="col-12 col-sm-6 col-xl-3">
                            <div class="card resumen-card <?= $indicador[3] ?> border-0 shadow-sm h-100">
                                <div class="card-body d-flex align-items-center justify-content-between gap-2">

                                    <div>
                                        <p class="resumen-label mb-1"><?= $indicador[0] ?></p>
                                        <p class="resumen-numero mb-0"><?= $indicador[1] ?></p>
                                    </div>

                                    <div class="resumen-icono" aria-hidden="true">
                                        <i class="fa-solid <?= $indicador[2] ?>"></i>
                                    </div>

                                </div>
                            </div>
                        </div>

                    <?php } ?>

                </div>

            <?php } ?>

            <section class="card border-0 shadow-sm mb-4" aria-labelledby="accesosRapidos">
                <div class="card-body">

                    <h2 class="h5 mb-3" id="accesosRapidos">Accesos rápidos</h2>

                    <div class="d-flex flex-wrap gap-2">

                        <?php foreach (array_merge(array_slice($menuAdministracion, 1), $menuOperaciones) as $acceso) { ?>

                            <a href="<?= $acceso[0] ?>" class="btn btn-outline-dark acceso-rapido">
                                <i class="<?= $acceso[2] ?> me-2" aria-hidden="true"></i><?= $acceso[1] ?>
                            </a>

                        <?php } ?>

                    </div>

                </div>
            </section>

            <?php if ($mensaje === "") { ?>
                <div class="row g-3">
                    <section class="col-12 col-xl-8" aria-labelledby="pedidosRecientes">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h2 class="h5" id="pedidosRecientes">Pedidos recientes</h2>
                                <?php if (!$pedidosRecientes) { ?>
                                    <p class="admin-description mb-0">Todavía no hay pedidos registrados.</p>
                                <?php } else { ?>
                                    <div class="table-responsive">
                                        <table class="table align-middle mb-0">
                                            <thead><tr><th>Número</th><th>Cliente</th><th>Fecha de registro</th><th>Total</th><th>Estado</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($pedidosRecientes as $pedido) { ?>
                                                    <tr>
                                                        <td><a href="pedidos.php?detalle=<?= (int) $pedido["idPedido"] ?>">#<?= (int) $pedido["idPedido"] ?></a></td>
                                                        <td><?= htmlspecialchars($pedido["email"]) ?></td>
                                                        <td class="text-nowrap"><?= htmlspecialchars($pedido["fechaPedido"]) ?></td>
                                                        <td><?= number_format((float) $pedido["Total"], 2, ",", ".") ?></td>
                                                        <td><?= htmlspecialchars($pedido["Estado"]) ?></td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </section>
                    <section class="col-12 col-xl-4" aria-labelledby="productosReposicion">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h2 class="h5" id="productosReposicion">Productos que necesitan reposición</h2>
                                <?php if (!$reposicion) { ?>
                                    <p class="admin-description mb-0">No hay productos con stock bajo o agotado.</p>
                                <?php } else { ?>
                                    <ul class="list-group list-group-flush mb-2">
                                        <?php foreach ($reposicion as $producto) { ?>
                                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center gap-2">
                                                <span class="text-break"><?= htmlspecialchars($producto["Producto"]) ?>
                                                    <small class="d-block admin-description"><?= htmlspecialchars($producto["SKU"]) ?></small>
                                                </span>
                                                <span class="text-nowrap fw-semibold"><?= (int) $producto["Cantidad"] ?> u.</span>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                    <a href="inventario.php" class="btn btn-outline-dark btn-sm">Ver inventario</a>
                                <?php } ?>
                            </div>
                        </div>
                    </section>
                </div>
            <?php } ?>

        </main>

    </div>

</body>

</html>
