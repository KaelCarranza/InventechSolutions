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

        <h2 class="text-center mb-4">CRUD de Usuarios</h2>
        <a href="index.php">Inicio Admin</a>
        <br><br>
        <?php
        include "../controllers/usuarios/registrarUsuario.php";
        include "../controllers/usuarios/eliminarUsuario.php";
        include "../controllers/usuarios/modificarUsuario.php";
        ?>

        <div class="row g-4">
            <div class="col-12 col-md-4">
                <form class="crud-form border rounded p-4 shadow-sm" method="POST">
                    <h4 class="text-center mb-4">Registrar Usuario</h4>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                        <small class="text-muted">La contraseña debe tener mínimo 8 caracteres.</small>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Rol</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Seleccione un rol</option>
                            <option value="Administrador">Administrador</option>
                            <option value="Cliente">Cliente</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" name="btnInsertar" value="1">Insertar Usuario</button>
                </form>
            </div>

            <div class="col-12 col-md-8">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered align-middle">

                        <thead class="table-dark">
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Email</th>
                                <th scope="col">Rol</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Fecha de registro</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php
                            $sql = $conexion->query(" select * from users ");
                            while ($datos = $sql->fetchObject()) { ?>
                                <tr>
                                    <td><?= $datos->userId ?></td>
                                    <td><?= $datos->email ?></td>
                                    <td><?= $datos->role ?></td>
                                    <td><?= $datos->loginStatus ?></td>
                                    <td><?= $datos->registerDate ?></td>
                                    <td>
                                        <a href="usuarios.php?idUsuario=<?= $datos->userId ?>" class="btn btn-small btn-warning"><i class="fa-solid fa-pen-to-square"></i></a>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="idUsuario" value="<?= $datos->userId ?>">
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

</body>
</html>