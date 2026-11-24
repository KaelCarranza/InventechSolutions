<?php
session_start();

// Eliminar datos de autenticación
unset(
    $_SESSION["userId"],
    $_SESSION["role"],
    $_SESSION["redirect_after_login"]
);

// El carrito se mantiene si existe.
// Esto permite continuar como visitante.

session_regenerate_id(true);

header("Location: /inventechSolutions/index.php");
exit();
?>
