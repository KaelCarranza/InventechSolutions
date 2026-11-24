function mostrarPassword() {

    const password = document.getElementById("password");
    const icono = document.getElementById("iconPassword");
    const boton = document.querySelector(".btn-password");

    if (password.type === "password") {

        password.type = "text";

        icono.classList.remove("fa-eye");
        icono.classList.add("fa-eye-slash");

        boton.setAttribute("aria-label", "Ocultar contraseña");

    } else {

        password.type = "password";

        icono.classList.remove("fa-eye-slash");
        icono.classList.add("fa-eye");

        boton.setAttribute("aria-label", "Mostrar contraseña");

    }
}