<?php
require_once __DIR__ . "/../auth/sesion.php";

iniciarSesionInventech();

if (isset($_SESSION["userId"])) {
    require_once __DIR__ . "/../config/db.php";
    try {
        if (rolSesionActual($conexion) !== null) {
            return;
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        http_response_code(503);
        exit("No se pudo verificar la sesión.");
    }
}

// Volver a la tienda tras iniciar sesión
header("Location: " . BASE_URL . "auth/login.php");
exit();
