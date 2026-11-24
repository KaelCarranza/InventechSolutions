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

    if ($usuario && password_verify($password, $usuario["password"])) {

        // Verificar si la cuenta está habilitada
        if ($usuario["loginStatus"] !== "Habilitado") {

            $mensaje = "Su cuenta está deshabilitada. Contacte al administrador.";
        } else {

            // Regenerar sesión
            session_regenerate_id(true);

            // Guardar datos del usuario
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

    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/4b21b27482.js"
        crossorigin="anonymous"></script>

    <!-- CSS -->
    <link rel="stylesheet" href="../css/styles.css">

</head>

<body>

    <div class="contenedor">

        <!-- Logo -->
        <div class="login-logo">

            <i class="fa-solid fa-cube"></i>

        </div>

        <!-- Marca -->
        <h1>InvenTech Solutions</h1>

        <h2>Iniciar sesión</h2>

        <!-- Mensajes -->
        <?php if ($mensaje !== "") { ?>

            <p class="mensaje">

                <?= htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") ?>

            </p>

        <?php } ?>

        <!-- Formulario -->
        <form method="POST">

            <div class="campo-formulario">

                <label for="email">
                    Correo electrónico
                </label>

                <input type="email"
                    id="email"
                    name="email"
                    placeholder="Correo electrónico"
                    autocomplete="email"
                    required>

            </div>

            <div class="campo-formulario">

                <label for="password">Contraseña</label>

                <div class="password-contenedor">

                    <input type="password"
                        id="password"
                        name="password"
                        placeholder="Contraseña"
                        required>

                    <button type="button"
                        class="btn-password"
                        onclick="mostrarPassword()"
                        aria-label="Mostrar contraseña">

                        <i class="fa-solid fa-eye" id="iconPassword"></i>

                    </button>

                </div>

            </div>

            <button type="submit">

                Ingresar

                <i class="fa-solid fa-arrow-right"></i>

            </button>

        </form>

        <!-- Registro -->
        <div class="login-footer">

            <p>
                ¿No tienes cuenta?
                <a href="registro.php">Registrarse</a>
            </p>

            <!-- Volver a la tienda -->
            <a href="../index.php" class="volver-tienda">

                <i class="fa-solid fa-arrow-left"></i>

                Volver a la tienda

            </a>

        </div>

    </div>

    <script src="../js/password.js"></script>

</body>

</html>