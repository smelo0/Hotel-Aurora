(function() {
    let idleTimer;
    let countdownTimer;
    
    // Tiempos ajustados al sprint: 2 min de inactividad + 3 min de cuenta regresiva = 5 min total
    const IDLE_TIME_LIMIT = 120; // 2 minutos (Soft Lock)
    const COUNTDOWN_LIMIT = 60; // 3 minutos de reloj circular (Hard Lock)
    let secondsRemaining = COUNTDOWN_LIMIT;

    const modal = document.getElementById('inactivityModal');
    const countdownEl = document.getElementById('countdownSecs');
    const clockProgress = document.getElementById('clockProgress');
    const CIRCUMFERENCE = 326.7;

    // Inicia o reinicia el conteo principal de inactividad
    function startIdleTimer() {
        clearTimeout(idleTimer);
        idleTimer = setTimeout(showInactivityModal, IDLE_TIME_LIMIT * 1000);
    }

    // Muestra el modal e inicia la cuenta regresiva final
    function showInactivityModal() {
        secondsRemaining = COUNTDOWN_LIMIT;
        countdownEl.textContent = secondsRemaining;

        // Reset del reloj (círculo completo)
        clockProgress.style.transition = 'none';
        clockProgress.style.strokeDashoffset = '0';

        modal.style.display = 'flex';

        setTimeout(() => {
            clockProgress.style.transition = 'stroke-dashoffset 1s linear';
            updateProgressBar();
        }, 50);

        countdownTimer = setInterval(() => {
            secondsRemaining--;
            countdownEl.textContent = secondsRemaining;
            updateProgressBar();

            if (secondsRemaining <= 0) {
                clearInterval(countdownTimer);
                window.location.href = '/Hotel-Aurora/controladores/logout.php?panel=user';
            }
        }, 1000);
    }

    // Actualiza la animación circular del reloj
    function updateProgressBar() {
        const fraction = secondsRemaining / COUNTDOWN_LIMIT;
        clockProgress.style.strokeDashoffset = (CIRCUMFERENCE * (1 - fraction)) + '';
    }

    // Función pública para cerrar el modal y reanudar la sesión
    window.resetInactivityTimer = function() {
        modal.style.display = 'none';
        clearInterval(countdownTimer);
        clockProgress.style.strokeDashoffset = '0';
        startIdleTimer();
    };

    // Detectar interacción del usuario para reiniciar el reloj de 2 minutos
    const events = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'];
    events.forEach(eventType => {
        window.addEventListener(eventType, () => {
            if (modal.style.display === 'none' || modal.style.display === '') {
                startIdleTimer();
            }
        });
    });

    startIdleTimer();
})();

window.mostrarCampoClave = function() {
    document.getElementById('btnSeguirSesion').style.display = 'none';
    document.getElementById('verificacionClave').style.display = 'block';
};

window.verificarClaveDesbloqueo = async function() {
    const clave = document.getElementById('claveDesbloqueo').value;
    const errorEl = document.getElementById('errorClave');

    if (!clave) {
        errorEl.textContent = 'Ingresa tu contraseña.';
        errorEl.style.display = 'block';
        return;
    }

    try {
        const response = await fetch('controladores/verificar_clave.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'clave=' + encodeURIComponent(clave)
        });
        const data = await response.json();

        if (data.valido) {
            document.getElementById('claveDesbloqueo').value = '';
            errorEl.style.display = 'none';
            document.getElementById('btnSeguirSesion').style.display = 'block';
            document.getElementById('verificacionClave').style.display = 'none';
            window.resetInactivityTimer(); // ya existe en tu código
        } else {
            errorEl.textContent = 'Contraseña incorrecta.';
            errorEl.style.display = 'block';
        }
    } catch (e) {
        errorEl.textContent = 'Error al verificar. Intenta de nuevo.';
        errorEl.style.display = 'block';
    }
};