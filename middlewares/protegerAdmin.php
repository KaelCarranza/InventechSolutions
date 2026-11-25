<?php
require_once __DIR__ . "/../auth/sesion.php";

iniciarSesionInventech();

if (!isset($_SESSION["userId"])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

require_once __DIR__ . "/../config/db.php";

try {
    $rol = rolSesionActual($conexion);
} catch (PDOException $e) {
    error_log($e->getMessage());
    responderError(503, "No se pudo verificar el acceso. Inténtelo nuevamente.", true);
}

if ($rol !== "Administrador") {
    responderError(403, "No tiene acceso a esta sección. Vuelva al inicio o inicie sesión con una cuenta autorizada.", true);
}
