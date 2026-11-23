
<?php

require_once __DIR__ . "/../config/config.php";

// Iniciar sesión si todavía no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isset($_SESSION["userId"])) {

    // Guardar el destino solicitado para regresar tras el login.
    // No aceptamos una URL externa proporcionada por el visitante.
    $_SESSION["redirect_after_login"] = BASE_URL . "checkout.php";

    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

// Validar que el rol sea uno de los permitidos
$rolesPermitidos = ["Administrador", "Cliente"];

if (!in_array($_SESSION["role"] ?? "", $rolesPermitidos, true)) {
    http_response_code(403);
    exit("Acceso denegado: rol no válido.");
}
?>