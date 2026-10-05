<dialog class="privacy-policy-dialog" aria-labelledby="privacyPolicyTitle">
    <div class="privacy-policy-dialog__header">
        <button type="button" class="privacy-policy-dialog__close" aria-label="Cerrar política de datos">&times;</button>
    </div>
    <div class="privacy-policy-dialog__content">
        <section data-policy-document="privacy">
            <?php $privacyPolicyCookiesUrl = $privacyPolicyCookiesUrl ?? 'interfaz/legal/politica_cookies.php'; ?>
            <?php include __DIR__ . '/../interfaz/legal/contenido_politica_privacidad.php'; ?>
        </section>
        <section data-policy-document="cookies" hidden>
            <?php $privacyPolicyUrl = $privacyPolicyUrl ?? 'interfaz/legal/politica_privacidad.php'; ?>
            <?php include __DIR__ . '/../interfaz/legal/contenido_politica_cookies.php'; ?>
        </section>
    </div>
</dialog>
<style>
    .privacy-policy-dialog {
        width: min(40rem, calc(100vw - 2rem));
        max-width: none;
        max-height: min(42rem, calc(100vh - 2rem));
        padding: 0;
        overflow: hidden;
        border: 1px solid #d7e0df;
        border-radius: 1rem;
        background: #fff;
        box-shadow: 0 24px 80px rgba(7, 20, 31, .38);
    }
    .privacy-policy-dialog::backdrop {
        background: rgba(7, 15, 25, .72);
        backdrop-filter: blur(4px);
    }
    .privacy-policy-dialog__header {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 1rem;
        min-height: 3rem;
        padding: .4rem .65rem;
        color: #fff;
        background: #164b4b;
    }
    .privacy-policy-dialog > .language-switcher {
        position: absolute;
        top: .55rem;
        right: 3.5rem;
        z-index: 1;
        gap: .1rem;
        padding: .1rem .25rem;
        border-radius: .45rem;
        font-size: .65rem;
        box-shadow: none;
    }
    .privacy-policy-dialog > .language-switcher .language-switcher__icon {
        width: .9rem;
        height: .9rem;
    }
    .privacy-policy-dialog > .language-switcher #language-select {
        width: 4.3rem;
        min-width: 0;
        padding: .1rem .9rem .1rem .1rem;
        font-size: inherit;
    }
    .privacy-policy-dialog__close {
        display: grid;
        width: 2rem;
        height: 2rem;
        place-items: center;
        border: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, .12);
        color: #fff;
        font-size: 1.5rem;
        cursor: pointer;
    }
    .privacy-policy-dialog__content {
        max-height: min(34rem, calc(100vh - 6rem));
        overflow: auto;
        padding: 1.25rem;
        background: #0f172a;
        color: rgba(238, 244, 255, .88);
        font: 400 .95rem/1.65 Manrope, sans-serif;
    }
    .privacy-policy-content h1 {
        margin: 0 0 .5rem;
        color: #fff;
        font: 700 1.5rem/1.3 'Playfair Display', serif;
    }
    .privacy-policy-content h2 {
        margin: 1.25rem 0 .4rem;
        color: #fff;
        font: 700 1.1rem/1.4 'Playfair Display', serif;
    }
    .privacy-policy-content p {
        margin: 0 0 .75rem;
    }
    .privacy-policy-content ul {
        margin: 0 0 .9rem;
        padding-left: 1.25rem;
        list-style: disc;
    }
    .privacy-policy-content li {
        padding-left: .15rem;
        margin: .25rem 0;
    }
    .privacy-policy-content strong {
        color: #fff;
    }
    .privacy-policy-content__highlight {
        color: #d7b06a;
    }
    .privacy-policy-content__updated,
    .privacy-policy-content__cookies {
        color: rgba(238, 244, 255, .65);
        font-size: .82rem;
    }
    .privacy-policy-content__cookies a {
        color: #d7b06a;
        text-decoration: underline;
    }
    .privacy-policy-content__code {
        padding: .1rem .3rem;
        border-radius: .25rem;
        background: rgba(255, 255, 255, .1);
        color: #fff;
    }
    @media (max-width: 600px) {
        .privacy-policy-dialog {
            width: calc(100vw - 1rem);
            max-height: calc(100vh - 1rem);
        }
        .privacy-policy-dialog__content {
            max-height: calc(100vh - 5rem);
            padding: 1rem;
        }
        .privacy-policy-dialog > .language-switcher {
            top: .6rem;
            right: 3.2rem;
        }
    }
