<?php

if (realpath($_SERVER["SCRIPT_FILENAME"] ?? "") === __FILE__) {
    http_response_code(404);
    exit();
}

require_once __DIR__ . "/../bitacora/registrarActividad.php";

if (!empty($_POST["btnEliminar"])) {

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


            $verificar = $conexion->prepare("
                SELECT COUNT(*)
                FROM detallepedido
                WHERE idProducto = :idProducto
            ");

            $verificar->execute([
                ":idProducto" => $idProducto
            ]);

            $totalPedidos = (int) $verificar->fetchColumn();

            if ($totalPedidos > 0) {

                echo '<div class="alert alert-warning">
                        No se puede eliminar el producto porque tiene
                        pedidos asociados. Puede desactivarlo en su lugar.
                      </div>';

            } else {


                $consulta = $conexion->prepare("
                    SELECT Imagen
                    FROM productos
                    WHERE idProducto = :idProducto FOR UPDATE
                ");

                $consulta->execute([
                    ":idProducto" => $idProducto
                ]);

                $producto = $consulta->fetch(PDO::FETCH_ASSOC);

                if (!$producto) {

                    echo '<div class="alert alert-warning">
                            El producto no existe.
                          </div>';

                } else {


                    $sql = $conexion->prepare("
                        DELETE FROM productos
                        WHERE idProducto = :idProducto
                    ");

                    $resultado = $sql->execute([
                        ":idProducto" => $idProducto
                    ]);

                    if ($resultado && $sql->rowCount() > 0) {
                        registrarActividad($conexion, "Productos", "Eliminación", $idProducto, "Producto eliminado.");
                        $conexion->commit();


                        if (!empty($producto["Imagen"])) {

                            $rutaImagen = dirname(__DIR__, 2)
                                . "/assets/img/productos/"
                                . basename($producto["Imagen"]);

                            if (is_file($rutaImagen)) {

                                if (!@unlink($rutaImagen)) {
                                    error_log(
                                        "No se pudo eliminar la imagen."
                                    );
                                }

                            }
                        }

                        echo '<div class="alert alert-success">
                                Producto eliminado correctamente.
                              </div>';

                    } else {
                        $conexion->rollBack();

                        echo '<div class="alert alert-danger">
                                No se pudo eliminar el producto.
                              </div>';

                    }
                }
            }

            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            error_log($e->getMessage());

            if (
                $e->getCode() === "23000" &&
                (int) ($e->errorInfo[1] ?? 0) === 1451
            ) {

                echo '<div class="alert alert-warning">
                        El producto tiene registros asociados.
                        No puede ser eliminado.
                      </div>';

            } else {

                echo '<div class="alert alert-danger">
                        Error al eliminar el producto.
                      </div>';

            }
        }
    }
}
?>
