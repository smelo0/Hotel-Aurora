function cambiarPanel(panelDestino) {
    const login = document.getElementById('panel-login');
    const registro = document.getElementById('panel-registro');

    if (panelDestino === 'registro') {
        login.style.display = 'none';
        registro.style.display = 'block';
        return;
    }

    registro.style.display = 'none';
    login.style.display = 'block';
}

function inicializarValidacionRegistro() {
    const form = document.querySelector('#panel-registro form');
    if (!form) return;

    const campos = [
        {
            input: document.getElementById('nom_usu'),
            hint: document.getElementById('nom_usu_hint'),
            ayuda: 'Solo se permiten letras y espacios.',
            error: 'Escribe un nombre usando solo letras y espacios.',
            permiteCaracteres: value => /^[\p{L}\p{M} ]*$/u.test(value),
            esValido: value => /^[\p{L}\p{M}]+(?: [\p{L}\p{M}]+)*$/u.test(value.trim()) && value.length <= 140,
            limpiar: value => value.replace(/[^\p{L}\p{M} ]/gu, '').replace(/ {2,}/g, ' ')
        },
        {
            input: document.getElementById('doc_usu'),
            hint: document.getElementById('doc_usu_hint'),
            ayuda: 'Solo se permiten números, sin puntos ni espacios.',
            error: 'Ingresa entre 4 y 20 dígitos, sin letras ni caracteres especiales.',
            permiteCaracteres: value => /^[0-9]*$/.test(value),
            esValido: value => /^[0-9]{4,20}$/.test(value),
            limpiar: value => value.replace(/[^0-9]/g, '')
        },
        {
            input: document.getElementById('tel_usu'),
            hint: document.getElementById('tel_usu_hint'),
            ayuda: 'Solo se permiten números y un + opcional al inicio.',
            error: 'Ingresa entre 7 y 15 dígitos; el + solo se permite al inicio.',
            permiteCaracteres: value => /^\+?[0-9]*$/.test(value),
            esValido: value => /^\+?[0-9]{7,15}$/.test(value),
            limpiar: value => `${value.startsWith('+') ? '+' : ''}${value.replace(/[^0-9]/g, '')}`
        }
    ].filter(campo => campo.input && campo.hint);

    campos.forEach(campo => {
        const actualizarMensaje = mostrarError => {
            const valor = campo.input.value;
            const mensaje = valor !== '' && !campo.esValido(valor)
                ? campo.error
                : mostrarError && campo.input.required && valor === ''
                    ? 'Este campo es obligatorio.'
                    : '';
            campo.input.setCustomValidity(mensaje);
            campo.input.setAttribute('aria-invalid', String(mensaje !== ''));
            campo.hint.textContent = mensaje || campo.ayuda;
            campo.hint.classList.toggle('is-error', mensaje !== '');
        };

        campo.input.addEventListener('beforeinput', event => {
            if (event.isComposing || event.inputType.startsWith('delete')) return;
            const texto = event.data ?? event.dataTransfer?.getData('text/plain');
            if (texto === null || texto === undefined || texto === '') return;
            const inicio = campo.input.selectionStart ?? campo.input.value.length;
            const fin = campo.input.selectionEnd ?? inicio;
            const valorPropuesto = campo.input.value.slice(0, inicio) + texto + campo.input.value.slice(fin);
            if (!campo.permiteCaracteres(valorPropuesto)) event.preventDefault();
        });

        campo.input.addEventListener('paste', event => {
            const texto = event.clipboardData?.getData('text/plain') ?? '';
            const inicio = campo.input.selectionStart ?? campo.input.value.length;
            const fin = campo.input.selectionEnd ?? inicio;
            const valorPropuesto = campo.input.value.slice(0, inicio) + texto + campo.input.value.slice(fin);
            if (!campo.permiteCaracteres(valorPropuesto)) event.preventDefault();
        });

        const limpiarYValidar = event => {
            if (event?.isComposing) return;
            const valorLimpio = campo.limpiar(campo.input.value);
            if (valorLimpio !== campo.input.value) campo.input.value = valorLimpio;
            actualizarMensaje(false);
        };

        campo.input.addEventListener('input', limpiarYValidar);
        campo.input.addEventListener('compositionend', limpiarYValidar);
        campo.input.addEventListener('blur', () => actualizarMensaje(true));
        campo.input.addEventListener('invalid', () => actualizarMensaje(true));
        actualizarMensaje(false);
    });

    form.addEventListener('submit', event => {
        const campoInvalido = campos.find(campo => {
            actualizarMensajeCampo(campo);
            return campo.input.value !== '' && !campo.esValido(campo.input.value);
        });
        if (!campoInvalido) return;
        event.preventDefault();
        campoInvalido.input.focus();
        campoInvalido.hint.textContent = campoInvalido.error;
        campoInvalido.hint.classList.add('is-error');
    });

    function actualizarMensajeCampo(campo) {
        if (campo.input.value === '') {
            campo.input.setCustomValidity('');
            return;
        }
        campo.input.setCustomValidity(campo.esValido(campo.input.value) ? '' : campo.error);
    }
}

const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('vista') === 'registro') {
    cambiarPanel('registro');
}

document.addEventListener('DOMContentLoaded', inicializarValidacionRegistro);
