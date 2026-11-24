<?php
require_once __DIR__ . "/../middlewares/protegerAdmin.php";
require_once __DIR__ . "/../config/db.php";

// Ejecutar controladores
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

// Consultar productos
$sql = $conexion->query("
    SELECT productos.*, categorias.Nombre AS NombreCategoria
    FROM productos
    LEFT JOIN categorias
        ON productos.idCategoria = categorias.idCategoria
    ORDER BY productos.idProducto DESC
");

$productos = $sql->fetchAll(PDO::FETCH_OBJ);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestión de productos | InvenTech Solutions</title>

    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/4b21b27482.js"
        crossorigin="anonymous"></script>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- CSS Sidebar -->
    <link rel="stylesheet" href="../css/sidebar.css?v=1.0">

    <!-- CSS CRUD -->
    <link rel="stylesheet" href="../css/crud.css">

</head>

<body>

    <!-- BARRA LATERAL -->
    <?php require_once __DIR__ . "/sidebar.php"; ?>

    <div class="admin-content">

        <!-- BARRA SUPERIOR -->
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

        <!-- CONTENIDO PRINCIPAL -->
        <main class="admin-main">

            <!-- Encabezado -->
            <div class="mb-4">

                <h1>Gestión de productos</h1>

            </div>

            <!-- TARJETAS DE ESTADÍSTICAS -->
            <div class="row g-3 mb-4">

                <!-- Total de productos -->
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

                <!-- Productos activos -->
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

                <!-- Productos inactivos -->
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

            <!-- Mensajes -->
            <?= $mensajes ?>

            <div class="row g-4">

                <!-- FORMULARIO REGISTRAR PRODUCTO -->
                <div class="col-12 col-xl-4">

                    <div class="crud-card">

                        <div class="crud-card-header">

                            <div>
                                <h5>Registrar producto</h5>
                            </div>

                        </div>

                        <form method="POST" enctype="multipart/form-data">

                            <!-- SKU -->
                            <div class="mb-3">
                                <label for="sku" class="form-label"> SKU </label>
                                <input type="text" class="form-control" id="sku" name="sku" maxlength="50" placeholder="Ejemplo: CPU-AMD-5600G" required>
                            </div>


                            <!-- Producto -->
                            <div class="mb-3">
                                <label for="producto" class="form-label"> Producto </label>
                                <input type="text" class="form-control" id="producto" name="producto" placeholder="Nombre del producto" required>
                            </div>

                            <!-- Precio -->
                            <div class="mb-3">
                                <label for="precio" class="form-label"> Precio </label>
                                <input type="number" class="form-control" id="precio" name="precio" step="0.01" min="0" placeholder="0.00" required>
                            </div>

                            <!-- Cantidad -->
                            <div class="mb-3">
                                <label for="cantidad" class="form-label"> Cantidad </label>
                                <input type="number" class="form-control" id="cantidad" name="cantidad" min="0" step="1" placeholder="0" required>
                            </div>

                            <!-- Categoria -->
                            <div class="mb-3">
                                <label for="idCategoria" class="form-label"> Categoría </label>
                                <select class="form-select" id="idCategoria" name="idCategoria" required>
                                    <option value="">Seleccione una categoría</option>

                                    <?php
                                    $sqlCategorias = $conexion->query(
                                        "SELECT idCategoria, Nombre
                                        FROM categorias
                                        ORDER BY Nombre ASC"
                                    );

                                    while ($categoria = $sqlCategorias->fetchObject()) {
                                    ?>
                                        <option value="<?= (int) $categoria->idCategoria ?>"> <?= htmlspecialchars($categoria->Nombre) ?> </option>
                                    <?php } ?>
                                </select>
                            </div>


                            <!-- Descripción -->
                            <div class="mb-3">
                                <label for="descripcion" class="form-label"> Descripción </label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3" placeholder="Descripción del producto" required></textarea>
                            </div>

                            <!-- Imagen -->
                            <div class="mb-4">
                                <label for="imagen" class="form-label"> Imagen del producto </label>
                                <input type="file" class="form-control" id="imagen" name="imagen" accept=".jpg,.jpeg,.png,.webp" required>
                                <div class="form-text"> Formatos JPG, PNG y WEBP. Tamaño máximo: 2 MB. </div>
                            </div>

                            <!-- Botón -->
                            <button type="submit" class="btn btn-registrar w-100" name="btnInsertar" value="1"> <i class="fa-solid fa-plus me-2"></i> Registrar producto </button>
                        </form>

                    </div>

                </div>

                <!-- LISTADO DE PRODUCTOS -->
                <div class="col-12 col-xl-8">
                    <div class="crud-card">
                        <div class="crud-card-header">

                            <div>
                                <h5>Productos registrados</h5>
                            </div>

                        </div>

                        <div class="table-responsive">
                            <table class="table crud-table table-hover align-middle mb-0">

                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>SKU</th>
                                        <th>Imagen</th>
                                        <th>Producto</th>
                                        <th>Precio</th>
                                        <th>Cantidad</th>
                                        <th>Categoria</th>
                                        <th>Estado</th>
                                        <th>Descripción</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <?php if (empty($productos)) { ?>

                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                No hay productos registrados.
                                            </td>
                                        </tr>

                                    <?php } ?>

                                    <?php foreach ($productos as $datos) {

                                        $idProducto = (int) $datos->idProducto;
                                        $esActivo = (int) $datos->Activo === 1;

                                    ?>

                                        <tr>
                                            <!-- ID -->
                                            <td>
                                                #<?= $idProducto ?>
                                            </td>

                                            <!-- SKU -->
                                            <td>
                                                <span class="sku-producto">
                                                    <?= htmlspecialchars($datos->SKU ?? "Sin asignar") ?>
                                                </span>
                                            </td>

                                            <!-- Imagen -->
                                            <td>

                                                <?php if (!empty($datos->Imagen)): ?>

                                                    <img
                                                        class="producto-imagen"
                                                        src="../assets/img/productos/<?= rawurlencode($datos->Imagen) ?>"
                                                        alt="<?= htmlspecialchars($datos->Producto, ENT_QUOTES, 'UTF-8') ?>">

                                                <?php else: ?>

                                                    <div class="producto-sin-imagen">
                                                        <i class="fa-solid fa-image"></i>
                                                    </div>

                                                <?php endif; ?>

                                            </td>

                                            <!-- Nombre -->
                                            <td class="fw-semibold">

                                                <?= htmlspecialchars($datos->Producto) ?>

                                            </td>

                                            <!-- Precio -->
                                            <td class="precio-producto">

                                                ₡<?= number_format((float) $datos->Precio, 2) ?>

                                            </td>

                                            <!-- Cantidad -->
                                            <td>

                                                <span class="stock-badge <?= $datos->Cantidad > 0 ? 'stock-disponible' : 'stock-agotado' ?>">

                                                    <?= (int) $datos->Cantidad ?>

                                                </span>

                                            </td>

                                            <!-- Categoría -->
                                            <td>
                                                <span class="producto-categoria">
                                                    <?= htmlspecialchars($datos->NombreCategoria ?? "Sin categoría") ?>
                                                </span>
                                            </td>

                                            <!-- Estado -->
                                            <td>

                                                <span class="producto-estado <?= $esActivo ? 'estado-activo' : 'estado-inactivo' ?>">

                                                    <span class="estado-punto"></span>

                                                    <?= $esActivo ? "Activo" : "Inactivo" ?>

                                                </span>

                                            </td>

                                            <!-- Descripción -->
                                            <td class="descripcion-producto">

                                                <?= htmlspecialchars($datos->Descripcion) ?>

                                            </td>

                                            <!-- Acciones -->
                                            <td>

                                                <div class="crud-acciones">

                                                    <!-- Editar -->
                                                    <button type="button"
                                                        class="accion-btn accion-editar"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalEditarProducto<?= $idProducto ?>"
                                                        title="Editar producto"
                                                        aria-label="Editar producto">

                                                        <i class="fa-solid fa-pen-to-square"></i>

                                                    </button>

                                                    <!-- Activar / Desactivar producto -->
                                                    <form method="POST"
                                                        class="d-inline"
                                                        onsubmit="return confirm('¿Desea <?= $esActivo ? 'desactivar' : 'activar' ?> este producto?');">

                                                        <input type="hidden"
                                                            name="idProducto"
                                                            value="<?= $idProducto ?>">

                                                        <button type="submit"
                                                            class="accion-btn <?= $esActivo ? 'accion-deshabilitar' : 'accion-habilitar' ?>"
                                                            name="btnCambiarEstado"
                                                            value="1"
                                                            title="<?= $esActivo ? 'Desactivar producto' : 'Activar producto' ?>"
                                                            aria-label="<?= $esActivo ? 'Desactivar producto' : 'Activar producto' ?>">

                                                            <i class="fa-solid <?= $esActivo ? 'fa-ban' : 'fa-circle-check' ?>"></i>

                                                        </button>

                                                    </form>

                                                    <!-- Eliminar -->
                                                    <form method="POST"
                                                        class="d-inline"
                                                        onsubmit="return confirm('¿Desea eliminar este producto?');">

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

    <!-- MODALES PARA MODIFICAR PRODUCTOS -->

    <?php

    $sqlCategorias = $conexion->query(
        "SELECT idCategoria, Nombre
     FROM categorias
     ORDER BY Nombre ASC"
    );

    $categoriasEditar = $sqlCategorias->fetchAll(PDO::FETCH_OBJ);

    ?>

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

                        <!-- ENCABEZADO -->
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

                        <!-- CONTENIDO -->
                        <div class="modal-body">

                            <input type="hidden"
                                name="idProducto"
                                value="<?= $idProducto ?>">

                            <div class="row g-3">

                                <!-- SKU -->
                                <div class="col-12 col-md-6">

                                    <label class="form-label">SKU</label>

                                    <input type="text"
                                        class="form-control"
                                        name="sku"
                                        maxlength="50"
                                        value="<?= htmlspecialchars($datos->SKU ?? "", ENT_QUOTES, 'UTF-8') ?>"
                                        required>

                                </div>

                                <!-- Producto -->
                                <div class="col-12 col-md-6">

                                    <label class="form-label">Producto</label>

                                    <input type="text"
                                        class="form-control"
                                        name="producto"
                                        value="<?= htmlspecialchars($datos->Producto, ENT_QUOTES, 'UTF-8') ?>"
                                        required>

                                </div>

                                <!-- Precio -->
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

                                <!-- Cantidad -->
                                <div class="col-12 col-md-6">

                                    <label class="form-label">Cantidad</label>

                                    <input type="number"
                                        class="form-control"
                                        name="cantidad"
                                        min="0"
                                        step="1"
                                        value="<?= (int) $datos->Cantidad ?>"
                                        required>

                                </div>

                                <!-- Categoría -->
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

                                <!-- Estado -->
                                <div class="col-12 col-md-6">

                                    <label class="form-label">Estado</label>

                                    <select class="form-select"
                                        name="activo"
                                        required>

                                        <option value="1"
                                            <?= (int) $datos->Activo === 1 ? "selected" : "" ?>>

                                            Activo

                                        </option>

                                        <option value="0"
                                            <?= (int) $datos->Activo === 0 ? "selected" : "" ?>>

                                            Inactivo

                                        </option>

                                    </select>

                                </div>

                                <!-- Descripción -->
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

                                <!-- Imagen actual -->
                                <div class="col-12 col-md-4">

                                    <label class="form-label">
                                        Imagen actual
                                    </label>

                                    <div class="imagen-editar-contenedor">

                                        <?php if (!empty($datos->Imagen)) { ?>

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

                                            <span class="text-muted">
                                                Sin imagen
                                            </span>

                                        <?php } ?>

                                    </div>

                                </div>

                                <!-- Nueva imagen -->
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

                        <!-- BOTONES -->
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

    <!-- Evitar reenvio -->
    <script src="../js/reenvio.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>