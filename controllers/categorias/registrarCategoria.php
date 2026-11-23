<?php

if ($_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["btnInsertar"])) {

    $Nombre = trim($_POST["Nombre"] ?? "");
    $Descripcion = trim($_POST["Descripcion"] ?? "");

    if ($Nombre === "" || $Descripcion === "") {

        echo '<div class="alert alert-warning">
                Todos los campos son obligatorios.
              </div>';

    } elseif (mb_strlen($Nombre) > 100 ||
              mb_strlen($Descripcion) > 255) {

        echo '<div class="alert alert-warning">
                El nombre o la descripción
                supera la longitud permitida.
              </div>';

    } else {

        try {

            $sql = $conexion->prepare(
                "INSERT INTO categorias (Nombre, Descripcion)
                 VALUES (:nombre, :descripcion)"
            );

            $sql->execute([
                ":nombre" => $Nombre,
                ":descripcion" => $Descripcion
            ]);

            echo '<div class="alert alert-success">
                    Categoría registrada correctamente.
                  </div>';

        } catch (PDOException $e) {

            error_log($e->getMessage());

            if ($e->getCode() === "23000") {

                echo '<div class="alert alert-warning">
                        Ya existe una categoría con ese nombre.
                      </div>';

            } else {

                echo '<div class="alert alert-danger">
                        Error al registrar la categoría.
                      </div>';
            }
        }
    }
}
?>
