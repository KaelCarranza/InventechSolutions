<?php

session_start();

require_once __DIR__ . "/config/db.php";

// Función para evitar inyección de HTML
function escapar($valor)
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

// Obtener categorías
$sqlCategorias = $conexion->query("
    SELECT
        c.idCategoria,
        c.Nombre,
        COUNT(p.idProducto) AS totalProductos
    FROM categorias c
    LEFT JOIN productos p
        ON c.idCategoria = p.idCategoria
        AND p.Activo = 1
    GROUP BY c.idCategoria, c.Nombre
    ORDER BY c.Nombre ASC
");

$categorias = $sqlCategorias->fetchAll(PDO::FETCH_ASSOC);

// Obtener los últimos 8 productos activos
$sqlProductos = $conexion->query("
    SELECT
        p.idProducto,
        p.SKU,
        p.Producto,
        p.Precio,
        p.Cantidad,
        p.Descripcion,
        p.Imagen,
        c.Nombre AS NombreCategoria
    FROM productos p
    LEFT JOIN categorias c
        ON p.idCategoria = c.idCategoria
    WHERE p.Activo = 1
    ORDER BY p.fechaRegistro DESC, p.idProducto DESC
    LIMIT 8
");

$productos = $sqlProductos->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Inicio | InvenTech Solutions</title>

    <!-- Font Awesome -->
    <script
        src="https://kit.fontawesome.com/4b21b27482.js"
        crossorigin="anonymous">
    </script>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- Estilos -->
    <link rel="stylesheet" href="css/paleta.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/inicio.css">

</head>

<body>

    <!-- HEADER -->
    <?php require_once __DIR__ . "/includes/header.php"; ?>

    <main class="inicio">

        <!-- ===================================
             BANNER PRINCIPAL
        ==================================== -->

        <section class="hero">

            <div class="hero-contenedor">

                <div class="hero-contenido">

                    <span class="hero-etiqueta">
                        TECNOLOGÍA A TU ALCANCE
                    </span>

                    <h1>
                        Potencia tu mundo con
                        <span>tecnología.</span>
                    </h1>

                    <p>
                        Encuentra computadoras, componentes
                        y accesorios tecnológicos para llevar
                        tus proyectos al siguiente nivel.
                    </p>

                    <div class="hero-botones">

                        <a href="#productos"
                            class="inicio-btn inicio-btn-primary">

                            Explorar productos

                            <i class="fa-solid fa-arrow-right"></i>

                        </a>

                    </div>

                </div>

            </div>

        </section>


        <!-- ===================================
             ÚLTIMOS PRODUCTOS
        ==================================== -->

        <section class="inicio-seccion" id="productos">

            <div class="seccion-encabezado">

                <div>

                    <span class="seccion-etiqueta">
                        DESCUBRE LO NUEVO
                    </span>

                    <h2>Últimos productos</h2>

                    <p>
                        Explora los productos recientemente
                        incorporados a nuestro catálogo.
                    </p>

                </div>

            </div>

            <?php if (empty($productos)): ?>

                <div class="inicio-vacio">

                    <i class="fa-solid fa-box-open"></i>

                    <h3>No hay productos disponibles</h3>

                    <p>Próximamente tendremos nuevos productos.</p>

                </div>

            <?php else: ?>

                <div class="productos-grid">

                    <?php foreach ($productos as $producto): ?>

                        <?php

                        $stock = (int) $producto["Cantidad"];

                        $nombreImagen = basename(
                            $producto["Imagen"] ?? ""
                        );

                        $rutaImagen =
                            "assets/img/productos/" . $nombreImagen;

                        $imagenExiste =
                            $nombreImagen !== "" &&
                            is_file(__DIR__ . "/" . $rutaImagen);

                        ?>

                        <article class="inicio-producto-card">

                            <!-- Imagen -->
                            <div class="inicio-producto-imagen">

                                <?php if ($imagenExiste): ?>

                                    <img
                                        src="<?= escapar($rutaImagen) ?>"
                                        alt="<?= escapar($producto["Producto"]) ?>"
                                        loading="lazy">

                                <?php else: ?>

                                    <i class="fa-solid fa-box"></i>

                                <?php endif; ?>

                                <!-- Disponibilidad -->
                                <?php if ($stock > 0): ?>

                                    <span class="disponibilidad disponible">
                                        Disponible
                                    </span>

                                <?php else: ?>

                                    <span class="disponibilidad agotado">
                                        Agotado
                                    </span>

                                <?php endif; ?>

                            </div>


                            <!-- Información -->
                            <div class="inicio-producto-info">

                                <span class="inicio-producto-categoria">

                                    <?= escapar(
                                        $producto["NombreCategoria"]
                                        ?? "Sin categoría"
                                    ) ?>

                                </span>

                                <h3>

                                    <?= escapar($producto["Producto"]) ?>

                                </h3>

                                <span class="inicio-producto-sku">

                                    SKU:
                                    <?= escapar($producto["SKU"]) ?>

                                </span>

                                <p class="inicio-producto-descripcion">

                                    <?= escapar($producto["Descripcion"]) ?>

                                </p>

                                <div class="inicio-producto-precio">

                                    ₡<?= number_format(
                                        $producto["Precio"],
                                        2,
                                        ",",
                                        "."
                                    ) ?>

                                </div>

                                <div class="inicio-producto-stock">

                                    <?php if ($stock > 0): ?>

                                        <i class="fa-solid fa-circle-check"></i>

                                        <?= $stock ?> unidades disponibles

                                    <?php else: ?>

                                        Sin existencias

                                    <?php endif; ?>

                                </div>

                                <a
                                    href=""
                                    class="inicio-btn inicio-btn-primary">

                                    Agregar al carrito

                                    <i class="fa-solid fa-arrow-right"></i>

                                </a>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>


    </main>


</body>

</html>