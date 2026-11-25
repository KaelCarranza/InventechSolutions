<?php

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../config/respuesta.php";

const REVISION_SESION_SQL = "COALESCE((
    SELECT MAX(b.idBitacora) FROM bitacora b
    WHERE b.Modulo = 'Usuarios' AND b.idRegistro = u.userId
      AND b.Accion IN ('Modificación', 'Habilitación', 'Deshabilitación', 'Eliminación')
), 0)";

function iniciarSesionInventech(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set("session.use_strict_mode", "1");
        ini_set("session.use_only_cookies", "1");
        session_name("INVENTECHSESSID");
        session_set_cookie_params([
            "lifetime" => 0,
            "path" => BASE_URL,
            "secure" => !empty($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"]) !== "off",
            "httponly" => true,
            "samesite" => "Lax"
        ]);
        if (!@session_start()) {
            error_log(error_get_last()["message"] ?? "No se pudo iniciar la sesión de InvenTech.");
            http_response_code(503);
            exit("No se pudo iniciar la sesión. Inténtelo más tarde.");
        }
    }
    header("Cache-Control: no-store, private");
}

function rolSesionActual(PDO $conexion): ?string {
    if (!isset($_SESSION["userId"])) {
        return null;
    }
    $consulta = $conexion->prepare("
        SELECT u.role, u.loginStatus, " . REVISION_SESION_SQL . " AS revisionSesion
        FROM users u WHERE u.userId = ?
    ");
    $consulta->execute([$_SESSION["userId"]]);
    $cuenta = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$cuenta || $cuenta["loginStatus"] !== "Habilitado" ||
        !in_array($cuenta["role"], ["Administrador", "Cliente"], true) ||
        $cuenta["role"] !== ($_SESSION["role"] ?? null) ||
        (string) $cuenta["revisionSesion"] !== ($_SESSION["revisionSesion"] ?? null)) {
        $_SESSION = [];
        session_regenerate_id(true);
        return null;
    }
    return $cuenta["role"];
}

function destinoAutenticado(string $rol): string {
    return BASE_URL . ($rol === "Administrador" ? "admin/index.php" : "index.php");
}

function tokenAutenticacion(string $accion): string {
    $_SESSION["csrf_autenticacion"][$accion] ??= bin2hex(random_bytes(32));
    return $_SESSION["csrf_autenticacion"][$accion];
}

function exigirCsrfAutenticacion(string $accion): void {
    $recibido = $_POST["csrf_auth"] ?? null;
    $esperado = $_SESSION["csrf_autenticacion"][$accion] ?? null;
    if (!is_string($recibido) || !is_string($esperado) || !hash_equals($esperado, $recibido)) {
        responderError(403, "El formulario no es válido o caducó. Vuelva a abrirlo.", true);
    }
}
