// public/assets/js/password.js
// ---------------------------------------------------------------------------
// Fase 8b (cambio 1.1) - "Ojito" para ver u ocultar la contraseña.
// Lo usan public/index.php (login) y public/registro.php.
// Cada botón .pw-ojo está dentro de un .pw-wrap junto a un <input>: al hacer
// clic se alterna el tipo del input entre "password" (oculto) y "text" (visible)
// y se cambia el ícono y el texto para lectores de pantalla.
// ---------------------------------------------------------------------------
document.querySelectorAll('.pw-ojo').forEach((boton) => {
    const campo = boton.closest('.pw-wrap').querySelector('input');

    boton.addEventListener('click', () => {
        const mostrar = campo.type === 'password';
        campo.type = mostrar ? 'text' : 'password';
        boton.classList.toggle('visible', mostrar);
        boton.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
        campo.focus(); // el cursor vuelve al campo para seguir escribiendo
    });
});
