<?php

if (realpath($_SERVER["SCRIPT_FILENAME"] ?? "") === __FILE__) {
    http_response_code(404);
    exit();
}

require_once __DIR__ . "/../bitacora/registrarActividad.php";
require_once __DIR__ . "/protegerUltimoAdministrador.php";

if (!empty($_POST["btnEliminar"])) {
    $idUsuario = filter_input(INPUT_POST, "idUsuario", FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1, "max_range" => 4294967295]]);
    if ($idUsuario) {
        try {
            $conexion->beginTransaction();
            protegerUltimoAdministrador($conexion, (int) $idUsuario);
            // Guardar el correo histórico antes de eliminar la cuenta
            registrarActividad($conexion, "Usuarios", "Eliminación", (int) $idUsuario, "Usuario eliminado.");
            $sql = $conexion->prepare(
                "DELETE FROM users WHERE userId = :idUsuario"
            );

            $sql->execute(["idUsuario" => $idUsuario]);

            if ($sql->rowCount() > 0) {
                $conexion->commit();
                echo '<div class="alert alert-success">
                        Usuario eliminado correctamente.
                      </div>';
            } else {
                $conexion->rollBack();
                echo '<div class="alert alert-warning">El usuario no existe.</div>';
            }
        } catch (DomainException $e) {
            $conexion->rollBack();
            echo '<div class="alert alert-warning">' . htmlspecialchars($e->getMessage()) . '</div>';
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            if ((int) ($e->errorInfo[1] ?? 0) === 1451) {
                echo '<div class="alert alert-warning">
                        El usuario tiene pedidos asociados. Puede deshabilitar la cuenta en su lugar.
                      </div>';
            } else {
                error_log($e->getMessage());
                echo '<div class="alert alert-danger">No se pudo eliminar el usuario.</div>';
            }
        }
    } else {
        echo "<div class='alert alert-warning'>Seleccione un usuario válido en el listado.</div>";
    }
}
