<?php
$host = "localhost";
$bd = "tienda_inventech_v2";
$usuario = "root";
$password = "";

try {
    $conexion = new PDO(
        "mysql:host=$host;dbname=$bd;charset=utf8mb4",
        $usuario,
        $password
    );
    $conexion->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

}catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(503);
    exit("No se pudo conectar con la base de datos. Inténtelo más tarde.");
}
?>
