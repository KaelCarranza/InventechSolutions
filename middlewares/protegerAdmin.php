
<?php

require_once __DIR__ . "/../config/config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si existe sesión
if (!isset($_SESSION["userId"])) {

    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

// Verificar rol de administrador
if (($_SESSION["role"] ?? "") !== "Administrador") {

    http_response_code(403);

    exit("Acceso denegado: Se requieren permisos de administrador.");
}
?>
