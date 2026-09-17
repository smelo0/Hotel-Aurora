<div id="inactivityModal" class="inactivity-modal-overlay" style="display: none;">
    <div class="inactivity-modal-content aurora-glass reveal">
        <h3 class="font-display">¿Sigues ahí?</h3>
        <p>Tu sesión se cerrará por inactividad en breves segundos.</p>

        <!-- Reloj circular de cuenta regresiva -->
        <div class="aurora-clock-wrap">
            <svg class="aurora-clock-ring" viewBox="0 0 120 120">
                <defs>
                    <linearGradient id="clockGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#e7c98a" />
                        <stop offset="100%" stop-color="#b98d45" />
                    </linearGradient>
                </defs>
                <circle class="aurora-clock-ring__track" cx="60" cy="60" r="52"></circle>
                <circle id="clockProgress" class="aurora-clock-ring__progress" cx="60" cy="60" r="52"></circle>
            </svg>
            <span id="countdownSecs" class="aurora-clock-ring__label">60</span>
        </div>

        <button type="button" class="aurora-btn aurora-btn-primary" id="btnSeguirSesion" onclick="mostrarCampoClave()">Seguir en sesión</button>

        <div id="verificacionClave" style="display:none; margin-top: 15px;">
            <input type="password" id="claveDesbloqueo" placeholder="Confirma tu contraseña" class="aurora-input">
            <p id="errorClave" style="display:none; color:#f87171; font-size:13px; margin-top:6px;"></p>
            <button type="button" class="aurora-btn aurora-btn-primary" style="margin-top:12px; width:100%;" onclick="verificarClaveDesbloqueo()">Desbloquear</button>
        </div>
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
    z-index: 999999;
}

/* Tarjeta autocontenida: no depende de .glass-card del sitio principal */
.aurora-glass {
    background: rgba(23, 53, 79, 0.92);
    border: 1px solid rgba(255, 255, 255, 0.12);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
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
    color: #e7c98a;
}

.inactivity-modal-content p {
    color: #cbd5e1;
    font-size: 1.05rem;
    margin: 0 0 20px 0;
}

.aurora-clock-wrap {
    position: relative;
    width: 120px;
    height: 120px;
    margin: 0 auto 25px;
}

.aurora-clock-ring {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.aurora-clock-ring__track {
    fill: none;
    stroke: rgba(255, 255, 255, 0.14);
    stroke-width: 8;
}

.aurora-clock-ring__progress {
    fill: none;
    stroke: url(#clockGradient);
    stroke-width: 8;
    stroke-linecap: round;
    stroke-dasharray: 326.7;
    stroke-dashoffset: 0;
    transition: stroke-dashoffset 1s linear;
}

.aurora-clock-ring__label {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 2rem;
    font-weight: 700;
    color: #ffffff;
}

/* Botón autocontenido: no depende de .hero-button/.primary-button del sitio principal */
.aurora-btn {
    border: none;
    padding: 12px 28px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    border-radius: 12px;
    transition: transform 0.15s ease, opacity 0.15s ease;
}

.aurora-btn:hover {
    transform: translateY(-1px);
    opacity: 0.92;
}

.aurora-btn-primary {
    background: linear-gradient(135deg, #1c7a4a, #0d4a2b);
    color: #ffffff;
}

.aurora-input {
    width: 100%;
    border-radius: 12px;
    border: 1px solid #cbd5e1;
    padding: 12px;
    font-size: 0.9rem;
    box-sizing: border-box;
}
</style>