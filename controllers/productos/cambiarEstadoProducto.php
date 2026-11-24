<?php

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    !empty($_POST["btnCambiarEstado"])
) {

    $idProducto = filter_input(
        INPUT_POST,
        "idProducto",
        FILTER_VALIDATE_INT
    );

    if (!$idProducto || $idProducto < 1) {

        echo '<div class="alert alert-warning">
                Producto inválido.
              </div>';

    } else {

        try {

            $conexion->beginTransaction();

            // Consultar el estado actual
            $sql = $conexion->prepare("
                SELECT Activo
                FROM productos
                WHERE idProducto = :idProducto
                FOR UPDATE
            ");

            $sql->execute([
                ":idProducto" => $idProducto
            ]);

            $producto = $sql->fetch(PDO::FETCH_ASSOC);

            if (!$producto) {

                throw new RuntimeException(
                    "El producto no existe."
                );

            }

            // Determinar el nuevo estado
            $nuevoEstado = (int) $producto["Activo"] === 1 ? 0 : 1;

            // Actualizar el estado
            $sql = $conexion->prepare("
                UPDATE productos
                SET Activo = :activo
                WHERE idProducto = :idProducto
            ");

            $sql->execute([
                ":activo" => $nuevoEstado,
                ":idProducto" => $idProducto
            ]);

            $conexion->commit();

            if ($nuevoEstado === 1) {

                echo '<div class="alert alert-success">
                        Producto activado correctamente.
                      </div>';

            } else {

                echo '<div class="alert alert-success">
                        Producto desactivado correctamente.
                      </div>';

            }

        } catch (PDOException $e) {

            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            error_log($e->getMessage());

            echo '<div class="alert alert-danger">
                    Error al cambiar el estado del producto.
                  </div>';

        } catch (RuntimeException $e) {

            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            echo '<div class="alert alert-warning">'
                . htmlspecialchars($e->getMessage())
                . '</div>';

        }
    }
}
?>