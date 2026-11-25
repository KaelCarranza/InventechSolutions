<?php
require_once __DIR__ . "/../middlewares/protegerAdmin.php";
require_once __DIR__ . "/../middlewares/protegerCsrfAdmin.php";
require_once __DIR__ . "/../controllers/bitacora/registrarActividad.php";
require_once __DIR__ . "/../controllers/proformas/registrarProforma.php";
require_once __DIR__ . "/../controllers/proformas/consultarProforma.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "";
    $id = 0;
    $resultado = ["danger", "No se pudo guardar la proforma."];
    try {
        if ($accion === "registrar") {
            $clave = $_POST["claveProforma"] ?? null;
            if (!is_string($clave) || !isset($_SESSION["claveProforma"]) || !hash_equals($_SESSION["claveProforma"], $clave)) {
                throw new InvalidArgumentException("El formulario ya se procesó o caducó. Vuelva a abrirlo.");
            }
            $conexion->beginTransaction();
            $id = registrarProforma($conexion, $_POST, $clave);
            registrarActividad($conexion, "Proformas", "Registro", $id, "Proforma registrada.");
            $conexion->commit();
            unset($_SESSION["claveProforma"]);
            $resultado = ["success", "Proforma registrada correctamente."];
        } elseif ($accion === "cancelar") {
            $id = filter_input(INPUT_POST, "idProforma", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]) ?: 0;
            $cancelar = $conexion->prepare("UPDATE proformas SET Estado = 'Cancelada' WHERE idProforma = ? AND Estado = 'Vigente'");
            $conexion->beginTransaction();
            $cancelar->execute([$id]);
            $cancelada = $cancelar->rowCount() === 1;
            if ($cancelada) {
                registrarActividad($conexion, "Proformas", "Cancelación", $id, "Proforma cancelada.");
            }
            $conexion->commit();
            $resultado = $cancelada
                ? ["success", "Proforma cancelada."]
                : ["warning", "La proforma no existe o ya fue cancelada."];
        } else {
            throw new InvalidArgumentException("La acción solicitada no es válida.");
        }
    } catch (InvalidArgumentException $e) {
        $resultado = ["warning", $e->getMessage()];
    } catch (PDOException $e) {
        if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
            $resultado = ["warning", "La solicitud ya fue registrada."];
        } else {
            error_log($e->getMessage());
        }
    } finally {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
    }
    $_SESSION["resultadoProforma"] = $resultado;
    header("Location: " . BASE_URL . "admin/proformas.php" . ($id > 0 ? "?detalle=$id" : ""), true, 303);
    exit();
}

$_SESSION["claveProforma"] ??= bin2hex(random_bytes(32));
$resultado = $_SESSION["resultadoProforma"] ?? null;
unset($_SESSION["resultadoProforma"]);

$buscar = is_string($_GET["buscar"] ?? null) ? trim($_GET["buscar"]) : "";
$totales = ["Vigente" => 0, "Vencida" => 0, "Cancelada" => 0];
$estado = is_string($_GET["estado"] ?? null) && isset($totales[$_GET["estado"]]) ? $_GET["estado"] : "";
$fechas = [];
foreach (["desde", "hasta"] as $campo) {
    $valor = $_GET[$campo] ?? "";
    $fechas[$campo] = is_string($valor) && preg_match('/\A([1-9]\d{3})-(\d{2})-(\d{2})\z/', $valor, $partes)
        && checkdate((int) $partes[2], (int) $partes[3], (int) $partes[1]) ? $valor : "";
}
$idDetalle = filter_input(INPUT_GET, "detalle", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]) ?: 0;
$proforma = null;
$proformas = $clientes = $productos = [];
$errorProformas = "";

