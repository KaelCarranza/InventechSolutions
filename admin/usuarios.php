<?php
require_once __DIR__ . "/../middlewares/protegerAdmin.php";
require_once __DIR__ . "/../config/db.php";

// Ejecutar controladores
ob_start();

include __DIR__ . "/../controllers/usuarios/registrarUsuario.php";
include __DIR__ . "/../controllers/usuarios/eliminarUsuario.php";
include __DIR__ . "/../controllers/usuarios/modificarUsuario.php";
include __DIR__ . "/../controllers/usuarios/cambiarEstadoUsuario.php";

$mensajes = ob_get_clean();

// Estadísticas
$totalUsuarios = 0;
$totalAdmins = 0;
$totalClientes = 0;

try {
    $stmtTotal = $conexion->query(
        "SELECT COUNT(*) AS total FROM users"
    );

    $totalUsuarios = (int) $stmtTotal->fetch(PDO::FETCH_ASSOC)["total"];

    $stmtAdmins = $conexion->prepare(
        "SELECT COUNT(*) AS total FROM users WHERE role = :role"
    );

    $stmtAdmins->execute([":role" => "Administrador"]);

    $totalAdmins = (int) $stmtAdmins->fetch(PDO::FETCH_ASSOC)["total"];

    $stmtClientes = $conexion->prepare(
        "SELECT COUNT(*) AS total FROM users WHERE role = :role"
    );

    $stmtClientes->execute([":role" => "Cliente"]);

    $totalClientes = (int) $stmtClientes->fetch(PDO::FETCH_ASSOC)["total"];
} catch (PDOException $e) {

    $mensajes .= '<div class="alert alert-danger">
        Error al cargar las estadísticas.
    </div>';
}

// Consultar usuarios
$sql = $conexion->query(
    "SELECT userId, email, role, loginStatus, registerDate
     FROM users
     ORDER BY userId DESC"
);

