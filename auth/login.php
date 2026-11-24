<?php
session_start();
require_once __DIR__ . "/../config/db.php";

$mensaje = "";

if (isset($_GET["registro"]) && $_GET["registro"] === "exitoso") {
    $mensaje = "Registro exitoso. Inicia sesión.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $sql = "SELECT * FROM users WHERE email = ?";

    $consulta = $conexion->prepare($sql);
    $consulta->execute([$email]);

    $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

    // Verificar correo y contraseña
    if ($usuario && password_verify($password, $usuario["password"])) {

        // Verificar estado de la cuenta
        if ($usuario["loginStatus"] !== "Habilitado") {

            $mensaje = "Su cuenta está deshabilitada. Contacte al administrador.";

        } else {

            // Regenerar ID de sesión
            session_regenerate_id(true);

            // Guardar datos de sesión
            $_SESSION["userId"] = $usuario["userId"];
            $_SESSION["email"] = $usuario["email"];
            $_SESSION["role"] = $usuario["role"];

            // Redireccionar al inicio
            header("Location: /inventechSolutions/index.php");
            exit();

        }

    } else {

        $mensaje = "Correo o contraseña incorrectos.";

    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | InvenTech Solutions</title>

    <link rel="stylesheet" href="assets/css/styles.css">

</head>

<body>

    <div class="contenedor">

        <h1>InvenTech Solutions</h1>

        <h2>Iniciar sesión</h2>

        <?php if ($mensaje !== "") { ?>

            <p class="mensaje">
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
                autocomplete="current-password"
                required>

            <button type="submit">
                Ingresar
            </button>

        </form>

        <p>
            ¿No tienes cuenta?
            <a href="registro.php">Registrarse</a>
        </p>

    </div>

</body>

</html>