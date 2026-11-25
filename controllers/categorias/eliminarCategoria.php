<?php

if (realpath($_SERVER["SCRIPT_FILENAME"] ?? "") === __FILE__) {
    http_response_code(404);
    exit();
}

require_once __DIR__ . "/../bitacora/registrarActividad.php";

if (!empty($_POST["btnEliminar"])) {
    $idCategoria = filter_input(INPUT_POST, "idCategoria", FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1, "max_range" => 4294967295]]);
    if ($idCategoria) {
        try {
            $conexion->beginTransaction();
            $sql = $conexion->prepare("DELETE FROM categorias WHERE idCategoria = :idCategoria");
            $sql->execute(["idCategoria" => $idCategoria]);
            $eliminada = $sql->rowCount() > 0;
            if ($eliminada) {
                registrarActividad($conexion, "Categorías", "Eliminación", (int) $idCategoria, "Categoría eliminada.");
            }
            $conexion->commit();

            echo $eliminada
                ? '<div class="alert alert-success">Categoría eliminada. Los productos vinculados quedaron sin categoría.</div>'
                : '<div class="alert alert-warning">La categoría ya no existe. Vuelva al listado.</div>';
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log($e->getMessage());
            echo "<div class='alert alert-danger'>No se pudo eliminar la categoría. Inténtelo nuevamente.</div>";
        }
    } else {
        echo "<div class='alert alert-warning'>Seleccione una categoría válida en el listado.</div>";
    }
}
