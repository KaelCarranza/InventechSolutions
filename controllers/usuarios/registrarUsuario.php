<?php

if (realpath($_SERVER["SCRIPT_FILENAME"] ?? "") === __FILE__) {
    http_response_code(404);
    exit();
}

require_once __DIR__ . "/../bitacora/registrarActividad.php";
require_once __DIR__ . "/../../config/entrada.php";

if (!empty($_POST["btnInsertar"])) {

    if (
        !empty($_POST["email"]) &&
        !empty($_POST["password"]) &&
        !empty($_POST["role"])
    ) {

        $email = textoEntrada($_POST["email"]);
        $password = $_POST["password"];
        $role = $_POST["role"];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {

            echo '<div class="alert alert-warning">
                    Indique un correo válido de hasta 100 caracteres.
                  </div>';

        } elseif (!is_string($password) || !mb_check_encoding($password, "UTF-8") || mb_strlen($password, "UTF-8") < 8 || strlen($password) > 72 || str_contains($password, chr(0))) {

            echo '<div class="alert alert-warning">
                    Indique una contraseña válida, de al menos 8 caracteres y hasta 72 bytes.
                  </div>';

        } elseif (!in_array($role, ["Administrador", "Cliente"], true)) {

            echo '<div class="alert alert-warning">
                    Rol no válido.
                  </div>';

        } else {

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            try {
                $conexion->beginTransaction();
                $sql = $conexion->prepare(
                    "INSERT INTO users
                    (email, password, role, loginStatus, registerDate)
                    VALUES (?, ?, ?, 'Habilitado', NOW())"
                );
                $sql->execute([$email, $passwordHash, $role]);
                registrarActividad($conexion, "Usuarios", "Registro", (int) $conexion->lastInsertId(), "Usuario registrado.");
                $conexion->commit();

                echo '<div class="alert alert-success">
                        Usuario registrado correctamente.
                      </div>';
            } catch (PDOException $e) {
                if ($conexion->inTransaction()) {
                    $conexion->rollBack();
                }
                if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                    echo '<div class="alert alert-warning">El correo ya está registrado. Utilice otro.</div>';
                } else {
                    error_log($e->getMessage());
                    echo '<div class="alert alert-danger">No se pudo registrar al usuario. Inténtelo nuevamente.</div>';
                }
            }
        }

    } else {

        echo '<div class="alert alert-warning">
                Complete el correo, la contraseña y el rol.
              </div>';

    }
}
?>