
<?php

if (!empty($_POST["btnInsertar"])) {

    $producto = trim($_POST["producto"] ?? "");
    $precio = $_POST["precio"] ?? "";
    $cantidad = $_POST["cantidad"] ?? "";
    $descripcion = trim($_POST["descripcion"] ?? "");

    if (
        $producto === "" ||
        $precio === "" ||
        $cantidad === "" ||
        $descripcion === ""
    ) {
        echo '<div class="alert alert-warning">
                Todos los campos son obligatorios.
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

        $archivo = $_FILES["imagen"];

        $maximo = 2 * 1024 * 1024; // 2 MB

        if ($archivo["size"] > $maximo) {

            echo '<div class="alert alert-danger">
                    La imagen supera los 2 MB.
                  </div>';

        } else {

            $permitidos = [
                "image/jpeg" => "jpg",
                "image/png"  => "png",
                "image/webp" => "webp"
            ];

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($archivo["tmp_name"]);

            if (!isset($permitidos[$mime])) {

                echo '<div class="alert alert-danger">
                        Formato de imagen no permitido.
                      </div>';

            } elseif (@getimagesize($archivo["tmp_name"]) === false) {

                echo '<div class="alert alert-danger">
                        El archivo no es una imagen válida.
                      </div>';

            } else {

                $extension = $permitidos[$mime];

                // Nombre aleatorio para evitar duplicados
                $nombreImagen = bin2hex(random_bytes(16))
                    . "." . $extension;

                // Ruta física absoluta
                $directorio = dirname(__DIR__, 2)
                    . "/assets/img/productos/";

                if (!is_dir($directorio)) {
                    mkdir($directorio, 0755, true);
                }

                $rutaDestino = $directorio . $nombreImagen;

                if (!move_uploaded_file(
                    $archivo["tmp_name"],
                    $rutaDestino
                )) {

                    echo '<div class="alert alert-danger">
                            No se pudo guardar la imagen.
                          </div>';

                } else {

                    try {

                        $sql = $conexion->prepare("
                            INSERT INTO productos
                                (Producto, Precio, Cantidad,
                                 Descripcion, Imagen)
                            VALUES
                                (:producto, :precio, :cantidad,
                                 :descripcion, :imagen)
                        ");

                        $sql->execute([
                            ":producto" => $producto,
                            ":precio" => $precio,
                            ":cantidad" => $cantidad,
                            ":descripcion" => $descripcion,
                            ":imagen" => $nombreImagen
                        ]);

                        echo '<div class="alert alert-success">
                                Producto registrado correctamente.
                              </div>';

                    } catch (PDOException $e) {

                        // Si falla MySQL, borrar la imagen subida
                        if (is_file($rutaDestino)) {
                            unlink($rutaDestino);
                        }

                        error_log($e->getMessage());

                        echo '<div class="alert alert-danger">
                                Error al registrar el producto.
                              </div>';
                    }
                }
            }
        }
    }
}
?>