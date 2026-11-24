<?php
require_once __DIR__ . "/../config/db.php";

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $mensaje = "Correo electrónico inválido.";

    } elseif (strlen($password) < 8) {

        $mensaje = "La contraseña debe tener al menos 8 caracteres.";

    } else {

        $consulta = $conexion->prepare(
            "SELECT userId FROM users WHERE email = ?"
        );

        $consulta->execute([$email]);

        if ($consulta->fetch()) {

            $mensaje = "Este correo ya está registrado.";

        } else {

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users
                    (email, password, role, loginStatus, registerDate)
                    VALUES (?, ?, 'Cliente', 'Habilitado', NOW())";

            $insertar = $conexion->prepare($sql);

            if ($insertar->execute([$email, $hash])) {

                header("Location: login.php?registro=exitoso");
                exit();

            } else {

                $mensaje = "Error al registrar el usuario.";

            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registro | InvenTech Solutions</title>

    <link rel="stylesheet" href="css/styles.css">

</head>

<body>

    <div class="contenedor">

        <h1>InvenTech Solutions</h1>

        <h2>Crear cuenta</h2>

        <?php if ($mensaje !== "") { ?>

            <p>
                <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
            </p>

        <?php } ?>

        <form method="POST">

            <input type="email"
                name="email"
                placeholder="Correo electrónico"
                autocomplete="email"
                required>

            <input type="password"
                name="password"
                placeholder="Contraseña"
                minlength="8"
                autocomplete="new-password"
                required>

            <button type="submit">
                Registrarse
            </button>

        </form>

        <p>
            ¿Ya tienes cuenta?
            <a href="login.php">Iniciar sesión</a>
        </p>

    </div>

</body>

</html>