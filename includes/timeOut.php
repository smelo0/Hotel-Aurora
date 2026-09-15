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
