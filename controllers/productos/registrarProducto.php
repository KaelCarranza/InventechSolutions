<?php

if (realpath($_SERVER["SCRIPT_FILENAME"] ?? "") === __FILE__) {
    http_response_code(404);
    exit();
}

require_once __DIR__ . "/../bitacora/registrarActividad.php";
require_once __DIR__ . "/../../config/entrada.php";

if (!empty($_POST["btnInsertar"])) {

    $sku = strtoupper(textoEntrada($_POST["sku"] ?? null));
    $producto = textoEntrada($_POST["producto"] ?? null);
    $precio = textoEntrada($_POST["precio"] ?? null);
    $descripcion = textoEntrada($_POST["descripcion"] ?? null);


    $idCategoria = filter_input(
        INPUT_POST,
        "idCategoria",
        FILTER_VALIDATE_INT
    );

    // Validar datos
    if (
        $sku === "" ||
        $producto === "" ||
        $precio === "" ||
        $descripcion === "" ||
        !$idCategoria ||
        $idCategoria < 1
    ) {

        echo '<div class="alert alert-warning">
                Todos los campos son obligatorios.
              </div>';

    } elseif (mb_strlen($sku) > 50) {

        echo '<div class="alert alert-warning">
                El SKU no puede superar los 50 caracteres.
              </div>';

    } elseif (mb_strlen($producto) > 150 || strlen($descripcion) > 65535) {
        echo '<div class="alert alert-warning">El nombre admite hasta 150 caracteres y la descripción hasta 65 535 bytes.</div>';
    } elseif (!precioEntradaValido($precio)) {

        echo '<div class="alert alert-warning">
                Indique un precio entre 0 y 99 999 999,99, con hasta dos decimales y sin notación científica.
              </div>';

    } elseif (
        !isset($_FILES["imagen"]) ||
        $_FILES["imagen"]["error"] !== UPLOAD_ERR_OK
    ) {

        echo '<div class="alert alert-warning">
                Seleccione una imagen JPG, PNG o WEBP de hasta 2 MB.
              </div>';

    } else {

        $rutaDestino = null;

        try {

            // Validar SKU
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


            $archivo = $_FILES["imagen"];


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


            $extension = $permitidos[$mime];


            $nombreImagen = bin2hex(random_bytes(16))
                . "." . $extension;


            $directorio = dirname(__DIR__, 2)
                . "/assets/img/productos/";


            if (!is_dir($directorio) && !@mkdir($directorio, 0755, true) && !is_dir($directorio)) {
                error_log(error_get_last()["message"] ?? "No se pudo crear el directorio de imágenes.");
                throw new RuntimeException("No se pudo guardar la imagen. Inténtelo nuevamente.");
            }

            $rutaDestino = $directorio . $nombreImagen;


            if (!@move_uploaded_file(
                $archivo["tmp_name"],
                $rutaDestino
            )) {

                error_log(error_get_last()["message"] ?? "No se pudo escribir la imagen.");
                throw new RuntimeException(
                    "No se pudo guardar la imagen."
                );

            }

            // Registrar con stock inicial en cero
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
                    0,
                    :descripcion,
                    :imagen,
                    :idCategoria,
                    1,
                    NOW()
                )
            ");

            $conexion->beginTransaction();
            $resultado = $sql->execute([
                ":sku" => $sku,
                ":producto" => $producto,
                ":precio" => $precio,
                ":descripcion" => $descripcion,
                ":imagen" => $nombreImagen,
                ":idCategoria" => $idCategoria
            ]);

            if (!$resultado) {

                throw new RuntimeException(
                    "No se pudo registrar el producto."
                );

            }

            registrarActividad($conexion, "Productos", "Registro", (int) $conexion->lastInsertId(), "Producto registrado.");
            $conexion->commit();

            echo '<div class="alert alert-success">
                    Producto registrado correctamente.
                    Las existencias deben gestionarse desde inventario.
                  </div>';

        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            // Retirar la imagen si falla el registro
            if (
                $rutaDestino !== null &&
                is_file($rutaDestino)
            ) {

                if (!@unlink($rutaDestino)) {
                    error_log("No se pudo retirar la imagen de una operación rechazada.");
                }

            }

            error_log($e->getMessage());

            echo '<div class="alert alert-danger">
                    Error al registrar el producto.
                  </div>';

        } catch (RuntimeException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }


            if (
                $rutaDestino !== null &&
                is_file($rutaDestino)
            ) {

                if (!@unlink($rutaDestino)) {
                    error_log("No se pudo retirar la imagen de una operación rechazada.");
                }

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