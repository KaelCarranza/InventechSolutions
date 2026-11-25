<?php
require_once __DIR__ . "/../middlewares/protegerAdmin.php";
require_once __DIR__ . "/../middlewares/protegerCsrfAdmin.php";
require_once __DIR__ . "/../config/db.php";

exigirOperacionPost(["btnInsertarCategoria", "btnModificar", "btnEliminar"]);

ob_start();
include __DIR__ . "/../controllers/categorias/registrarCategoria.php";
include __DIR__ . "/../controllers/categorias/eliminarCategoria.php";
include __DIR__ . "/../controllers/categorias/modificarCategoria.php";
$mensajes = ob_get_clean();
$reabrirRegistro = isset($_POST["btnInsertarCategoria"]) && !str_contains($mensajes, "alert-success");
$nombreRegistro = $reabrirRegistro ? mb_substr(textoEntrada($_POST["Nombre"] ?? null), 0, 300) : "";
$descripcionRegistro = $reabrirRegistro ? mb_substr(textoEntrada($_POST["Descripcion"] ?? null), 0, 1200) : "";
try {
    // Consultar categorías
    $sql = $conexion->query("SELECT * FROM categorias ORDER BY idCategoria DESC");
    $categorias = $sql->fetchAll(PDO::FETCH_OBJ);

    $totalCategorias = count($categorias);
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
    <title>Gestión de categorías | InvenTech Solutions</title>
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
                <span class="topbar-title">Administración / Categorías</span>
            </div>
        </header>

        <main class="admin-main">

            <div class="mb-4">
                <h1>Gestión de categorías</h1>
            </div>

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

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h1 class="fw-bold mb-1">Categorías registradas</h1>
                </div>
                <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarCategoria">
                    <i class="fa-solid fa-plus me-2"></i>Agregar categoría
                </button>
            </div>
            <!-- Lista de categorías -->
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

                                    <td class="text-muted">#<?= $idCategoria ?></td>

                                    <td>
                                        <div class="categoria-info">
                                            <span class="fw-semibold"><?= htmlspecialchars($datos->Nombre) ?></span>
                                        </div>
                                    </td>

                                    <td class="descripcion-categoria"><?= htmlspecialchars($datos->Descripcion ?? "") ?></td>

                                    <td>
                                        <div class="crud-acciones">

                                            <button type="button" class="accion-btn accion-editar" data-bs-toggle="modal" data-bs-target="#modalEditarCategoria<?= $idCategoria ?>" title="Editar categoría" aria-label="Editar categoría">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>

                                            <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar esta categoría? Los productos vinculados quedarán sin categoría.');">
                                                <input type="hidden" name="csrf_admin" value="<?= $tokenCsrfAdmin ?>">
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
    <!-- Registrar categoría -->
    <div class="modal fade" id="modalRegistrarCategoria" tabindex="-1" aria-labelledby="tituloModalRegistrarCategoria" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="csrf_admin" value="<?= $tokenCsrfAdmin ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="tituloModalRegistrarCategoria">Registrar categoría</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <?php if ($reabrirRegistro) { echo $mensajes; } ?>

                        <div class="mb-3">
                            <label for="Nombre" class="form-label">Nombre de la categoría</label>
                            <input type="text" class="form-control" id="Nombre" name="Nombre" maxlength="100"
                                value="<?= htmlspecialchars($nombreRegistro) ?>" placeholder="Ingrese el nombre de la categoría" required>
                        </div>

                        <div class="mb-3">
                            <label for="Descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="Descripcion" name="Descripcion" rows="4" maxlength="255"
                                placeholder="Ingrese una descripción" required><?= htmlspecialchars($descripcionRegistro) ?></textarea>
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
    <!-- Editar categorías -->
    <?php foreach ($categorias as $datos) {
        $idCategoria = (int) $datos->idCategoria;
    ?>
        <div class="modal fade" id="modalEditarCategoria<?= $idCategoria ?>" tabindex="-1" aria-labelledby="tituloEditarCategoria<?= $idCategoria ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST">
                        <input type="hidden" name="csrf_admin" value="<?= $tokenCsrfAdmin ?>">
                        <div class="modal-header">
                            <h5 class="modal-title" id="tituloEditarCategoria<?= $idCategoria ?>">Modificar categoría</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">

                            <input type="hidden" name="idCategoria" value="<?= $idCategoria ?>">

                            <div class="mb-3">
                                <label class="form-label" for="nombreEditar<?= $idCategoria ?>">Nombre de la categoría</label>
                                <input type="text" class="form-control" id="nombreEditar<?= $idCategoria ?>" name="Nombre" value="<?= htmlspecialchars($datos->Nombre, ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="descripcionEditar<?= $idCategoria ?>">Descripción</label>
                                <textarea class="form-control" id="descripcionEditar<?= $idCategoria ?>" name="Descripcion" rows="4" required><?= htmlspecialchars($datos->Descripcion ?? "", ENT_QUOTES, 'UTF-8') ?></textarea>
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

    <script src="../js/reenvio.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($reabrirRegistro) { ?>
        <script>
            if (window.bootstrap) bootstrap.Modal.getOrCreateInstance(document.getElementById("modalRegistrarCategoria")).show();
        </script>
    <?php } ?>
</body>

</html>
