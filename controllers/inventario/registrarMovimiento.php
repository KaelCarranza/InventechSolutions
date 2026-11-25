<?php

require_once __DIR__ . "/../bitacora/registrarActividad.php";

require_once __DIR__ . "/../../middlewares/protegerAdmin.php";
require_once __DIR__ . "/../../middlewares/protegerCsrfAdmin.php";
require_once __DIR__ . "/movimientos.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $idProducto = filter_input(INPUT_POST, "idProducto", FILTER_VALIDATE_INT);
    $cantidad = filter_input(INPUT_POST, "cantidad", FILTER_VALIDATE_INT);
    $tipo = $_POST["tipo"] ?? "";
    $motivo = $_POST["motivo"] ?? "";
    $claveMovimiento = $_POST["claveMovimiento"] ?? "";
    $resultadoMovimiento = ["danger", "No se pudo registrar el movimiento."];

    try {
        if (
            !$idProducto || !$cantidad || !is_string($tipo) ||
            !is_string($motivo) || !is_string($claveMovimiento)
        ) {
            throw new InvalidArgumentException("Revise el producto, el tipo, la cantidad y el motivo del movimiento.");
        }

        $conexion->beginTransaction();
        $idMovimiento = aplicarMovimientoInventario(
            $conexion, $idProducto, (int) $_SESSION["userId"], $tipo, $cantidad, $motivo, $claveMovimiento
        );
        registrarActividad($conexion, "Inventario", $tipo, $idMovimiento, "$tipo manual de existencias.");
        $conexion->commit();
        $resultadoMovimiento = ["success", "Movimiento registrado correctamente."];
    } catch (InvalidArgumentException $e) {
        $resultadoMovimiento = ["warning", $e->getMessage()];
    } catch (PDOException $e) {
        if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
            $resultadoMovimiento = ["warning", "Este movimiento ya fue registrado."];
        } else {
            error_log($e->getMessage());
        }
    } finally {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
    }

    $_SESSION["resultadoMovimiento"] = $resultadoMovimiento;
    header("Location: " . BASE_URL . "admin/inventario.php", true, 303);
    exit();
}
