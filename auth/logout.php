<?php
require_once __DIR__ . "/sesion.php";

iniciarSesionInventech();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Allow: POST");
    responderError(405, "Para cerrar sesión, abra el inicio y utilice el botón Cerrar sesión.");
}

exigirCsrfAutenticacion("logout");
$_SESSION = [];
$cookie = session_get_cookie_params();
setcookie(session_name(), "", [
    "expires" => time() - 3600,
    "path" => $cookie["path"],
    "domain" => $cookie["domain"],
    "secure" => $cookie["secure"],
    "httponly" => $cookie["httponly"],
    "samesite" => $cookie["samesite"]
]);
session_destroy();

header("Location: " . BASE_URL . "index.php", true, 303);
exit();
