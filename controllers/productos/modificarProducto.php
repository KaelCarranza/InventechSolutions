<?php

if (realpath($_SERVER["SCRIPT_FILENAME"] ?? "") === __FILE__) {
    http_response_code(404);
    exit();
}

require_once __DIR__ . "/../bitacora/registrarActividad.php";
require_once __DIR__ . "/../../config/entrada.php";

if (!empty($_POST["btnModificar"])) {

    $idProducto = filter_input(
        INPUT_POST,
        "idProducto",
        FILTER_VALIDATE_INT
    );

    $idCategoria = filter_input(
        INPUT_POST,
        "idCategoria",
        FILTER_VALIDATE_INT
    );

    $sku = strtoupper(textoEntrada($_POST["sku"] ?? null));
    $producto = textoEntrada($_POST["producto"] ?? null);
    $precio = textoEntrada($_POST["precio"] ?? null);
    $descripcion = textoEntrada($_POST["descripcion"] ?? null);

    // Validar datos
    if (
        !$idProducto ||
        $idProducto < 1 ||
        !$idCategoria ||
        $idCategoria < 1 ||
        $sku === "" ||
        $producto === "" ||
        $descripcion === ""
    ) {

        echo '<div class="alert alert-warning">
                Todos los campos son obligatorios o contienen datos inválidos.
              </div>';

    } elseif (mb_strlen($sku) > 50) {
        echo '<div class="alert alert-warning">El SKU no puede superar los 50 caracteres.</div>';
    } elseif (mb_strlen($producto) > 150 || strlen($descripcion) > 65535) {
        echo '<div class="alert alert-warning">El nombre admite hasta 150 caracteres y la descripción hasta 65 535 bytes.</div>';
    } elseif (!precioEntradaValido($precio)) {

        echo '<div class="alert alert-warning">
                Indique un precio entre 0 y 99 999 999,99, con hasta dos decimales y sin notación científica.
              </div>';

    } else {

        $rutaNuevaImagen = null;

        try {
            $conexion->beginTransaction();


            $consulta = $conexion->prepare("
                SELECT Imagen
                FROM productos
                WHERE idProducto = :idProducto FOR UPDATE
            ");

            $consulta->execute([
                ":idProducto" => $idProducto
            ]);

            $productoActual = $consulta->fetch(PDO::FETCH_ASSOC);

            if (!$productoActual) {

                throw new RuntimeException(
                    "El producto no existe."
                );

            }

            // Validar SKU
            $verificarSKU = $conexion->prepare("
                SELECT idProducto
                FROM productos
                WHERE SKU = :sku
                AND idProducto <> :idProducto
            ");

            $verificarSKU->execute([
                ":sku" => $sku,
                ":idProducto" => $idProducto
            ]);

            if ($verificarSKU->fetch()) {

                throw new RuntimeException(
                    "El SKU ya pertenece a otro producto."
                );

            }


            $verificarCategoria = $conexion->prepare("
                SELECT idCategoria
                FROM categorias
                WHERE idCategoria = :idCategoria
            ");

            $verificarCategoria->execute([
                ":idCategoria" => $idCategoria
            ]);

            if (!$verificarCategoria->fetch()) {

                throw new RuntimeException(
                    "La categoría seleccionada no existe."
                );

            }


            $imagenAnterior = $productoActual["Imagen"];
            $nombreImagen = $imagenAnterior;

            $directorio = dirname(__DIR__, 2)
                . "/assets/img/productos/";


            if (
                isset($_FILES["imagen"]) &&
                $_FILES["imagen"]["error"] !== UPLOAD_ERR_NO_FILE
            ) {

                $archivo = $_FILES["imagen"];

                if ($archivo["error"] !== UPLOAD_ERR_OK) {

                    throw new RuntimeException(
                        "Seleccione una imagen JPG, PNG o WEBP de hasta 2 MB."
                    );

                }


                $maximo = 2 * 1024 * 1024;

                if ($archivo["size"] > $maximo) {

                    throw new RuntimeException(
                        "La imagen supera los 2 MB."
                    );

                }


                $permitidos = [
                    "image/jpeg" => "jpg",
                    "image/png"  => "png",
                    "image/webp" => "webp"
                ];


                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($archivo["tmp_name"]);

                if (!isset($permitidos[$mime])) {

                    throw new RuntimeException(
                        "Formato de imagen no permitido."
                    );

                }

                // Validar la imagen
                if (@getimagesize($archivo["tmp_name"]) === false) {

                    throw new RuntimeException(
                        "El archivo no es una imagen válida."
                    );

                }


                $nombreImagen = bin2hex(random_bytes(16))
                    . "." . $permitidos[$mime];


                if (!is_dir($directorio) && !@mkdir($directorio, 0755, true) && !is_dir($directorio)) {
                    error_log(error_get_last()["message"] ?? "No se pudo crear el directorio de imágenes.");
                    throw new RuntimeException("No se pudo guardar la imagen. Inténtelo nuevamente.");
                }

                $rutaNuevaImagen = $directorio . $nombreImagen;


                if (!@move_uploaded_file(
                    $archivo["tmp_name"],
                    $rutaNuevaImagen
                )) {

                    error_log(error_get_last()["message"] ?? "No se pudo escribir la imagen.");
                    throw new RuntimeException(
                        "No se pudo guardar la imagen. Inténtelo nuevamente."
                    );

                }
            }

            // Actualizar datos sin cambiar stock ni estado
            $sql = $conexion->prepare("
                UPDATE productos
                SET SKU = :sku,
                    Producto = :producto,
                    Precio = :precio,
                    Descripcion = :descripcion,
                    Imagen = :imagen,
                    idCategoria = :idCategoria
                WHERE idProducto = :idProducto
            ");

            $resultado = $sql->execute([
                ":sku" => $sku,
                ":producto" => $producto,
                ":precio" => $precio,
                ":descripcion" => $descripcion,
                ":imagen" => $nombreImagen,
                ":idCategoria" => $idCategoria,
                ":idProducto" => $idProducto
            ]);

            if (!$resultado) {

                throw new RuntimeException(
                    "No se pudo modificar el producto."
                );

            }

            $cambio = $sql->rowCount() > 0;
            if ($cambio) {
                registrarActividad($conexion, "Productos", "Modificación", $idProducto, "Producto modificado.");
            }
            $conexion->commit();

            // Retirar la imagen anterior tras confirmar
            if (
                $rutaNuevaImagen !== null &&
                !empty($imagenAnterior) &&
                $imagenAnterior !== $nombreImagen
            ) {

                $rutaAnterior = $directorio
                    . basename($imagenAnterior);

                if (is_file($rutaAnterior)) {

                    if (!@unlink($rutaAnterior)) {

                        error_log(
                            "No se pudo eliminar la imagen anterior."
                        );

                    }
                }
            }

            echo $cambio
                ? '<div class="alert alert-success">Producto modificado correctamente.</div>'
                : '<div class="alert alert-info">No se realizaron cambios en el producto.</div>';

        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            // Retirar la imagen si falla la edición
            if (
                $rutaNuevaImagen !== null &&
                is_file($rutaNuevaImagen)
            ) {

                if (!@unlink($rutaNuevaImagen)) {
                    error_log("No se pudo retirar la imagen de una operación rechazada.");
                }

            }

            error_log($e->getMessage());

            echo '<div class="alert alert-danger">
                    Error al modificar el producto.
                  </div>';

        } catch (RuntimeException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }


            if (
                $rutaNuevaImagen !== null &&
                is_file($rutaNuevaImagen)
            ) {

                if (!@unlink($rutaNuevaImagen)) {
                    error_log("No se pudo retirar la imagen de una operación rechazada.");
                }

            }

            echo '<div class="alert alert-warning">'
                . htmlspecialchars(
                    $e->getMessage(),
                    ENT_QUOTES,
                    'UTF-8'
                )
                . '</div>';

        }
    }
}
?>
