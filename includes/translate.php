<div class="language-switcher">
    <label id="language-select-label" for="language-select">Idioma</label>
    <select id="language-select" onchange="cambiarIdioma(this.value)">
        <option value="es">Español</option>
        <option value="en">English</option>
        <option value="pt">Português</option>
        <option value="fr">Français</option>
        <option value="it">Italiano</option>
    </select>
</div>

<div id="google_translate_element" style="display:none;"></div>
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

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
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: 1px solid rgba(255, 255, 255, 0.25);
    font-weight: 600;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    will-change: transform;
    background-color: rgba(8, 59, 38, 0.85);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    color: #ffffff;
    border-radius: 99px;
    padding: 6px 16px;
    font-size: 0.875rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
}

#language-select-label {
    color: #ffffff;
    font-weight: bold;
}

#language-select {
    background: transparent;
    color: #ffffff;
    border: none;
    outline: none;
    font-weight: inherit;
    font-size: inherit;
    cursor: pointer;
}

#language-select option {
    background-color: #083b26;
    color: #ffffff;
}
</style>