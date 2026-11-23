<?php

if (!empty($_POST["btnEliminar"])) {
    if (!empty($_POST["idUsuario"])) {
        $idUsuario = $_POST["idUsuario"];
        $sql = $conexion->prepare(
            "DELETE FROM users WHERE userId = :idUsuario"
        );

        if ($sql->execute(["idUsuario" => $idUsuario])) {
            echo '<div class="alert alert-success">
                    Usuario Eliminado correctamente
                  </div>';
        } else {
            echo "<div class='alert alert-danger'>Error al eliminar usuario!!</div>";
        }
    } else {
        echo "<div class='alert alert-warning'>Usuario no válido!!</div>";
    }
}
