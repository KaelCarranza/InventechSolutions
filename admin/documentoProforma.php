<?php
require_once __DIR__ . "/../middlewares/protegerAdmin.php";
require_once __DIR__ . "/../controllers/proformas/consultarProforma.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]) ?: 0;
if ($id === 0) {
    responderError(400, "Seleccione una proforma válida desde el listado.");
}
try {
    $proforma = consultarProforma($conexion, $id);
} catch (PDOException $e) {
    error_log($e->getMessage());
    responderError(503, "No se pudo cargar el documento. Inténtelo nuevamente.");
}
if (!$proforma) {
    responderError(404, "La proforma solicitada no existe. Consulte el listado de proformas.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proforma #<?= $id ?> | InvenTech Solutions</title>
    <link rel="stylesheet" href="../css/paleta.css">
    <style>
        body { margin: 0; background: var(--it-background); color: var(--it-text); font: 16px Arial, sans-serif; }
        main { max-width: 820px; margin: 2rem auto; padding: 2rem; background: var(--it-surface); }
        header { border-bottom: 3px solid var(--it-accent); padding-bottom: 1rem; }
        h1 { color: var(--it-primary); margin: 0; }
        .acciones { display: flex; flex-wrap: wrap; align-items: center; gap: 1rem; margin-bottom: 1rem; }
        .acciones button { background: var(--it-accent); color: var(--it-primary); padding: .5rem .75rem; border: 0; border-radius: .35rem; cursor: pointer; }
        .acciones a { color: var(--it-primary); }
        .datos-proforma { display: grid; grid-template-columns: auto 1fr; gap: .4rem 1rem; }
        dt, th { font-weight: 700; }
        dd { margin: 0; overflow-wrap: anywhere; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: .5rem; border-bottom: 1px solid var(--it-border); }
        th:last-child, td:last-child { text-align: right; }
        tfoot tr:last-child { font-weight: 700; }
        @media (max-width: 600px) { main { margin: 0; padding: 1rem; } }
        @media print {
            body, main { background: white; color: black; }
            main { max-width: none; margin: 0; padding: 0; }
            .acciones { display: none; }
            .table-responsive { overflow: visible; }
            thead { display: table-header-group; }
            tfoot { break-inside: avoid; }
            tr { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <main>
        <nav class="acciones" aria-label="Acciones del documento">
            <a href="proformas.php?detalle=<?= $id ?>">Volver al detalle</a>
            <button type="button" onclick="window.print()">Imprimir o guardar PDF</button>
        </nav>
        <header><h1>InvenTech Solutions</h1></header>
        <?php require __DIR__ . "/../includes/detalleProforma.php"; ?>
        <p>Esta cotización no constituye una factura ni un comprobante de pago. Los productos están sujetos a disponibilidad al momento de la compra.</p>
    </main>
</body>
</html>
