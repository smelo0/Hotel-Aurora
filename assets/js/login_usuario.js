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

const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('vista') === 'registro') {
    cambiarPanel('registro');
}
