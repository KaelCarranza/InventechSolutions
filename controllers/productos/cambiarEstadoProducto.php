<?php

if (realpath($_SERVER["SCRIPT_FILENAME"] ?? "") === __FILE__) {
    http_response_code(404);
    exit();
}

require_once __DIR__ . "/../bitacora/registrarActividad.php";

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    !empty($_POST["btnCambiarEstado"])
) {

    $idProducto = filter_input(
        INPUT_POST,
        "idProducto",
        FILTER_VALIDATE_INT
    );

    $estadoDeseado = $_POST["estadoDeseado"] ?? null;

    if (!$idProducto || $idProducto < 1) {

        echo '<div class="alert alert-warning">
                Producto inválido.
              </div>';

    } elseif (!in_array($estadoDeseado, ["0", "1"], true)) {

        echo '<div class="alert alert-warning">Estado solicitado inválido.</div>';
    } else {

        try {

            $conexion->beginTransaction();


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

            $nuevoEstado = (int) $estadoDeseado;
            if ((int) $producto["Activo"] === $nuevoEstado) {
                $conexion->rollBack();
                echo '<div class="alert alert-info">El producto ya tiene el estado solicitado.</div>';
                return;
            }


            $sql = $conexion->prepare("
                UPDATE productos
                SET Activo = :activo
                WHERE idProducto = :idProducto
            ");

            $sql->execute([
                ":activo" => $nuevoEstado,
                ":idProducto" => $idProducto
            ]);

            registrarActividad(
                $conexion, "Productos", $nuevoEstado === 1 ? "Activación" : "Desactivación",
                $idProducto, $nuevoEstado === 1 ? "Producto activado." : "Producto desactivado."
            );
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
