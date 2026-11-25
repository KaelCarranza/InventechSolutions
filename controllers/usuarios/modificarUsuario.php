<?php

if (realpath($_SERVER["SCRIPT_FILENAME"] ?? "") === __FILE__) {
    http_response_code(404);
    exit();
}

require_once __DIR__ . "/../bitacora/registrarActividad.php";
require_once __DIR__ . "/../../config/entrada.php";
require_once __DIR__ . "/protegerUltimoAdministrador.php";

if (!empty($_POST["btnModificar"])) {

    $idUsuario = filter_input(
        INPUT_POST,
        "idUsuario",
        FILTER_VALIDATE_INT
    );

    $email = textoEntrada($_POST["email"] ?? null);
    $password = $_POST["password"] ?? "";
    $role = $_POST["role"] ?? "";

    if (
        !$idUsuario ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100 ||
        !in_array($role, ["Administrador", "Cliente"], true)
    ) {

        echo '<div class="alert alert-warning">
                Datos inválidos o campos vacíos.
              </div>';

    } elseif (!is_string($password) || ($password !== "" && (!mb_check_encoding($password, "UTF-8") || mb_strlen($password, "UTF-8") < 8 || strlen($password) > 72 || str_contains($password, chr(0))))) {

        echo '<div class="alert alert-warning">
                Indique una contraseña válida, de al menos 8 caracteres y hasta 72 bytes.
              </div>';

    } else {

        try {
            $conexion->beginTransaction();
            if ($role === "Cliente") {
                protegerUltimoAdministrador($conexion, $idUsuario);
            }

            if ($password !== "") {

                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $sql = $conexion->prepare(
                    "UPDATE users
                     SET email = :email,
                         password = :password,
                         role = :role
                     WHERE userId = :idUsuario"
                );

                $resultado = $sql->execute([
                    ":email" => $email,
                    ":password" => $passwordHash,
                    ":role" => $role,
                    ":idUsuario" => $idUsuario
                ]);

            } else {

                $sql = $conexion->prepare(
                    "UPDATE users
                     SET email = :email,
                         role = :role
                     WHERE userId = :idUsuario"
                );

                $resultado = $sql->execute([
                    ":email" => $email,
                    ":role" => $role,
                    ":idUsuario" => $idUsuario
                ]);
            }

            $cambio = $resultado && $sql->rowCount() > 0;
            if ($cambio) {
                registrarActividad($conexion, "Usuarios", "Modificación", $idUsuario, "Usuario modificado.");
            } else {
                $consulta = $conexion->prepare("SELECT userId FROM users WHERE userId = ? FOR UPDATE");
                $consulta->execute([$idUsuario]);
                $existe = (bool) $consulta->fetchColumn();
            }
            $conexion->commit();

            if ($cambio) {

                echo '<div class="alert alert-success">
                        Usuario modificado correctamente.
                      </div>';

            } else {

                echo $existe
                    ? '<div class="alert alert-info">No se realizaron cambios en el usuario.</div>'
                    : '<div class="alert alert-warning">El usuario ya no existe. Vuelva al listado.</div>';
            }

        } catch (DomainException $e) {
            $conexion->rollBack();
            echo '<div class="alert alert-warning">' . htmlspecialchars($e->getMessage()) . '</div>';
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                echo '<div class="alert alert-warning">El correo ya está registrado. Utilice otro.</div>';
            } else {
                error_log($e->getMessage());
                echo '<div class="alert alert-danger">No se pudo modificar el usuario. Inténtelo nuevamente.</div>';
            }
        }
    }
}
?>
