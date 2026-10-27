function googleTranslateElementInit() {
    if (typeof google === 'undefined' || !google.translate) return;
    new google.translate.TranslateElement({
        pageLanguage: 'es',
        includedLanguages: 'en,es,pt,fr,it',
        autoDisplay: false
    }, 'google_translate_element');

    sincronizarComboGoogleTranslate();
}

function obtenerIdiomaActivo() {
    const match = document.cookie.match(/(?:^|;\s*)googtrans=([^;]+)/);
    if (match) {
        const langParts = decodeURIComponent(match[1]).split('/');
        if (langParts.length >= 3 && langParts[2]) {
            return langParts[2];
        }
    }
    try {
        const stored = localStorage.getItem('aurora_preferred_lang');
        if (stored && ['es', 'en', 'pt', 'fr', 'it'].includes(stored)) {
            return stored;
        }
    } catch (_) {}
    return 'es';
}

function borrarCookieGoogtrans() {
    const domain = window.location.hostname;
    const domainParts = domain.split('.');

    document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
    document.cookie = `googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=${domain}`;

    if (domainParts.length > 2) {
        const rootDomain = domainParts.slice(-2).join('.');
        document.cookie = `googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=.${rootDomain}`;
    }
}

function fijarCookieGoogtrans(lang) {
    borrarCookieGoogtrans();
    try {
        localStorage.setItem('aurora_preferred_lang', lang);
    } catch (_) {}

    if (lang !== 'es') {
        const valorCookie = `/es/${lang}`;
        const domain = window.location.hostname;
        document.cookie = `googtrans=${valorCookie}; path=/; SameSite=Lax`;
        if (domain !== 'localhost' && domain !== '127.0.0.1') {
            document.cookie = `googtrans=${valorCookie}; path=/; domain=${domain}; SameSite=Lax`;
        }
    }
}

function dispararCambioEnComboGoogle(lang) {
    const combo = document.querySelector('.goog-te-combo');
    if (!combo) return false;
    combo.value = lang === 'es' ? '' : lang;
    combo.dispatchEvent(new Event('change', { bubbles: true }));
    return true;
}

function sincronizarComboGoogleTranslate() {
    const lang = obtenerIdiomaActivo();
    const selector = document.getElementById('language-select');
    if (selector && selector.value !== lang) {
        selector.value = lang;
    }
    if (lang !== 'es') {
        fijarCookieGoogtrans(lang);
        let intentos = 0;
        const timer = window.setInterval(() => {
            intentos++;
            const combo = document.querySelector('.goog-te-combo');
            if (combo) {
                if (combo.value !== lang) {
                    combo.value = lang;
                    combo.dispatchEvent(new Event('change', { bubbles: true }));
                }
                window.clearInterval(timer);
            } else if (intentos >= 20) {
                window.clearInterval(timer);
            }
        }, 150);
    }
}

function cambiarIdioma(lang) {
    const privacyDialog = document.querySelector('.privacy-policy-dialog');
    if (privacyDialog && privacyDialog.open) {
        try {
            const activePolicy = privacyDialog.querySelector('[data-policy-document]:not([hidden])');
            sessionStorage.setItem(
                'reopenPolicyDialogMode',
                activePolicy ? activePolicy.dataset.policyDocument : 'privacy'
            );
        } catch (error) {
            console.error('No se pudo conservar el modal de políticas durante el cambio de idioma.', error);
        }
    }

    fijarCookieGoogtrans(lang);

    if (lang !== 'es' && dispararCambioEnComboGoogle(lang)) {
        return;
    }

    window.location.href = window.location.pathname + window.location.search + window.location.hash;
}

const protegerIconosDeTraduccion = (root = document) => {
    try {
        const elementos = root.querySelectorAll?.(
            '.material-symbols-outlined, [class*="material-symbols"], .language-switcher, #language-select, #language-select option'
        );
        if (elementos) {
            elementos.forEach((el) => {
                if (!el.classList.contains('notranslate')) {
                    el.classList.add('notranslate');
                }
                if (el.getAttribute('translate') !== 'no') {
                    el.setAttribute('translate', 'no');
                }
            });
        }
    } catch (_) {}
};

document.addEventListener('DOMContentLoaded', () => {
    protegerIconosDeTraduccion();

    if ('MutationObserver' in window && document.body) {
        const observer = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                for (const node of mutation.addedNodes) {
                    if (node.nodeType === 1) {
                        if (node.matches?.('.material-symbols-outlined, [class*="material-symbols"], .language-switcher')) {
                            node.classList.add('notranslate');
                            node.setAttribute('translate', 'no');
                        }
                        protegerIconosDeTraduccion(node);
                    }
                }
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    const selector = document.getElementById('language-select');
    if (selector) {
        selector.value = obtenerIdiomaActivo();
        selector.addEventListener('change', () => cambiarIdioma(selector.value));
    }
    sincronizarComboGoogleTranslate();
});
