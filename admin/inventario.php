<?php
require_once __DIR__ . "/../middlewares/protegerAdmin.php";
require_once __DIR__ . "/../config/db.php";

$mensajes = ob_get_clean();

// Estadísticas
$totalProductos = 0;
$totalStockBajo = 0;
$totalAgotados = 0;

try {

    $sql = $conexion->query("
        SELECT
            COUNT(*) AS totalProductos,
            COUNT(CASE WHEN Cantidad BETWEEN 1 AND 5 THEN 1 END) AS totalStockBajo,
            COUNT(CASE WHEN Cantidad = 0 THEN 1 END) AS totalAgotados
        FROM productos
    ");

    $estadisticas = $sql->fetch(PDO::FETCH_ASSOC);

    $totalProductos = (int) $estadisticas["totalProductos"];
    $totalStockBajo = (int) $estadisticas["totalStockBajo"];
    $totalAgotados = (int) $estadisticas["totalAgotados"];
} catch (PDOException $e) {

    $mensajes .= '<div class="alert alert-danger">
        Error al cargar las estadísticas.
    </div>';
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel de Administración</title>

    <script src="https://kit.fontawesome.com/4b21b27482.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css?v=1.0">
    <link rel="stylesheet" href="../css/crud.css?v=1.0">

</head>

<body>

    <!-- Barra lateral -->
    <?php require_once __DIR__ . "/sidebar.php"; ?>

    <div class="admin-content">

        <!-- Barra superior -->
        <header class="admin-topbar">

            <div class="topbar-left">

                <span class="topbar-title"> Administración / Inventario</span>

            </div>

        </header>

        <!-- Contenido del módulo -->
        <main class="admin-main">

            <h1>Inventario</h1>

            <div class="row g-3 mb-4">

                <!-- Total de productos -->
                <div class="col-12 col-md-4">

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

                <!-- Stock bajo -->
                <div class="col-12 col-md-4">

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

                <!-- Productos agotados -->
                <div class="col-12 col-md-4">

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

        </main>

    </div>

    <!-- Evitar reenvio -->
    <script src="../js/reenvio.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>