const sidebarAbrir = document.getElementById("sidebarAbrir");
const sidebarCerrar = document.getElementById("sidebarCerrar");
const sidebarOverlay = document.getElementById("sidebarOverlay");
const vistaMovil = window.matchMedia("(max-width: 768px)");

window.addEventListener("pageshow", function (evento) {
    if (evento.persisted) {
        window.location.reload();
    }
});

document.addEventListener("click", function (evento) {
    const control = evento.target.closest("[data-bs-toggle='modal']");
    if (!control || window.bootstrap || document.getElementById("modalesNoDisponibles")) {
        return;
    }
    const aviso = document.createElement("p");
    aviso.id = "modalesNoDisponibles";
    aviso.className = "alert alert-warning";
    aviso.setAttribute("role", "alert");
    aviso.textContent = "No se pudo abrir el formulario. Compruebe que los recursos de la página estén disponibles, recargue y vuelva a intentarlo.";
    control.insertAdjacentElement("afterend", aviso);
});

function cerrarSidebar() {
    document.body.classList.remove("sidebar-open");
    sidebarAbrir.setAttribute("aria-expanded", "false");

    if (vistaMovil.matches) {
        sidebarAbrir.focus();
    }
}

sidebarAbrir.addEventListener("click", function () {
    document.body.classList.add("sidebar-open");
    sidebarAbrir.setAttribute("aria-expanded", "true");
    sidebarCerrar.focus();
});

sidebarCerrar.addEventListener("click", cerrarSidebar);
sidebarOverlay.addEventListener("click", cerrarSidebar);

document.addEventListener("keydown", function (evento) {
    if (evento.key === "Escape" && document.body.classList.contains("sidebar-open")) {
        cerrarSidebar();
    }
});

vistaMovil.addEventListener("change", function () {
    if (!vistaMovil.matches && document.body.classList.contains("sidebar-open")) {
        cerrarSidebar();
    }
});
