<?php
require_once __DIR__ . "/../middlewares/protegerAdmin.php";

function fechaValidaBitacora($valor): bool {
    return is_string($valor) && preg_match('/\A([1-9]\d{3})-(\d{2})-(\d{2})\z/', $valor, $partes)
        && checkdate((int) $partes[2], (int) $partes[3], (int) $partes[1]);
}

$modulos = ["Usuarios", "Categorías", "Productos", "Inventario", "Pedidos", "Proformas"];
$acciones = ["Registro", "Modificación", "Habilitación", "Deshabilitación", "Eliminación",
    "Activación", "Desactivación", "Entrada", "Salida", "Cambio de estado", "Cancelación"];
$buscar = is_string($_GET["buscar"] ?? null) ? trim($_GET["buscar"]) : "";
$modulo = in_array($_GET["modulo"] ?? "", $modulos, true) ? $_GET["modulo"] : "";
$accion = in_array($_GET["accion"] ?? "", $acciones, true) ? $_GET["accion"] : "";
$desde = fechaValidaBitacora($_GET["desde"] ?? null) ? $_GET["desde"] : "";
$hasta = fechaValidaBitacora($_GET["hasta"] ?? null) ? $_GET["hasta"] : "";
$error = "";
foreach (["desde" => $desde, "hasta" => $hasta] as $campo => $fecha) {
    if (isset($_GET[$campo]) && $_GET[$campo] !== "" && $fecha === "") {
        $error = "Indique fechas válidas.";
    }
}
if ($desde !== "" && $hasta !== "" && $desde > $hasta) {
    $error = "La fecha inicial debe ser igual o anterior a la fecha final.";
}
$pagina = filter_input(INPUT_GET, "pagina", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]) ?: 1;
$porPagina = 25;
$paginas = 1;
$filas = [];
$resumen = ["total" => 0, "hoy" => 0, "administradores" => 0];

