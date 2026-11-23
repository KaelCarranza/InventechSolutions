<?php
require_once __DIR__ . "/../middlewares/protegerAdmin.php";
require_once __DIR__ . "/../config/db.php";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD</title>

    <script src="https://kit.fontawesome.com/4b21b27482.js" crossorigin="anonymous"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <div class="container-fluid mt-4">

        <h2 class="text-center mb-4">CRUD de Categorias</h2>
        <a href="index.php">Inicio Admin</a>
        <br><br>
        <?php
        include "../controllers/categorias/registrarCategoria.php";
        include "../controllers/categorias/EliminarCategoria.php";
        ?>

        <div class="row g-4">

            <div class="col-12 col-md-4">

                <form class="crud-form border rounded p-4 shadow-sm" method="POST">

                    <h4 class="text-center mb-4">Registrar Categoría</h4>

                    <div class="mb-3">
                        <label for="categoria" class="form-label">Nombre</label>
                        <input type="text" class="form-control"
                            id="Nombre" name="Nombre">
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripcion</label>
                        <input type="text" class="form-control"
                            id="Descripcion" name="Descripcion">
                    </div>


                    <button type="submit" class="btn btn-primary w-100" name="btnInsertar" value="1">
                        Insertar Categoría
                    </button>

                </form>

            </div>

            <div class="col-12 col-md-8">

                <div class="table-responsive">

                    <table class="table table-striped table-hover table-bordered align-middle">

                        <thead class="table-dark">
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Nombre</th>
                                <th scope="col">Descripcion</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php
                            $sql = $conexion->query(" select * from categorias ");
                            while ($datos = $sql->fetchObject()) { ?>
                                <tr>
                                    <td><?= $datos->idCategoria ?></td>
                                    <td><?= $datos->Nombre ?></td>
                                    <td><?= $datos->Descripcion ?></td>
                                    <td>
                                        <a href="categorias.php?idCategoria=<?= $datos->idCategoria ?>" class="btn btn-small btn-warning"><i class="fa-solid fa-pen-to-square"></i></a>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="idCategoria" value="<?= $datos->idCategoria ?>">
                                            <button type="submit" class="btn btn-small btn-danger" name="btnEliminar" value="1">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php }
                            ?>
                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        history.replaceState(null, null, location.pathname);
    </script>
</body>

</html>