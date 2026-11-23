<?php

require_once __DIR__ . "/../middlewares/protegerAdmin.php";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="https://kit.fontawesome.com/4b21b27482.js" crossorigin="anonymous"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
</head>
<body>
    <h1>Panel de administración</h1>
<p>Solo administradores pueden ver esta página.</p>

<a href="../index.php" class="btn btn-primary">Inicio</a>
<br><br>
<a href="usuarios.php" class="btn btn-primary">Usuarios</a>
<br><br>
<a href="productos.php" class="btn btn-primary">Productos</a>
<br><br>
<a href="categorias.php" class="btn btn-primary">Categorias</a>
<br><br>
<a href="" class="btn btn-primary">Inventario</a>
<br><br>
<a href="" class="btn btn-primary">Pedidos</a>
<br><br>
<a href="" class="btn btn-primary">Proformas</a>
<br><br>
<a href="" class="btn btn-primary">Reportes</a>
<br><br>
<a href="" class="btn btn-primary">Bitacora</a>
<br><br>
</body>
</html>


