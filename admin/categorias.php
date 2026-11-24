<?php
require_once __DIR__ . "/../middlewares/protegerAdmin.php";
require_once __DIR__ . "/../config/db.php";
// Ejecutar controladores
ob_start();
include __DIR__ . "/../controllers/categorias/registrarCategoria.php";
include __DIR__ . "/../controllers/categorias/EliminarCategoria.php";
include __DIR__ . "/../controllers/categorias/modificarCategoria.php";
$mensajes = ob_get_clean();
// Consultar categorías
$sql = $conexion->query("SELECT * FROM categorias ORDER BY idCategoria DESC");
$categorias = $sql->fetchAll(PDO::FETCH_OBJ);
// Total de categorías
$totalCategorias = count($categorias);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de categorías | InvenTech Solutions</title>
    <script src="https://kit.fontawesome.com/4b21b27482.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css?v=2.0">
    <link rel="stylesheet" href="../css/usuarios.css?v=2.0">
    <link rel="stylesheet" href="../css/crud.css?v=2.0">
</head>

<body>
    <!-- BARRA LATERAL -->
    <?php require_once __DIR__ . "/sidebar.php"; ?>
    <div class="admin-content">
        <!-- BARRA SUPERIOR -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <span class="topbar-title">Administración / Categorías</span>
            </div>
        </header>
        <!-- CONTENIDO DEL MÓDULO -->
        <main class="admin-main">
            <!-- Encabezado -->
            <div class="mb-4">
                <h1>Gestión de categorías</h1>
            </div>
            <!-- TARJETA DE ESTADÍSTICAS -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <div class="card resumen-card resumen-total border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <p class="resumen-label mb-1">Total de categorías</p>
                                <h3 class="resumen-numero mb-0"><?= $totalCategorias ?></h3>
                            </div>
                            <div class="resumen-icono">
                                <i class="fa-solid fa-layer-group"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?= $mensajes ?>
            <!-- ENCABEZADO DE CATEGORÍAS -->
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h1 class="fw-bold mb-1">Categorías registradas</h1>
                </div>
                <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarCategoria">
                    <i class="fa-solid fa-plus me-2"></i>Agregar categoría
                </button>
            </div>
            <!-- TABLA DE CATEGORÍAS -->
            <div class="crud-card">
                <div class="table-responsive">
                    <table class="table crud-table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categorias)) { ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4">No hay categorías registradas.</td>
                                </tr>
                            <?php } ?>
                            <?php foreach ($categorias as $datos) {
                                $idCategoria = (int) $datos->idCategoria;
                            ?>
                                <tr>
                                    <!-- ID -->
                                    <td class="text-muted">#<?= $idCategoria ?></td>
                                    <!-- Nombre -->
                                    <td>
                                        <div class="categoria-info">
                                            <span class="fw-semibold"><?= htmlspecialchars($datos->Nombre) ?></span>
                                        </div>
                                    </td>
                                    <!-- Descripción -->
                                    <td class="descripcion-categoria"><?= htmlspecialchars($datos->Descripcion) ?></td>
                                    <!-- Acciones -->
                                    <td>
                                        <div class="crud-acciones">
                                            <!-- Editar -->
                                            <button type="button" class="accion-btn accion-editar" data-bs-toggle="modal" data-bs-target="#modalEditarCategoria<?= $idCategoria ?>" title="Editar categoría" aria-label="Editar categoría">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <!-- Eliminar -->
                                            <form method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar esta categoría?');">
                                                <input type="hidden" name="idCategoria" value="<?= $idCategoria ?>">
                                                <button type="submit" class="accion-btn accion-eliminar" name="btnEliminar" value="1" title="Eliminar categoría" aria-label="Eliminar categoría">
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
        </main>
    </div>
    <!-- MODAL REGISTRAR CATEGORÍA -->
    <div class="modal fade" id="modalRegistrarCategoria" tabindex="-1" aria-labelledby="tituloModalRegistrarCategoria" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="tituloModalRegistrarCategoria">Registrar categoría</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Nombre -->
                        <div class="mb-3">
                            <label for="Nombre" class="form-label">Nombre de la categoría</label>
                            <input type="text" class="form-control" id="Nombre" name="Nombre" placeholder="Ingrese el nombre de la categoría" required>
                        </div>
                        <!-- Descripción -->
                        <div class="mb-3">
                            <label for="Descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="Descripcion" name="Descripcion" rows="4" placeholder="Ingrese una descripción" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-outline-success" name="btnInsertarCategoria" value="1">
                            <i class="fa-solid fa-plus me-1"></i>Registrar categoría
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- MODALES PARA MODIFICAR CATEGORÍAS -->
    <?php foreach ($categorias as $datos) {
        $idCategoria = (int) $datos->idCategoria;
    ?>
        <div class="modal fade" id="modalEditarCategoria<?= $idCategoria ?>" tabindex="-1" aria-labelledby="tituloEditarCategoria<?= $idCategoria ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title" id="tituloEditarCategoria<?= $idCategoria ?>">Modificar categoría</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <!-- ID Categoría -->
                            <input type="hidden" name="idCategoria" value="<?= $idCategoria ?>">
                            <!-- Nombre -->
                            <div class="mb-3">
                                <label class="form-label" for="nombreEditar<?= $idCategoria ?>">Nombre de la categoría</label>
                                <input type="text" class="form-control" id="nombreEditar<?= $idCategoria ?>" name="Nombre" value="<?= htmlspecialchars($datos->Nombre, ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <!-- Descripción -->
                            <div class="mb-3">
                                <label class="form-label" for="descripcionEditar<?= $idCategoria ?>">Descripción</label>
                                <textarea class="form-control" id="descripcionEditar<?= $idCategoria ?>" name="Descripcion" rows="4" required><?= htmlspecialchars($datos->Descripcion, ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-outline-success" name="btnModificar" value="1">
                                <i class="fa-solid fa-floppy-disk me-1"></i>Guardar cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php } ?>
    <!-- Evitar reenvío -->
    <script src="../js/reenvio.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>