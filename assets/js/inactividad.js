(function() {
    const IDLE_LIMIT_MS = 150000;
    const COUNTDOWN_LIMIT_SECONDS = 60;
    const ACTIVITY_REPORT_INTERVAL_MS = 10000;
    const modal = document.getElementById('inactivityModal');

    if (!modal) {
        return;
    }

    const csrfToken = modal.dataset.csrf;
    const countdownEl = document.getElementById('countdownSecs');
    const clockProgress = document.getElementById('clockProgress');
    const errorEl = document.getElementById('errorClave');
    const CIRCUMFERENCE = 326.7;
    let idleTimer;
    let countdownTimer;
    let lockStateTimer;
    let countdownEndsAt = 0;
    let pausedUntil = 0;
    let pausedSecondsRemaining = 0;
    let lastActivityReport = 0;
    let lockRequestPending = false;
    let unlockRequestPending = false;
    let pauseRequested = false;

    function redirectToPublicInterface() {
        window.location.href = '/Hotel-Aurora/interfaz_usu.php';
    }

    async function postSessionAction(url, values) {
        const body = new URLSearchParams(values);
        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body.toString()
        });

        const data = await response.json();
        if (response.status === 440 || data.status === 'expired') {
            redirectToPublicInterface();
            return null;
        }

        return { response: response, data: data };
    }

    function secondsUntilDeadline() {
        return Math.max(0, Math.ceil((countdownEndsAt - Date.now()) / 1000));
    }

    function updateProgressBar(secondsRemaining) {
        const fraction = Math.max(0, secondsRemaining / COUNTDOWN_LIMIT_SECONDS);
        clockProgress.style.strokeDashoffset = (CIRCUMFERENCE * (1 - fraction)) + '';
    }

    function finishCountdown() {
        clearInterval(countdownTimer);
        window.location.href = '/Hotel-Aurora/controladores/logout.php?motivo=inactividad';
    }

    function renderCountdown() {
        const now = Date.now();
        const secondsRemaining = now < pausedUntil
            ? pausedSecondsRemaining
            : secondsUntilDeadline();

        countdownEl.textContent = secondsRemaining;
        updateProgressBar(secondsRemaining);

        if (secondsRemaining <= 0 && now >= pausedUntil) {
            finishCountdown();
        }
    }

    function showInactivityModal(data) {
        clearTimeout(idleTimer);
        if (modal.style.display === 'flex') {
            return;
        }

        const secondsRemaining = Number(data.segundos_restantes ?? COUNTDOWN_LIMIT_SECONDS);
        countdownEndsAt = Date.now() + Math.max(0, secondsRemaining) * 1000;
        pausedUntil = 0;
        pausedSecondsRemaining = 0;
        pauseRequested = false;
        modal.style.display = 'flex';
        clearInterval(lockStateTimer);
        lockStateTimer = window.setInterval(checkSessionLockState, 1500);
        document.getElementById('btnSeguirSesion').style.display = 'block';
        document.getElementById('verificacionClave').style.display = 'none';
        clockProgress.style.transition = 'none';
        clockProgress.style.strokeDashoffset = '0';
        renderCountdown();

        window.setTimeout(() => {
            clockProgress.style.transition = 'stroke-dashoffset 1s linear';
        }, 50);
        clearInterval(countdownTimer);
        countdownTimer = window.setInterval(renderCountdown, 250);
    }

    async function checkSessionLockState() {
        try {
            const result = await postSessionAction('/Hotel-Aurora/controladores/actividad_sesion.php', {
                accion: 'estado'
            });
            if (!result) {
                return;
            }

            if (result.data.status === 'active' && modal.style.display === 'flex') {
                clearInterval(lockStateTimer);
                clearInterval(countdownTimer);
                modal.style.display = 'none';
                pausedUntil = 0;
                pauseRequested = false;
                lastActivityReport = 0;
                startIdleTimer();
            } else if (result.data.bloqueada && modal.style.display !== 'flex') {
                showInactivityModal(result.data);
            }
        } catch (error) {
            // El contador local sigue siendo la referencia visual mientras la red no responde.
        }
    }

    async function registerLock() {
        if (lockRequestPending || modal.style.display === 'flex') {
            return;
        }

        lockRequestPending = true;
        try {
            const result = await postSessionAction('/Hotel-Aurora/controladores/actividad_sesion.php', {
                accion: 'bloquear'
            });
            if (!result) {
                return;
            }

            if (result.data.bloqueada || result.response.status === 423) {
                showInactivityModal(result.data);
            } else if (result.response.status === 409) {
                idleTimer = window.setTimeout(registerLock, Number(result.data.reintentar_en || 1) * 1000);
            }
        } catch (error) {
            idleTimer = window.setTimeout(registerLock, 3000);
        } finally {
            lockRequestPending = false;
        }
    }

    function startIdleTimer() {
        clearTimeout(idleTimer);
        idleTimer = window.setTimeout(registerLock, IDLE_LIMIT_MS);
    }

    async function reportActivity() {
        const now = Date.now();
        if (now - lastActivityReport < ACTIVITY_REPORT_INTERVAL_MS) {
            return;
        }

        lastActivityReport = now;
        try {
            const result = await postSessionAction('/Hotel-Aurora/controladores/actividad_sesion.php', {
                accion: 'actividad'
            });
            if (result && result.data.bloqueada) {
                showInactivityModal(result.data);
            }
        } catch (error) {
            // El temporizador local conserva el bloqueo aunque falle el reporte de actividad.
        }
    }

    async function requestUnlockPause() {
        if (pauseRequested) {
            return;
        }

        pauseRequested = true;
        try {
            const result = await postSessionAction('/Hotel-Aurora/controladores/verificar_clave.php', {
                accion: 'pausar'
            });
            if (!result) {
                return;
            }

            if (result.data.status === 'paused') {
                pausedSecondsRemaining = secondsUntilDeadline();
                pausedUntil = Date.now() + Number(result.data.segundos_pausa) * 1000;
                countdownEndsAt += Number(result.data.segundos_pausa) * 1000;
            } else {
                pauseRequested = false;
                errorEl.textContent = 'No se pudo pausar el contador. Ingresa tu contraseña antes de que termine.';
                errorEl.style.display = 'block';
            }
        } catch (error) {
            pauseRequested = false;
            errorEl.textContent = 'No se pudo validar la solicitud. El contador continuará.';
            errorEl.style.display = 'block';
        }
    }

    window.mostrarCampoClave = function() {
        document.getElementById('btnSeguirSesion').style.display = 'none';
        document.getElementById('verificacionClave').style.display = 'block';
        document.getElementById('claveDesbloqueo').focus();
        requestUnlockPause();
    };

    window.verificarClaveDesbloqueo = async function() {
        const claveInput = document.getElementById('claveDesbloqueo');
        const clave = claveInput.value;
        if (unlockRequestPending) {
            return;
        }

        if (!clave) {
            errorEl.textContent = 'Ingresa tu contraseña.';
            errorEl.style.display = 'block';
            return;
        }

        unlockRequestPending = true;
        try {
            const result = await postSessionAction('/Hotel-Aurora/controladores/verificar_clave.php', {
                clave: clave
            });
            if (!result) {
                return;
            }

            if (result.data.valido) {
                claveInput.value = '';
                errorEl.style.display = 'none';
                modal.style.display = 'none';
                clearInterval(countdownTimer);
                clearInterval(lockStateTimer);
                pausedUntil = 0;
                pauseRequested = false;
                lastActivityReport = 0;
                startIdleTimer();
            } else if (result.data.intentos_agotados) {
                errorEl.textContent = 'Se agotaron los intentos. La sesión se cerrará al terminar el contador.';
                errorEl.style.display = 'block';
            } else {
                errorEl.textContent = 'Contraseña incorrecta.';
                errorEl.style.display = 'block';
            }
        } catch (error) {
            errorEl.textContent = 'Error al verificar. Intenta de nuevo.';
            errorEl.style.display = 'block';
        } finally {
            unlockRequestPending = false;
        }
    };

    const activityEvents = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'];
    activityEvents.forEach((eventType) => {
        window.addEventListener(eventType, () => {
            if (modal.style.display !== 'flex') {
                startIdleTimer();
                reportActivity();
            }
        }, { passive: true });
    });

    startIdleTimer();
})();
