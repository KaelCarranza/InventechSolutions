<?php if (!isset($proforma)) { return; } ?>
<h2>Proforma #<?= (int) $proforma["idProforma"] ?></h2>
<dl class="datos-proforma row">
    <dt class="col-sm-3">Solicitante</dt><dd class="col-sm-9"><?= htmlspecialchars($proforma["nombreSolicitante"]) ?></dd>
    <dt class="col-sm-3">Correo</dt><dd class="col-sm-9"><?= htmlspecialchars($proforma["correoSolicitante"]) ?></dd>
    <dt class="col-sm-3">Emisión</dt><dd class="col-sm-9"><?= htmlspecialchars($proforma["fechaEmision"]) ?></dd>
    <dt class="col-sm-3">Vigente hasta</dt><dd class="col-sm-9"><?= htmlspecialchars($proforma["fechaVencimiento"]) ?></dd>
    <dt class="col-sm-3">Estado</dt><dd class="col-sm-9"><?= htmlspecialchars($proforma["estadoActual"]) ?></dd>
</dl>
<div class="table-responsive">
    <table class="table align-middle">
        <thead><tr><th scope="col">Producto</th><th scope="col">Cantidad</th><th scope="col">Precio unitario</th><th scope="col">Subtotal</th></tr></thead>
        <tbody>
            <?php foreach ($proforma["lineas"] as $linea) { ?>
                <tr>
                    <td><?= htmlspecialchars($linea["nombreProducto"]) ?></td>
                    <td><?= (int) $linea["cantidad"] ?></td>
                    <td><?= htmlspecialchars($linea["precioUnitario"]) ?></td>
                    <td><?= htmlspecialchars($linea["subtotal"]) ?></td>
                </tr>
            <?php } ?>
        </tbody>
        <tfoot>
            <tr><th scope="row" colspan="3">Subtotal</th><td><?= htmlspecialchars($proforma["Subtotal"]) ?></td></tr>
            <tr><th scope="row" colspan="3">Impuestos (<?= htmlspecialchars($proforma["tasaImpuesto"]) ?> %)</th><td><?= htmlspecialchars($proforma["Impuestos"]) ?></td></tr>
            <tr><th scope="row" colspan="3">Total</th><td><?= htmlspecialchars($proforma["Total"]) ?></td></tr>
        </tfoot>
    </table>
</div>
