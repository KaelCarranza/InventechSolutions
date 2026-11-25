function mostrarPassword() {
    const password = document.getElementById("password");
    const mostrar = document.getElementById("iconPassword");
    const ocultar = document.getElementById("iconPasswordOculto");
    const boton = document.querySelector(".btn-password");
    const visible = password.type === "password";

    password.type = visible ? "text" : "password";
    mostrar.hidden = visible;
    ocultar.hidden = !visible;
    boton.setAttribute("aria-label", visible ? "Ocultar contraseña" : "Mostrar contraseña");
}
