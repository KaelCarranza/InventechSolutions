<?php

$paginaActual = basename($_SERVER["PHP_SELF"]);

$menuAdministracion = [
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

<!-- Barra lateral -->
<aside class="admin-sidebar" id="sidebar">

    <!-- Marca -->
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

    <!-- Menú -->
    <nav class="sidebar-menu">

        <!-- Administración -->
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

        <!-- Operaciones -->
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

    <!-- Parte inferior -->
    <div class="sidebar-footer">

        <div class="sidebar-divider"></div>

        <a href="../index.php" class="menu-link tienda-link">

            <i class="fa-solid fa-arrow-left"></i>
            <span>Volver a la tienda</span>

        </a>

    </div>

</aside>

<!-- Fondo para dispositivos móviles -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>