<div id="inactivityModal" class="inactivity-modal-overlay" style="display: none;">
    <div class="inactivity-modal-content glass-card reveal">
        <h3 class="font-display">¿Sigues ahí?</h3>
        <p>Tu sesión se cerrará por inactividad en breves segundos.</p>

        <!-- Reloj circular de cuenta regresiva -->
        <div class="clock-wrap">
            <svg class="clock-ring" viewBox="0 0 120 120">
                <defs>
                    <linearGradient id="clockGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#e7c98a" />
                        <stop offset="100%" stop-color="#b98d45" />
                    </linearGradient>
                </defs>
                <circle class="clock-ring__track" cx="60" cy="60" r="52"></circle>
                <circle id="clockProgress" class="clock-ring__progress" cx="60" cy="60" r="52"></circle>
            </svg>
            <span id="countdownSecs" class="clock-ring__label">10</span>
        </div>

        <button type="button" class="btn-keep-alive hero-button primary-button" onclick="resetInactivityTimer()">Seguir en sesión</button>
    </div>
</div>

<style>
.inactivity-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(9, 20, 32, 0.7);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 99999;
}

/* Reusa la estética .glass-card del sitio: vidrio esmerilado + dorado */
.inactivity-modal-content {
    padding: 35px 30px;
    border-radius: 20px;
    text-align: center;
    max-width: 480px;
    width: 90%;
}

.inactivity-modal-content h3 {
    margin-top: 0;
    margin-bottom: 12px;
    font-size: 1.6rem;
    color: var(--gold);
}

.inactivity-modal-content p {
    color: var(--text-secondary);
    font-size: 1.05rem;
    margin: 0 0 20px 0;
}

.inactivity-modal-content strong {
    color: var(--text-primary);
}

/* Reloj circular alineado al lenguaje visual dorado del sitio */
.clock-wrap {
    position: relative;
    width: 120px;
    height: 120px;
    margin: 0 auto 25px;
}

.clock-ring {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg); /* empieza arriba, como un reloj */
}

.clock-ring__track {
    fill: none;
    stroke: rgba(255, 255, 255, 0.14);
    stroke-width: 8;
}

.clock-ring__progress {
    fill: none;
    stroke: url(#clockGradient);
    stroke-width: 8;
    stroke-linecap: round;
    stroke-dasharray: 326.7; /* 2 * PI * r(52) */
    stroke-dashoffset: 0;
    transition: stroke-dashoffset 1s linear;
}

.clock-ring__label {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
    font-family: inherit;
}

.btn-keep-alive {
    border: none;
    padding: 12px 28px;
    font-size: 1rem;
    cursor: pointer;
}
</style>

<script>
(function() {
    let idleTimer;
    let countdownTimer;
    const IDLE_TIME_LIMIT = 60 * 5; // segundos de inactividad
    const COUNTDOWN_LIMIT = 10; // segundos del modal
    let secondsRemaining = COUNTDOWN_LIMIT;

    const modal = document.getElementById('inactivityModal');
    const countdownEl = document.getElementById('countdownSecs');
    const clockProgress = document.getElementById('clockProgress');
    const CIRCUMFERENCE = 326.7; // 2 * PI * r(52), debe coincidir con stroke-dasharray en CSS

    // Inicia o reinicia el conteo principal de inactividad
    function startIdleTimer() {
        clearTimeout(idleTimer);
        idleTimer = setTimeout(showInactivityModal, IDLE_TIME_LIMIT * 1000);
    }

    // Muestra el modal e inicia la cuenta regresiva final
    function showInactivityModal() {
        secondsRemaining = COUNTDOWN_LIMIT;
        countdownEl.textContent = secondsRemaining;

        // Reset del reloj (círculo completo, sin animación)
        clockProgress.style.transition = 'none';
        clockProgress.style.strokeDashoffset = '0';

        modal.style.display = 'flex';

        // Forzar un repaint para reiniciar la transición CSS limpiamente
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
                window.location.href = 
                '<?php session_destroy(); 
                echo "../interfaz_usu.php"; ?>';
            }
        }, 1000);
    }

    // Actualiza el reloj: el anillo se va "vaciando" a medida que pasa el tiempo
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

    // Detectar interacción del usuario
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
</script>