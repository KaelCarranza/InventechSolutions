<?php
require_once __DIR__ . "/../middlewares/protegerAdmin.php";
require_once __DIR__ . "/../middlewares/protegerCsrfAdmin.php";
require_once __DIR__ . "/../controllers/bitacora/registrarActividad.php";
require_once __DIR__ . "/../controllers/pedidos/registrarPedido.php";
require_once __DIR__ . "/../controllers/pedidos/cambiarEstadoPedido.php";

$transiciones = [
    "Pendiente" => ["Pagado", "Cancelado"],
    "Pagado" => ["Enviado", "Cancelado"],
    "Enviado" => ["Entregado"],
    "Entregado" => [],
    "Cancelado" => []
];
$metodosPago = ["Efectivo", "Tarjeta"];
$estados = array_keys($transiciones);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "";
    $idPedido = filter_input(INPUT_POST, "idPedido", FILTER_VALIDATE_INT) ?: 0;
    $destino = BASE_URL . "admin/pedidos.php";
    $resultado = ["danger", "No se pudo guardar el pedido."];
    try {
        $conexion->beginTransaction();
        if ($accion === "registrar") {
            $clave = $_POST["clavePedido"] ?? null;
            if (!is_string($clave) || !isset($_SESSION["clavePedido"]) || !hash_equals($_SESSION["clavePedido"], $clave)) {
                throw new InvalidArgumentException("El formulario ya se procesó o caducó. Vuelva a abrirlo.");
            }
            $idPedido = registrarPedido($conexion, $_POST, $metodosPago);
            registrarActividad($conexion, "Pedidos", "Registro", $idPedido, "Pedido registrado como Pendiente.");
            $mensaje = "Pedido registrado como Pendiente.";
        } elseif ($accion === "estado" && $idPedido > 0 && is_string($_POST["estado"] ?? null)) {
            $nuevoEstado = $_POST["estado"];
            $estadoAnterior = cambiarEstadoPedido($conexion, $idPedido, (int) $_SESSION["userId"], $nuevoEstado, $transiciones);
            registrarActividad(
                $conexion, "Pedidos", $nuevoEstado === "Cancelado" ? "Cancelación" : "Cambio de estado",
                $idPedido, "Estado del pedido: $estadoAnterior → $nuevoEstado."
            );
            $mensaje = "Estado del pedido actualizado.";
        } else {
            throw new InvalidArgumentException("La acción solicitada no es válida.");
        }
        $conexion->commit();
        if ($accion === "registrar") {
            unset($_SESSION["clavePedido"]);
        }
        $resultado = ["success", $mensaje];
    } catch (InvalidArgumentException $e) {
        $resultado = ["warning", $e->getMessage()];
    } catch (PDOException $e) {
        error_log($e->getMessage());
    } finally {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
    }
    if ($idPedido > 0 && ($accion === "estado" || $resultado[0] === "success")) {
        $destino .= "?detalle=" . $idPedido;
    }
    $_SESSION["resultadoPedido"] = $resultado;
    header("Location: " . $destino, true, 303);
    exit();
}

$_SESSION["clavePedido"] ??= bin2hex(random_bytes(32));
$resultado = $_SESSION["resultadoPedido"] ?? null;
unset($_SESSION["resultadoPedido"]);

$buscar = is_string($_GET["buscar"] ?? null) ? trim($_GET["buscar"]) : "";
$estado = in_array($_GET["estado"] ?? "", $estados, true) ? $_GET["estado"] : "";
$fechas = [];
foreach (["desde", "hasta"] as $campo) {
    $valor = $_GET[$campo] ?? "";
    $fechas[$campo] = is_string($valor) && preg_match('/\A([1-9]\d{3})-(\d{2})-(\d{2})\z/', $valor, $partes)
        && checkdate((int) $partes[2], (int) $partes[3], (int) $partes[1]) ? $valor : "";
}

