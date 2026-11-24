<?php
require_once __DIR__ . "/../config/db.php";

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    // Validar correo electrónico
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $mensaje = "Correo electrónico inválido.";
    } elseif (strlen($password) < 8) {

        $mensaje = "La contraseña debe tener al menos 8 caracteres.";
    } else {

        try {

            // Verificar si el correo ya existe
            $consulta = $conexion->prepare(
                "SELECT userId FROM users WHERE email = ?"
            );

            $consulta->execute([$email]);

            if ($consulta->fetch()) {

                $mensaje = "Este correo ya está registrado.";
            } else {

                // Encriptar contraseña mediante hash
                $hash = password_hash($password, PASSWORD_DEFAULT);

                // Registrar usuario
                $sql = "INSERT INTO users (
                            email,
                            password,
                            role,
                            loginStatus
                        )
                        VALUES (?, ?, ?, ?)";

                $insertar = $conexion->prepare($sql);

                if ($insertar->execute([
                    $email,
                    $hash,
                    "Cliente",
                    "Habilitado"
                ])) {

                    header("Location: login.php?registro=exitoso");
                    exit();
                } else {

                    $mensaje = "Error al registrar el usuario.";
                }
            }
        } catch (PDOException $e) {

            error_log($e->getMessage());

            $mensaje = "Error al registrar el usuario.";
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

        <h2>Crear cuenta</h2>

        <!-- Mensajes -->
        <?php if ($mensaje !== "") { ?>

            <p class="mensaje">

                <?= htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") ?>

            </p>

        <?php } ?>

        <!-- Formulario -->
        <form method="POST">

            <!-- Correo electrónico -->
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

            <!-- Contraseña -->
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

            <!-- Botón -->
            <button type="submit">

                Registrarse

                <i class="fa-solid fa-arrow-right"></i>

            </button>

        </form>

        <!-- Pie del formulario -->
        <div class="login-footer">

            <p>

                ¿Ya tienes cuenta?
                <a href="login.php">Iniciar sesión</a>

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