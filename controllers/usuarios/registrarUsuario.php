<?php

if (!empty($_POST["btnInsertar"])) {

    if (
        !empty($_POST["email"]) &&
        !empty($_POST["password"]) &&
        !empty($_POST["role"])
    ) {

        $email = trim($_POST["email"]);
        $password = $_POST["password"];
        $role = $_POST["role"];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            echo '<div class="alert alert-warning">
                    Correo electrónico inválido.
                  </div>';

        } elseif (strlen($password) < 8) {

            echo '<div class="alert alert-warning">
                    La contraseña debe tener mínimo 8 caracteres.
                  </div>';

        } elseif (!in_array($role, ["Administrador", "Cliente"], true)) {

            echo '<div class="alert alert-warning">
                    Rol no válido.
                  </div>';

        } else {

            $verificar = $conexion->prepare(
                "SELECT userId FROM users WHERE email = ?"
            );

            $verificar->execute([$email]);

            if ($verificar->fetch()) {

                echo '<div class="alert alert-warning">
                        El correo ya está registrado.
                      </div>';

            } else {

                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $sql = $conexion->prepare(
                    "INSERT INTO users
                    (email, password, role, loginStatus, registerDate)
                    VALUES (?, ?, ?, 'Habilitado', NOW())"
                );

                if ($sql->execute([
                    $email,
                    $passwordHash,
                    $role
                ])) {

                    echo '<div class="alert alert-success">
                            Usuario registrado correctamente.
                          </div>';

                } else {

                    echo '<div class="alert alert-danger">
                            Error al registrar usuario.
                          </div>';

                }
            }
        }

    } else {

        echo '<div class="alert alert-warning">
                Campos vacíos!!
              </div>';

    }
}
?>