</style>
<script>
    (() => {
        const dialog = document.querySelector('.privacy-policy-dialog');
        if (!dialog) return;
        const languageSwitcher = document.querySelector('.language-switcher');
        let languagePlaceholder = null;

        function showPolicy(mode) {
            const activeMode = mode === 'cookies' ? 'cookies' : 'privacy';
            dialog.querySelectorAll('[data-policy-document]').forEach(section => {
                section.hidden = section.dataset.policyDocument !== activeMode;
            });
            dialog.setAttribute('aria-labelledby', activeMode === 'cookies' ? 'cookiePolicyTitle' : 'privacyPolicyTitle');
        }

        function openPolicy(mode) {
            const cookieSettingsModal = document.getElementById('modal-cookies');
            if (cookieSettingsModal && getComputedStyle(cookieSettingsModal).display !== 'none') {
                if (typeof window.cerrarModalConfiguracion === 'function') {
                    window.cerrarModalConfiguracion();
                } else {
                    cookieSettingsModal.style.display = 'none';
                }
            }

            const systemTour = document.getElementById('systemTour');
            if (systemTour && !systemTour.hidden) {
                systemTour.hidden = true;
                const spotlight = document.getElementById('systemTourSpotlight');
                if (spotlight) spotlight.hidden = true;
            }

            const bookingModal = document.getElementById('bookingModal');
            if (bookingModal && getComputedStyle(bookingModal).display !== 'none') {
                if (typeof window.closeBookingModal === 'function') {
                    window.closeBookingModal();
                } else {
                    bookingModal.style.display = 'none';
                    document.body.classList.remove('overflow-hidden');
                }
            }

            document.querySelectorAll('dialog[open]').forEach(openDialog => {
                if (openDialog !== dialog) openDialog.close();
            });
            if (languageSwitcher && languageSwitcher.parentElement !== dialog) {
                languagePlaceholder = document.createComment('language-switcher-position');
                languageSwitcher.parentNode.insertBefore(languagePlaceholder, languageSwitcher);
                dialog.insertBefore(languageSwitcher, dialog.querySelector('.privacy-policy-dialog__header'));
            }
            showPolicy(mode);
            dialog.showModal();
            document.body.classList.add('privacy-policy-open');
            document.documentElement.classList.add('privacy-policy-open');
        }

        document.addEventListener('click', event => {
            if (!(event.target instanceof Element)) return;

            const policyLink = event.target.closest('[data-open-cookie-policy], [data-open-privacy-policy], [data-switch-policy]');
            if (!policyLink) return;

            event.preventDefault();
            if (policyLink.hasAttribute('data-switch-policy')) {
                showPolicy(policyLink.dataset.switchPolicy);
                dialog.querySelector('.privacy-policy-dialog__content').scrollTop = 0;
                return;
            }

            openPolicy(policyLink.hasAttribute('data-open-cookie-policy') ? 'cookies' : 'privacy');
        });

        dialog.querySelector('.privacy-policy-dialog__close').addEventListener('click', () => dialog.close());
        dialog.addEventListener('close', () => {
            document.body.classList.remove('privacy-policy-open');
            document.documentElement.classList.remove('privacy-policy-open');
            if (languageSwitcher && languageSwitcher.parentElement === dialog) {
                if (languagePlaceholder && languagePlaceholder.parentNode) {
                    languagePlaceholder.parentNode.insertBefore(languageSwitcher, languagePlaceholder);
                    languagePlaceholder.remove();
                    languagePlaceholder = null;
                } else {
                    document.body.appendChild(languageSwitcher);
                }
            }
        });
        dialog.addEventListener('click', event => {
            if (event.target === dialog) dialog.close();
        });

        let reopenAfterLanguageChange = null;
        try {
            reopenAfterLanguageChange = sessionStorage.getItem('reopenPolicyDialogMode');
            if (reopenAfterLanguageChange) sessionStorage.removeItem('reopenPolicyDialogMode');
        } catch (error) {
            console.error('No se pudo restaurar el modal de políticas después del cambio de idioma.', error);
        }
        if (reopenAfterLanguageChange) openPolicy(reopenAfterLanguageChange);
    })();
</script>
<style>
    html.privacy-policy-open,
    body.privacy-policy-open {
        overflow: hidden;
    }
</style>
