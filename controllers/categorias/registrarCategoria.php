<?php

if (realpath($_SERVER["SCRIPT_FILENAME"] ?? "") === __FILE__) {
    http_response_code(404);
    exit();
}

require_once __DIR__ . "/../bitacora/registrarActividad.php";
require_once __DIR__ . "/../../config/entrada.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["btnInsertarCategoria"])) {

    $Nombre = textoEntrada($_POST["Nombre"] ?? null);
    $Descripcion = textoEntrada($_POST["Descripcion"] ?? null);

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
            $conexion->beginTransaction();

            $sql = $conexion->prepare(
                "INSERT INTO categorias (Nombre, Descripcion)
                 VALUES (:nombre, :descripcion)"
            );

            $sql->execute([
                ":nombre" => $Nombre,
                ":descripcion" => $Descripcion
            ]);

            registrarActividad($conexion, "Categorías", "Registro", (int) $conexion->lastInsertId(), "Categoría registrada.");
            $conexion->commit();

            echo '<div class="alert alert-success">
                    Categoría registrada correctamente.
                  </div>';

        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

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
