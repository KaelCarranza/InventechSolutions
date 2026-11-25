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

    if (($_GET["registro"] ?? "") === "exitoso") {
        $mensaje = "Registro exitoso. Inicia sesión.";
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        exigirCsrfAutenticacion("login");
        $email = is_string($_POST["email"] ?? null) ? trim($_POST["email"]) : "";
        $password = is_string($_POST["password"] ?? null) ? $_POST["password"] : "";
        $mensaje = "Correo o contraseña incorrectos, o cuenta no disponible.";

        if (filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 100 && $password !== "") {
            $consulta = $conexion->prepare("
                SELECT u.userId, u.password, u.role, u.loginStatus, " . REVISION_SESION_SQL . " AS revisionSesion
                FROM users u WHERE u.email = ?
            ");
            $consulta->execute([$email]);
            $usuario = $consulta->fetch(PDO::FETCH_ASSOC);
            if ($usuario && password_verify($password, $usuario["password"]) &&
                $usuario["loginStatus"] === "Habilitado" &&
                in_array($usuario["role"], ["Cliente", "Administrador"], true)) {
                if (!session_regenerate_id(true)) {
                    http_response_code(503);
                    exit("No se pudo iniciar la sesión. Inténtelo más tarde.");
                }
                $_SESSION = [
                    "userId" => (int) $usuario["userId"],
                    "role" => $usuario["role"],
                    "revisionSesion" => (string) $usuario["revisionSesion"]
                ];
                header("Location: " . destinoAutenticado($usuario["role"]), true, 303);
                exit();
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

    <title>Login | InvenTech Solutions</title>


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

        <h2>Iniciar sesión</h2>


        <?php if ($mensaje !== "") { ?>

            <p class="mensaje">

                <?= htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") ?>

            </p>

        <?php } ?>


        <form method="POST">
            <input type="hidden" name="csrf_auth" value="<?= tokenAutenticacion("login") ?>">

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

                Ingresar

                <i class="fa-solid fa-arrow-right"></i>

            </button>

        </form>


        <div class="login-footer">

            <p>
                ¿No tienes cuenta?
                <a href="registro.php">Registrarse</a>
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
