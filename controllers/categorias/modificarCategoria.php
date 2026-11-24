<?php

if (!empty($_POST["btnModificar"])) {

    $idCategoria = filter_input(
        INPUT_POST,
        "idCategoria",
        FILTER_VALIDATE_INT
    );

    $nombre = trim($_POST["Nombre"] ?? "");
    $descripcion = trim($_POST["Descripcion"] ?? "");

    if (
        !$idCategoria ||
        $idCategoria < 1 ||
        $nombre === "" ||
        $descripcion === ""
    ) {

        echo '<div class="alert alert-warning">
                Todos los campos son obligatorios.
              </div>';

    } else {

        try {

            // Verificar que no exista otra categoría con el mismo nombre
            $verificar = $conexion->prepare("
                SELECT idCategoria
                FROM categorias
                WHERE Nombre = :nombre
                AND idCategoria <> :idCategoria
            ");

            $verificar->execute([
                ":nombre" => $nombre,
                ":idCategoria" => $idCategoria
            ]);

            if ($verificar->fetch()) {

                echo '<div class="alert alert-warning">
                        Ya existe una categoría con ese nombre.
                      </div>';

            } else {

                $sql = $conexion->prepare("
                    UPDATE categorias
                    SET Nombre = :nombre,
                        Descripcion = :descripcion
                    WHERE idCategoria = :idCategoria
                ");

                if ($sql->execute([
                    ":nombre" => $nombre,
                    ":descripcion" => $descripcion,
                    ":idCategoria" => $idCategoria
                ])) {

                    echo '<div class="alert alert-success">
                            Categoría modificada correctamente.
                          </div>';

                } else {

                    echo '<div class="alert alert-danger">
                            Error al modificar la categoría.
                          </div>';

                }
            }

        } catch (PDOException $e) {

            error_log($e->getMessage());

            echo '<div class="alert alert-danger">
                    No se pudo modificar la categoría.
                  </div>';

        }
    }
}
?>