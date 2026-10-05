<div class="language-switcher">
    <svg class="language-switcher__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <circle cx="12" cy="12" r="9"></circle>
        <path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"></path>
    </svg>
    <label id="language-select-label" for="language-select">Idioma</label>
    <select id="language-select" aria-labelledby="language-select-label" onchange="cambiarIdioma(this.value)">
        <option value="es">Español</option>
        <option value="en">English</option>
        <option value="pt">Português</option>
        <option value="fr">Français</option>
        <option value="it">Italiano</option>
    </select>
</div>

<div id="google_translate_element" style="display:none;"></div>
<script type="text/javascript" src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

<script>
function googleTranslateElementInit() {
    new google.translate.TranslateElement({
        pageLanguage: 'es', 
        includedLanguages: 'en,es,pt,fr,it', 
        autoDisplay: false
    }, 'google_translate_element');
}

// Función auxiliar para borrar la cookie en todas sus variaciones posibles
function borrarCookieGoogtrans() {
    var domain = window.location.hostname;
    var domainParts = domain.split('.');
    
    // Borrar en el path raíz
    document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
    document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=" + domain;
    
    // Borrar para dominios con/sin 'www'
    if (domainParts.length > 2) {
        var rootDomain = domainParts.slice(-2).join('.');
        document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=." + rootDomain;
    }
}

function cambiarIdioma(lang) {
    var privacyDialog = document.querySelector('.privacy-policy-dialog');
    if (privacyDialog && privacyDialog.open) {
        try {
            var activePolicy = privacyDialog.querySelector('[data-policy-document]:not([hidden])');
            sessionStorage.setItem('reopenPolicyDialogMode', activePolicy ? activePolicy.dataset.policyDocument : 'privacy');
        } catch (error) {
            console.error('No se pudo conservar el modal de políticas durante el cambio de idioma.', error);
        }
    }

    // 1. Siempre limpiamos rastros previos
    borrarCookieGoogtrans();

    if (lang !== 'es') {
        var valorCookie = "/es/" + lang;
        var domain = window.location.hostname;
        
        // 2. Escribimos la cookie garantizando disponibilidad inmediata para el script de Google
        document.cookie = "googtrans=" + valorCookie + "; path=/;";
        
        // Si no estamos en localhost, asegurar la cookie en el dominio
        if (domain !== 'localhost' && domain !== '127.0.0.1') {
            document.cookie = "googtrans=" + valorCookie + "; path=/; domain=" + domain;
        }
    }
    
    // 3. Forzar recarga limpia desde el servidor
    window.location.href = window.location.pathname + window.location.search;
}

// Sincronizar el select visual con la cookie actual al cargar
document.addEventListener('DOMContentLoaded', function() {
    var match = document.cookie.match(/(?:^|;\s*)googtrans=([^;]+)/);
    var select = document.getElementById('language-select');
    
    if (select) {
        if (match) {
            var langParts = decodeURIComponent(match[1]).split('/');
            if (langParts.length >= 3 && langParts[2]) {
                select.value = langParts[2];
                return;
            }
        }
        select.value = 'es'; // Valor por defecto si no hay cookie
    }
});
</script>

<style>
body { top: 0px !important; }
.goog-te-banner-frame,
.goog-te-banner-frame.skiptranslate,
iframe.goog-te-banner-frame,
iframe.skiptranslate,
body > .skiptranslate,
.goog-tooltip,
.goog-tooltip:hover { 
    display: none !important; 
}

.goog-text-highlight { 
    background-color: transparent !important; 
    border: none !important; 
    box-shadow: none !important; 
}

/* Estilos ajustados del contenedor */
.language-switcher {
    position: fixed;
    top: 1rem;
    right: 1rem;
    z-index: 80;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    border: 1px solid rgba(226, 232, 240, 0.95);
    font-weight: 600;
    background-color: rgba(255, 255, 255, 0.96);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    color: #2c5e5e;
    border-radius: 0.75rem;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.12);
}

#language-select-label {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

.language-switcher__icon {
    width: 1.1rem;
    height: 1.1rem;
    flex: 0 0 auto;
    color: #2c5e5e;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

#language-select {
    width: auto;
    min-width: 5rem;
    padding: 0.25rem 1.25rem 0.25rem 0.25rem;
    background-color: transparent;
    color: #334155;
    border: none;
    outline: none;
    font-weight: 700;
    font-size: inherit;
    cursor: pointer;
}

#language-select option {
    background-color: #ffffff;
    color: #334155;
}

@media (max-width: 640px) {
    .language-switcher {
        top: 0.65rem;
        right: 0.65rem;
        padding: 0.2rem 0.35rem;
    }
    #language-select {
        min-width: 4.5rem;
    }
}
</style>