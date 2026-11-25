<?php

require_once __DIR__ . "/config.php";

function responderError(int $estado, string $mensaje, bool $mostrarLogin = false): void {
    http_response_code($estado);
    header("Content-Type: text/html; charset=UTF-8");
    $base = htmlspecialchars(BASE_URL);
    $texto = htmlspecialchars($mensaje);
    $enlace = $mostrarLogin ? '<a href="' . $base . 'auth/login.php">Iniciar sesión</a>' : '';
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Solicitud no completada | InvenTech Solutions</title></head>'
        . '<body style="margin:0;background:#f4f4f5;color:#181818;font:1rem Arial,sans-serif">'
        . '<main style="max-width:36rem;margin:3rem auto;padding:1.5rem">'
        . '<h1>No se completó la solicitud</h1><p>' . $texto . '</p><nav style="display:flex;gap:1rem;flex-wrap:wrap">'
        . '<a href="' . $base . 'index.php">Ir al inicio</a>' . $enlace . '</nav></main></body></html>';
    exit();
}

function exigirOperacionPost(array $nombres): void {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        return;
    }
    $recibidas = array_intersect_key($_POST, array_fill_keys($nombres, true));
    if (count($recibidas) !== 1 || reset($recibidas) !== "1") {
        responderError(400, "El formulario no identifica una acción válida. Vuelva al listado y utilice el botón de la operación.");
    }
}
