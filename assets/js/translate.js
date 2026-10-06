function googleTranslateElementInit() {
    new google.translate.TranslateElement({
        pageLanguage: 'es',
        includedLanguages: 'en,es,pt,fr,it',
        autoDisplay: false
    }, 'google_translate_element');
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

    borrarCookieGoogtrans();

    if (lang !== 'es') {
        const valorCookie = `/es/${lang}`;
        const domain = window.location.hostname;

        document.cookie = `googtrans=${valorCookie}; path=/;`;
        if (domain !== 'localhost' && domain !== '127.0.0.1') {
            document.cookie = `googtrans=${valorCookie}; path=/; domain=${domain}`;
        }
    }

    window.location.href = window.location.pathname + window.location.search;
}

document.addEventListener('DOMContentLoaded', () => {
    const selector = document.getElementById('language-select');
    if (selector) {
        selector.addEventListener('change', () => cambiarIdioma(selector.value));

        const match = document.cookie.match(/(?:^|;\s*)googtrans=([^;]+)/);
        if (match) {
            const langParts = decodeURIComponent(match[1]).split('/');
            if (langParts.length >= 3 && langParts[2]) {
                selector.value = langParts[2];
                return;
            }
        }
        selector.value = 'es';
    }
});
