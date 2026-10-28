const menu = document.querySelector('.menu-boton');
const navegacion = document.querySelector('#navegacion');
if (menu && navegacion) {
    document.body.classList.add('menu-listo');
    menu.hidden = false;
    const cerrarMenu = () => {
        menu.setAttribute('aria-expanded', 'false');
        navegacion.classList.remove('abierto');
    };
    menu.addEventListener('click', () => {
        const abierto = menu.getAttribute('aria-expanded') === 'true';
        menu.setAttribute('aria-expanded', String(!abierto));
        navegacion.classList.toggle('abierto', !abierto);
    });
    navegacion.querySelectorAll('a').forEach((enlace) => enlace.addEventListener('click', cerrarMenu));
    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape' && menu.getAttribute('aria-expanded') === 'true') {
            cerrarMenu();
            menu.focus();
        }
    });
}

const reserva = document.querySelector('#reserva');
if (reserva) {
    reserva.addEventListener('submit', (event) => {
        const inicio = reserva.elements.hora_inicio;
        const fin = reserva.elements.hora_fin;
        fin.setCustomValidity(fin.value <= inicio.value ? 'La hora de fin debe ser posterior al inicio.' : '');
        const servicio = reserva.querySelector('input[name="servicios[]"]');
        servicio.setCustomValidity(reserva.querySelector('input[name="servicios[]"]:checked') ? '' : 'Selecciona al menos un servicio.');
        if (!reserva.reportValidity()) event.preventDefault();
    });
    reserva.addEventListener('input', () => {
        reserva.elements.hora_fin.setCustomValidity('');
        reserva.querySelector('input[name="servicios[]"]').setCustomValidity('');
    });
}
document.querySelectorAll('form[data-confirmar]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirmar)) event.preventDefault();
    });
});
