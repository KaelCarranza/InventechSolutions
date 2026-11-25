<?php

require_once __DIR__ . "/../bitacora/registrarActividad.php";
require_once __DIR__ . "/protegerUltimoAdministrador.php";

require_once __DIR__ . "/../../middlewares/protegerAdmin.php";
require_once __DIR__ . "/../../middlewares/protegerCsrfAdmin.php";
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

    $nuevoEstado = $_POST["estadoDeseado"] ?? null;

    if (!$idUsuario || $idUsuario < 1) {

        echo '<div class="alert alert-warning">
                Usuario inválido.
              </div>';
    } elseif ($idUsuario === (int) ($_SESSION["userId"] ?? 0)) {

        echo '<div class="alert alert-warning">
                No puedes cambiar el estado de tu propia cuenta.
              </div>';
    } elseif (!in_array($nuevoEstado, ["Habilitado", "Deshabilitado"], true)) {

        echo '<div class="alert alert-warning">Estado solicitado inválido.</div>';
    } else {

        try {

            $conexion->beginTransaction();
            if ($nuevoEstado === "Deshabilitado") {
                protegerUltimoAdministrador($conexion, $idUsuario);
            }


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
            } elseif (!in_array($usuario["loginStatus"], ["Habilitado", "Deshabilitado"], true)) {
                throw new Exception("Estado desconocido.");
            } elseif ($usuario["loginStatus"] === $nuevoEstado) {
                $conexion->rollBack();
                echo '<div class="alert alert-info">El usuario ya tiene el estado solicitado.</div>';
            } else {


                $sql = $conexion->prepare("
                    UPDATE users
                    SET loginStatus = :estado
                    WHERE userId = :idUsuario
                ");

                $sql->execute([
                    ":estado" => $nuevoEstado,
                    ":idUsuario" => $idUsuario
                ]);

                registrarActividad(
                    $conexion, "Usuarios", $nuevoEstado === "Habilitado" ? "Habilitación" : "Deshabilitación",
                    $idUsuario, "Estado del usuario: " . $usuario["loginStatus"] . " → " . $nuevoEstado . "."
                );
                $conexion->commit();

                echo '<div class="alert alert-success">
                        Estado del usuario actualizado correctamente.
                      </div>';
            }
        } catch (DomainException $e) {
            $conexion->rollBack();
            echo '<div class="alert alert-warning">' . htmlspecialchars($e->getMessage()) . '</div>';
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
