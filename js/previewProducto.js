function previsualizarImagen(input, idProducto) {

    const preview = document.getElementById(
        "preview" + idProducto
    );

    if (!input.files || !input.files[0] || !preview) {
        return;
    }

    const archivo = input.files[0];

    const formatosPermitidos = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];

    if (!formatosPermitidos.includes(archivo.type)) {

        alert("Formato de imagen no permitido.");

        input.value = "";

        return;
    }

    if (archivo.size > 2 * 1024 * 1024) {

        alert("La imagen no puede superar los 2 MB.");

        input.value = "";

        return;
    }

    const lector = new FileReader();

    lector.onload = function (evento) {

        preview.src = evento.target.result;

        preview.classList.remove("d-none");

    };

    lector.readAsDataURL(archivo);
}