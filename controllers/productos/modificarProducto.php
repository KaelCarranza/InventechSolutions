<?php

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

    $sku = strtoupper(trim($_POST["sku"] ?? ""));
    $producto = trim($_POST["producto"] ?? "");
    $precio = $_POST["precio"] ?? "";
    $cantidad = $_POST["cantidad"] ?? "";
    $descripcion = trim($_POST["descripcion"] ?? "");
    $activo = $_POST["activo"] ?? "";

    if (
        !$idProducto ||
        $idProducto < 1 ||
        !$idCategoria ||
        $idCategoria < 1 ||
        $sku === "" ||
        strlen($sku) > 50 ||
        $producto === "" ||
        $descripcion === ""
    ) {

        echo '<div class="alert alert-warning">
                Todos los campos son obligatorios o contienen datos inválidos.
              </div>';

    } elseif (
        !is_numeric($precio) ||
        (float) $precio < 0 ||
        filter_var($cantidad, FILTER_VALIDATE_INT) === false ||
        (int) $cantidad < 0 ||
        !in_array($activo, ["0", "1"], true)
    ) {

        echo '<div class="alert alert-warning">
                Precio, cantidad o estado inválidos.
              </div>';

    } else {

        $rutaNuevaImagen = null;

        try {

            // Verificar producto existente
            $consulta = $conexion->prepare("
                SELECT Imagen
                FROM productos
                WHERE idProducto = :idProducto
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

            // Verificar SKU duplicado
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

            // Verificar categoría
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

            // Conservar imagen actual
            $imagenAnterior = $productoActual["Imagen"];
            $nombreImagen = $imagenAnterior;

            $directorio = dirname(__DIR__, 2)
                . "/assets/img/productos/";

            // Verificar si se seleccionó una nueva imagen
            if (
                isset($_FILES["imagen"]) &&
                $_FILES["imagen"]["error"] !== UPLOAD_ERR_NO_FILE
            ) {

                $archivo = $_FILES["imagen"];

                if ($archivo["error"] !== UPLOAD_ERR_OK) {
                    throw new RuntimeException(
                        "Error al cargar la imagen."
                    );
                }

                // Máximo 2 MB
                $maximo = 2 * 1024 * 1024;

                if ($archivo["size"] > $maximo) {
                    throw new RuntimeException(
                        "La imagen supera los 2 MB."
                    );
                }

                // Formatos permitidos
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

                if (@getimagesize($archivo["tmp_name"]) === false) {
                    throw new RuntimeException(
                        "El archivo no es una imagen válida."
                    );
                }

                // Generar nombre único
                $nombreImagen = bin2hex(random_bytes(16))
                    . "." . $permitidos[$mime];

                if (!is_dir($directorio)) {
                    mkdir($directorio, 0755, true);
                }

                $rutaNuevaImagen = $directorio . $nombreImagen;

                if (!move_uploaded_file(
                    $archivo["tmp_name"],
                    $rutaNuevaImagen
                )) {

                    throw new RuntimeException(
                        "No se pudo guardar la imagen."
                    );

                }
            }

            // Actualizar producto
            $sql = $conexion->prepare("
                UPDATE productos
                SET SKU = :sku,
                    Producto = :producto,
                    Precio = :precio,
                    Cantidad = :cantidad,
                    Descripcion = :descripcion,
                    Imagen = :imagen,
                    idCategoria = :idCategoria,
                    Activo = :activo
                WHERE idProducto = :idProducto
            ");

            $resultado = $sql->execute([
                ":sku" => $sku,
                ":producto" => $producto,
                ":precio" => $precio,
                ":cantidad" => $cantidad,
                ":descripcion" => $descripcion,
                ":imagen" => $nombreImagen,
                ":idCategoria" => $idCategoria,
                ":activo" => $activo,
                ":idProducto" => $idProducto
            ]);

            if (!$resultado) {
                throw new RuntimeException(
                    "No se pudo modificar el producto."
                );
            }

            // Eliminar imagen anterior si se cambió
            if (
                $rutaNuevaImagen !== null &&
                !empty($imagenAnterior) &&
                $imagenAnterior !== $nombreImagen
            ) {

                $rutaAnterior = $directorio
                    . basename($imagenAnterior);

                if (is_file($rutaAnterior)) {

                    if (!@unlink($rutaAnterior)) {
                        error_log("No se pudo eliminar la imagen anterior.");
                    }

                }
            }

            echo '<div class="alert alert-success">
                    Producto modificado correctamente.
                  </div>';

        } catch (PDOException $e) {

            // Eliminar nueva imagen si falla MySQL
            if (
                $rutaNuevaImagen !== null &&
                is_file($rutaNuevaImagen)
            ) {
                unlink($rutaNuevaImagen);
            }

            error_log($e->getMessage());

            echo '<div class="alert alert-danger">
                    Error al modificar el producto.
                  </div>';

        } catch (RuntimeException $e) {

            if (
                $rutaNuevaImagen !== null &&
                is_file($rutaNuevaImagen)
            ) {
                unlink($rutaNuevaImagen);
            }

            echo '<div class="alert alert-warning">'
                . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
                . '</div>';

        }
    }
}
?>