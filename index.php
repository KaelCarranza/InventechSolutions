<?php
session_start();

require_once "config/db.php";

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
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>InvenTech Solutions</title>

    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/4b21b27482.js" crossorigin="anonymous"></script>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Estilos del header -->
    <link rel="stylesheet" href="css/header.css?v=2.0">
    
</head>

<body>

    <?php include "includes/header.php"; ?>

    <main>

        <section class="hero">

            <div class="hero-content">

                <h1>Bienvenido a InvenTech Solutions</h1>

                <p>
                    Descubre la tecnología que transforma tu mundo.
                </p>

                <a href="#productos" class="btn">Explorar productos</a>

            </div>

        </section>


    </main>

</body>

</html>