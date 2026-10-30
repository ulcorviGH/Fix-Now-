// public/assets/js/volver.js
// ---------------------------------------------------------------------------
// Fase 9 (punto 1) - Botón "← Volver" de las páginas legales.
// Si la persona llegó desde otra página del sitio (login o registro), "Volver"
// usa el historial del navegador: así el formulario que estaba completando
// sigue como lo dejó. Si abrió la página directamente (no hay historial),
// el enlace normal la lleva al inicio de sesión.
// ---------------------------------------------------------------------------
document.querySelectorAll('[data-volver]').forEach((enlace) => {
    enlace.addEventListener('click', (evento) => {
        const vinoDeNuestroSitio = document.referrer && new URL(document.referrer).origin === window.location.origin;
        if (vinoDeNuestroSitio && window.history.length > 1) {
            evento.preventDefault();
            window.history.back();
        }
    });
});
