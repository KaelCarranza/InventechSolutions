<?php

if (!empty($_POST["btnEliminar"])) {
    if (!empty($_POST["idCategoria"])) {
        $idCategoria = $_POST["idCategoria"];
        $sql = $conexion->prepare(
            "DELETE FROM categorias WHERE idCategoria = :idCategoria"
        );

        if ($sql->execute(["idCategoria" => $idCategoria])) {
            echo '<div class="alert alert-success">
                    Categoria Eliminada correctamente
                  </div>';
        } else {
            echo "<div class='alert alert-danger'>Error al eliminar la categoria!!</div>";
        }
    } else {
        echo "<div class='alert alert-warning'>Categoria no válida!!</div>";
    }
}
