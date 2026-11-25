<?php
require_once __DIR__ . "/sesion.php";

iniciarSesionInventech();
require_once __DIR__ . "/../config/db.php";

$mensaje = "";
$email = "";
try {
    $rol = rolSesionActual($conexion);
    if ($rol !== null) {
        header("Location: " . destinoAutenticado($rol), true, 303);
        exit();
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        exigirCsrfAutenticacion("registro");
        $email = is_string($_POST["email"] ?? null) ? trim($_POST["email"]) : "";
        $password = is_string($_POST["password"] ?? null) ? $_POST["password"] : "";

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
            $mensaje = "Indique un correo válido de hasta 100 caracteres.";
        } elseif (!mb_check_encoding($password, "UTF-8") || mb_strlen($password, "UTF-8") < 8) {
            $mensaje = "La contraseña debe tener al menos 8 caracteres.";
        } elseif (strlen($password) > 72 || str_contains($password, chr(0))) {
            $mensaje = "Utilice una contraseña de hasta 72 bytes y sin caracteres nulos.";
        } else {
            $insertar = $conexion->prepare("
                INSERT INTO users (email, password, role, loginStatus)
                VALUES (?, ?, 'Cliente', 'Habilitado')
            ");
            try {
                $insertar->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
                header("Location: " . BASE_URL . "auth/login.php?registro=exitoso", true, 303);
                exit();
            } catch (PDOException $e) {
                if ((int) ($e->errorInfo[1] ?? 0) !== 1062) {
                    throw $e;
                }
                $mensaje = "Este correo ya está registrado.";
            }
        }
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(503);
    $mensaje = "El servicio no está disponible. Inténtelo más tarde.";
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <link rel="icon" href="../favicon.ico" type="image/x-icon">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registro | InvenTech Solutions</title>


    <script src="https://kit.fontawesome.com/4b21b27482.js"
        crossorigin="anonymous"></script>


    <link rel="stylesheet" href="../css/styles.css">

</head>

<body>

    <div class="contenedor">


        <div class="login-logo">

            <i class="fa-solid fa-cube"></i>

        </div>


        <h1>InvenTech Solutions</h1>

        <h2>Crear cuenta</h2>


        <?php if ($mensaje !== "") { ?>

            <p class="mensaje">

                <?= htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") ?>

            </p>

        <?php } ?>


        <form method="POST">
            <input type="hidden" name="csrf_auth" value="<?= tokenAutenticacion("registro") ?>">


            <div class="campo-formulario">

                <label for="email">
                    Correo electrónico
                </label>

                <input type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($email) ?>"
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
                        autocomplete="new-password"
                        minlength="8"
                        required>

                    <button type="button"
                        class="btn-password"
                        onclick="mostrarPassword()"
                        aria-label="Mostrar contraseña">

                        <span id="iconPassword" aria-hidden="true"><i class="fa-solid fa-eye"></i></span>
                        <span id="iconPasswordOculto" aria-hidden="true" hidden><i class="fa-solid fa-eye-slash"></i></span>

                    </button>

                </div>

            </div>


            <button type="submit">

                Registrarse

                <i class="fa-solid fa-arrow-right"></i>

            </button>

        </form>


        <div class="login-footer">

            <p>

                ¿Ya tienes cuenta?
                <a href="login.php">Iniciar sesión</a>

            </p>


            <a href="../index.php" class="volver-tienda">

                <i class="fa-solid fa-arrow-left"></i>

                Volver a la tienda

            </a>

        </div>

    </div>

    <script src="../js/password.js"></script>

</body>

</html>