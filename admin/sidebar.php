<?php

$paginaActual = basename($_SERVER["PHP_SELF"]);

$menuAdministracion = [
    ["index.php", "Inicio", "fa-solid fa-house"],
    ["usuarios.php", "Usuarios", "fa-solid fa-users"],
    ["productos.php", "Productos", "fa-solid fa-box"],
    ["categorias.php", "Categorías", "fa-solid fa-layer-group"],
    ["inventario.php", "Inventario", "fa-solid fa-boxes-stacked"]
];

$menuOperaciones = [
    ["pedidos.php", "Pedidos", "fa-solid fa-cart-shopping"],
    ["proformas.php", "Proformas", "fa-solid fa-file-invoice"],
    ["reportes.php", "Reportes", "fa-solid fa-chart-column"],
    ["bitacora.php", "Bitácora", "fa-solid fa-clock-rotate-left"]
];

?>

<button type="button" class="sidebar-abrir" id="sidebarAbrir"
    aria-controls="sidebar" aria-expanded="false">
    <i class="fa-solid fa-bars" aria-hidden="true"></i>
    Menú administrativo
</button>


<aside class="admin-sidebar" id="sidebar">

    <button type="button" class="sidebar-cerrar" id="sidebarCerrar">
        Cerrar menú
    </button>


    <a href="index.php" class="sidebar-brand">

        <div class="brand-icon">
            <i class="fa-solid fa-cube"></i>
        </div>

        <div class="brand-text">
            <strong>InvenTech</strong>
            <small>SOLUTIONS</small>
        </div>

    </a>

    <div class="sidebar-divider"></div>


    <nav class="sidebar-menu">


        <div class="menu-section">

            <span class="menu-title">Administración</span>

            <?php foreach ($menuAdministracion as $opcion) { ?>

                <a href="<?= $opcion[0] ?>"
                    class="menu-link <?= $paginaActual === $opcion[0] ? 'active' : '' ?>">

                    <i class="<?= $opcion[2] ?>"></i>
                    <span><?= $opcion[1] ?></span>

                </a>

            <?php } ?>

        </div>


        <div class="menu-section">

            <span class="menu-title">Operaciones</span>

            <?php foreach ($menuOperaciones as $opcion) { ?>

                <a href="<?= $opcion[0] ?>"
                    class="menu-link <?= $paginaActual === $opcion[0] ? 'active' : '' ?>">

                    <i class="<?= $opcion[2] ?>"></i>
                    <span><?= $opcion[1] ?></span>

                </a>

            <?php } ?>

        </div>

    </nav>


    <div class="sidebar-footer">

        <div class="sidebar-divider"></div>

        <a href="../index.php" class="menu-link tienda-link">

            <i class="fa-solid fa-arrow-left"></i>
            <span>Volver a la tienda</span>

        </a>

        <?php if (isset($_SESSION["userId"]) && function_exists("tokenAutenticacion")) { ?>
            <form action="<?= BASE_URL ?>auth/logout.php" method="POST">
                <input type="hidden" name="csrf_auth" value="<?= tokenAutenticacion("logout") ?>">
                <button type="submit" class="menu-link tienda-link w-100 border-0" style="background-color: transparent;">
                    <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                    <span>Cerrar sesión</span>
                </button>
            </form>
        <?php } ?>

    </div>

</aside>


<div class="sidebar-overlay" id="sidebarOverlay"></div>
<script src="../js/sidebar.js" defer></script>
