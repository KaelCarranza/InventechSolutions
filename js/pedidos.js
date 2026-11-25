const formularioPedido = document.getElementById("formPedido");

if (formularioPedido) {
    const lineas = document.getElementById("lineasPedido");
    const agregar = document.getElementById("agregarLinea");
    const modelo = lineas.firstElementChild.cloneNode(true);
    const totalPedido = document.getElementById("totalPedido");
    const maximo = 9999999999;

    function calcularPedido() {
        let total = 0;
        const filas = Array.from(lineas.children);
        const seleccionados = filas.map(fila => fila.querySelector("select").value);
        filas.forEach(function (fila) {
            const producto = fila.querySelector("select");
            const cantidad = Number(fila.querySelector("input").value);
            const precio = Math.round(Number(producto.selectedOptions[0].dataset.precio || 0) * 100);
            const subtotal = Number.isInteger(cantidad) && cantidad > 0 ? precio * cantidad : 0;

            producto.setCustomValidity(seleccionados.filter(id => id && id === producto.value).length > 1
                ? "Seleccione un producto distinto para cada línea."
                : "");
            fila.querySelector(".precio-linea").value = (precio / 100).toFixed(2);
            fila.querySelector(".subtotal-linea").value = subtotal > maximo ? "Fuera de límite" : (subtotal / 100).toFixed(2);
            fila.querySelector(".quitar-linea").hidden = filas.length === 1;
            total += subtotal;
        });
        totalPedido.value = total > maximo ? "Fuera de límite" : (total / 100).toFixed(2);
    }

    agregar.hidden = false;
    agregar.addEventListener("click", function () {
        const fila = modelo.cloneNode(true);
        lineas.appendChild(fila);
        calcularPedido();
        fila.querySelector("select").focus();
    });
    lineas.addEventListener("input", calcularPedido);
    lineas.addEventListener("change", calcularPedido);
    lineas.addEventListener("click", function (evento) {
        const quitar = evento.target.closest(".quitar-linea");
        if (quitar && lineas.children.length > 1) {
            quitar.closest(".linea-pedido").remove();
            calcularPedido();
            agregar.focus();
        }
    });
    calcularPedido();
}
