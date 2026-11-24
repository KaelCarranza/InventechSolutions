<?php

// index.php ya inició la sesión.
$logueado = isset($_SESSION["userId"]);

$rol = $_SESSION["role"] ?? null;

$esAdministrador = (
    $logueado &&
    $rol === "Administrador"
);

?>

<header class="header">

    <a href="index.php" class="marca">

        <div class="logo-icono">
            <i class="fa-solid fa-cube"></i>
        </div>

        <div class="logo-texto">
            <h2>InvenTech</h2>
            <span>SOLUTIONS</span>
        </div>

    </a>

    <nav class="nav">

        <a href="index.php">
            Inicio
        </a>

        <a href="">
            Productos
        </a>

        <a href="">
            Proformas
        </a>

        <?php if ($logueado): ?>

            <a href="">
                Mis pedidos
            </a>

        <?php endif; ?>

        <?php if ($esAdministrador): ?>

            <a href="admin/usuarios.php"
               class="nav-admin">
                <i class="fa-solid fa-user-shield"></i>
                Administración
            </a>

        <?php endif; ?>

    </nav>

    <div class="header-acciones">

        <a href="carrito.php" class="carrito-icono">
            <i class="fa-solid fa-cart-shopping"></i>
        </a>

        <?php if (!$logueado): ?>

            <a href="/inventechSolutions/auth/login.php" class="btn-login">
                Iniciar sesión
            </a>

        <?php else: ?>

            <a href="/inventechSolutions/auth/logout.php" class="btn-logout">
                Cerrar sesión
            </a>

        <?php endif; ?>

    </div>

</header>
