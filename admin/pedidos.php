<?php
require_once __DIR__ . "/../middlewares/protegerAdmin.php";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel de Administración</title>

    <script src="https://kit.fontawesome.com/4b21b27482.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css?v=2.0">

</head>

<body>

    <!-- Barra lateral -->
    <?php require_once __DIR__ . "/sidebar.php"; ?>

    <div class="admin-content">

        <!-- Barra superior -->
        <header class="admin-topbar">

            <div class="topbar-left">

                <span class="topbar-title"> Administración / Pedidos</span>

            </div>

        </header>

        <!-- Contenido del módulo -->
        <main class="admin-main">

            <h1>Pedidos</h1>

            <h1 style="color: red;">Página en desarrollo.</h1>

            <!-- Contenido de cada página -->

        </main>

    </div>

        <!-- Evitar reenvio -->
    <script src="../js/reenvio.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>