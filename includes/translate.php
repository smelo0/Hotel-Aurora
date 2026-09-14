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

<!-- Ya no necesitamos ocultar el elemento de Google porque usaremos cookies -->
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

function cambiarIdioma(lang) {
    if (lang === 'es') {
        // Borramos la cookie de traducción para volver al español original
        document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=" + document.domain;
    } else {
        // Creamos la cookie con el formato que exige Google (/idioma_origen/idioma_destino)
        var valorCookie = "/es/" + lang;
        document.cookie = "googtrans=" + valorCookie + "; path=/;";
        document.cookie = "googtrans=" + valorCookie + "; path=/; domain=" + document.domain;
    }
    // Recargamos para aplicar el cambio de inmediato
    location.reload();
}

// Mantener seleccionado el idioma actual en el desplegable al recargar la página
window.addEventListener('DOMContentLoaded', function() {
    var match = document.cookie.match(new RegExp('(^| )googtrans=([^;]+)'));
    if (match) {
        var langParts = match[2].split('/');
        if (langParts.length >= 3) {
            var currentLang = langParts[2];
            var select = document.getElementById('language-select');
            if (select) {
                select.value = currentLang;
            }
        }
    }
});
</script>
</script>
<style>
body { top: 0px !important; }
    .goog-te-banner-frame { display: none !important; }
    .goog-tooltip { display: none !important; }
    .goog-tooltip:hover { display: none !important; }
    .goog-text-highlight { background-color: transparent !important; border: none !important; box-shadow: none !important; }
    
    /* Estilo básico para el selector */
    .language-switcher {
        margin: 20px 0;
    }


#language-select-label {
    margin: 0 2px;
    color: #fff;
    font-size: rem;
    font-weight: bold;
}

/* Ocultamos el banner que Google agrega arriba de la página por
   defecto, y corregimos el "salto" de 40px que ese banner le
   agrega al body cuando aparece. */
.goog-te-banner-frame,
.goog-te-banner-frame.skiptranslate,
iframe.goog-te-banner-frame,
iframe.skiptranslate,
body > .skiptranslate { display: none !important; }



.language-switcher {
    border: 1px solid ;
    font-weight: 800;
    transition: transform 0.16s ease, box-shadow 0.16s ease, opacity 0.16s ease;
    will-change: transform;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #000;
    border-radius: 18px;
    padding: 0 3px;
}

#language-switcher:hover {
    transform: translate3d(0, -4px, 0);
    background-color: rgba(255, 255, 255, 0.1);
}

#language-switcher {
    background-color: #fff;
}
</style>