$usuarios = $sql->fetchAll(PDO::FETCH_OBJ);
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestión de usuarios | InvenTech Solutions</title>

    <script src="https://kit.fontawesome.com/4b21b27482.js"
        crossorigin="anonymous"></script>

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
                <span class="topbar-title">
                    Administración / Usuarios
                </span>
            </div>

        </header>

        <!-- CONTENIDO DEL MÓDULO -->
        <main class="admin-main">

            <!-- Encabezado -->
            <div class="mb-4">
                <h1>Gestión de usuarios</h1>
            </div>

            <!-- TARJETAS DE ESTADÍSTICAS -->
            <div class="row g-3 mb-4">

                <!-- Total de usuarios -->
                <div class="col-12 col-md-4">
                    <div class="card resumen-card resumen-total border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center justify-content-between">

                            <div>
                                <p class="resumen-label mb-1"> Total de usuarios</p>
                                <h3 class="resumen-numero mb-0"><?= $totalUsuarios ?></h3>
                            </div>

                            <div class="resumen-icono">
                                <i class="fa-solid fa-users"></i>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Administradores -->
                <div class="col-12 col-md-4">
                    <div class="card resumen-card resumen-admin border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center justify-content-between">

                            <div>
                                <p class="resumen-label mb-1">Administradores</p>
                                <h3 class="resumen-numero mb-0"><?= $totalAdmins ?></h3>
                            </div>

                            <div class="resumen-icono">
                                <i class="fa-solid fa-user-shield"></i>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Clientes -->
                <div class="col-12 col-md-4">
                    <div class="card resumen-card resumen-cliente border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center justify-content-between">

                            <div>
                                <p class="resumen-label mb-1">Clientes</p>
                                <h3 class="resumen-numero mb-0"><?= $totalClientes ?></h3>
                            </div>

                            <div class="resumen-icono">
                                <i class="fa-solid fa-user"></i>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            <!-- Mensajes -->
            <?= $mensajes ?>

            <!-- ENCABEZADO DE USUARIOS -->
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">

                <div>
                    <h1 class="fw-bold mb-1"> Usuarios registrados </h1>
                </div>

                <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalRegistrar">
                    <i class="fa-solid fa-user-plus me-2"></i> Agregar usuario
                </button>

            </div>

            <!-- TABLA DE USUARIOS -->
            <div class="crud-card">

                <div class="table-responsive">

                    <table class="table crud-table table-hover align-middle mb-0">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Correo electrónico</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Fecha de registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (empty($usuarios)) { ?>

                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        No hay usuarios registrados.
                                    </td>
                                </tr>

                            <?php } ?>

                            <?php foreach ($usuarios as $datos) {

                                $idUsuario = (int) $datos->userId;

                                $inicial = strtoupper(substr($datos->email, 0, 1));

                                $esAdmin = $datos->role === "Administrador";

                                $esActivo = $datos->loginStatus === "Habilitado";
                            ?>

                                <tr>

                                    <!-- ID -->
                                    <td class="text-muted">
                                        #<?= $idUsuario ?>
                                    </td>

                                    <!-- Correo electrónico -->
                                    <td>
                                        <div class="usuario-tabla-info">
                                            <span class="fw-semibold">
                                                <?= htmlspecialchars($datos->email) ?>
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Rol -->
                                    <td>
                                        <span class="rol-badge <?= $esAdmin ? 'rol-admin' : 'rol-cliente' ?>">
                                            <?= htmlspecialchars($datos->role) ?>
                                        </span>
                                    </td>

                                    <!-- Estado -->
                                    <td>
                                        <span class="usuario-tabla-estado <?= $esActivo ? 'estado-activo' : 'estado-inactivo' ?>">
                                            <span class="estado-punto"></span>
                                            <?= htmlspecialchars((string) $datos->loginStatus) ?>
                                        </span>

                                    </td>

                                    <!-- Fecha de registro -->
                                    <td>
                                        <?= htmlspecialchars((string) $datos->registerDate) ?>
                                    </td>

                                    <!-- Acciones -->
                                    <td>
                                        <div class="crud-acciones">
                                            <!-- Editar -->
                                            <button type="button"
                                                class="accion-btn accion-editar"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEditar<?= $idUsuario ?>"
                                                title="Editar usuario"
                                                aria-label="Editar usuario">

                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>

                                            <!-- Habilitar / Deshabilitar -->
                                            <form method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('¿Desea cambiar el estado de este usuario?');">

                                                <input type="hidden"
                                                    name="idUsuario"
                                                    value="<?= $idUsuario ?>">

                                                <button type="submit"
                                                    class="accion-btn <?= $esActivo ? 'accion-deshabilitar' : 'accion-habilitar' ?>"
                                                    name="btnCambiarEstado"
                                                    value="1"
                                                    title="<?= $esActivo ? 'Deshabilitar usuario' : 'Habilitar usuario' ?>"
                                                    aria-label="<?= $esActivo ? 'Deshabilitar usuario' : 'Habilitar usuario' ?>">

                                                    <i class="fa-solid <?= $esActivo ? 'fa-user-xmark' : 'fa-user-check' ?>"></i>

                                                </button>

                                            </form>

                                            <!-- Eliminar -->
                                            <form method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('¿Está seguro de eliminar este usuario?');">

                                                <input type="hidden"
                                                    name="idUsuario"
                                                    value="<?= $idUsuario ?>">

                                                <button type="submit"
                                                    class="accion-btn accion-eliminar"
                                                    name="btnEliminar"
                                                    value="1"
                                                    title="Eliminar usuario"
                                                    aria-label="Eliminar usuario">

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


    <!-- MODAL REGISTRAR USUARIO -->

    <div class="modal fade"
        id="modalRegistrar"
        tabindex="-1"
        aria-labelledby="tituloModalRegistrar"
        aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                <form method="POST">

                    <div class="modal-header">

                        <h5 class="modal-title" id="tituloModalRegistrar">
                            Registrar usuario
                        </h5>

                        <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Cerrar">
                        </button>

                    </div>

                    <div class="modal-body">

                        <!-- Email -->
                        <div class="mb-3">

                            <label for="email" class="form-label">
                                Correo electrónico
                            </label>

                            <input type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                placeholder="usuario@correo.com"
                                required>

                        </div>

                        <!-- Contraseña -->
                        <div class="mb-3">

                            <label for="password" class="form-label">
                                Contraseña
                            </label>

                            <input type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                minlength="8"
                                autocomplete="new-password"
                                placeholder="Ingrese una contraseña"
                                required>

                            <small class="text-muted">

                                La contraseña debe tener mínimo 8 caracteres.

                            </small>

                        </div>

                        <!-- Rol -->
                        <div class="mb-3">

                            <label for="role" class="form-label">
                                Rol
                            </label>

                            <select class="form-select"
                                id="role"
                                name="role"
                                required>

                                <option value="">
                                    Seleccione un rol
                                </option>

                                <option value="Administrador">
                                    Administrador
                                </option>

                                <option value="Cliente">
                                    Cliente
                                </option>

                            </select>

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
                            name="btnInsertar"
                            value="1">

                            <i class="fa-solid fa-user-plus me-1"></i>

                            Registrar usuario

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>


    <!-- MODALES PARA MODIFICAR USUARIOS -->

    <?php foreach ($usuarios as $datos) {

        $idUsuario = (int) $datos->userId;
    ?>

        <div class="modal fade"
            id="modalEditar<?= $idUsuario ?>"
            tabindex="-1"
            aria-labelledby="tituloEditar<?= $idUsuario ?>"
            aria-hidden="true">

            <div class="modal-dialog modal-dialog-centered">

                <div class="modal-content">

                    <form method="POST">

                        <div class="modal-header">

                            <h5 class="modal-title"
                                id="tituloEditar<?= $idUsuario ?>">

                                Modificar usuario

                            </h5>

                            <button type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Cerrar">
                            </button>

                        </div>

                        <div class="modal-body">

                            <!-- ID usuario -->
                            <input type="hidden"
                                name="idUsuario"
                                value="<?= $idUsuario ?>">

                            <!-- Email -->
                            <div class="mb-3">

                                <label class="form-label"
                                    for="emailEditar<?= $idUsuario ?>">

                                    Correo electrónico

                                </label>

                                <input type="email"
                                    class="form-control"
                                    id="emailEditar<?= $idUsuario ?>"
                                    name="email"
                                    value="<?= htmlspecialchars($datos->email, ENT_QUOTES, 'UTF-8') ?>"
                                    required>

                            </div>

                            <!-- Contraseña -->
                            <div class="mb-3">

                                <label class="form-label"
                                    for="passwordEditar<?= $idUsuario ?>">

                                    Nueva contraseña

                                </label>

                                <input type="password"
                                    class="form-control"
                                    id="passwordEditar<?= $idUsuario ?>"
                                    name="password"
                                    minlength="8"
                                    autocomplete="new-password">

                                <small class="text-muted">

                                    Dejar vacío para mantener la contraseña actual.

                                </small>

                            </div>

                            <!-- Rol -->
                            <div class="mb-3">

                                <label class="form-label"
                                    for="roleEditar<?= $idUsuario ?>">

                                    Rol

                                </label>

                                <select class="form-select"
                                    id="roleEditar<?= $idUsuario ?>"
                                    name="role"
                                    required>

                                    <option value="Administrador"
                                        <?= $datos->role === "Administrador" ? "selected" : "" ?>>

                                        Administrador

                                    </option>

                                    <option value="Cliente"
                                        <?= $datos->role === "Cliente" ? "selected" : "" ?>>

                                        Cliente

                                    </option>

                                </select>

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

    <!-- Evitar reenvio -->
    <script src="../js/reenvio.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>