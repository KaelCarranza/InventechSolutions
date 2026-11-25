<?php
require_once __DIR__ . "/../middlewares/protegerAdmin.php";
require_once __DIR__ . "/../middlewares/protegerCsrfAdmin.php";
require_once __DIR__ . "/../config/db.php";

exigirOperacionPost(["btnInsertar", "btnModificar", "btnEliminar", "btnCambiarEstado"]);


ob_start();

include __DIR__ . "/../controllers/productos/registrarProducto.php";
include __DIR__ . "/../controllers/productos/eliminarProducto.php";
include __DIR__ . "/../controllers/productos/modificarProducto.php";
include __DIR__ . "/../controllers/productos/cambiarEstadoProducto.php";

$mensajes = ob_get_clean();

// Estadísticas
$totalProductos = 0;
$totalActivos = 0;
$totalInactivos = 0;

try {

    $sql = $conexion->query("
        SELECT
            COUNT(*) AS totalProductos,
            COUNT(CASE WHEN Activo = 1 THEN 1 END) AS totalActivos,
            COUNT(CASE WHEN Activo = 0 THEN 1 END) AS totalInactivos
        FROM productos
    ");

    $estadisticas = $sql->fetch(PDO::FETCH_ASSOC);

    $totalProductos = (int) $estadisticas["totalProductos"];
    $totalActivos = (int) $estadisticas["totalActivos"];
    $totalInactivos = (int) $estadisticas["totalInactivos"];
} catch (PDOException $e) {

    $mensajes .= '<div class="alert alert-danger">
        Error al cargar las estadísticas.
    </div>';
}

try {
    // Consultar productos
    $sql = $conexion->query("
        SELECT productos.*, categorias.Nombre AS NombreCategoria
        FROM productos
        LEFT JOIN categorias
            ON productos.idCategoria = categorias.idCategoria
        ORDER BY productos.idProducto DESC
    ");

    $productos = $sql->fetchAll(PDO::FETCH_OBJ);
    $categoriasEditar = $conexion->query("SELECT idCategoria, Nombre FROM categorias ORDER BY Nombre")->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log($e->getMessage());
    responderError(503, "No se pudo cargar el listado. Inténtelo nuevamente.");
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestión de productos | InvenTech Solutions</title>


    <script src="https://kit.fontawesome.com/4b21b27482.js"
        crossorigin="anonymous"></script>


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <link rel="stylesheet" href="../css/sidebar.css?v=2.0">


    <link rel="stylesheet" href="../css/crud.css?v=2.0">
    <style>
        @media (min-width: 992px) {
            .form-producto-alta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 1rem; }
            .form-producto-alta button { grid-column: 1 / -1; }
            .tabla-productos th, .tabla-productos td { padding-inline: .35rem; }
            .tabla-productos .descripcion-producto { min-width: 8rem; }
            .tabla-productos .crud-acciones { flex-wrap: wrap; gap: .25rem; }
            .tabla-productos td:nth-child(2), .tabla-productos td:nth-child(4) { overflow-wrap: anywhere; }
        }
    </style>

</head>

<body>


    <?php require_once __DIR__ . "/sidebar.php"; ?>

    <div class="admin-content">


        <header class="admin-topbar">

            <div class="topbar-left">

                <span class="topbar-title">
                    Administración / Productos
                </span>

            </div>

            <div class="topbar-user">

                <i class="fa-solid fa-circle-user"></i>
                <span>Administrador</span>

            </div>

        </header>


        <main class="admin-main">


            <div class="mb-4">

                <h1>Gestión de productos</h1>

            </div>

            <!-- Indicadores -->
            <div class="row g-3 mb-4">


                <div class="col-12 col-md-4">
                    <div class="card resumen-card resumen-total border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center justify-content-between">

                            <div>
                                <p class="resumen-label mb-1">Total de productos</p>
                                <h3 class="resumen-numero mb-0">
                                    <?= $totalProductos ?>
                                </h3>
                            </div>

                            <div class="resumen-icono">
                                <i class="fa-solid fa-boxes-stacked"></i>
                            </div>

                        </div>
                    </div>
                </div>


                <div class="col-12 col-md-4">
                    <div class="card resumen-card resumen-admin border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center justify-content-between">

                            <div>
                                <p class="resumen-label mb-1">Productos activos</p>
                                <h3 class="resumen-numero mb-0">
                                    <?= $totalActivos ?>
                                </h3>
                            </div>

                            <div class="resumen-icono">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>

                        </div>
                    </div>
                </div>


                <div class="col-12 col-md-4">
                    <div class="card resumen-card resumen-cliente border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center justify-content-between">

                            <div>
                                <p class="resumen-label mb-1">Productos inactivos</p>
                                <h3 class="resumen-numero mb-0">
                                    <?= $totalInactivos ?>
                                </h3>
                            </div>

                            <div class="resumen-icono">
                                <i class="fa-solid fa-circle-xmark"></i>
                            </div>

                        </div>
                    </div>
                </div>

            </div>


            <?= $mensajes ?>

            <div class="row g-4">

                <!-- Registrar producto -->
                <div class="col-12">

                    <div class="crud-card">

                        <div class="crud-card-header">

                            <div>
                                <h5>Registrar producto</h5>
                            </div>

                        </div>

                        <form method="POST" enctype="multipart/form-data" class="form-producto-alta">
                            <input type="hidden" name="csrf_admin" value="<?= $tokenCsrfAdmin ?>">


                            <div class="mb-3">
                                <label for="sku" class="form-label"> SKU </label>
                                <input type="text" class="form-control" id="sku" name="sku" maxlength="50" placeholder="Ejemplo: CPU-AMD-5600G" required>
                            </div>



                            <div class="mb-3">
                                <label for="producto" class="form-label"> Producto </label>
                                <input type="text" class="form-control" id="producto" name="producto" placeholder="Nombre del producto" required>
                            </div>


                            <div class="mb-3">
                                <label for="precio" class="form-label"> Precio </label>
                                <input type="number" class="form-control" id="precio" name="precio" step="0.01" min="0" placeholder="0.00" required>
                            </div>


                            <div class="mb-3">
                                <label for="idCategoria" class="form-label"> Categoría </label>
                                <select class="form-select" id="idCategoria" name="idCategoria" required>
                                    <option value="">Seleccione una categoría</option>

                                    <?php
                                    foreach ($categoriasEditar as $categoria) {
                                    ?>
                                        <option value="<?= (int) $categoria->idCategoria ?>"> <?= htmlspecialchars($categoria->Nombre) ?> </option>
                                    <?php } ?>
                                </select>
                            </div>



                            <div class="mb-3">
                                <label for="descripcion" class="form-label"> Descripción </label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3" placeholder="Descripción del producto" required></textarea>
                            </div>


                            <div class="mb-4">
                                <label for="imagen" class="form-label"> Imagen del producto </label>
                                <input type="file" class="form-control" id="imagen" name="imagen" accept=".jpg,.jpeg,.png,.webp" required>
                                <div class="form-text"> Formatos JPG, PNG y WEBP. Tamaño máximo: 2 MB. </div>
                            </div>


                            <button type="submit" class="btn btn-registrar w-100" name="btnInsertar" value="1"> <i class="fa-solid fa-plus me-2"></i> Registrar producto </button>
                        </form>

                    </div>

                </div>

                <!-- Lista de productos -->
                <div class="col-12">
                    <div class="crud-card">
                        <div class="crud-card-header">

                            <div>
                                <h5>Productos registrados</h5>
                            </div>

                        </div>

                        <div class="table-responsive">
                            <table class="table crud-table tabla-productos table-hover align-middle mb-0">

                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>SKU</th>
                                        <th>Imagen</th>
                                        <th>Producto</th>
                                        <th>Precio</th>
                                        <th>Categoria</th>
                                        <th>Descripción</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <?php if (empty($productos)) { ?>

                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                No hay productos registrados.
                                            </td>
                                        </tr>

                                    <?php } ?>

                                    <?php foreach ($productos as $datos) {

                                        $idProducto = (int) $datos->idProducto;
                                        $esActivo = (int) $datos->Activo === 1;

                                    ?>

                                        <tr>

                                            <td>
                                                #<?= $idProducto ?>
                                            </td>


                                            <td>
                                                <span class="sku-producto">
                                                    <?= htmlspecialchars($datos->SKU ?? "Sin asignar") ?>
                                                </span>
                                            </td>


                                            <td>

                                                <?php if (!empty($datos->Imagen) && is_file(__DIR__ . "/../assets/img/productos/" . basename($datos->Imagen))): ?>

                                                    <img
                                                        class="producto-imagen"
                                                        src="../assets/img/productos/<?= rawurlencode($datos->Imagen) ?>"
                                                        alt="<?= htmlspecialchars($datos->Producto, ENT_QUOTES, 'UTF-8') ?>">

                                                <?php else: ?>

                                                    <div class="producto-sin-imagen">
                                                        <i class="fa-solid fa-image" aria-hidden="true"></i>
                                                    </div>
                                                    <small class="text-muted d-block">Sin imagen</small>

                                                <?php endif; ?>

                                            </td>


                                            <td class="fw-semibold">

                                                <?= htmlspecialchars($datos->Producto) ?>

                                            </td>


                                            <td class="precio-producto">

                                                ₡<?= number_format((float) $datos->Precio, 2) ?>

                                            </td>


                                            <td>
                                                <span class="producto-categoria">
                                                    <?= htmlspecialchars($datos->NombreCategoria ?? "Sin categoría") ?>
                                                </span>
                                            </td>


                                            <td class="descripcion-producto">

                                                <?= htmlspecialchars($datos->Descripcion ?? "") ?>

                                            </td>


                                            <td>

                                                <div class="crud-acciones">


                                                    <button type="button"
                                                        class="accion-btn accion-editar"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalEditarProducto<?= $idProducto ?>"
                                                        title="Editar producto"
                                                        aria-label="Editar producto">

                                                        <i class="fa-solid fa-pen-to-square"></i>

                                                    </button>


                                                    <form method="POST"
                                                        class="d-inline"
                                                        onsubmit="return confirm('¿Desea <?= $esActivo ? 'desactivar' : 'activar' ?> este producto?');">
                                                        <input type="hidden" name="csrf_admin" value="<?= $tokenCsrfAdmin ?>">

                                                        <input type="hidden"
                                                            name="idProducto"
                                                            value="<?= $idProducto ?>">
                                                        <input type="hidden" name="estadoDeseado" value="<?= $esActivo ? "0" : "1" ?>">

                                                        <button type="submit"
                                                            class="accion-btn <?= $esActivo ? 'accion-deshabilitar' : 'accion-habilitar' ?>"
                                                            name="btnCambiarEstado"
                                                            value="1"
                                                            title="<?= $esActivo ? 'Desactivar producto' : 'Activar producto' ?>"
                                                            aria-label="<?= $esActivo ? 'Desactivar producto' : 'Activar producto' ?>">

                                                            <i class="fa-solid <?= $esActivo ? 'fa-ban' : 'fa-circle-check' ?>"></i>

                                                        </button>

                                                    </form>


                                                    <form method="POST"
                                                        class="d-inline"
                                                        onsubmit="return confirm('¿Desea eliminar este producto?');">
                                                        <input type="hidden" name="csrf_admin" value="<?= $tokenCsrfAdmin ?>">

                                                        <input type="hidden"
                                                            name="idProducto"
                                                            value="<?= $idProducto ?>">

                                                        <button type="submit"
                                                            class="accion-btn accion-eliminar"
                                                            name="btnEliminar"
                                                            value="1"
                                                            title="Eliminar producto"
                                                            aria-label="Eliminar producto">

                                                            <i class="fa-solid fa-trash"></i>

                                                        </button>

                                                    </form>

                                                </div>

                                            </td>
                                        </tr>

                                    <?php } ?>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>
        </main>

    </div>

    <!-- Editar productos -->

    <?php foreach ($productos as $datos) {

        $idProducto = (int) $datos->idProducto;

    ?>

        <div class="modal fade"
            id="modalEditarProducto<?= $idProducto ?>"
            tabindex="-1"
            aria-labelledby="tituloEditarProducto<?= $idProducto ?>"
            aria-hidden="true">

            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">

                <div class="modal-content">

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_admin" value="<?= $tokenCsrfAdmin ?>">


                        <div class="modal-header">

                            <h5 class="modal-title"
                                id="tituloEditarProducto<?= $idProducto ?>">

                                Modificar producto

                            </h5>

                            <button type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Cerrar">
                            </button>

                        </div>


                        <div class="modal-body">

                            <input type="hidden"
                                name="idProducto"
                                value="<?= $idProducto ?>">

                            <div class="row g-3">


                                <div class="col-12 col-md-6">

                                    <label class="form-label">SKU</label>

                                    <input type="text"
                                        class="form-control"
                                        name="sku"
                                        maxlength="50"
                                        value="<?= htmlspecialchars($datos->SKU ?? "", ENT_QUOTES, 'UTF-8') ?>"
                                        required>

                                </div>


                                <div class="col-12 col-md-6">

                                    <label class="form-label">Producto</label>

                                    <input type="text"
                                        class="form-control"
                                        name="producto"
                                        value="<?= htmlspecialchars($datos->Producto, ENT_QUOTES, 'UTF-8') ?>"
                                        required>

                                </div>


                                <div class="col-12 col-md-6">

                                    <label class="form-label">Precio</label>

                                    <input type="number"
                                        class="form-control"
                                        name="precio"
                                        min="0"
                                        step="0.01"
                                        value="<?= htmlspecialchars((string) $datos->Precio) ?>"
                                        required>

                                </div>


                                <div class="col-12 col-md-6">

                                    <label class="form-label">Categoría</label>

                                    <select class="form-select"
                                        name="idCategoria"
                                        required>

                                        <option value="">
                                            Seleccione una categoría
                                        </option>

                                        <?php foreach ($categoriasEditar as $categoria) { ?>

                                            <option
                                                value="<?= (int) $categoria->idCategoria ?>"
                                                <?= (int) $datos->idCategoria === (int) $categoria->idCategoria
                                                    ? "selected" : "" ?>>

                                                <?= htmlspecialchars($categoria->Nombre) ?>

                                            </option>

                                        <?php } ?>

                                    </select>

                                </div>


                                <div class="col-12">

                                    <label class="form-label">
                                        Descripción
                                    </label>

                                    <textarea
                                        class="form-control"
                                        name="descripcion"
                                        rows="3"
                                        required><?= htmlspecialchars($datos->Descripcion ?? "", ENT_QUOTES, 'UTF-8') ?></textarea>

                                </div>


                                <div class="col-12 col-md-4">

                                    <label class="form-label">
                                        Imagen actual
                                    </label>

                                    <div class="imagen-editar-contenedor">

                                        <?php if (!empty($datos->Imagen) && is_file(__DIR__ . "/../assets/img/productos/" . basename($datos->Imagen))) { ?>

                                            <img
                                                src="../assets/img/productos/<?= rawurlencode($datos->Imagen) ?>"
                                                alt="Imagen del producto"
                                                class="imagen-editar"
                                                id="preview<?= $idProducto ?>">

                                        <?php } else { ?>

                                            <img
                                                alt="Vista previa"
                                                class="imagen-editar d-none"
                                                id="preview<?= $idProducto ?>">

                                            <span class="text-muted" data-sin-imagen>
                                                Sin imagen
                                            </span>

                                        <?php } ?>

                                    </div>

                                </div>


                                <div class="col-12 col-md-8">

                                    <label class="form-label">
                                        Nueva imagen
                                    </label>

                                    <input type="file"
                                        class="form-control"
                                        name="imagen"
                                        accept=".jpg,.jpeg,.png,.webp"
                                        onchange="previsualizarImagen(this, <?= $idProducto ?>)">

                                    <div class="form-text">

                                        Formatos JPG, PNG y WEBP.
                                        Tamaño máximo: 2 MB.

                                        <br>

                                        Dejar vacío para conservar la imagen actual.

                                    </div>

                                </div>

                            </div>

                        </div>


                        <div class="modal-footer">

                            <button type="button"
                                class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">

                                Cancelar

                            </button>

                            <button type="submit"
                                class="btn btn-outline-success"
                                name="btnModificar"
                                value="1">

                                <i class="fa-solid fa-floppy-disk me-1"></i>

                                Guardar cambios

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    <?php } ?>


    <script src="../js/reenvio.js"></script>
    <script src="../js/previewProducto.js" defer></script>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
