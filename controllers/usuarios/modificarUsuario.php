<?php

if (!empty($_POST["btnModificar"])) {

    $idUsuario = filter_input(
        INPUT_POST,
        "idUsuario",
        FILTER_VALIDATE_INT
    );

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $role = $_POST["role"] ?? "";

    if (
        !$idUsuario ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        !in_array($role, ["Administrador", "Cliente"], true)
    ) {

        echo '<div class="alert alert-warning">
                Datos inválidos o campos vacíos.
              </div>';

    } elseif ($password !== "" && strlen($password) < 8) {

        echo '<div class="alert alert-warning">
                La contraseña debe tener mínimo 8 caracteres.
              </div>';

    } else {

        try {

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

            if ($resultado) {

                echo '<div class="alert alert-success">
                        Usuario modificado correctamente.
                      </div>';

            } else {

                echo '<div class="alert alert-danger">
                        Error al modificar el usuario.
                      </div>';
            }

        } catch (PDOException $e) {

            echo '<div class="alert alert-danger">
                    No se pudo modificar el usuario.
                  </div>';
        }
    }
}
?>