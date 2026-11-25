<?php

// Sesión validada en la portada.
$logueado = $rol !== null;

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

        <a href="index.php#productos">
            Productos
        </a>

        <a href="proformas.php">
            Proformas
        </a>

        <?php if ($logueado): ?>

            <a href="pedidos.php">
                Mis pedidos
            </a>

        <?php endif; ?>

        <?php if ($esAdministrador): ?>

            <a href="admin/index.php"
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

            <a href="<?= BASE_URL ?>auth/login.php" class="btn-login">
                Iniciar sesión
            </a>

        <?php else: ?>

            <span class="usuario-rol">
                <i class="fa-solid fa-user"></i>
                <?= htmlspecialchars($rol) ?>
            </span>

            <form action="<?= BASE_URL ?>auth/logout.php" method="POST">
                <input type="hidden" name="csrf_auth" value="<?= tokenAutenticacion("logout") ?>">
                <button type="submit" class="btn-logout">Cerrar sesión</button>
            </form>

        <?php endif; ?>

    </div>

</header>
