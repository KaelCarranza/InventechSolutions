<?php
require_once __DIR__ . "/auth/sesion.php";
iniciarSesionInventech();

require_once "config/db.php";

try {
    $rol = rolSesionActual($conexion);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(503);
    exit("No se pudo verificar la sesión.");
}

// Consultar los productos visibles
$sql = $conexion->prepare("
    SELECT
        idProducto,
        Producto,
        Precio,
        Cantidad,
        Descripcion,
        Imagen
    FROM productos
    WHERE Activo = 1
    ORDER BY fechaRegistro DESC
");

$sql->execute();
$productos = $sql->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Inicio - InvenTech Solutions</title>

    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/index.css">

    <link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <?php include "includes/header.php"; ?>

    <main>

        <section class="hero">

            <div class="hero-contenido">

                <span class="etiqueta">
                    INNOVACIÓN Y TECNOLOGÍA
                </span>

                <p>
                    Encuentra componentes, periféricos
                    y accesorios para tu computadora.
                </p>

                <a href="#productos" class="btn-principal">
                    Explorar productos
                </a>

            </div>

        </section>

        <section class="productos-section" id="productos">

            <div class="section-encabezado">

                <h2>Nuestros productos</h2>

                <p>
                    Explora nuestro catálogo tecnológico.
                </p>

            </div>      

            <div class="productos-grid">
                 <!-- productos -->
            </div>

        </section>

    </main>

</body>
</html>
