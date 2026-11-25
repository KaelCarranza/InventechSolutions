<?php

require_once __DIR__ . "/../config/respuesta.php";

if (!in_array($_SERVER["REQUEST_METHOD"], ["GET", "HEAD", "POST"], true)) {
    header("Allow: GET, HEAD, POST");
    responderError(405, "Este método no está disponible. Abra el módulo para continuar.");
}

if (empty($_SESSION["csrf_admin"])) {
    $_SESSION["csrf_admin"] = bin2hex(random_bytes(32));
}

$tokenCsrfAdmin = $_SESSION["csrf_admin"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tokenEnviado = $_POST["csrf_admin"] ?? "";

    if (!is_string($tokenEnviado) || !hash_equals($tokenCsrfAdmin, $tokenEnviado)) {
        responderError(403, "El formulario no es válido o caducó. Vuelva a abrirlo e inténtelo nuevamente.", true);
    }
}
