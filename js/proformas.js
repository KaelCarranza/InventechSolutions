const formularioProforma = document.getElementById("formProforma");

if (formularioProforma) {
    const lineas = document.getElementById("lineasProforma");
    const agregar = document.getElementById("agregarLineaProforma");
    const modelo = lineas.firstElementChild.cloneNode(true);
    const subtotalVisible = document.getElementById("subtotalProforma");
    const impuestosVisibles = document.getElementById("impuestosProforma");
    const totalVisible = document.getElementById("totalProforma");
    const tasaImpuesto = formularioProforma.elements.tasaImpuesto;
    const maximo = 9999999999;

    function mostrarImporte(centavos) {
        return centavos > maximo ? "Fuera de límite" : (centavos / 100).toFixed(2);
    }

    function calcularProforma() {
        let subtotal = 0;
        const filas = Array.from(lineas.children);
        const seleccionados = filas.map(fila => fila.querySelector("select").value);

        filas.forEach(function (fila) {
            const producto = fila.querySelector("select");
            const cantidad = Number(fila.querySelector("input").value);
            const precio = Math.round(Number(producto.selectedOptions[0]?.dataset.precio || 0) * 100);
            const importe = Number.isInteger(cantidad) && cantidad > 0 ? precio * cantidad : 0;

            producto.setCustomValidity(seleccionados.filter(id => id && id === producto.value).length > 1
                ? "Seleccione un producto distinto para cada línea."
                : "");
            fila.querySelector(".precio-linea").value = mostrarImporte(precio);
            fila.querySelector(".subtotal-linea").value = mostrarImporte(importe);
            fila.querySelector(".quitar-linea").hidden = filas.length === 1;
            subtotal += importe;
        });

        subtotalVisible.value = mostrarImporte(subtotal);
        if (tasaImpuesto.value === "" || !tasaImpuesto.validity.valid) {
            impuestosVisibles.value = "—";
            totalVisible.value = "—";
            return;
        }
        const tasa = Math.round(Number(tasaImpuesto.value) * 100);
        const impuestos = Math.floor((subtotal * tasa + 5000) / 10000);
        impuestosVisibles.value = subtotal > maximo ? "Fuera de límite" : mostrarImporte(impuestos);
        totalVisible.value = mostrarImporte(subtotal + impuestos);
    }

    agregar.hidden = false;
    agregar.addEventListener("click", function () {
        const fila = modelo.cloneNode(true);
        lineas.appendChild(fila);
        calcularProforma();
        fila.querySelector("select").focus();
    });
    formularioProforma.addEventListener("input", calcularProforma);
    formularioProforma.addEventListener("change", calcularProforma);
    lineas.addEventListener("click", function (evento) {
        const quitar = evento.target.closest(".quitar-linea");
        if (quitar && lineas.children.length > 1) {
            quitar.closest(".linea-proforma").remove();
            calcularProforma();
            agregar.focus();
        }
    });
    calcularProforma();
}