$contadores = array_fill_keys($estados, 0);
$pedidos = $clientes = $productos = $lineas = [];
$detalle = null;
$idDetalle = filter_input(INPUT_GET, "detalle", FILTER_VALIDATE_INT) ?: 0;
$errorPedidos = "";
try {
    $consulta = $conexion->query("SELECT Estado, COUNT(*) AS total FROM pedidos GROUP BY Estado");
    foreach ($consulta as $fila) {
        $contadores[$fila["Estado"]] = (int) $fila["total"];
    }

    $condiciones = [];
    $parametros = [];
    if ($buscar !== "") {
        if (ctype_digit($buscar)) {
            $condiciones[] = "p.idPedido = ?";
            $parametros[] = $buscar;
        } else {
            $condiciones[] = "u.email LIKE ? ESCAPE '!'";
            $parametros[] = "%" . strtr($buscar, ["!" => "!!", "%" => "!%", "_" => "!_"]) . "%";
        }
    }
    if ($estado !== "") {
        $condiciones[] = "p.Estado = ?";
        $parametros[] = $estado;
    }
    if ($fechas["desde"] !== "") {
        $condiciones[] = "p.fechaPedido >= ?";
        $parametros[] = $fechas["desde"] . " 00:00:00";
    }
    if ($fechas["hasta"] !== "") {
        $condiciones[] = "p.fechaPedido <= ?";
        $parametros[] = $fechas["hasta"] . " 23:59:59";
    }
    $where = $condiciones ? " WHERE " . implode(" AND ", $condiciones) : "";
    $consulta = $conexion->prepare("
        SELECT p.idPedido, p.Estado, p.Total, p.fechaPedido, p.metodoPago, u.email
        FROM pedidos p JOIN users u ON u.userId = p.userId
        $where ORDER BY p.fechaPedido DESC, p.idPedido DESC
    ");
    $consulta->execute($parametros);
    $pedidos = $consulta->fetchAll(PDO::FETCH_ASSOC);

    $clientes = $conexion->query("
        SELECT userId, email FROM users WHERE role = 'Cliente' AND loginStatus = 'Habilitado' ORDER BY email
    ")->fetchAll(PDO::FETCH_ASSOC);
    $productos = $conexion->query("
        SELECT idProducto, SKU, Producto, Precio FROM productos WHERE Activo = 1 ORDER BY Producto, SKU
    ")->fetchAll(PDO::FETCH_ASSOC);

    if ($idDetalle > 0) {
        $consulta = $conexion->prepare("
            SELECT p.idPedido, p.userId, p.Estado, p.Total, p.fechaPedido, p.metodoPago, u.email
            FROM pedidos p JOIN users u ON u.userId = p.userId WHERE p.idPedido = ?
        ");
        $consulta->execute([$idDetalle]);
        $detalle = $consulta->fetch(PDO::FETCH_ASSOC);
        if ($detalle) {
            $consulta = $conexion->prepare("
                SELECT nombreProducto, precioUnitario, cantidad, subtotal
                FROM detallepedido WHERE idPedido = ? ORDER BY idDetalle
            ");
            $consulta->execute([$idDetalle]);
            $lineas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $errorPedidos = "No se pudieron cargar los pedidos.";
}

$filtros = ["buscar" => $buscar, "estado" => $estado, ...$fechas];
$enlaceListado = "pedidos.php?" . http_build_query(array_filter($filtros, fn($valor) => $valor !== ""));
$indicadores = ["Total de pedidos" => array_sum($contadores), ...$contadores];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos | InvenTech Solutions</title>
    <script src="https://kit.fontawesome.com/4b21b27482.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css?v=2.0">
    <link rel="stylesheet" href="../css/crud.css?v=2.0">
</head>
<body>
    <?php require_once __DIR__ . "/sidebar.php"; ?>
    <div class="admin-content">
        <header class="admin-topbar">
            <div class="topbar-left"><span class="topbar-title">Administración / Pedidos</span></div>
        </header>
        <main class="admin-main">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h1 class="mb-0">Pedidos</h1>
                <?php if ($clientes && $productos && $errorPedidos === "") { ?>
                    <button type="button" class="btn btn-registrar" data-bs-toggle="modal" data-bs-target="#modalPedido">Registrar pedido</button>
                <?php } ?>
            </div>

            <?php if ($resultado) { ?>
                <div class="alert alert-<?= $resultado[0] ?>" role="alert"><?= htmlspecialchars($resultado[1]) ?></div>
            <?php } ?>
            <?php if ($errorPedidos !== "") { ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errorPedidos) ?></div>
            <?php } else { ?>
                <?php if (!$clientes || !$productos) { ?>
                    <p class="admin-description">Para registrar un pedido necesitas un cliente habilitado y al menos un producto activo.</p>
                <?php } ?>
                <div class="row row-cols-2 row-cols-md-3 row-cols-xl-6 g-2 mb-4">
                    <?php foreach ($indicadores as $etiqueta => $total) { ?>
                        <div class="col">
                            <div class="card border-0 shadow-sm h-100 <?= $etiqueta === "Total de pedidos" ? "resumen-total" : "" ?>">
                                <div class="card-body p-3">
                                    <p class="small mb-1"><?= $etiqueta ?></p>
                                    <p class="fs-4 fw-bold mb-0"><?= $total ?></p>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <?php if ($detalle) { ?>
                    <section class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                <h2 class="h5 mb-0">Pedido #<?= (int) $detalle["idPedido"] ?></h2>
                                <a class="btn btn-outline-dark btn-sm" href="<?= htmlspecialchars($enlaceListado) ?>">Cerrar detalle</a>
                            </div>
                            <dl class="row mb-2">
                                <dt class="col-sm-3">Cliente</dt><dd class="col-sm-9"><?= htmlspecialchars($detalle["email"]) ?> (#<?= (int) $detalle["userId"] ?>)</dd>
                                <dt class="col-sm-3">Fecha</dt><dd class="col-sm-9"><?= htmlspecialchars($detalle["fechaPedido"]) ?></dd>
                                <dt class="col-sm-3">Método de pago</dt><dd class="col-sm-9"><?= htmlspecialchars($detalle["metodoPago"]) ?></dd>
                                <dt class="col-sm-3">Estado</dt><dd class="col-sm-9"><?= htmlspecialchars($detalle["Estado"]) ?></dd>
                            </dl>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead><tr><th>Producto</th><th>Cantidad</th><th>Precio unitario</th><th>Subtotal</th></tr></thead>
                                    <tbody>
                                        <?php if (!$lineas) { ?><tr><td colspan="4">Este pedido no tiene líneas.</td></tr><?php } ?>
                                        <?php foreach ($lineas as $linea) { ?>
                                            <tr>
                                                <td><?= htmlspecialchars($linea["nombreProducto"]) ?></td>
                                                <td><?= (int) $linea["cantidad"] ?></td>
                                                <td><?= htmlspecialchars($linea["precioUnitario"]) ?></td>
                                                <td><?= htmlspecialchars($linea["subtotal"]) ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                    <tfoot><tr><th colspan="3">Total</th><td><?= htmlspecialchars($detalle["Total"]) ?></td></tr></tfoot>
                                </table>
                            </div>
                            <?php if ($transiciones[$detalle["Estado"]] ?? []) { ?>
                                <form method="POST" class="d-flex flex-wrap align-items-end gap-2">
                                    <input type="hidden" name="csrf_admin" value="<?= $tokenCsrfAdmin ?>">
                                    <input type="hidden" name="accion" value="estado">
                                    <input type="hidden" name="idPedido" value="<?= (int) $detalle["idPedido"] ?>">
                                    <div>
                                        <label class="form-label" for="nuevoEstado">Cambiar estado</label>
                                        <select class="form-select" id="nuevoEstado" name="estado" required>
                                            <option value="">Seleccione</option>
                                            <?php foreach ($transiciones[$detalle["Estado"]] as $opcion) { ?>
                                                <option><?= $opcion ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <button class="btn btn-registrar" type="submit">Actualizar estado</button>
                                </form>
                                <?php if ($detalle["Estado"] === "Pendiente") { ?>
                                    <p class="small admin-description mt-2 mb-0">Marcar como Pagado confirma el pago y descuenta las existencias.</p>
                                <?php } ?>
                            <?php } ?>
                        </div>
                    </section>
                <?php } elseif ($idDetalle > 0) { ?>
                    <div class="alert alert-warning">El pedido solicitado no existe.</div>
                <?php } ?>

                <section class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h5 mb-3">Listado de pedidos</h2>
                        <form method="GET" class="row g-2 align-items-end mb-3">
                            <div class="col-12 col-lg-4">
                                <label class="form-label" for="buscar">Número o correo del cliente</label>
                                <input class="form-control" id="buscar" name="buscar" value="<?= htmlspecialchars($buscar) ?>">
                            </div>
                            <div class="col-12 col-sm">
                                <label class="form-label" for="estado">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="">Todos</option>
                                    <?php foreach ($estados as $opcion) { ?>
                                        <option <?= $opcion === $estado ? "selected" : "" ?>><?= $opcion ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <?php foreach (["desde" => "Desde", "hasta" => "Hasta"] as $campo => $etiqueta) { ?>
                                <div class="col-6 col-sm">
                                    <label class="form-label" for="<?= $campo ?>"><?= $etiqueta ?></label>
                                    <input type="date" class="form-control" id="<?= $campo ?>" name="<?= $campo ?>" value="<?= $fechas[$campo] ?>">
                                </div>
                            <?php } ?>
                            <div class="col-auto"><button class="btn btn-dark" type="submit">Filtrar</button>
                                <a class="btn btn-outline-dark" href="pedidos.php">Limpiar</a></div>
                        </form>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead><tr><th>Número</th><th>Cliente</th><th>Fecha</th><th>Método de pago</th><th>Total</th><th>Estado</th><th>Acciones</th></tr></thead>
                                <tbody>
                                    <?php if (!$pedidos) { ?><tr><td colspan="7" class="text-center py-4">No hay pedidos para mostrar.</td></tr><?php } ?>
                                    <?php foreach ($pedidos as $pedido) { ?>
                                        <tr>
                                            <td>#<?= (int) $pedido["idPedido"] ?></td>
                                            <td><?= htmlspecialchars($pedido["email"]) ?></td>
                                            <td class="text-nowrap"><?= htmlspecialchars($pedido["fechaPedido"]) ?></td>
                                            <td><?= htmlspecialchars($pedido["metodoPago"]) ?></td>
                                            <td><?= htmlspecialchars($pedido["Total"]) ?></td>
                                            <td><?= htmlspecialchars($pedido["Estado"]) ?></td>
                                            <td><a class="btn btn-outline-dark btn-sm text-nowrap" href="<?= htmlspecialchars("pedidos.php?" . http_build_query([...$filtros, "detalle" => $pedido["idPedido"]])) ?>">Ver detalle</a></td>
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

    <?php if ($clientes && $productos && $errorPedidos === "") { ?>
        <div class="modal fade" id="modalPedido" tabindex="-1" aria-labelledby="tituloPedido" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" id="formPedido">
                        <input type="hidden" name="accion" value="registrar">
                        <input type="hidden" name="csrf_admin" value="<?= $tokenCsrfAdmin ?>">
                        <input type="hidden" name="clavePedido" value="<?= $_SESSION["clavePedido"] ?>">
                        <div class="modal-header">
                            <h2 class="modal-title h5" id="tituloPedido">Registrar pedido</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-2 mb-3">
                                <div class="col-sm">
                                    <label class="form-label" for="clientePedido">Cliente</label>
                                    <select class="form-select" id="clientePedido" name="userId" required>
                                        <option value="">Seleccione</option>
                                        <?php foreach ($clientes as $cliente) { ?>
                                            <option value="<?= (int) $cliente["userId"] ?>"><?= htmlspecialchars($cliente["email"]) ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-sm">
                                    <label class="form-label" for="metodoPago">Método de pago</label>
                                    <select class="form-select" id="metodoPago" name="metodoPago" required>
                                        <option value="">Seleccione</option>
                                        <?php foreach ($metodosPago as $metodo) { ?><option><?= $metodo ?></option><?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div id="lineasPedido">
                                <div class="linea-pedido border rounded p-2 mb-2">
                                    <div class="row g-2 align-items-end">
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
                            <button type="button" id="agregarLinea" class="btn btn-outline-dark btn-sm" hidden>Agregar producto</button>
                            <p class="text-end fw-bold mb-1">Total <output id="totalPedido">0.00</output></p>
                            <p class="small admin-description mb-0">El pedido se registra como Pendiente y no reserva existencias.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Cerrar</button>
                            <button type="submit" class="btn btn-registrar">Guardar pedido</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php } ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/pedidos.js" defer></script>
</body>
</html>
