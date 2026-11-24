<?php

if (!empty($_POST["btnInsertar"])) {

    $sku = strtoupper(trim($_POST["sku"] ?? ""));
    $producto = trim($_POST["producto"] ?? "");
    $precio = $_POST["precio"] ?? "";
    $cantidad = $_POST["cantidad"] ?? "";
    $descripcion = trim($_POST["descripcion"] ?? "");

    // Recibir categoría
    $idCategoria = filter_input(
        INPUT_POST,
        "idCategoria",
        FILTER_VALIDATE_INT
    );

    // Validar campos obligatorios
    if (
        $sku === "" ||
        $producto === "" ||
        $precio === "" ||
        $cantidad === "" ||
        $descripcion === "" ||
        !$idCategoria ||
        $idCategoria < 1
    ) {

        echo '<div class="alert alert-warning">
                Todos los campos son obligatorios.
              </div>';

    } elseif (strlen($sku) > 50) {

        echo '<div class="alert alert-warning">
                El SKU no puede superar los 50 caracteres.
              </div>';

    } elseif (
        !is_numeric($precio) ||
        (float)$precio < 0 ||
        filter_var($cantidad, FILTER_VALIDATE_INT) === false ||
        (int)$cantidad < 0
    ) {

        echo '<div class="alert alert-warning">
                Precio o cantidad inválidos.
              </div>';

    } elseif (
        !isset($_FILES["imagen"]) ||
        $_FILES["imagen"]["error"] !== UPLOAD_ERR_OK
    ) {

        echo '<div class="alert alert-warning">
                Debes seleccionar una imagen válida.
              </div>';

    } else {

        $rutaDestino = null;

        try {

            // Verificar que el SKU no esté registrado
            $verificarSKU = $conexion->prepare("
                SELECT idProducto
                FROM productos
                WHERE SKU = :sku
            ");

            $verificarSKU->execute([
                ":sku" => $sku
            ]);

            if ($verificarSKU->fetchColumn()) {

                throw new RuntimeException(
                    "El SKU ya está registrado."
                );

            }

            // Verificar que la categoría exista
            $sqlCategoria = $conexion->prepare("
                SELECT idCategoria
                FROM categorias
                WHERE idCategoria = :idCategoria
            ");

            $sqlCategoria->execute([
                ":idCategoria" => $idCategoria
            ]);

            if (!$sqlCategoria->fetchColumn()) {

                throw new RuntimeException(
                    "La categoría seleccionada no existe."
                );

            }

            // Obtener imagen
            $archivo = $_FILES["imagen"];

            // Tamaño máximo: 2 MB
            $maximo = 2 * 1024 * 1024;

            if ($archivo["size"] > $maximo) {

                throw new RuntimeException(
                    "La imagen supera los 2 MB."
                );

            }

            // Formatos de imagen permitidos
            $permitidos = [
                "image/jpeg" => "jpg",
                "image/png"  => "png",
                "image/webp" => "webp"
            ];

            // Verificar el tipo real del archivo
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($archivo["tmp_name"]);

            if (!isset($permitidos[$mime])) {

                throw new RuntimeException(
                    "Formato de imagen no permitido."
                );

            }

            // Verificar que realmente sea una imagen
            if (@getimagesize($archivo["tmp_name"]) === false) {

                throw new RuntimeException(
                    "El archivo no es una imagen válida."
                );

            }

            // Obtener extensión
            $extension = $permitidos[$mime];

            // Generar nombre aleatorio para evitar duplicados
            $nombreImagen = bin2hex(random_bytes(16))
                . "." . $extension;

            // Ruta física absoluta
            $directorio = dirname(__DIR__, 2)
                . "/assets/img/productos/";

            // Crear directorio si no existe
            if (!is_dir($directorio)) {

                mkdir($directorio, 0755, true);

            }

            $rutaDestino = $directorio . $nombreImagen;

            // Guardar imagen en el servidor
            if (!move_uploaded_file(
                $archivo["tmp_name"],
                $rutaDestino
            )) {

                throw new RuntimeException(
                    "No se pudo guardar la imagen."
                );

            }

            // Registrar producto
            $sql = $conexion->prepare("
                INSERT INTO productos (
                    SKU,
                    Producto,
                    Precio,
                    Cantidad,
                    Descripcion,
                    Imagen,
                    idCategoria,
                    Activo,
                    fechaRegistro
                )
                VALUES (
                    :sku,
                    :producto,
                    :precio,
                    :cantidad,
                    :descripcion,
                    :imagen,
                    :idCategoria,
                    1,
                    NOW()
                )
            ");

            $resultado = $sql->execute([
                ":sku" => $sku,
                ":producto" => $producto,
                ":precio" => $precio,
                ":cantidad" => $cantidad,
                ":descripcion" => $descripcion,
                ":imagen" => $nombreImagen,
                ":idCategoria" => $idCategoria
            ]);

            if (!$resultado) {

                throw new RuntimeException(
                    "No se pudo registrar el producto."
                );

            }

            echo '<div class="alert alert-success">
                    Producto registrado correctamente.
                  </div>';

        } catch (PDOException $e) {

            // Si falla MySQL, eliminar la imagen subida
            if (
                $rutaDestino !== null &&
                is_file($rutaDestino)
            ) {

                unlink($rutaDestino);

            }

            error_log($e->getMessage());

            echo '<div class="alert alert-danger">
                    Error al registrar el producto.
                  </div>';

        } catch (RuntimeException $e) {

            // Eliminar la imagen si ocurre algún error
            if (
                $rutaDestino !== null &&
                is_file($rutaDestino)
            ) {

                unlink($rutaDestino);

            }

            echo '<div class="alert alert-warning">'
                . htmlspecialchars(
                    $e->getMessage(),
                    ENT_QUOTES,
                    "UTF-8"
                )
                . '</div>';

        }
    }
}
?>