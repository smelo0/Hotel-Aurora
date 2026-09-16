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

        <button type="button" class="btn-keep-alive hero-button primary-button" id="btnSeguirSesion" onclick="mostrarCampoClave()">Seguir en sesión</button>

        <div id="verificacionClave" style="display:none; margin-top: 15px;">
            <input type="password" id="claveDesbloqueo" placeholder="Confirma tu contraseña" class="w-full rounded-xl border-slate-300 p-3 text-sm">
            <p id="errorClave" style="display:none; color:#f87171; font-size:13px; margin-top:6px;"></p>
            <button type="button" class="hero-button primary-button mt-3 w-full" onclick="verificarClaveDesbloqueo()">Desbloquear</button>
        </div>
    </div>
</div>