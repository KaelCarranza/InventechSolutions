<?php

require_once __DIR__ . "/../../middlewares/protegerAdmin.php";
require_once __DIR__ . "/../../config/db.php";

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    !empty($_POST["btnCambiarEstado"])
) {

    $idUsuario = filter_input(
        INPUT_POST,
        "idUsuario",
        FILTER_VALIDATE_INT
    );

    if (!$idUsuario || $idUsuario < 1) {

        echo '<div class="alert alert-warning">
                Usuario inválido.
              </div>';
    } elseif ($idUsuario === (int) ($_SESSION["userId"] ?? 0)) {

        echo '<div class="alert alert-warning">
                No puedes cambiar el estado de tu propia cuenta.
              </div>';
    } else {

        try {

            $conexion->beginTransaction();

            // Consultar el estado actual
            $sql = $conexion->prepare("
                SELECT loginStatus
                FROM users
                WHERE userId = :idUsuario
                FOR UPDATE
            ");

            $sql->execute([
                ":idUsuario" => $idUsuario
            ]);

            $usuario = $sql->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {

                $conexion->rollBack();

                echo '<div class="alert alert-warning">
                        El usuario no existe.
                      </div>';
            } else {

                // Determinar el nuevo estado
                if ($usuario["loginStatus"] === "Habilitado") {

                    $nuevoEstado = "Deshabilitado";
                } elseif ($usuario["loginStatus"] === "Deshabilitado") {

                    $nuevoEstado = "Habilitado";
                } else {

                    throw new Exception("Estado desconocido.");
                }

                // Actualizar estado
                $sql = $conexion->prepare("
                    UPDATE users
                    SET loginStatus = :estado
                    WHERE userId = :idUsuario
                ");

                $sql->execute([
                    ":estado" => $nuevoEstado,
                    ":idUsuario" => $idUsuario
                ]);

                $conexion->commit();

                echo '<div class="alert alert-success">
                        Estado del usuario actualizado correctamente.
                      </div>';
            }
        } catch (Exception $e) {

            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            error_log($e->getMessage());

            echo '<div class="alert alert-danger">
                    Error al cambiar el estado del usuario.
                  </div>';
        }
    }
}
