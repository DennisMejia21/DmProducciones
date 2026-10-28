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