try {
    $resumen = $conexion->query("
        SELECT COUNT(*) AS total,
            COUNT(CASE WHEN fechaRegistro >= CURRENT_DATE()
                AND fechaRegistro < CURRENT_DATE() + INTERVAL 1 DAY THEN 1 END) AS hoy,
            COUNT(DISTINCT idAdministradorHistorico) AS administradores
        FROM bitacora
    ")->fetch(PDO::FETCH_ASSOC);
    if ($error === "") {
        $condiciones = $parametros = [];
        if ($buscar !== "") {
            $patron = "%" . strtr($buscar, ["!" => "!!", "%" => "!%", "_" => "!_"]) . "%";
            $condiciones[] = "(correoAdministrador LIKE ? ESCAPE '!' OR Descripcion LIKE ? ESCAPE '!')";
            $parametros[] = $patron;
            $parametros[] = $patron;
        }
        foreach (["Modulo" => $modulo, "Accion" => $accion] as $columna => $valor) {
            if ($valor !== "") {
                $condiciones[] = "$columna = ?";
                $parametros[] = $valor;
            }
        }
        if ($desde !== "") {
            $condiciones[] = "fechaRegistro >= ?";
            $parametros[] = $desde . " 00:00:00";
        }
        if ($hasta !== "") {
            $condiciones[] = "fechaRegistro <= ?";
            $parametros[] = $hasta . " 23:59:59";
        }
        $where = $condiciones ? " WHERE " . implode(" AND ", $condiciones) : "";
        $consulta = $conexion->prepare("SELECT COUNT(*) FROM bitacora $where");
        $consulta->execute($parametros);
        $totalFiltrado = (int) $consulta->fetchColumn();
        $paginas = max(1, (int) ceil($totalFiltrado / $porPagina));
        $pagina = min($pagina, $paginas);
        $offset = ($pagina - 1) * $porPagina;
        $consulta = $conexion->prepare("
            SELECT fechaRegistro, correoAdministrador, Modulo, Accion, Descripcion, idRegistro
            FROM bitacora $where
            ORDER BY fechaRegistro DESC, idBitacora DESC
            LIMIT $porPagina OFFSET $offset
        ");
        $consulta->execute($parametros);
        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = "No se pudo cargar la bitácora. Vuelva a intentarlo.";
}

$indicadores = ["Total de acciones" => $resumen["total"], "Acciones de hoy" => $resumen["hoy"],
    "Administradores con actividad" => $resumen["administradores"]];
$filtros = array_filter(compact("buscar", "modulo", "accion", "desde", "hasta"), fn($valor) => $valor !== "");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitácora | InvenTech Solutions</title>
    <script src="https://kit.fontawesome.com/4b21b27482.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css?v=2.0">
    <link rel="stylesheet" href="../css/crud.css?v=2.0">
</head>
<body>
    <?php require_once __DIR__ . "/sidebar.php"; ?>
    <div class="admin-content">
        <header class="admin-topbar"><div class="topbar-left"><span class="topbar-title">Administración / Bitácora</span></div></header>
        <main class="admin-main">
            <h1 class="mb-3">Bitácora administrativa</h1>
            <div class="row row-cols-1 row-cols-sm-3 g-2 mb-4">
                <?php foreach ($indicadores as $etiqueta => $total) { ?>
                    <div class="col">
                        <div class="card border-0 shadow-sm h-100 <?= $etiqueta === "Total de acciones" ? "resumen-total" : "" ?>">
                            <div class="card-body"><p class="small mb-1"><?= $etiqueta ?></p><p class="fs-4 fw-bold mb-0"><?= (int) $total ?></p></div>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <section class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h5 mb-3">Historial de actividades</h2>
                    <form method="GET" class="row g-2 align-items-end mb-3">
                        <div class="col-12 col-lg-4">
                            <label class="form-label" for="buscar">Correo o descripción</label>
                            <input class="form-control" id="buscar" name="buscar" value="<?= htmlspecialchars($buscar) ?>">
                        </div>
                        <?php foreach (["modulo" => ["Módulo", $modulos, $modulo], "accion" => ["Acción", $acciones, $accion]] as $campo => [$etiqueta, $opciones, $seleccion]) { ?>
                            <div class="col-6 col-lg">
                                <label class="form-label" for="<?= $campo ?>"><?= $etiqueta ?></label>
                                <select class="form-select" id="<?= $campo ?>" name="<?= $campo ?>">
                                    <option value="">Todas</option>
                                    <?php foreach ($opciones as $opcion) { ?>
                                        <option <?= $opcion === $seleccion ? "selected" : "" ?>><?= $opcion ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        <?php } ?>
                        <?php foreach (["desde" => ["Desde", $desde], "hasta" => ["Hasta", $hasta]] as $campo => [$etiqueta, $fecha]) { ?>
                            <div class="col-6 col-xl">
                                <label class="form-label" for="<?= $campo ?>"><?= $etiqueta ?></label>
                                <input type="date" class="form-control" id="<?= $campo ?>" name="<?= $campo ?>" value="<?= $fecha ?>">
                            </div>
                        <?php } ?>
                        <div class="col-auto d-flex gap-2">
                            <button type="submit" class="btn btn-dark">Aplicar filtros</button>
                            <a class="btn btn-outline-dark" href="bitacora.php">Limpiar filtros</a>
                        </div>
                    </form>

                    <?php if ($error !== "") { ?>
                        <div class="alert alert-warning" role="alert"><?= htmlspecialchars($error) ?></div>
                    <?php } else { ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead><tr><th scope="col">Fecha y hora</th><th scope="col">Administrador</th><th scope="col">Módulo</th><th scope="col">Acción</th><th scope="col">Descripción</th><th scope="col">Registro</th></tr></thead>
                                <tbody>
                                    <?php if (!$filas) { ?><tr><td colspan="6" class="text-center py-4">No hay actividades para mostrar.</td></tr><?php } ?>
                                    <?php foreach ($filas as $fila) { ?>
                                        <tr>
                                            <td class="text-nowrap"><?= htmlspecialchars($fila["fechaRegistro"]) ?></td>
                                            <td><?= htmlspecialchars($fila["correoAdministrador"]) ?></td>
                                            <td><?= htmlspecialchars($fila["Modulo"]) ?></td>
                                            <td><?= htmlspecialchars($fila["Accion"]) ?></td>
                                            <td><?= htmlspecialchars($fila["Descripcion"]) ?></td>
                                            <td><?= $fila["idRegistro"] === null ? "—" : "#" . (int) $fila["idRegistro"] ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($paginas > 1) { ?>
                            <nav class="d-flex flex-wrap align-items-center gap-2 mt-3" aria-label="Páginas de actividades">
                                <?php if ($pagina > 1) { ?>
                                    <a class="btn btn-outline-dark btn-sm" href="<?= htmlspecialchars("bitacora.php?" . http_build_query([...$filtros, "pagina" => $pagina - 1])) ?>">Anterior</a>
                                <?php } ?>
                                <span class="small">Página <?= $pagina ?> de <?= $paginas ?></span>
                                <?php if ($pagina < $paginas) { ?>
                                    <a class="btn btn-outline-dark btn-sm" href="<?= htmlspecialchars("bitacora.php?" . http_build_query([...$filtros, "pagina" => $pagina + 1])) ?>">Siguiente</a>
                                <?php } ?>
                            </nav>
                        <?php } ?>
                    <?php } ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
