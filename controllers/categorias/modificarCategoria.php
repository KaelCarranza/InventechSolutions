<?php

if (realpath($_SERVER["SCRIPT_FILENAME"] ?? "") === __FILE__) {
    http_response_code(404);
    exit();
}

require_once __DIR__ . "/../bitacora/registrarActividad.php";
require_once __DIR__ . "/../../config/entrada.php";

if (!empty($_POST["btnModificar"])) {

    $idCategoria = filter_input(
        INPUT_POST,
        "idCategoria",
        FILTER_VALIDATE_INT
    );

    $nombre = textoEntrada($_POST["Nombre"] ?? null);
    $descripcion = textoEntrada($_POST["Descripcion"] ?? null);

    if (
        !$idCategoria ||
        $idCategoria < 1 ||
        $nombre === "" ||
        $descripcion === ""
    ) {

        echo '<div class="alert alert-warning">
                Todos los campos son obligatorios.
              </div>';

    } elseif (mb_strlen($nombre) > 100 || mb_strlen($descripcion) > 255) {
        echo '<div class="alert alert-warning">Revise la longitud: el nombre admite 100 caracteres y la descripción 255.</div>';
    } else {

        try {
            $conexion->beginTransaction();
            $consulta = $conexion->prepare("SELECT idCategoria FROM categorias WHERE idCategoria = ? FOR UPDATE");
            $consulta->execute([$idCategoria]);
            if (!$consulta->fetchColumn()) {
                $conexion->rollBack();
                echo '<div class="alert alert-warning">La categoría ya no existe. Vuelva al listado.</div>';
                return;
            }


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
                $conexion->rollBack();

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
                    if ($sql->rowCount() > 0) {
                        registrarActividad($conexion, "Categorías", "Modificación", $idCategoria, "Categoría modificada.");
                        $mensaje = '<div class="alert alert-success">Categoría modificada correctamente.</div>';
                    } else {
                        $mensaje = '<div class="alert alert-info">No se realizaron cambios en la categoría.</div>';
                    }
                    $conexion->commit();
                    echo $mensaje;

                } else {
                    $conexion->rollBack();

                    echo '<div class="alert alert-danger">
                            Error al modificar la categoría.
                          </div>';

                }
            }

        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            error_log($e->getMessage());

            echo '<div class="alert alert-danger">
                    No se pudo modificar la categoría.
                  </div>';

        }
    }
}
?>