try {
    $hoy = $conexion->query("SELECT CURRENT_DATE()")->fetchColumn();
    foreach ($conexion->query("SELECT " . ESTADO_PROFORMA_SQL . " AS estadoActual, COUNT(*) AS total FROM proformas GROUP BY estadoActual") as $fila) {
        $totales[$fila["estadoActual"]] = (int) $fila["total"];
    }

    $condiciones = $parametros = [];
    if ($buscar !== "") {
        $patron = "%" . strtr($buscar, ["!" => "!!", "%" => "!%", "_" => "!_"]) . "%";
        $condicion = "(nombreSolicitante LIKE ? ESCAPE '!' OR correoSolicitante LIKE ? ESCAPE '!'";
        $parametros = [$patron, $patron];
        if (ctype_digit($buscar)) {
            $condicion .= " OR idProforma = ?";
            $parametros[] = $buscar;
        }
        $condiciones[] = $condicion . ")";
    }
    if ($estado !== "") {
        $condiciones[] = "(" . ESTADO_PROFORMA_SQL . ") = ?";
        $parametros[] = $estado;
    }
    if ($fechas["desde"] !== "") {
        $condiciones[] = "fechaEmision >= ?";
        $parametros[] = $fechas["desde"] . " 00:00:00";
    }
    if ($fechas["hasta"] !== "") {
        $condiciones[] = "fechaEmision <= ?";
        $parametros[] = $fechas["hasta"] . " 23:59:59";
    }
    $where = $condiciones ? " WHERE " . implode(" AND ", $condiciones) : "";
    $consulta = $conexion->prepare("
        SELECT idProforma, nombreSolicitante, correoSolicitante, fechaEmision, fechaVencimiento, Total,
            " . ESTADO_PROFORMA_SQL . " AS estadoActual
        FROM proformas $where ORDER BY fechaEmision DESC, idProforma DESC
    ");
    $consulta->execute($parametros);
    $proformas = $consulta->fetchAll(PDO::FETCH_ASSOC);

    $clientes = $conexion->query("SELECT userId, email FROM users WHERE role = 'Cliente' ORDER BY email")->fetchAll(PDO::FETCH_ASSOC);
    $productos = $conexion->query("
        SELECT idProducto, SKU, Producto, Precio FROM productos WHERE Activo = 1 ORDER BY Producto, SKU
    ")->fetchAll(PDO::FETCH_ASSOC);
    if ($idDetalle > 0) {
        $proforma = consultarProforma($conexion, $idDetalle);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $errorProformas = "No se pudieron cargar las proformas.";
}

$indicadores = [
    "Total de proformas" => array_sum($totales),
    "Vigentes" => $totales["Vigente"],
    "Vencidas" => $totales["Vencida"],
    "Canceladas" => $totales["Cancelada"]
];
$filtros = array_filter(["buscar" => $buscar, "estado" => $estado, ...$fechas], fn($valor) => $valor !== "");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proformas | InvenTech Solutions</title>
    <script src="https://kit.fontawesome.com/4b21b27482.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css?v=2.0">
    <link rel="stylesheet" href="../css/crud.css?v=2.0">
</head>
<body>
    <?php require_once __DIR__ . "/sidebar.php"; ?>
    <div class="admin-content">
        <header class="admin-topbar"><div class="topbar-left"><span class="topbar-title">Administración / Proformas</span></div></header>
        <main class="admin-main">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h1 class="mb-0">Proformas</h1>
                <?php if ($productos && $errorProformas === "") { ?>
                    <button type="button" class="btn btn-registrar" data-bs-toggle="modal" data-bs-target="#modalProforma">Registrar proforma</button>
                <?php } ?>
            </div>
            <?php if ($resultado) { ?>
                <div class="alert alert-<?= $resultado[0] ?>" role="alert"><?= htmlspecialchars($resultado[1]) ?></div>
            <?php } ?>
            <?php if ($errorProformas !== "") { ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errorProformas) ?></div>
            <?php } else { ?>
                <?php if (!$productos) { ?><p class="admin-description">Para emitir una proforma se necesita al menos un producto activo.</p><?php } ?>
                <div class="row row-cols-2 row-cols-lg-4 g-2 mb-4">
                    <?php foreach ($indicadores as $etiqueta => $total) { ?>
                        <div class="col">
                            <div class="card border-0 shadow-sm h-100 <?= $etiqueta === "Total de proformas" ? "resumen-total" : "" ?>">
                                <div class="card-body"><p class="small mb-1"><?= $etiqueta ?></p><p class="fs-4 fw-bold mb-0"><?= $total ?></p></div>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <?php if ($proforma) { ?>
                    <section class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                <a class="btn btn-outline-dark btn-sm" href="documentoProforma.php?id=<?= (int) $proforma["idProforma"] ?>">Imprimir o guardar PDF</a>
                                <a class="btn btn-outline-dark btn-sm" href="<?= htmlspecialchars("proformas.php?" . http_build_query($filtros)) ?>">Cerrar detalle</a>
                            </div>
                            <?php require __DIR__ . "/../includes/detalleProforma.php"; ?>
                            <p class="small admin-description">
                                <?= $proforma["userId"] === null ? "Sin cuenta asociada." : "Cliente asociado #" . (int) $proforma["userId"] . "." ?>
                            </p>
                            <?php if ($proforma["estadoActual"] !== "Cancelada") { ?>
                                <form method="POST" onsubmit="return confirm('¿Desea cancelar esta proforma?');">
                                    <input type="hidden" name="csrf_admin" value="<?= $tokenCsrfAdmin ?>">
                                    <input type="hidden" name="accion" value="cancelar">
                                    <input type="hidden" name="idProforma" value="<?= (int) $proforma["idProforma"] ?>">
                                    <button class="btn btn-outline-danger btn-sm" type="submit">Cancelar proforma</button>
                                </form>
                            <?php } ?>
                        </div>
                    </section>
                <?php } elseif ($idDetalle > 0) { ?>
                    <div class="alert alert-warning">La proforma solicitada no existe.</div>
                <?php } ?>

                <section class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h5 mb-3">Listado de proformas</h2>
                        <form method="GET" class="row g-2 align-items-end mb-3">
                            <div class="col-12 col-lg-4">
                                <label class="form-label" for="buscar">Número, nombre o correo</label>
                                <input class="form-control" id="buscar" name="buscar" value="<?= htmlspecialchars($buscar) ?>">
                            </div>
                            <div class="col-12 col-sm">
                                <label class="form-label" for="estado">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="">Todos</option>
                                    <?php foreach (array_keys($totales) as $opcion) { ?>
                                        <option <?= $opcion === $estado ? "selected" : "" ?>><?= $opcion ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <?php foreach (["desde" => "Emitida desde", "hasta" => "Emitida hasta"] as $campo => $etiqueta) { ?>
                                <div class="col-6 col-sm">
                                    <label class="form-label" for="<?= $campo ?>"><?= $etiqueta ?></label>
                                    <input type="date" class="form-control" id="<?= $campo ?>" name="<?= $campo ?>" value="<?= $fechas[$campo] ?>">
                                </div>
                            <?php } ?>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-dark">Filtrar</button>
                                <a class="btn btn-outline-dark" href="proformas.php">Limpiar</a>
                            </div>
                        </form>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead><tr><th>Número</th><th>Solicitante</th><th>Correo</th><th>Emisión</th><th>Vencimiento</th><th>Total</th><th>Estado</th><th>Acciones</th></tr></thead>
                                <tbody>
                                    <?php if (!$proformas) { ?><tr><td colspan="8" class="text-center py-4">No hay proformas para mostrar.</td></tr><?php } ?>
                                    <?php foreach ($proformas as $fila) { ?>
                                        <tr>
                                            <td>#<?= (int) $fila["idProforma"] ?></td><td><?= htmlspecialchars($fila["nombreSolicitante"]) ?></td>
                                            <td><?= htmlspecialchars($fila["correoSolicitante"]) ?></td><td><?= htmlspecialchars($fila["fechaEmision"]) ?></td>
                                            <td><?= htmlspecialchars($fila["fechaVencimiento"]) ?></td><td><?= htmlspecialchars($fila["Total"]) ?></td>
                                            <td><?= htmlspecialchars($fila["estadoActual"]) ?></td>
                                            <td><a class="btn btn-outline-dark btn-sm text-nowrap" href="<?= htmlspecialchars("proformas.php?" . http_build_query([...$filtros, "detalle" => $fila["idProforma"]])) ?>">Ver detalle</a></td>
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

    <?php if ($productos && $errorProformas === "") { ?>
        <div class="modal fade" id="modalProforma" tabindex="-1" aria-labelledby="tituloProforma" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" id="formProforma">
                        <input type="hidden" name="accion" value="registrar">
                        <input type="hidden" name="csrf_admin" value="<?= $tokenCsrfAdmin ?>">
                        <input type="hidden" name="claveProforma" value="<?= $_SESSION["claveProforma"] ?>">
                        <div class="modal-header">
                            <h2 class="modal-title h5" id="tituloProforma">Registrar proforma</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-2 mb-3">
                                <label class="col-sm-6">Nombre del solicitante
                                    <input class="form-control" name="nombre" maxlength="150" required>
                                </label>
                                <label class="col-sm-6">Correo electrónico
                                    <input type="email" class="form-control" name="correo" maxlength="255" required>
                                </label>
                                <label class="col-12">Cliente asociado (opcional)
                                    <select class="form-select" name="userId">
                                        <option value="">Sin cuenta asociada</option>
                                        <?php foreach ($clientes as $cliente) { ?>
                                            <option value="<?= (int) $cliente["userId"] ?>"><?= htmlspecialchars($cliente["email"]) ?></option>
                                        <?php } ?>
                                    </select>
                                </label>
                                <label class="col-sm-6">Tasa de impuesto (%)
                                    <input type="number" class="form-control" name="tasaImpuesto" min="0" max="100" step="0.01" required>
                                </label>
                                <label class="col-sm-6">Vigente hasta (inclusive)
                                    <input type="date" class="form-control" name="fechaVencimiento" min="<?= htmlspecialchars($hoy) ?>" required>
                                </label>
                            </div>
                            <div id="lineasProforma">
                                <div class="linea-proforma border rounded p-2 mb-2">
                                    <div class="row g-2">
                                        <label class="col-sm-8">Producto
                                            <select class="form-select" name="idProducto[]" required>
                                                <option value="">Seleccione</option>
                                                <?php foreach ($productos as $producto) { ?>
                                                    <option value="<?= (int) $producto["idProducto"] ?>" data-precio="<?= htmlspecialchars($producto["Precio"]) ?>">
                                                        <?= htmlspecialchars($producto["SKU"] . " · " . $producto["Producto"]) ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </label>
                                        <label class="col-sm-4">Cantidad
                                            <input type="number" class="form-control" name="cantidad[]" min="1" max="4294967295" step="1" value="1" required>
                                        </label>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-3 mt-2 small">
                                        <span>Precio unitario <output class="precio-linea">0.00</output></span>
                                        <span>Subtotal <output class="subtotal-linea">0.00</output></span>
                                        <button type="button" class="btn btn-outline-dark btn-sm quitar-linea ms-auto" hidden>Quitar</button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" id="agregarLineaProforma" class="btn btn-outline-dark btn-sm" hidden>Agregar producto</button>
                            <dl class="row text-end mb-2">
                                <dt class="col-8">Subtotal</dt><dd class="col-4"><output id="subtotalProforma">0.00</output></dd>
                                <dt class="col-8">Impuestos</dt><dd class="col-4"><output id="impuestosProforma">0.00</output></dd>
                                <dt class="col-8">Total</dt><dd class="col-4"><output id="totalProforma">0.00</output></dd>
                            </dl>
                            <p class="small admin-description mb-0">La cotización no reserva existencias ni garantiza disponibilidad futura.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Cerrar</button>
                            <button type="submit" class="btn btn-registrar">Guardar proforma</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php } ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/proformas.js" defer></script>
</body>
</html>
